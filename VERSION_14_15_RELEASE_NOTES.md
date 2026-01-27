# Version 14.15 Release Notes

**Release Date:** January 27, 2026  
**Focus:** Payment Error Logging and Debugging Improvements

---

## Overview

Version 14.15 addresses the critical issue of "not knowing where to find debug logs" for Stripe payment errors. This release introduces a comprehensive error logging system with an admin dashboard, making it easy to diagnose and debug payment issues.

---

## What's New

### 🆕 Payment Error Logging System

**Database Error Tracking**
- All payment errors are now logged to a dedicated database table (`wp_ielts_cm_payment_errors`)
- Automatic capture of user information, error details, IP address, and timestamp
- Categorized error types (validation, security, database, Stripe API, system, configuration)

**Admin Dashboard**
- New admin page: **IELTS Course → Payment Errors**
- View all payment errors in one place with pagination
- See error statistics and trends
- View detailed error information with one click
- Delete individual errors or clear all logs
- Built-in help guide on where to find debug logs

**Enhanced Error Messages**
- Users now see specific error messages instead of generic "Server error"
- Error codes (DB001, STRIPE001, etc.) for easy reference
- Contextual debugging help based on user role (admin vs regular user)
- Multi-line error messages with step-by-step debugging instructions

**Improved JavaScript Error Handling**
- Better error parsing from AJAX responses
- Helpful debugging information displayed to users
- Different guidance for administrators (with admin page link) vs regular users
- Browser console still shows detailed technical errors for developers

---

## Problem Solved

### Before Version 14.15

**User Experience:**
```
❌ "Server error: error Please check console for details"
❌ Don't know where to find debug logs
❌ No way to see what went wrong
❌ Have to contact support with generic error
```

**Developer Experience:**
```
❌ Errors only in PHP error_log (if enabled)
❌ No centralized error tracking
❌ Difficult to diagnose production issues
❌ Can't see historical error patterns
```

### After Version 14.15

**User Experience:**
```
✅ "Unable to process payment. Please try again or contact support. 
   For assistance, please mention Error Code: DB001"
   
✅ Clear debugging instructions
✅ Error codes for support reference
✅ Admins see link to admin dashboard
```

**Developer Experience:**
```
✅ All errors in centralized admin dashboard
✅ Error statistics and trends visible
✅ Detailed error information preserved
✅ Easy to diagnose production issues
✅ Historical error tracking
```

---

## Error Codes Added

| Code | Meaning | Common Cause |
|------|---------|--------------|
| DB001 | Database insert failed | Table missing, DB connection issue |
| DB002 | Payment ID not retrieved | Database issue |
| STRIPE001 | Stripe API error | Invalid API keys, network issue |
| PAY001 | Payment not found | Database inconsistency |
| SYS001 | System error | Missing required class |

---

## Technical Changes

### Files Modified

1. **includes/class-database.php**
   - Added `payment_error_log_table` property
   - Added error log table to database schema
   - Created `log_payment_error()` static method
   - Created `ensure_payment_error_table_exists()` method

2. **includes/class-stripe-payment.php**
   - Created `verify_nonce()` helper method
   - Enhanced all error responses with error codes
   - Added database error logging to all error paths
   - Improved error messages with context

3. **includes/admin/class-admin.php**
   - Added "Payment Errors" menu item
   - Created `payment_errors_page()` method
   - Built error statistics display
   - Added pagination for error logs
   - Included help section with debugging guide

4. **assets/js/registration-payment.js**
   - Enhanced `handleAjaxError()` function
   - Added error code extraction
   - Added contextual help based on user role
   - Changed from `.text()` to `.html()` for multi-line messages

5. **includes/class-shortcodes.php**
   - Added `isAdmin` flag to payment script localization

6. **ielts-course-manager.php**
   - Updated version to 14.15

### Database Schema Changes

**New Table:** `wp_ielts_cm_payment_errors`

```sql
CREATE TABLE wp_ielts_cm_payment_errors (
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
);
```

---

## Upgrade Instructions

### From Version 14.14 to 14.15

1. **Automatic Upgrade**
   - Update the plugin files
   - No manual steps required
   - Error log table is created automatically on first use

2. **Manual Table Creation** (optional)
   - If you want to create the table immediately:
   - Run SQL in WordPress database (see schema above)
   - Or deactivate/reactivate the plugin

3. **No Data Loss**
   - Existing payment records are not affected
   - Only adds new error logging table

---

## Usage Guide

### For Site Administrators

**Viewing Payment Errors:**
1. Go to WordPress Admin
2. Click **IELTS Course → Payment Errors**
3. Review error statistics and logs
4. Click "View Details" for more information
5. Delete old errors or clear all logs as needed

**Understanding Errors:**
- Check error type to understand category
- Look at error message for user-facing description
- Click "View Details" for technical information
- Note the date/time to correlate with user reports

### For Support Staff

**Helping Users with Payment Issues:**
1. Ask user for error code (e.g., "DB001")
2. Check Payment Errors admin page
3. Search for errors around the time user reported
4. Use error details to diagnose issue
5. Provide specific solution based on error type

### For Developers

**Logging Custom Errors:**
```php
IELTS_CM_Database::log_payment_error(
    'custom_type',
    'Error message',
    array('detail' => 'value'),
    $user_id,
    $email,
    $membership_type,
    $amount
);
```

---

## Testing Checklist

- [x] Error logging to database works
- [x] Admin page displays correctly
- [x] Error statistics show accurate counts
- [x] Pagination works for large error lists
- [x] Delete individual errors works
- [x] Clear all logs works with confirmation
- [x] View details button shows error information
- [x] User sees enhanced error messages
- [x] Admins see admin dashboard link in errors
- [x] Regular users see generic help text
- [x] Error codes appear in messages
- [x] Browser console shows detailed errors
- [ ] Test with actual payment failures
- [ ] Verify email notifications (if implemented)

---

## Known Issues

None at this time.

---

## Future Enhancements

Planned for future versions:

1. Email notifications for critical errors
2. Export errors to CSV
3. Automatic cleanup of old logs
4. Error analytics and charts
5. Integration with external logging services (Sentry, etc.)

---

## Breaking Changes

**None.** This is a backwards-compatible update.

All existing functionality continues to work as before. Only new features added.

---

## Security Considerations

### Data Privacy

The error logging system collects:
- User email addresses
- IP addresses
- Browser information (user agent)

**Recommendations:**
1. Include error logging in your privacy policy
2. Consider implementing automatic log cleanup
3. Provide users ability to request deletion of their logs
4. Be mindful of GDPR compliance if applicable

### SQL Injection Protection

All database queries use prepared statements:
```php
$wpdb->prepare("SELECT * FROM %s WHERE id = %d", $table, $id)
```

---

## Support

For issues or questions about this release:

1. Check the Payment Error Logging Guide (PAYMENT_ERROR_LOGGING_GUIDE.md)
2. Review errors in the admin dashboard
3. Enable WP_DEBUG for detailed PHP errors
4. Contact plugin support with error codes and details

---

## Credits

**Developed by:** GitHub Copilot  
**Requested by:** impact2021  
**Issue:** "Don't know where to find the debug log for the Stripe issue"

---

## Changelog

### [14.15] - 2026-01-27

#### Added
- Payment error logging system with database table
- Admin dashboard for viewing payment errors
- Error statistics and trends
- Error categorization by type
- Enhanced error messages with error codes
- Contextual debugging help for users
- Multi-line error message support
- Admin vs user role-based help text

#### Changed
- Error handling now logs to database in addition to error_log
- JavaScript error messages are more informative
- Version updated to 14.15

#### Fixed
- Issue where users couldn't find debug logs
- Generic "Server error" messages now show specific errors
- Better error diagnosis for support and debugging

---

**Full Documentation:** See PAYMENT_ERROR_LOGGING_GUIDE.md
