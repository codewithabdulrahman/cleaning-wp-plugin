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
                // Check if slot is available
                if ($this->is_slot_available($date, $slot_time, $duration)) {
                    $slots[] = array(
                        'time' => $slot_time,
                        'formatted_time' => date('g:i A', $current_time),
                        'available' => true
                    );
                }
            }
            
            $current_time += ($slot_duration * 60);
        }
        
        return $slots;
    }
    
    public function is_slot_available($date, $time, $duration) {
        global $wpdb;
        
        $bookings_table = $wpdb->prefix . 'cb_bookings';
        $slot_holds_table = $wpdb->prefix . 'cb_slot_holds';
        
        // Check for existing bookings
        $existing_bookings = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_time, total_duration FROM $bookings_table 
             WHERE booking_date = %s 
             AND status IN ('pending', 'confirmed', 'paid')",
            $date
        ));
        
        foreach ($existing_bookings as $booking) {
            if ($this->slots_overlap($time, $duration, $booking->booking_time, $booking->total_duration)) {
                return false;
            }
        }
        
        // Check for active slot holds
        $active_holds = $wpdb->get_results($wpdb->prepare(
            "SELECT booking_time, duration FROM $slot_holds_table 
             WHERE booking_date = %s 
             AND expires_at > NOW()",
            $date
        ));
        
        foreach ($active_holds as $hold) {
            if ($this->slots_overlap($time, $duration, $hold->booking_time, $hold->duration)) {
                return false;
            }
        }
        
        return true;
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
