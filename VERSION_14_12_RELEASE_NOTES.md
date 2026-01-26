# IELTS Course Manager - Version 14.12 Release Notes

**Release Date:** January 2026

## New Features

### 1. Login Statistics Shortcode
Added a new `[ielts_login_stats]` shortcode that displays user login statistics on any page.

**Features:**
- Shows time since last login (in minutes, hours, or days)
- Displays total number of logins
- Shows total time spent logged in
- Attractive card-based design with icons
- Responsive layout for mobile devices

**Usage:**
```
[ielts_login_stats]
```

**Optional attributes:**
- `show_last_login` - Show/hide last login time (default: "yes")
- `show_login_count` - Show/hide login count (default: "yes")  
- `show_total_time` - Show/hide total time logged in (default: "yes")

**Example:**
```
[ielts_login_stats show_last_login="yes" show_login_count="yes" show_total_time="no"]
```

**Tracked Data:**
- `_ielts_cm_last_login` - Timestamp of last login
- `_ielts_cm_login_count` - Total number of logins
- `_ielts_cm_total_time_logged_in` - Total time logged in (in seconds)
- `_ielts_cm_session_start` - Current session start time

### 2. Create Account Button Animation
Added a loading animation to the Create Account button to provide visual feedback during registration.

**Features:**
- Animated ellipsis (. .. ... repeating)
- Works for both free trial and paid membership registrations
- Button becomes disabled during submission
- Smooth CSS animation using keyframes

**Technical Details:**
- CSS class `.loading` added to button during submission
- Animation shows 1 dot → 2 dots → 3 dots in a continuous loop
- White text color on primary button background
- Button is disabled to prevent double submissions

### 3. Login Tracking System
Implemented automatic login tracking to support the statistics shortcode.

**Features:**
- Tracks login events via WordPress `wp_login` action hook
- Updates session time on each page load (for active sessions only)
- Session timeout after 1 hour of inactivity (prevents tracking inactive time)
- Resets session timer on each page load to accurately track active time

**Technical Implementation:**
- Hooks into `wp_login` action to track user logins
- Uses `wp_footer` action to track session time on page loads
- Stores data in WordPress user meta tables

## Version Update
- Updated plugin version from 14.11 to 14.12
- Updated version constant `IELTS_CM_VERSION`

## Files Changed
1. `ielts-course-manager.php` - Updated version numbers
2. `includes/class-ielts-course-manager.php` - Added login tracking hooks and methods
3. `includes/class-shortcodes.php` - Added login stats shortcode and helper methods
4. `assets/css/frontend.css` - Added loading animation styles
5. `assets/js/registration-payment.js` - Updated to use CSS loading class

## Upgrade Notes
- No database migrations required
- Login tracking starts automatically on first login after update
- Existing users will see "0" stats until they log in again
- Session time tracking is conservative (only counts active sessions under 1 hour)

## Compatibility
- WordPress 5.8+
- PHP 7.2+
- All modern browsers support CSS animations
