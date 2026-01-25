# Version 14.2 Release Notes

## New Features

### 1. Configurable Membership Durations

**Location:** Memberships → Settings → Membership Durations

- Added ability to set custom duration for each membership type
- Duration can be configured in:
  - Hours
  - Days
  - Weeks
  - Months
- **Default Values:**
  - Academic Trial: 6 hours
  - General Training Trial: 6 hours
  - Academic Full Membership: 30 days
  - General Training Full Membership: 30 days

**Technical Details:**
- Settings stored in `ielts_cm_membership_durations` option
- Each membership type has a `value` and `unit` pair
- Automatic expiry date calculation based on configured duration
- Uses WordPress timezone functions for accurate time calculations

### 2. "Become a Full Member" Page Configuration

**Location:** Memberships → Settings → Become a Full Member Page

- Added URL field for the upgrade/membership page
- URL is displayed in trial countdown widget
- Can be set to any URL (e.g., `https://www.ieltstestonline.com/become-a-member`)
- Used in email templates as `{upgrade_url}` placeholder

**Technical Details:**
- Settings stored in `ielts_cm_full_member_page_url` option
- Properly sanitized as URL input
- Falls back to home URL if not set

### 3. Trial Countdown Widget

**Location:** Displayed automatically on all pages for trial members

**Features:**
- Fixed position in bottom left corner of screen
- Real-time countdown timer showing time remaining
- Displays time in format: `Xd Xh Xm Xs`
- "Become a Full Member" button with configured URL
- Dismissible with close button (X)
- Beautiful gradient purple design
- Only visible for trial members with active subscriptions
- Automatically hides when trial expires

**Technical Details:**
- JavaScript-based real-time countdown
- Updates every second
- Server-side check ensures only trial users see it
- Responsive design that works on all screen sizes
- Z-index of 9999 ensures it stays on top
- Smooth hover animations and transitions

### 4. Email Templates System

**Location:** Memberships → Emails

**Templates Included:**
1. **New Trial Enrollment**
   - Sent when user registers for a trial membership
   - Default subject: "Welcome to Your Free Trial!"
   
2. **Full Membership Enrollment**
   - Sent when user registers for full membership
   - Default subject: "Welcome to Your Full Membership!"

3. **Trial Course Expired**
   - Can be sent when trial membership expires
   - Default subject: "Your Trial Has Expired"
   - Includes upgrade link

4. **Full Membership Expired**
   - Can be sent when full membership expires
   - Default subject: "Your Membership Has Expired"
   - Includes renewal link

**Email Placeholders:**
- `{username}` - User's display name
- `{email}` - User's email address
- `{membership_name}` - Full membership type name
- `{expiry_date}` - Expiry date of membership
- `{upgrade_url}` - URL to become a full member page
- `{renewal_url}` - URL to renew membership

**Technical Details:**
- Each template has subject and message fields
- Messages support HTML through `wp_kses_post()`
- Automatic sending on user registration for trial/full memberships
- Templates stored in separate options for easy management
- Uses WordPress `wp_mail()` function for sending

## Updated Features

### Registration System Updates
- Now uses configurable duration settings instead of hardcoded values
- Automatically sends enrollment emails based on membership type
- Improved expiry date calculation
- Support for hours-based memberships (for short trials)

### Membership Management
- Enhanced settings page with better organization
- Duration settings grouped by membership type
- Clear visual separation of settings sections
- Improved user experience with dropdown selectors

## Technical Changes

### New Functions Added

**In `IELTS_CM_Membership` class:**
- `emails_page()` - Display email templates admin page
- `send_enrollment_email($user_id, $membership_type)` - Send enrollment emails
- `calculate_expiry_date($membership_type)` - Calculate expiry based on duration settings

**In `IELTS_CM_Frontend` class:**
- `enqueue_scripts()` - Enqueue frontend styles
- `get_countdown_widget_styles()` - Generate CSS for countdown widget
- `add_trial_countdown_widget()` - Display countdown timer for trial users

### New Settings Registered
- `ielts_cm_membership_durations` - Stores duration configuration
- `ielts_cm_full_member_page_url` - Stores upgrade page URL
- `ielts_cm_email_trial_enrollment` - Trial enrollment email template
- `ielts_cm_email_full_enrollment` - Full enrollment email template
- `ielts_cm_email_trial_expired` - Trial expired email template
- `ielts_cm_email_full_expired` - Full expired email template

### New Admin Menu Items
- "Emails" submenu under Memberships menu

## Version Information
- **Previous Version:** 14.1
- **Current Version:** 14.2
- **Release Date:** 2026-01-25

## Files Modified
1. `ielts-course-manager.php` - Updated version numbers
2. `includes/class-membership.php` - Added duration settings, email templates, and helper functions
3. `includes/class-shortcodes.php` - Updated registration to use new duration system
4. `includes/frontend/class-frontend.php` - Added trial countdown widget

## Upgrade Notes

### For Administrators
1. Navigate to **Memberships → Settings** to configure:
   - Membership durations for each type
   - "Become a Full Member" page URL
2. Navigate to **Memberships → Emails** to customize:
   - Email templates for all membership events
   - Subject lines and message content
3. Test trial membership registration to see the countdown widget in action

### For Developers
- New helper methods available in `IELTS_CM_Membership` class
- Email sending is automatic on registration
- Duration calculation uses configurable settings
- Frontend countdown widget can be styled via CSS

## Backward Compatibility
- All existing functionality preserved
- Default values match previous hardcoded behavior
- No database migrations required
- Existing memberships continue to work normally

## Security Considerations
- All user inputs properly sanitized
- Nonce verification on all forms
- Email content filtered through `wp_kses_post()`
- URL inputs validated with proper sanitization
- Duration values use `absint()` for integer validation

## Future Enhancements
- Automated email sending for expired memberships (cron job)
- Email preview functionality in admin
- Email sending history/logs
- Support for custom email headers and styling
- Email template variations per membership type
