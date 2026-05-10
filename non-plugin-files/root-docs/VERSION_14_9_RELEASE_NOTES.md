# Version 14.9 Release Notes

**Release Date**: January 26, 2026  
**Version**: 14.9

## Overview

This release focuses on improving the email system for membership notifications, specifically addressing issues with free trial expiry emails and adding professional email sender customization.

## New Features

### Configurable Email Sender Information

Administrators can now customize the sender name and email address for all membership-related emails, replacing the default "WordPress" sender.

**Benefits:**
- Professional branding on all emails
- Improved email deliverability
- Better user trust and recognition
- Easy configuration through the admin panel

**How to Use:**
1. Navigate to: `IELTS Course Manager → Email Templates`
2. Find the new "Email Sender Settings" section at the top
3. Configure:
   - **From Name**: The name recipients will see (e.g., "IELTS Team")
   - **From Email Address**: The email address that will appear as sender
4. Click "Save Changes"

**Default Behavior:**
- If left blank, From Name defaults to your WordPress site name
- If left blank, From Email defaults to your WordPress admin email

## Bug Fixes

### Fixed Free Trial Expiry Email Not Sending

**Issue**: Free trial expiry emails were not being sent reliably due to email template validation issues.

**Root Cause**: When email templates were stored as empty arrays in the database, the validation logic could trigger PHP warnings and prevent default templates from being initialized.

**Fix**: Enhanced email template validation to properly handle empty arrays:
```php
// Before:
if (empty($email_template['subject']) || empty($email_template['message']))

// After:
if (empty($email_template) || empty($email_template['subject']) || empty($email_template['message']))
```

**Impact**: 
- Trial expiry emails now send reliably
- Default templates are properly created when needed
- No PHP warnings about undefined array indices
- Consistent behavior across all email types (enrollment and expiry)

### Added Email Address Validation

**Enhancement**: Added validation when saving email sender address to ensure only valid email addresses are saved.

**User Feedback**: Administrators now receive an error message if they enter an invalid email address, preventing silent failures.

## Technical Changes

### Files Modified

#### `ielts-course-manager.php`
- Updated plugin version to 14.9
- Updated `IELTS_CM_VERSION` constant to 14.9

#### `includes/class-membership.php`
- Added `custom_email_from_name()` filter function
- Added `custom_email_from_address()` filter function
- Registered new WordPress options: `ielts_cm_email_from_name` and `ielts_cm_email_from_address`
- Added UI fields for email sender configuration in `emails_page()`
- Enhanced email template validation in `send_enrollment_email()` and `send_expiry_email()`
- Added email address validation with user feedback
- Improved PHPDoc comments for better code documentation

### WordPress Filters Added

- `wp_mail_from_name`: Applied to customize email sender name
- `wp_mail_from`: Applied to customize email sender address

## Testing Recommendations

### Testing Email Sender Settings

1. Log into WordPress admin panel
2. Navigate to: `IELTS Course Manager → Email Templates`
3. Configure sender settings with your desired values
4. Save changes
5. Trigger a test email (create a trial membership or enrollment)
6. Verify the email arrives with correct sender information

### Testing Free Trial Expiry Emails

1. Create a test user with a trial membership
2. Set the expiry date to a past date
3. Manually trigger the cron job or wait for daily execution:
   ```php
   do_action('ielts_cm_check_expired_memberships');
   ```
   Or via WP-CLI:
   ```bash
   wp cron event run ielts_cm_check_expired_memberships
   ```
4. Check the user's email inbox for the expiry notification
5. Verify the email contains correct information and sender details

## Upgrade Notes

- **Backward Compatible**: This release is fully backward compatible
- **No Database Changes**: No database migrations required
- **Default Behavior**: If you don't configure sender settings, emails will continue using WordPress defaults
- **Automatic Template Creation**: Default email templates will be automatically created if they don't exist

## Known Issues

None at this time.

## Credits

- Fixed issue reported by users regarding free trial expiry emails not being sent
- Implemented requested feature for customizable email sender information

## Support

For questions or issues with this release, please contact support or file an issue in the repository.
