# Cleaning Booking System - WordPress Plugin

A comprehensive booking system for cleaning services built as a WordPress plugin with WooCommerce integration.

## Features

### Core Booking Flow
- **ZIP Code Check**: Whitelisted postal codes with optional surcharges
- **Service Selection**: Multiple service types (house, apartment, office, etc.) with base pricing
- **Square Meters Input**: Dynamic pricing and duration calculation based on area
- **Extras Selection**: Add-on services (dishwashing, ironing, cooking, etc.)
- **Date & Time Selection**: Calendar with available slots respecting total duration
- **Customer Details**: Contact information and special instructions
- **WooCommerce Checkout**: Seamless payment processing

### Admin Features
- **Services Management**: Create/edit services with pricing and duration multipliers
- **Extras Management**: Manage add-on services with individual pricing
- **ZIP Code Management**: Whitelist service areas with optional surcharges
- **Business Hours**: Configure operating hours for each day of the week
- **Booking Management**: View, update, and manage all bookings
- **Settings**: Slot duration, buffer times, and booking hold times

### Technical Features
- **REST API**: Complete API for frontend wizard functionality
- **Slot Management**: Duration-aware availability with temporary holds
- **WooCommerce Integration**: Dynamic product creation and order management
- **Responsive Design**: Mobile-friendly booking interface
- **Security**: Nonces, sanitization, and proper escaping
- **Shortcode Support**: `[cb_widget]` for easy integration

## Installation

1. Upload the plugin files to `/wp-content/plugins/cleaning-booking/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce is installed and activated
4. Configure your services, extras, and ZIP codes in the admin panel

## Usage

### Shortcode
Use the `[cb_widget]` shortcode to display the booking widget on any page:

```
[cb_widget]
```

### Shortcode Parameters
- `title` - Custom title for the widget (default: "Book Your Cleaning Service")
- `show_steps` - Show step indicators (true/false, default: true)
- `theme` - Theme color (light/dark, default: light)

Example:
```
[cb_widget title="Schedule Your Cleaning" show_steps="true" theme="light"]
```

### Admin Configuration

1. **Services**: Go to Cleaning Booking > Services to add/edit cleaning services
2. **Extras**: Go to Cleaning Booking > Extras to manage add-on services
3. **ZIP Codes**: Go to Cleaning Booking > ZIP Codes to whitelist service areas
4. **Settings**: Go to Cleaning Booking > Settings to configure business hours and timing
5. **Bookings**: Go to Cleaning Booking > Bookings to view and manage bookings

## Database Schema

The plugin creates the following database tables:

- `wp_cb_services` - Cleaning services with pricing and duration
- `wp_cb_extras` - Additional services and add-ons
- `wp_cb_zip_codes` - Whitelisted postal codes with surcharges
- `wp_cb_bookings` - Customer bookings and details
- `wp_cb_booking_extras` - Junction table for booking extras
- `wp_cb_slot_holds` - Temporary slot holds during checkout

## API Endpoints

The plugin provides REST API endpoints for the frontend wizard:

- `POST /wp-json/cleaning-booking/v1/check-zip` - Validate ZIP code
- `GET /wp-json/cleaning-booking/v1/services` - Get available services
- `GET /wp-json/cleaning-booking/v1/extras` - Get available extras
- `POST /wp-json/cleaning-booking/v1/calculate-price` - Calculate pricing
- `GET /wp-json/cleaning-booking/v1/available-slots` - Get available time slots
- `POST /wp-json/cleaning-booking/v1/hold-slot` - Temporarily hold a slot
- `POST /wp-json/cleaning-booking/v1/release-slot` - Release a held slot
- `POST /wp-json/cleaning-booking/v1/create-booking` - Create a new booking

## WooCommerce Integration

The plugin integrates seamlessly with WooCommerce:

- Creates dynamic products for each booking
- Handles checkout process and payment
- Links bookings to orders
- Updates booking status based on order status
- Displays booking details in order admin and frontend

## File Structure

```
cleaning-booking/
├── cleaning-booking.php          # Main plugin file
├── includes/
│   ├── class-cb-database.php     # Database management
│   ├── class-cb-admin.php        # Admin interface
│   ├── class-cb-rest-api.php     # REST API endpoints
│   ├── class-cb-frontend.php     # Frontend booking wizard
│   ├── class-cb-woocommerce.php  # WooCommerce integration
│   └── class-cb-slot-manager.php # Slot availability management
├── templates/
│   ├── admin/                    # Admin page templates
│   └── frontend/                 # Frontend templates
├── assets/
│   ├── css/                      # Stylesheets
│   └── js/                       # JavaScript files
└── README.md                     # This file
```

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Security

The plugin follows WordPress security best practices:

- All user inputs are sanitized and validated
- Nonces are used for form submissions and AJAX requests
- Database queries use prepared statements
- User capabilities are checked for admin functions
- Output is properly escaped

## Support

For support and customization requests, please contact the plugin developer.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- Complete booking system with WooCommerce integration
- Admin interface for managing services, extras, and bookings
- REST API for frontend wizard
- Responsive design and mobile support
