# Payment Error Logging Guide - Version 14.15

## Overview

This guide explains the comprehensive payment error logging system added in version 14.15 to help diagnose Stripe payment issues and server errors.

## Problem Statement

Previously, when payment errors occurred:
- Users saw generic "Server error" messages with no details
- Error logs were only in PHP error_log files that users couldn't access
- No centralized view of payment errors for administrators
- Difficult to diagnose and debug payment issues

## Solution

Version 14.15 introduces a comprehensive error logging system with:

1. **Database Error Logging** - All payment errors are logged to a dedicated database table
2. **Admin Dashboard** - View, filter, and analyze payment errors from WordPress admin
3. **Enhanced Error Messages** - Users see helpful error messages with error codes
4. **Contextual Help** - Different guidance for administrators vs regular users
5. **Error Categorization** - Errors are categorized by type for easier analysis

---

## Features

### 1. Database Error Logging Table

**Table:** `wp_ielts_cm_payment_errors`

**Fields:**
- `id` - Unique error log ID
- `user_id` - WordPress user ID (if available)
- `error_type` - Category of error (e.g., `stripe_api_error`, `database_error`, `validation_error`)
- `error_message` - User-friendly error message
- `error_details` - JSON-encoded detailed error information
- `user_email` - User's email address
- `membership_type` - Membership type being purchased
- `amount` - Payment amount
- `ip_address` - Client IP address
- `user_agent` - Browser user agent
- `created_at` - Timestamp of error

**Error Types:**
- `validation_error` - Input validation failures (missing fields, invalid email, etc.)
- `security_error` - Security issues (nonce verification failed, amount mismatch)
- `database_error` - Database operation failures
- `stripe_api_error` - Stripe API errors
- `configuration_error` - System configuration issues (missing API keys)
- `system_error` - System errors (missing classes, internal errors)

### 2. Admin Dashboard

**Location:** WordPress Admin → IELTS Course → Payment Errors

**Features:**
- **Statistics Dashboard**
  - Total errors logged
  - Errors in last 24 hours
  - Breakdown by error type
  
- **Error Log Table**
  - Paginated list of all errors (50 per page)
  - Date/time of error
  - Error type and message
  - User information (with link to user profile)
  - View detailed error information
  - Delete individual errors
  
- **Bulk Actions**
  - Clear all logs (with confirmation)
  
- **Help Information**
  - Guide on where to find different types of debug logs
  - Instructions for WordPress debug.log
  - Browser console instructions

### 3. Enhanced Error Messages

**User-Facing Error Messages Now Include:**
- Specific error description (not generic "Network error")
- Error code (e.g., `DB001`, `STRIPE001`, `PAY001`)
- Contextual debugging help
- Different instructions for admins vs regular users

**Example Error Message for Regular User:**
```
Unable to process payment. Please try again or contact support. 
For assistance, please mention Error Code: DB001

🔍 Debugging Help:
• Check the browser console (F12) for detailed error information
• Contact the site administrator with the error code shown above
```

**Example Error Message for Administrator:**
```
Unable to process payment. Please try again or contact support. 
For assistance, please mention Error Code: DB001

🔍 Debugging Help:
• Check the browser console (F12) for detailed error information
• Visit WordPress Admin → IELTS Course → Payment Error Logs for detailed error history
• Check WordPress debug.log (usually in wp-content/debug.log) for server-side errors
• Error Code: DB001 (provide this to support)
```

### 4. Error Codes Reference

| Error Code | Description | Likely Cause |
|------------|-------------|--------------|
| `DB001` | Database insert failed | Database connection issue, table doesn't exist |
| `DB002` | Payment ID not retrieved | Database insert succeeded but ID retrieval failed |
| `STRIPE001` | Stripe API error | Invalid API keys, network issue, Stripe service down |
| `PAY001` | Payment not found | Payment record missing from database |
| `SYS001` | Membership handler not loaded | Plugin class loading issue |

---

## Usage Guide

### For Administrators

#### Viewing Payment Errors

1. Log in to WordPress admin
2. Navigate to **IELTS Course → Payment Errors**
3. Review the error statistics and recent logs
4. Click "View Details" on any error to see full error information

#### Understanding Error Types

- **validation_error**: Usually user input issues, fix by improving form validation or user guidance
- **security_error**: Potential security issues, investigate immediately
- **database_error**: Database connectivity or schema issues
- **stripe_api_error**: Stripe configuration or API issues
- **configuration_error**: Missing settings (API keys, etc.)
- **system_error**: Plugin code issues, may require developer support

#### Debugging Common Issues

**"Payment system not configured" (Error Code: None)**
- **Cause:** Stripe API keys not set
- **Fix:** Go to IELTS Course → Settings → Stripe Settings and add your API keys

**"Database error creating payment record" (Error Code: DB001)**
- **Cause:** Payment table doesn't exist or database connection failed
- **Fix:** Deactivate and reactivate the plugin to recreate tables, or check database connection

**"Stripe API error" (Error Code: STRIPE001)**
- **Cause:** Stripe API issue (invalid keys, network problem, etc.)
- **Fix:** Check Stripe Dashboard, verify API keys are correct and active

### For Developers

#### Logging Custom Payment Errors

```php
// Log a payment error from anywhere in the code
IELTS_CM_Database::log_payment_error(
    'custom_error_type',           // Error type
    'User-friendly error message', // Message shown to user
    array(                         // Additional details (JSON encoded)
        'detail1' => 'value1',
        'detail2' => 'value2'
    ),
    $user_id,                      // Optional: User ID
    $user_email,                   // Optional: User email
    $membership_type,              // Optional: Membership type
    $amount                        // Optional: Payment amount
);
```

#### Automatic Error Logging

Payment errors are automatically logged in these scenarios:

1. **Nonce verification failure** (`security_error`)
2. **Validation errors** (`validation_error`)
   - Missing required fields
   - Invalid email format
   - Email already exists
   - Invalid membership type
   - Amount mismatch
   
3. **Database errors** (`database_error`)
   - Payment table doesn't exist
   - Insert failures
   
4. **Stripe API errors** (`stripe_api_error`)
   - Any exception from Stripe API calls
   
5. **System errors** (`system_error`)
   - Missing required classes
   - Configuration issues

---

## Testing

### Test Error Logging

1. **Test validation error:**
   - Try to register without filling all fields
   - Check Payment Errors page for logged error

2. **Test Stripe API error:**
   - Temporarily use invalid Stripe API key
   - Attempt a payment
   - Check Payment Errors page for API error

3. **Test database error:**
   - Manually drop the payments table: `DROP TABLE wp_ielts_cm_payments;`
   - Attempt a payment
   - Error should be logged and table should be auto-created

### Verify Error Display

1. Open browser Developer Tools (F12)
2. Navigate to Console tab
3. Trigger a payment error
4. Verify:
   - Console shows detailed error object
   - User sees helpful error message with error code
   - Error is logged to database

---

## Migration Notes

### Upgrading from 14.14 to 14.15

The payment error log table is created automatically when:
1. The plugin is activated
2. The first error logging attempt is made (auto-creation fallback)

**No manual migration needed.**

To manually create the table:

```sql
CREATE TABLE IF NOT EXISTS wp_ielts_cm_payment_errors (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) DEFAULT NULL,
    error_type varchar(100) NOT NULL,
    error_message text NOT NULL,
    error_details longtext DEFAULT NULL,
    user_email varchar(255) DEFAULT NULL,
    membership_type varchar(50) DEFAULT NULL,
    amount decimal(10,2) DEFAULT NULL,
    ip_address varchar(45) DEFAULT NULL,
    user_agent text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY error_type (error_type),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Privacy Considerations

### Data Collected

The error logging system collects:
- User ID (if available)
- User email
- IP address
- User agent (browser information)
- Payment details (amount, membership type)
- Error details

### Data Retention

- Error logs are stored indefinitely
- Administrators can manually delete individual errors or clear all logs
- Consider implementing automatic cleanup of old logs (e.g., after 90 days)

### GDPR Compliance

To comply with GDPR:
1. Include error logging in your privacy policy
2. Provide users ability to request deletion of their error logs
3. Consider anonymizing IP addresses
4. Implement automatic log cleanup

---

## Troubleshooting

### "Payment Error Logs page is empty"

**Possible causes:**
1. No errors have occurred yet ✓
2. Table doesn't exist - check database for `wp_ielts_cm_payment_errors`
3. Database connection issues

### "Errors not being logged"

**Check:**
1. Database table exists
2. WordPress has write permissions to database
3. Error logging function is being called (check code)

### "View Details button shows empty alert"

**Cause:** `error_details` field is NULL or empty

**Fix:** This is normal for errors that don't have additional details

---

## Future Enhancements

Potential improvements for future versions:

1. **Email Notifications**
   - Send email to admin when critical errors occur
   - Configurable notification settings

2. **Export Functionality**
   - Export errors to CSV for analysis
   - Send error reports to external logging services

3. **Automatic Cleanup**
   - Option to auto-delete old error logs
   - Configurable retention period

4. **Error Analytics**
   - Graphs and charts showing error trends
   - Error rate monitoring

5. **Integration with External Services**
   - Send errors to Sentry, Bugsnag, or similar services
   - Slack/Discord notifications for critical errors

---

## Support

If you encounter issues with the error logging system:

1. Check the WordPress debug.log for PHP errors
2. Verify the payment errors table exists in the database
3. Enable WP_DEBUG to see detailed PHP errors
4. Contact plugin support with error codes and log details

---

**Version:** 14.15  
**Last Updated:** January 27, 2026  
**Author:** GitHub Copilot for IELTS Course Manager
