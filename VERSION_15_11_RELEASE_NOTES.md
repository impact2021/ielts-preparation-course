# Version 15.11 Release Notes

## Release Date
January 31, 2026

## Summary
Version 15.11 introduces a new dedicated registration shortcode for access code-based enrollment, providing a streamlined registration experience for users with pre-generated access codes.

## New Features

### Access Code Registration Shortcode
A new shortcode `[ielts_access_code_registration]` has been added specifically for access code-based user registration.

**Key Characteristics:**
- ✅ Access code field (required)
- ✅ Basic user information fields (name, email, password)
- ✅ Automatic course enrollment based on access code
- ✅ No payment integration
- ✅ No membership selection dropdown
- ✅ Complete separation from paid membership system

**Usage:**
```
[ielts_access_code_registration]
[ielts_access_code_registration redirect="/courses"]
```

## Technical Details

### Files Modified
1. `ielts-course-manager.php`
   - Updated version number from 15.10 to 15.11
   - Updated IELTS_CM_VERSION constant

2. `includes/class-shortcodes.php`
   - Added new shortcode registration: `ielts_access_code_registration`
   - Implemented `display_access_code_registration()` method
   - Full form processing, validation, and user creation logic

### Files Added
1. `ACCESS_CODE_REGISTRATION_SHORTCODE.md`
   - Complete documentation for the new shortcode
   - Usage examples and troubleshooting guide

2. `VERSION_15_11_RELEASE_NOTES.md`
   - This file

## Implementation Details

### Form Fields
1. **Access Code** (required)
   - 8-character code validation
   - Uppercase conversion
   - Real-time database validation

2. **First Name** (required)
   - Standard text sanitization

3. **Last Name** (required)
   - Standard text sanitization

4. **Email Address** (required)
   - Email format validation
   - Uniqueness check
   - Used as basis for username generation

5. **Password** (required)
   - Minimum 6 characters
   - Confirmation field required

### Access Code Validation
The system validates access codes against the following criteria:
- Code must exist in `wp_ielts_cm_access_codes` table
- Status must be 'active' (not 'used' or 'expired')
- If expiry_date is set, must not be in the past
- Code is case-insensitive (converted to uppercase)

### User Creation Process
When a valid access code is submitted:

1. **User Account Creation**
   - Username generated from email (e.g., john.doe@example.com → john.doe)
   - If username exists, timestamp is appended
   - Password is securely hashed by WordPress
   - First name, last name, and display name are set

2. **Membership Assignment**
   - Course group is read from access code (academic_module, general_module, or general_english)
   - Corresponding WordPress role is assigned (access_academic_module, etc.)
   - Membership metadata is set (_ielts_cm_membership_type, etc.)
   - Expiry date is calculated from access code's duration_days

3. **Course Enrollment**
   - User is enrolled in all courses matching their course group
   - Enrollment records are created in enrollment table
   - Expiry date is applied to all enrollments

4. **Access Code Usage**
   - Code status is changed from 'active' to 'used'
   - used_by field is set to new user's ID
   - used_date is set to current timestamp

5. **Auto-Login and Redirect**
   - User is automatically logged in
   - WordPress authentication cookies are set
   - User is redirected to specified page or home

## Security Features

### Input Validation
- All user inputs are sanitized using WordPress functions
- Email validation using WordPress `is_email()` function
- Password strength enforcement (minimum 6 characters)
- Access code format validation

### Security Measures
- Nonce verification for CSRF protection
- Prepared SQL statements to prevent SQL injection
- Proper escaping of all output to prevent XSS
- WordPress standard password hashing
- One-time use enforcement for access codes

### Access Control
- Access code system must be enabled in settings
- User registration must be enabled in WordPress
- Logged-in users are prevented from using the form
- Only active codes can be redeemed

## Compatibility

### WordPress Compatibility
- Tested with WordPress 5.8+
- Follows WordPress coding standards
- Uses WordPress core functions for user management

### Existing Systems Compatibility
- **Paid Membership System**: Completely separate, no interference
  - Different WordPress roles (access_* vs academic_*, general_*)
  - Different registration forms and flows
  - Different user metadata keys
  
- **Partner Dashboard**: Fully compatible
  - Partners can still create users manually
  - Partners can generate access codes
  - This shortcode provides user-facing self-service option

- **Access Code System**: Requires this system to be enabled
  - Must have access codes created via partner dashboard
  - Uses existing access code database tables
  - Compatible with existing code management

## Migration Notes

### For Existing Sites
No migration required. This is a new feature that:
- Adds a new shortcode only
- Does not modify existing functionality
- Does not change database schema
- Does not affect existing users or access codes

### For New Sites
1. Enable Access Code Membership System in settings
2. Create partner accounts with partner_admin role
3. Partners create access codes via dashboard
4. Add `[ielts_access_code_registration]` to a page
5. Share page URL with users

## Usage Scenarios

### Scenario 1: Educational Institution
- Institution purchases access for 100 students
- Admin enables access code system
- Admin or partner generates 100 codes (Academic Module, 365 days)
- Students visit registration page
- Students enter their unique codes
- Students gain immediate access to courses

### Scenario 2: Corporate Training
- Company wants to provide IELTS training to employees
- Company representative becomes a partner
- Partner creates codes for each employee
- Employees register using their codes
- Access automatically expires after training period

### Scenario 3: Limited-Time Promotion
- Site offers free access via promotional codes
- Admin creates codes with 30-day duration
- Codes are distributed via email/social media
- Users self-register with codes
- Access automatically expires after 30 days

## Known Limitations

1. **One Code Per Registration**: Each access code can only be used once
2. **No Code Validation Before Submit**: Code is validated on form submission, not in real-time
3. **No Code Recovery**: If user forgets their code, they must contact the partner
4. **Username Based on Email**: Username is auto-generated from email, not user-chosen
5. **No Trial Conversion**: Access code users cannot upgrade to paid memberships (separate systems)

## Future Enhancements (Not Included)

Potential improvements for future versions:
- Real-time AJAX access code validation
- Ability to preview course access before registration
- Email notification to code creator when code is used
- Bulk code distribution via email
- QR code generation for access codes
- Support for codes with limited uses (e.g., 5 redemptions)

## Testing Checklist

When testing this feature:

- [ ] Access code system is enabled in settings
- [ ] At least one active access code exists
- [ ] Shortcode renders form correctly
- [ ] Form validation works for all fields
- [ ] Invalid access code shows error
- [ ] Already used access code shows error
- [ ] Expired access code shows error
- [ ] Valid code creates user successfully
- [ ] User receives correct WordPress role
- [ ] User is enrolled in correct courses
- [ ] Access code is marked as used
- [ ] User is auto-logged in after registration
- [ ] User is redirected to correct page
- [ ] Error messages are clear and helpful
- [ ] Form is protected from CSRF attacks
- [ ] Input is properly sanitized
- [ ] Logged-in users see appropriate message

## Support and Documentation

For complete documentation, see:
- `ACCESS_CODE_REGISTRATION_SHORTCODE.md` - Complete shortcode documentation
- `ACCESS_CODE_SYSTEM_IMPLEMENTATION.md` - Access code system overview
- `ACCESS_CODE_PAID_MEMBERSHIP_COMPATIBILITY.md` - System separation details

## Upgrade Instructions

### From Version 15.10
1. Upload updated plugin files
2. No database changes required
3. No settings changes required
4. Add shortcode to desired pages
5. Test with a valid access code

### Version Number Update
- Plugin version: 15.10 → 15.11
- IELTS_CM_VERSION constant: '15.10' → '15.11'

## Credits

Developed by: IELTStestONLINE Team
Release Date: January 31, 2026
Version: 15.11
