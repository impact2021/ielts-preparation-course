# Membership System with Receipt PDF Generation

## Overview

This update adds a complete membership management system to the IELTS Course Manager plugin with the following features:

- **Membership Management**: Track user memberships with start/end dates and status
- **Payment Tracking**: Record all payment transactions with full details
- **Company Settings**: Configure company details for professional receipts
- **PDF Receipt Generation**: Automatically generate professional PDF receipts
- **Email Distribution**: Send receipts via email when memberships are activated
- **Resend Functionality**: Resend receipts to members from the admin panel

## Features

### 1. Admin Menu Pages

The system adds a new "Memberships" menu in the WordPress admin with three sub-pages:

#### All Memberships
- View all memberships with user details, start/end dates, and status
- Automatically updates status based on expiry dates (active/expired)
- "Resend Receipt" button for each membership
- Lists: ID, User, Email, Start Date, End Date, Status, Actions

#### Payments
- Complete payment transaction history
- View: ID, User, Amount, Currency, Payment Method, Transaction ID, Payment Date, Type, Status

#### Company Settings
- **Company Name**: Your business name
- **Company Address**: Full address including city, state, postal code
- **GST/Tax Number**: Your tax identification number
- **Phone Number**: Contact phone
- **Email Address**: Contact email (required)
- **Website**: Company website URL
- **Company Logo**: Upload logo for receipts (recommended size: 200x80px)

### 2. Receipt PDF Generation

Professional PDF receipts are automatically generated with:

**Header Section:**
- Company logo (if uploaded)
- Company name and full contact details
- GST/Tax number

**Receipt Information:**
- Unique receipt number (format: REC-000001)
- Receipt date

**Customer Details:**
- Customer name
- Email address
- User ID

**Payment Details Table:**
- Description of service
- Amount paid
- Currency

**Membership Period:**
- Start date
- Expiry date
- Duration (in days)

**Transaction Information:**
- Payment method (PayPal, Stripe, etc.)
- Transaction ID
- Payment date and time
- Status

**Footer:**
- Professional disclaimer note

### 3. Email Functionality

Receipts are automatically sent via email:

**When:**
- Automatically when a membership is activated (via `ielts_cm_membership_activated` action hook)
- Manually via "Resend Receipt" button in admin

**Email Contents:**
- HTML formatted email
- Professional greeting with customer name
- Membership details summary
- PDF receipt attached
- Contact information

### 4. Database Tables

Two new tables are created:

**wp_ielts_cm_memberships:**
- `id`: Membership ID
- `user_id`: WordPress user ID
- `start_date`: Membership start date
- `end_date`: Membership expiry date
- `status`: Membership status (active/expired)
- `created_date`: Record creation timestamp

**wp_ielts_cm_payments:**
- `id`: Payment ID
- `user_id`: WordPress user ID
- `membership_id`: Related membership ID
- `amount`: Payment amount
- `currency`: Currency code (USD, EUR, etc.)
- `payment_method`: Payment gateway used
- `transaction_id`: External transaction ID
- `payment_date`: When payment was made
- `status`: Payment status (completed, pending, etc.)
- `payment_type`: Type of payment (new, extension, etc.)
- `created_date`: Record creation timestamp

## Usage

### Initial Setup

1. **Configure Company Settings:**
   - Navigate to: `Memberships > Company Settings`
   - Fill in all required fields
   - Upload your company logo
   - Save settings

2. **Create a Membership (Programmatically):**

```php
// Example: Create a new membership
global $wpdb;
$user_id = 123; // WordPress user ID

// Insert membership
$wpdb->insert(
    $wpdb->prefix . 'ielts_cm_memberships',
    array(
        'user_id' => $user_id,
        'start_date' => current_time('mysql'),
        'end_date' => date('Y-m-d H:i:s', strtotime('+90 days')),
        'status' => 'active'
    ),
    array('%d', '%s', '%s', '%s')
);
$membership_id = $wpdb->insert_id;

// Insert payment record
$wpdb->insert(
    $wpdb->prefix . 'ielts_cm_payments',
    array(
        'user_id' => $user_id,
        'membership_id' => $membership_id,
        'amount' => 24.95,
        'currency' => 'USD',
        'payment_method' => 'paypal',
        'transaction_id' => 'TXN123456',
        'payment_date' => current_time('mysql'),
        'status' => 'completed',
        'payment_type' => 'new'
    ),
    array('%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
);

// Trigger receipt email
do_action('ielts_cm_membership_activated', $membership_id, $user_id);
```

3. **Resend Receipt:**
   - Go to `Memberships > All Memberships`
   - Click "Resend Receipt" button for any membership
   - Receipt will be sent to the user's email

### Integration with Payment Gateways

To integrate with a payment gateway (PayPal, Stripe, etc.):

```php
// After successful payment
function handle_successful_payment($user_id, $amount, $transaction_id, $gateway) {
    global $wpdb;
    
    // Create membership
    $wpdb->insert(
        $wpdb->prefix . 'ielts_cm_memberships',
        array(
            'user_id' => $user_id,
            'start_date' => current_time('mysql'),
            'end_date' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'status' => 'active'
        ),
        array('%d', '%s', '%s', '%s')
    );
    $membership_id = $wpdb->insert_id;
    
    // Record payment
    $wpdb->insert(
        $wpdb->prefix . 'ielts_cm_payments',
        array(
            'user_id' => $user_id,
            'membership_id' => $membership_id,
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => $gateway,
            'transaction_id' => $transaction_id,
            'payment_date' => current_time('mysql'),
            'status' => 'completed',
            'payment_type' => 'new'
        ),
        array('%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    
    // Send receipt email
    do_action('ielts_cm_membership_activated', $membership_id, $user_id);
}
```

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Admin functions require `manage_options` capability
- **SQL Injection Prevention**: All database queries use prepared statements
- **XSS Prevention**: All output is properly escaped
- **File Upload Validation**: Logo uploads are handled securely via WordPress upload functions
- **Directory Protection**: Receipt storage directory is protected with .htaccess

## File Storage

PDF receipts are temporarily stored in:
```
wp-content/uploads/ielts-receipts/
```

**Note**: 
- This directory is protected with `.htaccess` to prevent direct access
- PDF files are automatically deleted after being sent via email
- Files are named with membership ID and timestamp for uniqueness

## Customization

### Email Template

To customize the email template, edit the `get_receipt_email_message()` method in `includes/class-membership.php`.

### PDF Receipt Design

To customize the PDF layout, edit the `generate_receipt_pdf()` method in `includes/class-pdf-generator.php`.

### Styling

The membership admin pages use standard WordPress admin styling. To add custom CSS:

1. Edit `assets/css/admin.css`
2. Or add inline styles in the respective page methods

## Troubleshooting

### Receipts Not Generating

1. Check that FPDF library exists at `lib/fpdf/fpdf.php`
2. Verify write permissions for `wp-content/uploads/ielts-receipts/`
3. Check PHP error logs for specific errors

### Emails Not Sending

1. Verify WordPress email configuration (test with WordPress's built-in email)
2. Check spam folder
3. Consider installing an SMTP plugin like "WP Mail SMTP"
4. Ensure company email is set in Company Settings

### Logo Not Appearing in PDF

1. Verify logo is uploaded and saved in Company Settings
2. Check that logo file exists in uploads directory
3. Ensure logo is in a supported format (JPG, PNG, GIF)
4. Try reducing logo file size if it's very large

## Future Enhancements

Potential additions for later versions:

- Automatic email notifications for expiring memberships
- Bulk receipt generation and sending
- Receipt download from user account page
- Multiple receipt templates
- Multi-currency support with conversion rates
- Refund receipt generation
- Receipt printing functionality
- Export membership/payment data to CSV

## Support

For issues or questions:

1. Check this documentation
2. Review code comments in the source files
3. Check WordPress debug logs for errors
4. Contact plugin maintainer

## Version

- **Added in**: Version 11.14
- **Last Updated**: January 2026
- **Requires**: WordPress 5.8+, PHP 7.2+

## License

GPL v2 or later (same as WordPress)
