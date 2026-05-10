# Access Code Registration Shortcode

## Overview
Version 15.11 introduces a new shortcode `[ielts_access_code_registration]` that provides a dedicated registration form for users with access codes. This form is completely separate from the paid membership registration system.

## Shortcode Usage

### Basic Usage
```
[ielts_access_code_registration]
```

### With Redirect
```
[ielts_access_code_registration redirect="https://yoursite.com/courses"]
```

## Features

### What's Included
- **Access Code Field**: Primary field for entering the 8-character access code
- **Basic Registration Fields**: First Name, Last Name, Email, Password
- **No Payment Options**: This form has NO payment or membership selection fields
- **Automatic Login**: Users are automatically logged in after successful registration
- **Course Enrollment**: Users are automatically enrolled in courses based on their access code's course group

### What's NOT Included
- Payment fields or Stripe integration
- Membership type selection dropdown
- Upgrade/extension options
- Any paid membership functionality

## Access Code System Requirements

### Prerequisites
1. Access Code Membership system must be enabled
   - Go to: IELTS Courses → Settings
   - Enable "Access Code Membership System"

2. Access codes must be created first
   - Use the Partner Dashboard: `[iw_partner_dashboard]`
   - Partners can generate access codes for their students
   - Each code specifies: course group, duration, and expiry

### Course Groups
Access codes are assigned to one of three course groups:
- **Academic Module**: Academic + English courses + practice tests
- **General Training Module**: General + English courses + practice tests  
- **General English**: English courses only

## User Flow

1. User receives an access code from a partner/admin
2. User visits page with `[ielts_access_code_registration]` shortcode
3. User enters:
   - Access code (8 characters)
   - First name
   - Last name
   - Email address
   - Password (min 6 characters)
   - Password confirmation
4. System validates access code:
   - Code must exist in database
   - Code must be active (not already used)
   - Code must not be expired
5. On success:
   - User account is created
   - Access code is marked as "used"
   - User receives appropriate WordPress role (e.g., `access_academic_module`)
   - User is enrolled in all courses matching their course group
   - Membership expiry is set based on code's duration
   - User is auto-logged in
   - User is redirected to specified page or home

## Validation

### Access Code Validation
- Code must be exactly as created (case-insensitive, converted to uppercase)
- Code must have status = 'active'
- Code must not be expired
- Code can only be used once

### User Validation
- Email must be unique (not already registered)
- Password must be at least 6 characters
- All required fields must be filled

### Security
- Nonce validation on form submission
- SQL injection prevention via prepared statements
- XSS prevention via proper escaping
- WordPress standard password hashing

## Separation from Paid Membership

This shortcode is completely independent of the paid membership system:

| Feature | Paid Registration `[ielts_registration]` | Access Code Registration `[ielts_access_code_registration]` |
|---------|------------------------------------------|-------------------------------------------------------------|
| **Payment** | Yes - Stripe integration | No - Access code only |
| **Membership Selection** | Yes - Trial/Full/Plus options | No - Determined by access code |
| **Self-Service** | Yes - Anyone can register | No - Requires pre-created code |
| **WordPress Roles** | academic_trial, general_full, etc. | access_academic_module, etc. |
| **Who Creates Users** | Users themselves | Partners via dashboard or self with code |
| **Pricing** | Configured in admin settings | Free for user (partner pays) |

## Installation

### 1. Add Shortcode to a Page
Create a new page (e.g., "Register with Code") and add:
```
[ielts_access_code_registration redirect="/courses"]
```

### 2. Configure Settings
- Ensure Access Code Membership is enabled
- Optionally set redirect URL in shortcode parameter

### 3. Share Page URL
- Give the registration page URL to partners
- Partners can share it with students along with access codes

## Example Implementation

```html
<div class="custom-wrapper">
    <h1>Welcome Students!</h1>
    <p>Use the access code provided by your instructor to get started.</p>
    
    [ielts_access_code_registration redirect="/my-courses"]
    
    <p><small>Don't have an access code? <a href="/contact">Contact us</a></small></p>
</div>
```

## Troubleshooting

### "Access code registration is currently not available"
- Check that Access Code Membership System or Hybrid Site mode is enabled in IELTS Courses → Settings

### "Invalid or already used access code"
- Verify code is correct (8 characters, uppercase)
- Check if code has already been used
- Verify code status is 'active' in database

### "Email already exists"
- User may have already registered
- Direct them to login page instead

### User not enrolled in courses
- Check that courses exist with correct category slugs
- Verify course group mapping is correct
- Check enrollment table in database

## Version History

### Version 15.11
- Initial release of `[ielts_access_code_registration]` shortcode
- Full separation from paid membership registration
- Support for all three access code course groups
- Automatic enrollment and role assignment
