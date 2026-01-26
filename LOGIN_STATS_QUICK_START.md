# Quick Start Guide - Login Stats Shortcode

## Overview
Version 14.12 adds a new shortcode to display user login statistics on any page of your WordPress site.

## Basic Usage

### Step 1: Add the Shortcode
Add this shortcode to any page or post:
```
[ielts_login_stats]
```

### Step 2: Publish
Save and publish the page. Users will see their login statistics when logged in.

## Example Placements

### Homepage
Add to your homepage to welcome users back:
```
Welcome back! Here are your stats:
[ielts_login_stats]
```

### User Dashboard
Add to a user dashboard page:
```
<h2>Your Activity</h2>
[ielts_login_stats]
```

### My Account Page
Add to the account page for a complete user profile:
```
<h3>Login Statistics</h3>
[ielts_login_stats]
```

## Customization

### Show/Hide Specific Stats
You can hide specific stats using attributes:

**Hide Last Login:**
```
[ielts_login_stats show_last_login="no"]
```

**Hide Login Count:**
```
[ielts_login_stats show_login_count="no"]
```

**Hide Total Time:**
```
[ielts_login_stats show_total_time="no"]
```

**Show Only Login Count:**
```
[ielts_login_stats show_last_login="no" show_total_time="no"]
```

## Styling

### Default Colors
The shortcode uses a clean card-based design with:
- Light gray borders (#e5e7eb)
- Blue hover effect (#3b82f6)
- White background
- Emoji icons

### Custom Styling
You can customize the appearance using CSS in your theme:

```css
/* Change card background */
.ielts-stat-item {
    background: #f9fafb !important;
}

/* Change hover border color */
.ielts-stat-item:hover {
    border-color: #10b981 !important;
}

/* Change icon size */
.ielts-stat-icon {
    font-size: 40px !important;
}

/* Change value text color */
.ielts-stat-value {
    color: #059669 !important;
}
```

## What Users See

### New Users (No Login Data Yet)
- **Last Login:** "Never"
- **Total Logins:** 0
- **Time Logged In:** 0 minutes

### Example After Using the Site
- **Last Login:** "2 hours ago"
- **Total Logins:** 15
- **Time Logged In:** 3 hours 24 minutes

## Technical Details

### Data Tracking
- Login statistics start tracking automatically after the v14.12 update
- Existing users will see "0" or "Never" until their next login
- Session time only counts active browsing (1-hour timeout for inactive sessions)

### Data Storage
All data is stored securely in WordPress user meta:
- Last login timestamp
- Total number of logins
- Total time logged in (in seconds)

### Performance
- Minimal performance impact
- Only updates data for logged-in users
- Compatible with page caching

## Troubleshooting

### Stats Show All Zeros
- User hasn't logged in since v14.12 was installed
- Ask user to log out and log back in

### Stats Not Displaying
- User is not logged in (shortcode only works for authenticated users)
- Check that the shortcode is spelled correctly: `[ielts_login_stats]`

### Stats Seem Inaccurate
- Time tracking only counts active browsing (page loads)
- Sessions longer than 1 hour are considered inactive
- This is intentional to provide accurate "active time" statistics

## Create Account Button Animation

### What It Does
When users click the "Create Account" button:
1. The button text becomes transparent
2. An animated ellipsis appears (. .. ... repeating)
3. The button is disabled to prevent double-clicks
4. Animation continues until the form submits

### Customization
You can change the animation color using CSS:

```css
:root {
    --ielts-button-loading-color: #ffffff; /* Change to any color */
}
```

## Support

For questions or issues:
1. Check the VERSION_14_12_RELEASE_NOTES.md file
2. Review the IMPLEMENTATION_SUMMARY_V14_12.md file
3. Contact your developer for custom modifications

## Version Information
- **Plugin Version:** 14.12
- **Release Date:** January 2026
- **WordPress Required:** 5.8+
- **PHP Required:** 7.2+
