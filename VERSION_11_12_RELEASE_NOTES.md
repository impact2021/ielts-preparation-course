# Version 11.12 Release Notes

**Release Date:** January 2026  
**Previous Version:** 11.11  
**Current Version:** 11.12

## Overview

This release adds receipt and PDF functionality to the IELTS Course Manager plugin, allowing users to view their payment history and download printable receipts. This addresses the issue where users couldn't see anything related to receipts and receipt PDFs.

## Changes

### 1. Version Update

**Files Modified:**
- `ielts-course-manager.php` (lines 6, 23)

**Changes:**
- Updated plugin version from 11.11 to 11.12 in plugin header
- Updated `IELTS_CM_VERSION` constant to '11.12'

### 2. Database Schema Enhancement

**Files Modified:**
- `includes/class-database.php`

**New Table:** `wp_ielts_cm_payments`

**Table Structure:**
```sql
CREATE TABLE wp_ielts_cm_payments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    course_id bigint(20) DEFAULT NULL,
    amount decimal(10,2) NOT NULL,
    currency varchar(10) DEFAULT 'USD',
    payment_method varchar(50) DEFAULT NULL,
    transaction_id varchar(255) DEFAULT NULL,
    status varchar(20) DEFAULT 'completed',
    description text DEFAULT NULL,
    payment_date datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY course_id (course_id),
    KEY transaction_id (transaction_id)
)
```

**Features:**
- Tracks all payment transactions
- Links payments to users and courses
- Stores payment gateway details
- Automatic timestamp tracking
- Indexed for performance

### 3. Payment Receipt Management Class

**New File:** `includes/class-payment-receipt.php`

**Features:**

#### Singleton Pattern
- Single instance throughout plugin lifecycle
- Prevents redundant database connections
- Accessible via `IELTS_CM_Payment_Receipt::get_instance()`

#### Payment Management Methods
- `add_payment($user_id, $amount, $args)` - Record new payments
- `get_payment($payment_id)` - Retrieve payment by ID
- `get_user_payments($user_id)` - Get all user payments

#### Receipt Generation
- `download_receipt()` - AJAX handler for receipt downloads
- `generate_receipt_html()` - Create printable HTML receipt
- Includes customer information, payment details, transaction ID
- Professional formatting with print stylesheet
- Browser's "Print to PDF" functionality for PDF generation

#### Security Features
- Nonce verification (validated in correct order)
- User authentication required
- Permission checks (users see only their receipts)
- Admin override capability for support
- Proper output escaping throughout

### 4. My Account Page Enhancement

**Files Modified:**
- `includes/class-shortcodes.php`

**New Section:** "Payment History & Receipts"

**Features:**
- Payment history table with columns:
  - Date (internationalized format)
  - Description
  - Amount (with currency)
  - Status (with visual badges)
  - Receipt download link
- Status indicators:
  - ✅ Completed (green badge)
  - ⏳ Pending (yellow badge)
  - ❌ Failed (red badge)
- Secure download links with nonce protection
- Uses singleton instance for performance
- Built with `add_query_arg()` for secure URL building

**Display Logic:**
- Shows message if no payment history
- Displays course name if payment linked to course
- Falls back to description or generic "Course Payment"
- Proper date/time formatting with `date_i18n()`

### 5. Integration

**Files Modified:**
- `ielts-course-manager.php` - Include new class file
- `includes/class-ielts-course-manager.php` - Initialize singleton

**Integration Points:**
- Automatic database table creation on version update
- AJAX endpoint: `admin-ajax.php?action=ielts_cm_download_receipt`
- Shortcode: `[ielts_my_account]` now includes payment section

## Technical Details

### Receipt HTML Structure

```html
<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt #XXXXXX</title>
    <style>
        /* Professional receipt styling */
        /* Print-friendly layout */
        /* Responsive design */
    </style>
</head>
<body>
    <div class="receipt-header">
        <!-- Site name and receipt number -->
    </div>
    <div class="receipt-info">
        <!-- Customer and payment information -->
    </div>
    <div class="payment-details">
        <!-- Itemized payment breakdown -->
    </div>
    <div class="receipt-footer">
        <!-- Thank you message and support info -->
    </div>
    <div class="print-button">
        <!-- Print/Save as PDF button -->
    </div>
</body>
</html>
```

### Security Implementation

**CSRF Protection:**
```php
// Nonce generation (per payment)
$nonce = wp_create_nonce('download_receipt_' . $payment_id);

// Nonce verification (with proper order)
1. Validate payment_id
2. Verify nonce with validated ID
3. Check user authentication
4. Verify user permissions
```

**URL Building:**
```php
$url = add_query_arg(array(
    'action' => 'ielts_cm_download_receipt',
    'payment_id' => $payment->id,
    'nonce' => $nonce
), admin_url('admin-ajax.php'));
```

### Code Quality

**WordPress Standards:**
- ✅ Proper escaping with `esc_html()`, `esc_url()`, `esc_attr()`
- ✅ Internationalization with `__()`, `_e()`
- ✅ Date formatting with `date_i18n()`
- ✅ Prepared SQL statements
- ✅ WordPress coding style

**Best Practices:**
- ✅ Singleton pattern for performance
- ✅ Separation of concerns
- ✅ Clear method naming
- ✅ Comprehensive security checks
- ✅ Proper error handling

## Usage

### For Users

1. Navigate to My Account page
2. Scroll to "Payment History & Receipts" section
3. View all payment transactions
4. Click "Download PDF" for any payment
5. Browser opens printable receipt
6. Use browser's "Print" or "Save as PDF" function

### For Developers

**Add a Payment:**
```php
$payment_receipt = IELTS_CM_Payment_Receipt::get_instance();

$payment_id = $payment_receipt->add_payment(
    $user_id,
    24.95,
    array(
        'course_id' => 123,
        'currency' => 'USD',
        'payment_method' => 'PayPal',
        'transaction_id' => 'TXN123456',
        'status' => 'completed',
        'description' => 'IELTS Course Enrollment'
    )
);
```

**Retrieve User Payments:**
```php
$payment_receipt = IELTS_CM_Payment_Receipt::get_instance();
$payments = $payment_receipt->get_user_payments($user_id);

foreach ($payments as $payment) {
    echo $payment->amount;
    echo $payment->currency;
    echo $payment->status;
}
```

**Get Single Payment:**
```php
$payment_receipt = IELTS_CM_Payment_Receipt::get_instance();
$payment = $payment_receipt->get_payment($payment_id);

if ($payment) {
    echo $payment->description;
}
```

## Testing

### Manual Testing Steps

1. **Database Table Creation:**
   - Activate/update plugin
   - Check database for `wp_ielts_cm_payments` table
   - Verify table structure

2. **Add Test Payment:**
   ```php
   // Add via WordPress admin or custom code
   $payment_receipt = IELTS_CM_Payment_Receipt::get_instance();
   $payment_receipt->add_payment(get_current_user_id(), 24.95, array(
       'description' => 'Test Payment',
       'payment_method' => 'Test',
       'transaction_id' => 'TEST123'
   ));
   ```

3. **View Payment History:**
   - Go to page with `[ielts_my_account]` shortcode
   - Verify payment appears in table
   - Check date formatting
   - Verify status badge

4. **Download Receipt:**
   - Click "Download PDF" link
   - Verify receipt opens in new tab
   - Check all information displays correctly
   - Test "Print / Save as PDF" button

5. **Security Testing:**
   - Try accessing another user's receipt (should fail)
   - Try with invalid nonce (should fail)
   - Try without login (should fail)
   - Test admin override (should work)

## Database Migration

The payment table is created automatically when:
- Plugin is activated for the first time
- Plugin version is updated (via `check_version_update()`)

No manual migration needed. Safe to activate on existing installations.

## Backward Compatibility

✅ **Fully backward compatible**

- No changes to existing functionality
- No breaking changes to APIs
- No modifications to existing database tables
- New table added separately
- New features are opt-in (require payment data)
- Existing users see "no payment history" message

## Files Changed

1. `ielts-course-manager.php` - Version update, include new class
2. `includes/class-database.php` - Add payments table schema
3. `includes/class-ielts-course-manager.php` - Initialize payment receipt singleton
4. `includes/class-shortcodes.php` - Add payment history section
5. `includes/class-payment-receipt.php` - New file (payment management)

## Known Limitations

1. **PDF Generation:** Uses browser's "Print to PDF" rather than server-side PDF generation. This keeps the plugin lightweight without external dependencies.

2. **Payment Gateway Integration:** This PR adds the infrastructure for receipts. Actual payment processing requires integration with payment gateways (PayPal, Stripe, etc.) which should be added separately.

3. **Email Receipts:** Currently, receipts are download-only. Email functionality should be added in a future update.

4. **Refunds:** No refund handling in this version. Should be added as needed.

## Future Enhancements

Potential additions for later versions:
- Email receipt delivery
- Refund tracking and receipts
- Multiple payment methods per transaction
- Payment plan/installment tracking
- Tax calculation and display
- Invoice generation (pre-payment)
- Recurring payment support
- Export payment history to CSV
- Admin payment management interface

## Support

For issues or questions:
- Check documentation in code comments
- Review this release notes document
- Contact plugin maintainer at IELTStestONLINE

---

**Plugin Version:** 11.12  
**WordPress Requirement:** 5.8+  
**PHP Requirement:** 7.2+  
**Database Changes:** Yes (new table)  
**Breaking Changes:** No  
**Migration Required:** No (automatic)
