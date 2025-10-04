/**
 * Early initialization script to prevent Elementor conflicts
 * This script loads before any other scripts to ensure cb_frontend is available
 */

// Immediately create cb_frontend object to prevent Elementor errors
(function() {
    'use strict';
    
    // Create cb_frontend object immediately when this script loads
    window.cb_frontend = {
        tools: {},
        ajax_url: '',
        nonce: '',
        current_language: 'en',
        translations: {},
        strings: {},
        // Add all possible properties that Elementor might expect
        init: function() {},
        ready: function() {},
        onReady: function() {},
        components: {},
        modules: {},
        utils: {},
        helpers: {},
        // Add Elementor-specific properties if needed
        Frontend: {
            initOnReadyComponents: function() {},
            init: function() {}
        }
    };
    
    // Also ensure it's available when DOM is ready
    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.cb_frontend.tools) {
                window.cb_frontend.tools = {};
            }
        });
    }
})();
