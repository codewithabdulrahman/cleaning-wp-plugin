<?php
/**
 * Slot management class for booking availability
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_Slot_Manager {
    
    public function __construct() {
        // Clean up expired holds on init
        add_action('init', array($this, 'cleanup_expired_holds'));
    }
    
    public function get_available_slots($date, $duration) {
        global $wpdb;
        
        // Get business hours for the day
        $business_hours = get_option('cb_business_hours', array());
        $day_name = strtolower(date('l', strtotime($date)));
        
        if (!isset($business_hours[$day_name]) || !$business_hours[$day_name]['enabled']) {
            return array();
        }
        
        $start_time = $business_hours[$day_name]['start'];
        $end_time = $business_hours[$day_name]['end'];
        $slot_duration = get_option('cb_slot_duration', 30);
        $buffer_time = get_option('cb_buffer_time', 15);
        
        // Generate time slots
        $slots = array();
        $current_time = strtotime($date . ' ' . $start_time);
        $end_timestamp = strtotime($date . ' ' . $end_time);
        
        while ($current_time < $end_timestamp) {
            $slot_time = date('H:i', $current_time);
            $slot_end_time = date('H:i', $current_time + ($duration * 60));
            
            // Check if slot fits within business hours
            if (strtotime($date . ' ' . $slot_end_time) <= $end_timestamp) {
                // Always add the slot, but mark availability
                $is_available = $this->is_slot_available($date, $slot_time, $duration);
                $slots[] = array(
                    'time' => $slot_time,
                    'formatted_time' => date('g:i A', $current_time),
                    'available' => $is_available,
                    'disabled' => !$is_available
                );
            }
            
            $current_time += ($slot_duration * 60);
        }
        
        return $slots;
    }
    
    public function is_slot_available($date, $time, $duration) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $trucks_table = $wpdb->prefix . 'cb_trucks';
        
        // Count total active trucks (one truck = one slot)
        $total_trucks = $wpdb->get_var(
            "SELECT COUNT(*) FROM $trucks_table WHERE is_active = 1"
        );
        
        // If trucks table doesn't exist or no trucks, fall back to old logic
        if ($total_trucks === null || $total_trucks <= 0) {
            // Fallback: check for any existing bookings (old behavior)
            $existing_bookings_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $bookings_table 
                 WHERE booking_date = %s 
                 AND status IN ('pending', 'confirmed', 'paid', 'on-hold')
                 AND (
                     (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) > %s) OR
                     (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
                 )",
                $date,
                $time,
                $time,
                $time,
                $duration,
                $time,
                $duration
            ));
            
            // If no trucks configured, allow unlimited bookings (old behavior)
            return true;
        }
        
        // Count existing bookings for this time slot (pending, confirmed, paid, on-hold)
        $existing_bookings_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table 
             WHERE booking_date = %s 
             AND status IN ('pending', 'confirmed', 'paid', 'on-hold')
             AND (
                 (booking_time <= %s AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) > %s) OR
                 (booking_time < ADDTIME(%s, SEC_TO_TIME(%d * 60)) AND ADDTIME(booking_time, SEC_TO_TIME(total_duration * 60)) >= ADDTIME(%s, SEC_TO_TIME(%d * 60)))
             )",
            $date,
            $time,
            $time,
            $time,
            $duration,
            $time,
            $duration
        ));
        
        // One truck = one slot, so slot available if bookings < total trucks
        return $existing_bookings_count < $total_trucks;
    }
    
    private function slots_overlap($time1, $duration1, $time2, $duration2) {
        $start1 = strtotime($time1);
        $end1 = $start1 + ($duration1 * 60);
        $start2 = strtotime($time2);
        $end2 = $start2 + ($duration2 * 60);
        
        return ($start1 < $end2 && $end1 > $start2);
    }
    
    public function hold_slot($date, $time, $duration) {
        global $wpdb;
        
        // Check if slot is still available
        if (!$this->is_slot_available($date, $time, $duration)) {
            return false;
        }
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        $booking_hold_time = get_option('cb_booking_hold_time', 15);
        
        $session_id = wp_generate_uuid4();
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$booking_hold_time} minutes"));
        
        $result = $wpdb->insert($slot_holds_table, array(
            'booking_date' => $date,
            'booking_time' => $time,
            'duration' => $duration,
            'session_id' => $session_id,
            'expires_at' => $expires_at
        ));
        
        if ($result) {
            return $session_id;
        }
        
        return false;
    }
    
    public function release_slot($session_id) {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        $result = $wpdb->delete($slot_holds_table, array(
            'session_id' => $session_id
        ));
        
        return $result > 0;
    }
    
    public function cleanup_expired_holds() {
        global $wpdb;
        
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        // Delete expired holds
        $wpdb->query("DELETE FROM $slot_holds_table WHERE expires_at < NOW()");
    }
    
    public function get_booking_conflicts($date, $time, $duration, $exclude_booking_id = null) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        
        $query = $wpdb->prepare(
            "SELECT id, booking_reference, customer_name, booking_time, total_duration 
             FROM $bookings_table 
             WHERE booking_date = %s 
             AND status IN ('pending', 'confirmed', 'paid')",
            $date
        );
        
        if ($exclude_booking_id) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_booking_id);
        }
        
        $bookings = $wpdb->get_results($query);
        $conflicts = array();
        
        foreach ($bookings as $booking) {
            if ($this->slots_overlap($time, $duration, $booking->booking_time, $booking->total_duration)) {
                $conflicts[] = $booking;
            }
        }
        
        return $conflicts;
    }
    
    public function get_next_available_slot($date, $duration, $preferred_time = null) {
        $slots = $this->get_available_slots($date, $duration);
        
        if (empty($slots)) {
            return null;
        }
        
        // If preferred time is specified, try to find the closest available slot
        if ($preferred_time) {
            $preferred_timestamp = strtotime($preferred_time);
            $closest_slot = null;
            $min_diff = PHP_INT_MAX;
            
            foreach ($slots as $slot) {
                $slot_timestamp = strtotime($slot['time']);
                $diff = abs($slot_timestamp - $preferred_timestamp);
                
                if ($diff < $min_diff) {
                    $min_diff = $diff;
                    $closest_slot = $slot;
                }
            }
            
            return $closest_slot;
        }
        
        // Return the first available slot
        return $slots[0];
    }
    
    public function get_booking_calendar($start_date, $end_date) {
        $calendar = array();
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        while ($current_date <= $end_timestamp) {
            $date = date('Y-m-d', $current_date);
            $day_name = strtolower(date('l', $current_date));
            
            // Check if this day has business hours
            $business_hours = get_option('cb_business_hours', array());
            $has_hours = isset($business_hours[$day_name]) && $business_hours[$day_name]['enabled'];
            
            $calendar[$date] = array(
                'date' => $date,
                'day_name' => $day_name,
                'has_hours' => $has_hours,
                'is_past' => $current_date < strtotime('today'),
                'is_today' => $date === date('Y-m-d')
            );
            
            $current_date = strtotime('+1 day', $current_date);
        }
        
        return $calendar;
    }
    
}
