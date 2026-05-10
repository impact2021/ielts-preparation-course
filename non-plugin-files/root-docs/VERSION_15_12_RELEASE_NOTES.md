# Version 15.12 Release Notes

## Overview
This release addresses critical usability issues with the listening exercise feedback system and implements significant improvements to the partner dashboard for better student management.

## Issue 1: Listening Exercise "Show me" Button Fix

### Problem
When users clicked the "Show me" button on listening exercises, the URL did not update with the question anchor (#q1, #q2, etc.), unlike reading exercises. This made it impossible to share links to specific questions in listening exercises.

### Solution
Modified the `.show-in-transcript-link` click handler in `assets/js/frontend.js` to set `window.location.hash` before handling the transcript display. This ensures the URL is updated with the question anchor, matching the behavior of reading exercises.

### Technical Details
- **File Modified**: `assets/js/frontend.js`
- **Change**: Added `window.location.hash = '#q' + questionNumber;` at line 1505
- **Impact**: Users can now share direct links to specific questions in listening exercises

---

## Issue 2: Partner Dashboard Improvements

### 2a: Welcome Email Copy to Partner

#### Problem
When partners created new users manually, they had no record of the student's login credentials, making it difficult to provide support.

#### Solution
- Added a checkbox to the "Create User Manually" form (checked by default) to send a copy of the welcome email to the partner
- Modified `send_welcome_email()` function to accept an optional parameter and send a formatted copy to the partner with student details

#### Technical Details
- **Files Modified**: `includes/class-access-codes.php`
- **UI Change**: Added checkbox in form at line 549-555
- **Backend Change**: Updated `send_welcome_email()` at line 1448-1479
- **Email Format**: Partner receives a copy with clear labeling and student information

### 2b: Student Counts in Tab Buttons

#### Problem
Partners couldn't quickly see how many active vs. expired students they had without manually counting.

#### Solution
Added dynamic counts next to the "Active" and "Expired" tab buttons in the managed students section.

#### Technical Details
- **File Modified**: `includes/class-access-codes.php`
- **Changes**: 
  - Calculate counts in `partner_dashboard_shortcode()` (lines 413-427)
  - Display counts in tab buttons (lines 576-577)
- **Format**: "Active (X)" and "Expired (Y)"

### 2c: Managed Students Table Redesign

#### Problem
The original table was cluttered with too many columns and redundant information, making it difficult to scan and manage large student lists.

#### Solution
Completely redesigned the table structure for better readability:

**Column 1 - User Details (Compact)**
- Username (bold, primary identifier)
- Full name (smaller font, secondary)
- Email address (smaller font, tertiary)
- Compact line spacing (1.3 line-height)

**Column 2 - Membership**
- Renamed from "Group" to "Membership" for clarity
- Shows course access level

**Column 3 - Expiry Information**
- Expiry date (primary)
- Last login date (smaller font, below expiry)
- Format: "Last login: dd/mm/yyyy"

**Column 4 - Actions**
- Full-width buttons for better touch targets
- Vertical layout with spacing
- Three actions: Edit, Resend Email, Revoke
- Color-coded danger button for Revoke action

**Removed**
- Separate "Email" column (moved to User Details)
- "Last Login" column (moved under Expiry)
- "Status" column (redundant - status is shown via active/expired filter)

#### Technical Details
- **File Modified**: `includes/class-access-codes.php`
- **Function**: `render_students_table()` (lines 905-985)
- **Accessibility**: Added `scope="col"` attributes to all table headers
- **CSS**: Added `.iw-btn-full-width` class for button styling

### 2d: Your Codes Table Cleanup

#### Problem
The "Your Codes" table had too many filter options, including an "All" tab that showed everything (active, used, and expired codes mixed together) and an "Expired" tab that wasn't useful.

#### Solution
Simplified to only two relevant filters:
- **Used** (default): Shows codes that have been redeemed by students
- **Unused**: Shows available codes that haven't been used yet

#### Technical Details
- **File Modified**: `includes/class-access-codes.php`
- **Changes**:
  - Updated filter buttons HTML (lines 562-564)
  - Modified `filterCodes()` JavaScript function (lines 594-619)
  - Set default filter to "used" (line 779)
- **Removed**: "All", "Active", and "Expired" filter tabs

### 2e: Create Invite Codes Enhancement

#### Problem
Partners had to look elsewhere to see how many student slots they had remaining before creating invite codes.

#### Solution
Added "Remaining places: X" directly in the section header for immediate visibility.

#### Technical Details
- **File Modified**: `includes/class-access-codes.php`
- **Change**: Updated header at line 471
- **Format**: "Create Invite Codes (Remaining places: X)"

---

## Code Quality Improvements

### Accessibility
- Added `scope="col"` attributes to all table headers for screen reader compatibility
- Improved semantic HTML structure

### Maintainability
- Extracted inline button styles to CSS class `.iw-btn-full-width`
- Reduced code duplication
- Improved code organization

---

## Version Update

Updated plugin version from **15.11** to **15.12** in:
- Plugin header (`ielts-course-manager.php` line 5)
- Version constant (`ielts-course-manager.php` line 23)

---

## Files Modified

1. **assets/js/frontend.js** - Listening exercise "Show me" button fix
2. **ielts-course-manager.php** - Version number updates
3. **includes/class-access-codes.php** - All partner dashboard improvements

---

## Testing Recommendations

### Listening Exercises
1. Complete a listening exercise
2. Click "Show me" on any question
3. Verify URL updates with #qX hash
4. Test that the hash persists and can be shared

### Partner Dashboard - Create User Manually
1. Log in as a partner
2. Navigate to Partner Dashboard
3. Expand "Create User Manually" section
4. Create a new user with checkbox enabled
5. Verify both student and partner receive emails
6. Test with checkbox disabled

### Partner Dashboard - Managed Students
1. View managed students table
2. Verify new column layout is readable
3. Check active/expired counts in tabs
4. Test filtering between active and expired
5. Verify full-width buttons work correctly

### Partner Dashboard - Your Codes
1. View "Your Codes" section
2. Verify only "Used" and "Unused" tabs are shown
3. Verify "Used" is the default filter
4. Test switching between filters

### Partner Dashboard - Create Invite Codes
1. View section header
2. Verify "Remaining places" count is visible
3. Test that count matches (max students - current students)

---

## Browser Compatibility

All changes use standard JavaScript and CSS compatible with:
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Internet Explorer 11+ (for CSS features)

---

## Database Changes

No database schema changes required. All modifications are UI/UX improvements and logic enhancements.

---

## Upgrade Notes

This is a minor release with backward-compatible changes. No special upgrade procedures required.

**Recommended steps:**
1. Backup your site
2. Update plugin to version 15.12
3. Clear browser cache
4. Test partner dashboard functionality
5. Test listening exercise feedback

---

## Known Issues

None reported.

---

## Future Enhancements

Potential improvements for future releases:
- Bulk student management actions
- Export student list to CSV
- Email templates customization
- Advanced filtering options for codes and students
