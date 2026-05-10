# Partner Dashboard - User Guide

## Overview

The Partner Dashboard allows partner administrators to create invite codes and manage student enrollments without requiring payment. This is an alternative enrollment method to the payment-based membership system.

## Access Requirements

To access the partner dashboard, users must have one of the following:
- **Partner Admin** role (custom role created by the system)
- **Administrator** role
- The `manage_partner_invites` capability

## Setup Instructions

### 1. Enable the Access Code System

1. Go to **WordPress Admin → IELTS Courses → Settings**
2. Check the box for **"Enable Access Code Membership System"**
3. Click **Save Changes**
4. The "Partner Dashboard" menu will now appear in the admin sidebar

### 2. Configure Settings

1. Go to **Partner Dashboard → Settings**
2. Configure the following options:

#### Required Settings:
- **Default Invite Length (days)**: How long codes are valid (default: 30)
- **Max Students Per Partner**: Maximum active students (0 = unlimited)
- **Expiry Action**: What to do when students expire:
  - Remove course enrollments (keep WordPress user)
  - Delete WordPress user completely
- **Notify Days Before Expiry**: When to send advance notice (0 to disable)

#### Optional Settings:
- **Post-User Creation Redirect URL**: Where to redirect after user creation
- **Login Page URL**: URL of your login page
- **Registration Page URL**: URL of your registration page

3. Click **Save Changes**

### 3. Create a Dashboard Page

1. Go to **Pages → Add New**
2. Create a page titled "Partner Dashboard" (or any name you prefer)
3. In the content editor, add the shortcode:
   ```
   [iw_partner_dashboard]
   ```
4. Set the page to **Private** or restrict access as needed
5. Click **Publish**

### 4. Assign Partner Admin Role

For each partner who should access the dashboard:

1. Go to **Users → All Users**
2. Edit the user
3. Change their **Role** to **Partner Admin**
4. Click **Update User**

Alternatively, administrators can access the dashboard without role changes.

## Using the Dashboard

### Dashboard Overview

When partners visit the dashboard page, they'll see:

1. **System Status** - Shows active student count vs. maximum limit
2. **Create Invite Codes** - Generate codes for distribution
3. **Create User Manually** - Directly create users without codes
4. **All Invite Codes** - View and manage all generated codes
5. **Managed Students** - View and manage enrolled students
6. **CSV Export** - Download codes for external distribution

### Creating Invite Codes

Invite codes allow partners to distribute access to potential students.

**Steps:**
1. Go to the **Create Invite Codes** section
2. Select **Quantity** (1-10 codes)
3. Select **Days Valid** (30, 60, 90, 180, or 365 days)
4. Select **Course Group**:
   - IELTS Academic + English
   - IELTS General Training + English
   - General English Only
   - All Courses
5. Click **Create Codes**
6. Generated codes appear in a textarea
7. Click **Copy Codes** to copy to clipboard
8. Distribute codes to students

**Code Format:** `IELTS-XXXXXXXX` (8 random characters)

**What Happens:**
- Codes are stored in the database as "active"
- Partners can distribute codes via email, website, etc.
- Students use codes to register on the registration page
- Once used, codes become "used" and cannot be reused

### Creating Users Manually

Skip the invite code step and create users directly.

**Steps:**
1. Go to the **Create User Manually** section
2. Enter student's **Email Address**
3. Enter student's **First Name**
4. Enter student's **Last Name**
5. Select **Days of Access** (30, 60, 90, 180, or 365 days)
6. Select **Course Group**
7. Click **Create User**

**What Happens:**
- System generates a username from email (e.g., `john_example_com_12345`)
- System generates a secure random password
- User account is created in WordPress
- User is enrolled in selected IELTS courses
- IELTS membership metadata is set
- Welcome email is sent to user with login credentials
- Confirmation message shows the generated username

**Email Content:**
```
Subject: Welcome to IELTS Course

Welcome! Your account has been created.

Username: john_example_com_12345
Password: XyZ123!@#AbC

Please login to access your courses.
```

### Managing Invite Codes

View all generated codes in the **All Invite Codes** table.

**Table Columns:**
- **Code**: The invite code (e.g., IELTS-A1B2C3D4)
- **Course Group**: Which courses the code grants access to
- **Days**: How long the code is valid for
- **Status**: active (unused) or used
- **Used By**: Username of student who used the code (if used)
- **Created**: Date code was generated
- **Actions**: Delete button (for unused codes only)

**Actions:**
- **Delete**: Click to permanently delete an unused code
  - Confirmation dialog appears
  - Code is removed from database
  - Table refreshes automatically

### Managing Students

View all enrolled students in the **Managed Students** table.

**Table Columns:**
- **Username**: WordPress username
- **Email**: Student's email address
- **Course Group**: Which courses they have access to
- **Expiry Date**: When their access expires
- **Actions**: Revoke button

**Actions:**
- **Revoke**: Remove student's access
  - Confirmation dialog appears
  - Student is unenrolled from all IELTS courses
  - Student's enrollment records are deleted
  - IELTS membership status set to "expired"
  - Table refreshes automatically

**Note:** Depending on settings, this may either:
- Remove course enrollments (keep WordPress user)
- Delete WordPress user completely

### Exporting Codes

Download all codes as a CSV file for distribution or record-keeping.

**Steps:**
1. Scroll to the **All Invite Codes** section
2. Click **Download CSV** button
3. File downloads as `invite-codes.csv`

**CSV Format:**
```
Code,Course Group,Days,Status,Used By,Created
IELTS-A1B2C3D4,IELTS Academic + English,30,active,,2026-01-31
IELTS-E5F6G7H8,IELTS General Training + English,60,used,john_doe,2026-01-30
```

**Use Cases:**
- Import into Excel/Google Sheets
- Share with team members
- Print for distribution
- Archive for records

## Course Groups Explained

### IELTS Academic + English
- IELTS Academic module courses
- General English courses
- Best for: Students preparing for university admission

### IELTS General Training + English
- IELTS General Training module courses
- General English courses
- Best for: Students preparing for migration or work

### General English Only
- Only General English courses
- No IELTS-specific content
- Best for: Students focusing on language skills

### All Courses
- All IELTS courses (Academic + General Training)
- All General English courses
- Best for: Students who want full access

## IELTS Course Manager Integration

When users are created or enroll via invite codes, the system automatically:

1. **Sets Membership Type** based on course group:
   - Academic + English → `academic_full`
   - General Training + English → `general_full`
   - English Only → `english_full`
   - All Courses → `academic_full`

2. **Sets Membership Status**: `active`

3. **Sets Expiry Date**: Based on selected days

4. **Enrolls in Courses**: All published IELTS courses matching the group

5. **Stores Metadata**:
   - `_iw_user_manager` - Partner admin who created the user
   - `_iw_user_expiry` - Expiration date
   - `_iw_user_group` - Course group assignment
   - `_ielts_cm_membership_type` - IELTS membership type
   - `_ielts_cm_membership_status` - Membership status
   - `_ielts_cm_membership_expiry` - Expiry date

## Student Limits

Partners are subject to student limits configured in settings.

**When Limit is Reached:**
- Dashboard shows: "Maximum student limit reached"
- Create codes button is disabled
- Create user button is disabled
- Partners cannot create new codes or users
- Existing students are not affected

**To Increase Limit:**
1. Administrator goes to **Partner Dashboard → Settings**
2. Increase **Max Students Per Partner**
3. Save changes
4. Partners can now create more codes/users

**Unlimited:**
- Set **Max Students Per Partner** to `0`
- No limit enforced

## Troubleshooting

### Dashboard Says "Permission Denied"
- Ensure user has **Partner Admin** role or **Administrator** role
- Contact site administrator to assign correct role

### Can't Create Codes/Users
- Check if student limit has been reached
- Administrator can increase limit in settings
- Check that Access Code System is enabled in IELTS Courses → Settings

### Welcome Emails Not Sending
- Check WordPress email configuration
- Verify `wp_mail()` is working
- Consider using SMTP plugin (e.g., WP Mail SMTP)
- Check spam folders

### Students Can't Access Courses
- Verify courses are published
- Check course group mapping
- Ensure IELTS Course Manager plugin is active
- Check user's enrollment in database

### Codes Not Working for Students
- Ensure registration page has `[iw_register_with_code]` shortcode
- Check code status in dashboard (must be "active")
- Verify code hasn't expired
- Ensure Access Code System is enabled

## Best Practices

### Code Distribution
- Generate codes in batches for tracking
- Use CSV export for record-keeping
- Include course group information when sharing codes
- Set appropriate validity period (shorter for trial, longer for paid)

### User Management
- Use manual user creation for known students
- Use invite codes for self-service enrollment
- Review managed students regularly
- Revoke access promptly when needed

### Student Limits
- Set realistic limits based on capacity
- Monitor active student count
- Plan for growth

### Communication
- Send welcome emails with clear instructions
- Provide login page URL
- Offer support contact information
- Set expectations about access duration

## Support

For issues or questions:
- Contact your WordPress administrator
- Check the Partnership Area → Settings page
- Review this documentation
- Check WordPress error logs

## Technical Details

**Database Tables:**
- `wp_ielts_cm_access_codes` - Stores invite codes
- `wp_ielts_cm_enrollment` - Stores course enrollments
- User metadata for tracking and management

**WordPress Capabilities:**
- `manage_partner_invites` - Partner admin capability
- `manage_options` - Administrator capability

**Shortcodes:**
- `[iw_partner_dashboard]` - Partner dashboard
- `[iw_register_with_code]` - Student registration (to be implemented)
- `[iw_my_expiry]` - Student account info (to be implemented)
