# Membership System - Quick Start Guide

## Version 14.1 - New Features

This guide will help you get started with the new membership system.

## Step 1: Enable the Membership System

The **Memberships** admin menu is always visible in the WordPress admin sidebar, even when the system is disabled. This allows you to easily enable or configure the system at any time.

1. Log in to WordPress admin
2. Navigate to **Memberships → Settings**
3. Check the box "Enable the membership system"
4. Click "Save Changes"

## Step 2: Create Login, Registration, and Account Pages

### Create Login Page
1. Go to **Pages → Add New**
2. Title: "Login"
3. Add the shortcode: `[ielts_login redirect="/my-account"]`
4. Publish the page

### Create Registration Page
1. Go to **Pages → Add New**
2. Title: "Register"
3. Add the shortcode: `[ielts_registration redirect="/my-account"]`
4. Publish the page

### Create Account Page
1. Go to **Pages → Add New**
2. Title: "My Account"
3. Add the shortcode: `[ielts_account]`
4. Publish the page

## Step 3: Configure Membership Pricing (Optional)

1. Go to **Memberships → Payment Settings**
2. Set prices for each membership level:
   - Academic Module - Free Trial: 0.00
   - General Training - Free Trial: 0.00
   - Academic Module Full Membership: 49.99
   - General Training Full Membership: 49.99
3. Configure Stripe and/or PayPal if needed
4. Click "Save Changes"

## Step 4: Map Courses to Memberships

1. Go to **Memberships → Courses**
2. For each course, check which membership levels should have access
3. Click "Save Changes"

## Step 5: Assign Memberships to Users

### Method 1: Via User Edit Page
1. Go to **Users → All Users**
2. Click on a user to edit
3. Scroll to "Membership Information" section
4. Select membership type from dropdown
5. Set expiry date (or leave empty for lifetime)
6. Click "Update User"

### Method 2: Via Memberships Page
1. Go to **Memberships → Memberships**
2. View all current memberships
3. Click "Edit" next to any user to modify their membership

## Available Membership Levels

1. **Academic Module - Free Trial**
   - Code: `academic_trial`
   - Typical use: Trial access to academic IELTS courses

2. **General Training - Free Trial**
   - Code: `general_trial`
   - Typical use: Trial access to general training courses

3. **Academic Module Full Membership**
   - Code: `academic_full`
   - Typical use: Full access to all academic IELTS courses

4. **General Training Full Membership**
   - Code: `general_full`
   - Typical use: Full access to all general training courses

## Shortcode Reference

### Login Form
```
[ielts_login]
[ielts_login redirect="/dashboard"]
```
**Parameters:**
- `redirect` (optional): URL to redirect to after login

### Registration Form
```
[ielts_registration]
[ielts_registration redirect="/welcome"]
```
**Parameters:**
- `redirect` (optional): URL to redirect to after registration

### Account Page
```
[ielts_account]
```
**Parameters:** None

## Checking Membership Status

### In Users List
- Go to **Users → All Users**
- The "Membership" column shows:
  - Membership type
  - Expiry date
  - Status (Active in green, Expired in red)

### In Memberships Page
- Go to **Memberships → Memberships**
- See all users with memberships in a table format
- Filter by status

## FAQ

**Q: How do I disable the membership system?**
A: Go to Memberships → Settings and uncheck "Enable the membership system". Note: The admin menu will remain visible so you can re-enable it anytime.

**Q: What happens when a membership expires?**
A: The user's membership status will show as "Expired" in red, and they will lose access to membership-restricted courses.

**Q: Can I give a user lifetime membership?**
A: Yes! When editing a user, leave the expiry date field empty.

**Q: How do I know which courses are in which membership?**
A: Go to Memberships → Courses to see and edit the course-membership mappings.

**Q: Is payment processing automatic?**
A: The payment settings (Stripe/PayPal) are configured in this version, but automatic payment processing will be added in a future update.

## Support

For more detailed documentation, see:
- `VERSION_14_0_RELEASE_NOTES.md` - Complete feature documentation
- **Memberships → Docs** in WordPress admin

## Next Steps

After setting up the membership system:
1. Test the login/registration flow
2. Assign test memberships to users
3. Verify course access is working correctly
4. Configure your payment provider (if using paid memberships)

---

**Version:** 14.1  
**Last Updated:** January 2026  
**Plugin:** IELTS Course Manager
