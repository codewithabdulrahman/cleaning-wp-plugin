<?php
/**
 * PO File Parser Class
 * 
 * Handles parsing of .po translation files
 * 
 * @package CleaningBooking
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

class CB_PO_Parser {
    
    private $translations = array();
    private $current_msgid = '';
    private $current_msgstr = '';
    private $in_msgid = false;
    private $in_msgstr = false;
    private $current_category = 'general';
    
    /**
     * Parse PO file content
     */
    public function parse($content) {
        $this->reset();
        
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $this->process_line(trim($line));
        }
        
        // Add the last translation if exists
        $this->add_current_translation();
        
        return $this->translations;
    }
    
    /**
     * Reset parser state
     */
    private function reset() {
        $this->translations = array();
        $this->current_msgid = '';
        $this->current_msgstr = '';
        $this->in_msgid = false;
        $this->in_msgstr = false;
        $this->current_category = 'general';
    }
    
    /**
     * Process a single line
     */
    private function process_line($line) {
        if (empty($line)) {
            $this->handle_empty_line();
            return;
        }
        
        if ($this->is_comment($line)) {
            $this->handle_comment($line);
            return;
        }
        
        if ($this->is_msgid($line)) {
            $this->handle_msgid($line);
            return;
        }
        
        if ($this->is_msgstr($line)) {
            $this->handle_msgstr($line);
            return;
        }
        
        if ($this->is_continuation($line)) {
            $this->handle_continuation($line);
            return;
        }
    }
    
    /**
     * Check if line is a comment
     */
    private function is_comment($line) {
        return strpos($line, '#') === 0;
    }
    
    /**
     * Check if line is msgid
     */
    private function is_msgid($line) {
        return preg_match('/^msgid "(.*)"$/', $line);
    }
    
    /**
     * Check if line is msgstr
     */
    private function is_msgstr($line) {
        return preg_match('/^msgstr "(.*)"$/', $line);
    }
    
    /**
     * Check if line is continuation
     */
    private function is_continuation($line) {
        return preg_match('/^"(.*)"$/', $line);
    }
    
    /**
     * Handle empty line (end of translation block)
     */
    private function handle_empty_line() {
        $this->add_current_translation();
        $this->reset_current_translation();
    }
    
    /**
     * Handle comment line
     */
    private function handle_comment($line) {
        if (preg_match('/^# (.+)$/', $line, $matches)) {
            $this->current_category = $this->map_comment_to_category(trim($matches[1]));
        }
    }
    
    /**
     * Handle msgid line
     */
    private function handle_msgid($line) {
        if (preg_match('/^msgid "(.*)"$/', $line, $matches)) {
            $this->add_current_translation();
            $this->current_msgid = $matches[1];
            $this->in_msgid = true;
            $this->in_msgstr = false;
        }
    }
    
    /**
     * Handle msgstr line
     */
    private function handle_msgstr($line) {
        if (preg_match('/^msgstr "(.*)"$/', $line, $matches)) {
            $this->current_msgstr = $matches[1];
            $this->in_msgid = false;
            $this->in_msgstr = true;
        }
    }
    
    /**
     * Handle continuation line
     */
    private function handle_continuation($line) {
        if (preg_match('/^"(.*)"$/', $line, $matches)) {
            if ($this->in_msgid) {
                $this->current_msgid .= $matches[1];
            } elseif ($this->in_msgstr) {
                $this->current_msgstr .= $matches[1];
            }
        }
    }
    
    /**
     * Add current translation to results
     */
    private function add_current_translation() {
        if ($this->current_msgid && $this->current_msgstr) {
            $this->translations[] = array(
                'text_en' => $this->current_msgid,
                'text_el' => $this->current_msgstr,
                'category' => $this->current_category,
                'string_key' => $this->current_msgid
            );
        }
    }
    
    /**
     * Reset current translation state
     */
    private function reset_current_translation() {
        $this->current_msgid = '';
        $this->current_msgstr = '';
        $this->in_msgid = false;
        $this->in_msgstr = false;
    }
    
    /**
     * Map comment to category
     */
    private function map_comment_to_category($comment) {
        $mapping = array(
            'Form Fields' => 'form_fields',
            'Buttons' => 'buttons',
            'Messages' => 'messages',
            'Booking Widget' => 'booking_widget',
            'Steps' => 'steps',
            'Step Headers' => 'step_headers',
            'Sidebar' => 'sidebar',
            'Booking Summary' => 'booking_summary',
            'Error Messages' => 'error_messages',
            'Admin Panel' => 'admin_panel'
        );
        
        foreach ($mapping as $keyword => $category) {
            if (strpos($comment, $keyword) !== false) {
                return $category;
            }
        }
        
        return 'general';
    }
}
