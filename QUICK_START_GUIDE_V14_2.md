# Quick Start Guide - Membership Features v14.2

## For Administrators

### 1. Configure Membership Durations

**Steps:**
1. Go to WordPress Admin Dashboard
2. Navigate to **Memberships → Settings**
3. Scroll to "Membership Durations" section
4. For each membership type, set:
   - Duration value (number)
   - Duration unit (hours/days/weeks/months)

**Example Configuration:**
```
Academic Module - Free Trial
  Duration: 6 hours

General Training - Free Trial  
  Duration: 6 hours

Academic Module Full Membership
  Duration: 30 days

General Training Full Membership
  Duration: 30 days
```

5. Click "Save Changes"

### 2. Set Upgrade Page URL

**Steps:**
1. In the same **Memberships → Settings** page
2. Find "Become a Full Member Page" field
3. Enter the full URL where users can upgrade, for example:
   ```
   https://www.ieltstestonline.com/2026/wp-admin/admin.php?page=ielts-membership-settings
   ```
4. Click "Save Changes"

### 3. Customize Email Templates

**Steps:**
1. Navigate to **Memberships → Emails**
2. You'll see 4 email templates:
   - **New Trial Enrollment** - Sent automatically when users register for trial
   - **Full Membership Enrollment** - Sent automatically for full membership registration
   - **Trial Course Expired** - Ready for use (can be automated later)
   - **Full Membership Expired** - Ready for use (can be automated later)

3. For each template:
   - Edit the **Subject** line
   - Edit the **Message** body
   - Use placeholders like `{username}`, `{expiry_date}`, etc.

4. Click "Save Changes"

**Available Placeholders:**
- `{username}` - User's display name
- `{email}` - User's email
- `{membership_name}` - Membership type name
- `{expiry_date}` - When membership expires
- `{upgrade_url}` - Link to upgrade page
- `{renewal_url}` - Link to renewal page

## For Users

### What Trial Users Will See

When a user with a **trial membership** logs in, they will see:

1. **Countdown Widget** in the bottom left corner of every page with:
   - "Free Trial" heading
   - Real-time countdown timer (e.g., "5h 30m 45s")
   - "Become a Full Member" button
   - Close button (X) to dismiss

2. **Welcome Email** sent immediately after registration containing:
   - Welcome message
   - Trial expiry information
   - Link to upgrade

### Example Countdown Display

```
┌─────────────────────────┐
│ × Free Trial            │
│                         │
│    5h 30m 45s          │
│                         │
│  [Become a Full Member] │
└─────────────────────────┘
```

Position: Fixed at bottom left
Colors: Purple gradient background, white text
Updates: Every second in real-time

## Testing Your Setup

### Test 1: Create Trial User
1. Create a new test user with trial membership
2. Log in as that user
3. ✓ Verify countdown widget appears
4. ✓ Verify countdown is updating
5. ✓ Verify "Become a Full Member" link works
6. ✓ Check email inbox for welcome message

### Test 2: Duration Settings
1. Change trial duration to 1 hour in settings
2. Create new trial user
3. ✓ Verify expiry is set to 1 hour from now
4. ✓ Verify countdown shows correct time

### Test 3: Email Customization
1. Edit trial enrollment email template
2. Add custom message
3. Create new trial user
4. ✓ Verify email contains custom message
5. ✓ Verify placeholders are replaced correctly

## Troubleshooting

### Countdown Widget Not Showing
- ✓ Verify user has a trial membership (check Users list)
- ✓ Verify membership hasn't expired
- ✓ Verify membership system is enabled in Settings
- ✓ Clear browser cache

### Emails Not Sending
- ✓ Check WordPress email settings
- ✓ Verify SMTP is configured correctly
- ✓ Check server error logs for wp_mail failures
- ✓ Test with a plugin like "WP Mail SMTP"

### Countdown Shows Wrong Time
- ✓ Verify WordPress timezone settings
- ✓ Check duration configuration in Memberships → Settings
- ✓ Verify user's expiry date in user profile

## Admin Menu Structure

```
Memberships (main menu)
├── Memberships (current memberships list)
├── Docs (documentation)
├── Settings (NEW FEATURES HERE)
│   ├── Enable Membership System
│   ├── Become a Full Member Page URL ⭐ NEW
│   └── Membership Durations ⭐ NEW
├── Courses (course mapping)
├── Payment Settings (payment configuration)
└── Emails ⭐ NEW
    ├── New Trial Enrollment
    ├── Full Membership Enrollment  
    ├── Trial Course Expired
    └── Full Membership Expired
```

## Default Values Reference

### Durations
- Academic Trial: 6 hours
- General Training Trial: 6 hours
- Academic Full: 30 days
- General Training Full: 30 days

### URLs
- Upgrade Page: Empty (falls back to home page)

### Email Templates
All templates come with sensible defaults that include:
- Professional welcome messages
- Expiry information
- Clear calls to action
- Placeholder usage examples

## Next Steps

1. ✓ Configure your membership durations
2. ✓ Set your upgrade page URL
3. ✓ Customize your email templates
4. ✓ Test with a trial user account
5. ✓ Monitor email delivery
6. ✓ Gather user feedback

## Support

If you encounter any issues:
1. Check the error logs in WordPress
2. Review the implementation summary: `IMPLEMENTATION_SUMMARY_V14_2.md`
3. Check release notes: `VERSION_14_2_RELEASE_NOTES.md`
4. Contact your development team

---

**Version:** 14.2  
**Last Updated:** January 25, 2026  
**Compatibility:** WordPress 5.8+, PHP 7.2+
