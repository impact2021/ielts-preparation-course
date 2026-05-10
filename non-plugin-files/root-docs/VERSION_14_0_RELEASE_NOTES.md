# Version 14.0 - Membership System Implementation

## Overview
This release adds a comprehensive membership system to the IELTS Course Manager plugin, allowing sites to manage user memberships, control course access, and handle payments.

## Key Features

### 1. Membership Toggle
- The entire membership system can be enabled/disabled from Settings
- When disabled, all membership features are hidden
- Useful for sites with their own membership systems

### 2. Membership Levels
Four membership levels are available:
- **Academic Module - Free Trial** (`academic_trial`)
- **General Training - Free Trial** (`general_trial`)
- **Academic Module Full Membership** (`academic_full`)
- **General Training Full Membership** (`general_full`)

### 3. Shortcodes

#### Login Form
```
[ielts_login]
[ielts_login redirect="/dashboard"]
```
Displays a login form with optional redirect parameter.

#### Registration Form
```
[ielts_registration]
[ielts_registration redirect="/welcome"]
```
Displays a registration form with validation and optional redirect.

#### Account Page
```
[ielts_account]
```
Displays user account information including membership details.

### 4. Admin Menu Structure

A new "Memberships" top-level menu has been added with the following submenu items:

#### Memberships
- View all current memberships
- Shows user name, email, membership type, expiry date, and status
- Quick access to edit user profiles
- Color-coded status (Active in green, Expired in red)

#### Docs
- Complete documentation for the membership system
- Overview of features
- Membership levels reference
- Shortcode usage examples
- Management instructions

#### Settings
- **Enable/Disable Membership System**: Toggle to turn membership features on/off
- When disabled, membership features are hidden across the entire plugin

#### Courses
- Map courses to membership levels
- Grid interface showing all courses
- Checkboxes for each membership level
- Easy selection of which courses are included in which memberships

#### Payment Settings
- **Membership Pricing**: Set prices for each membership level (USD)
- **Stripe Integration**:
  - Enable/disable Stripe payments
  - Publishable key
  - Secret key
- **PayPal Integration**:
  - Enable/disable PayPal payments
  - Client ID
  - Secret key

### 5. Users List Enhancement

The WordPress Users list (`wp-admin/users.php`) now includes:
- **Membership column** showing:
  - Current membership type
  - Expiry date (if applicable)
  - "Expired" status in red if membership has expired
  - "None" for users without membership

### 6. User Profile Enhancement

The user edit page (`wp-admin/user-edit.php?user_id=xxx`) now includes:
- **Membership Information section** with:
  - Dropdown to select membership type
  - Date picker for expiry date
  - Option for lifetime membership (leave expiry empty)
- Only visible to users with `edit_users` capability

## Technical Details

### Files Added
- `includes/class-membership.php` - Main membership class

### Files Modified
- `ielts-course-manager.php` - Updated to version 14.0, includes membership class
- `includes/class-ielts-course-manager.php` - Added membership initialization
- `includes/class-shortcodes.php` - Added three new shortcodes

### Database
The system uses WordPress user meta to store membership data:
- `_ielts_cm_membership_type` - Stores the membership level
- `_ielts_cm_membership_expiry` - Stores expiry date (YYYY-MM-DD)

WordPress options are used for settings:
- `ielts_cm_membership_enabled` - Enable/disable toggle
- `ielts_cm_membership_course_mapping` - Course to membership mapping
- `ielts_cm_stripe_enabled` - Stripe enable/disable
- `ielts_cm_stripe_publishable_key` - Stripe publishable key
- `ielts_cm_stripe_secret_key` - Stripe secret key
- `ielts_cm_paypal_enabled` - PayPal enable/disable
- `ielts_cm_paypal_client_id` - PayPal client ID
- `ielts_cm_paypal_secret` - PayPal secret
- `ielts_cm_membership_pricing` - Pricing for each membership level

### Access Control
The `IELTS_CM_Membership` class provides:
- `is_enabled()` - Check if membership system is enabled
- `get_user_membership($user_id)` - Get user's membership type
- `user_has_course_access($user_id, $course_id)` - Check if user can access a course

## Usage Examples

### Enabling the Membership System
1. Go to **Memberships → Settings**
2. Check "Enable the membership system"
3. Click "Save Changes"

### Assigning a Membership to a User
1. Go to **Users → All Users**
2. Click on a user to edit
3. Scroll to "Membership Information" section
4. Select a membership type
5. Optionally set an expiry date
6. Click "Update User"

### Mapping Courses to Memberships
1. Go to **Memberships → Courses**
2. For each course, check the membership levels that should have access
3. Click "Save Changes"

### Setting Up Payment
1. Go to **Memberships → Payment Settings**
2. Enter pricing for each membership level
3. Enable and configure Stripe and/or PayPal
4. Click "Save Changes"

### Creating Login/Registration Pages
1. Create a new page for login:
   - Add shortcode: `[ielts_login redirect="/my-account"]`
2. Create a new page for registration:
   - Add shortcode: `[ielts_registration redirect="/my-account"]`
3. Create a new page for account:
   - Add shortcode: `[ielts_account]`

## Future Enhancements
The membership system provides a foundation for:
- Automated payment processing
- Membership renewal notifications
- Email notifications for expiring memberships
- Integration with third-party membership plugins
- Reporting and analytics
