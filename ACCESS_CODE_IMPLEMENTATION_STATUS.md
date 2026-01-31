# Access Code System - Implementation Status

## Summary

This document tracks the implementation of the Access Code Membership System for IELTS Course Manager, adapted from the IELTS Student Management plugin to work with IELTS Course Manager's custom post types instead of LearnDash.

## What's Been Completed ✅

### 1. Core Infrastructure
- ✅ Toggle added to IELTS Courses → Settings
- ✅ Access code class created (`includes/class-access-codes.php`)
- ✅ is_enabled() helper method implemented
- ✅ Conditional admin menu (only shows when toggle is ON)
- ✅ Integration with main plugin file
- ✅ Database tables created:
  - `wp_ielts_cm_access_codes` - Stores access codes
  - `wp_ielts_cm_access_code_courses` - Maps course groups to courses

### 2. Database Schema

#### Access Codes Table
```sql
wp_ielts_cm_access_codes
- id (bigint)
- code (varchar 50, unique)
- course_group (varchar 50)
- duration_days (int)
- created_by (bigint - user ID)
- created_date (datetime)
- status (varchar 20: active/used/expired/disabled)
- used_by (bigint - user ID, nullable)
- used_date (datetime, nullable)
- expiry_date (datetime, nullable)
```

#### Course Mapping Table
```sql
wp_ielts_cm_access_code_courses
- id (bigint)
- course_group (varchar 50)
- course_id (bigint - IELTS course post ID)
- created_date (datetime)
```

### 3. Settings Page
- ✅ Partnership Area menu created (admin only)
- ✅ Settings page with configuration options:
  - Default invite length (days)
  - Max students per partner
  - Expiry action (delete user vs remove enrollments)
  - Notification days before expiry
  - Post-registration redirect URLs
  - Login/registration page URLs

### 4. Basic Shortcodes
- ✅ `[iw_partner_dashboard]` - Partner dashboard (skeleton)
- ✅ `[iw_register_with_code]` - Registration form (placeholder)
- ✅ `[iw_my_expiry]` - Membership expiry display (basic)

## What Needs to Be Completed 🚧

### Priority 1: Core Functionality

#### Partner Dashboard (`[iw_partner_dashboard]`)
**Status**: Skeleton exists, needs full implementation

**Required Features**:
1. ✅ Permission check (manage_partner_invites capability)
2. ⏳ Create invite codes form (1-10 at once)
   - Select quantity
   - Select days valid
   - Select course group
   - Generate unique codes
   - Display generated codes in textarea
   - Copy to clipboard functionality
3. ⏳ Manual user creation form
   - Email, first name, last name inputs
   - Days of access selector
   - Course group selector
   - Auto-generate username from email
   - Generate secure random password
   - Send welcome email
4. ⏳ View all invite codes table
   - Code, course group, days, status
   - Used by (username), used date
   - Delete button for unused codes
5. ⏳ Managed students table
   - Username, email, course group, expiry
   - Revoke, update expiry, re-enroll actions
   - Search/filter functionality
6. ⏳ Excel/CSV export of codes

**AJAX Handlers Needed**:
- `iw_create_invite` - Generate codes
- `iw_create_user_manually` - Create user without code
- `iw_revoke_student` - Revoke access
- `iw_delete_code` - Delete unused code
- `iw_update_expiry` - Extend/shorten access
- `iw_reenrol_student` - Re-enroll expired student
- `iw_bulk_update_expiry` - Bulk update
- `iw_download_codes_excel` - Export codes

#### Registration Form (`[iw_register_with_code]`)
**Status**: Placeholder only

**Required Features**:
1. ⏳ Public registration form
   - Access code input
   - Username input
   - Email input
   - Password input
   - Password confirmation
2. ⏳ Code validation
   - Check code exists
   - Check code is active (not used)
   - Check code not expired
3. ⏳ User creation
   - Create WordPress user
   - Set user role (subscriber)
   - Set user metadata
4. ⏳ Course enrollment
   - Get courses for code's course group
   - Enroll user in IELTS courses
   - Add to enrollment table
5. ⏳ IELTS membership setup
   - Set membership type based on course group
   - Set membership status (active)
   - Set expiry date
6. ⏳ Post-registration
   - Auto-login user
   - Redirect to configured URL
   - Send welcome email
   - Notify partner admin

**AJAX Handler**:
- `iw_register_user` (no privileges required)

### Priority 2: User Management

#### Partner Admin Role
**Status**: Not created

**Required**:
1. ⏳ Create `partner_admin` role
   - Base capabilities: same as subscriber
   - Add capability: `manage_partner_invites`
2. ⏳ Add capability to administrator role
3. ⏳ Role assignment UI (admin only)

#### User Lifecycle Management
**Status**: Basic structure exists

**Required**:
1. ⏳ Expiry checking cron job
   - Daily check for expired users
   - Send advance notifications (X days before)
   - Execute expiry action on expiry date
2. ⏳ User metadata:
   - `_iw_user_manager` - Partner admin who created user
   - `_iw_user_expiry` - Expiration date
   - `_iw_user_group` - Course group assignment
   - `_iw_expiry_notice_sent` - Advance notice flag
   - `_iw_last_login` - Last login timestamp
3. ⏳ Enrollment management
   - Enroll user in courses based on group
   - Remove enrollments on expiry/revocation
   - Re-enroll functionality

### Priority 3: Course Group Mapping

#### Course Groups
**Defined**:
- `academic_english` - IELTS Academic + English
- `general_english` - IELTS General Training + English  
- `english_only` - General English Only
- `all_courses` - All Courses

#### Mapping to IELTS Courses
**Status**: Not implemented

**Required**:
1. ⏳ Admin interface to map course groups to IELTS courses
2. ⏳ Save mappings to `wp_ielts_cm_access_code_courses` table
3. ⏳ Use mappings during enrollment
4. ⏳ Default mappings based on course categories/tags

#### IELTS Course Manager Integration
**Status**: Basic structure exists

**Required**:
1. ⏳ Map course groups to IELTS membership types:
   - `academic_english` → `academic_full`
   - `general_english` → `general_full`
   - `english_only` → `english_full`
   - `all_courses` → `academic_full` (default)
2. ⏳ Set user metadata:
   - `_ielts_cm_membership_type`
   - `_ielts_cm_membership_status`
   - `_ielts_cm_membership_expiry`
3. ⏳ Sync with existing membership system

### Priority 4: Email Notifications

#### Email Templates Needed
**Status**: Basic welcome email exists

**Required**:
1. ⏳ Welcome email to new user
   - Include username and password
   - Login URL
   - Course access information
2. ⏳ New user notification to partner admin
   - User details
   - Course group
   - Expiry date
3. ⏳ Advance expiry notice to partner admin
   - X days before expiry
   - User details
   - Renewal options
4. ⏳ Expiry notification to partner admin
   - User has expired
   - Action taken (deleted vs unenrolled)
5. ⏳ Manual user creation confirmation
   - Optional copy to creating admin
   - Credentials included

### Priority 5: Additional Features

#### My Account Page (`[iw_my_expiry]`)
**Status**: Basic expiry display exists

**Enhancements Needed**:
1. ⏳ Display detailed expiry information
2. ⏳ Show days remaining
3. ⏳ Update email address form
4. ⏳ Update first/last name form
5. ⏳ Change password form
6. ⏳ View enrolled courses

#### Login Page (`[iw_login]`)
**Status**: Not implemented

**Required**:
1. ⏳ Custom login form
2. ⏳ Username or email login
3. ⏳ Remember me checkbox
4. ⏳ Lost password link
5. ⏳ Failed login handling
6. ⏳ Auto-redirect for logged-in users

#### Site-Wide Login Enforcement
**Status**: Not implemented

**Required**:
1. ⏳ Force login on all pages
2. ⏳ Except configured public pages (login, registration)
3. ⏳ Cache-control headers
4. ⏳ Redirect to login page with return URL

#### Excel/CSV Export
**Status**: Not implemented

**Required**:
1. ⏳ Export invite codes to Excel
2. ⏳ Include all code details
3. ⏳ Filter options (used/unused, date range)
4. ⏳ Download as .xlsx or .csv

## Code Organization

### Main File Structure
```
includes/
├── class-access-codes.php          # Main access code class (current: ~400 lines, needs: ~1500+ lines)
├── class-database.php              # Database tables ✅ COMPLETE
├── class-activator.php             # Plugin activation (no changes needed)
└── admin/
    └── class-admin.php             # Main settings (toggle added ✅)
```

### Recommended File Split (Future)
For better maintainability, consider splitting into:
```
includes/
└── access-codes/
    ├── class-access-codes.php           # Core class
    ├── class-access-codes-admin.php     # Admin functionality
    ├── class-access-codes-frontend.php  # Shortcodes and registration
    ├── class-access-codes-emails.php    # Email notifications
    └── class-access-codes-cron.php      # Cron jobs
```

## Testing Checklist

### Feature Testing
- [ ] Toggle access code system ON → Menu appears
- [ ] Toggle access code system OFF → Menu disappears
- [ ] Settings save correctly
- [ ] Partner admin role created and works
- [ ] Create invite codes (1-10 at once)
- [ ] View created codes in dashboard
- [ ] Delete unused codes
- [ ] Create user manually
- [ ] User receives welcome email
- [ ] Register with invite code
- [ ] Code marked as used after registration
- [ ] User enrolled in correct courses
- [ ] IELTS membership set correctly
- [ ] Expiry date calculated correctly
- [ ] Revoke student access works
- [ ] Update expiry works
- [ ] Re-enroll student works
- [ ] Cron job expires users correctly
- [ ] Advance notices sent correctly
- [ ] Excel/CSV export works

### Integration Testing
- [ ] Works alongside payment-based membership system
- [ ] Both systems can run simultaneously
- [ ] Each system can run independently
- [ ] IELTS Course Manager integration works
- [ ] Enrollment table updates correctly
- [ ] User metadata syncs correctly

### Security Testing
- [ ] Only authorized users can access dashboard
- [ ] Nonce verification on all AJAX requests
- [ ] Input validation and sanitization
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection

## Migration Path from Other Repository

### Files to Adapt
From the IELTS Student Management plugin:

1. **Main Plugin File**:
   - Adapt shortcode registrations
   - Adapt AJAX hook registrations
   - Adapt cron job setup

2. **Dashboard HTML/JS**:
   - Copy partner dashboard HTML structure
   - Adapt JavaScript for AJAX calls
   - Update table structures

3. **Registration Form**:
   - Copy registration form HTML
   - Adapt validation logic
   - Update enrollment logic (LearnDash → IELTS CM)

4. **Email Templates**:
   - Copy email content
   - Adapt for IELTS CM structure
   - Update links and branding

5. **Cron Jobs**:
   - Copy expiry checking logic
   - Adapt email sending
   - Update database queries

### Key Differences to Handle

| Feature | Old (LearnDash) | New (IELTS CM) |
|---------|----------------|----------------|
| Course Type | LearnDash Course CPT | `ielts_course` CPT |
| Enrollment | LearnDash enrollment | `wp_ielts_cm_enrollment` table |
| Membership | Custom user meta | IELTS CM membership meta |
| Content Hierarchy | LearnDash topics/lessons | IELTS lessons/sublessons/exercises |

## Next Steps

1. **Immediate** (1-2 hours):
   - Complete partner dashboard AJAX handlers
   - Implement code generation logic
   - Add manual user creation

2. **Short-term** (3-5 hours):
   - Implement registration form
   - Add course enrollment logic
   - Create partner admin role
   - Set up cron job

3. **Medium-term** (5-8 hours):
   - Implement all email notifications
   - Add course group mapping UI
   - Complete user management features
   - Add Excel/CSV export

4. **Final** (2-3 hours):
   - Testing and bug fixes
   - Documentation updates
   - Security review

**Total Estimated Time**: 15-20 hours

## Resources

- **Original Plugin**: IELTS Student Management
- **Target System**: IELTS Course Manager
- **Custom Post Types**: `ielts_course`, `ielts_lesson`, `ielts_resource`, `ielts_quiz`
- **Database Tables**: See `includes/class-database.php`
- **Membership System**: See `includes/class-membership.php`

## Notes

- The skeleton implementation provides the foundation
- Database tables are ready
- Settings infrastructure is complete
- Main work needed: AJAX handlers, registration form, and cron jobs
- Consider using custom agents for completing large sections
