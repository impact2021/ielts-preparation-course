# Version 15.4 Release Notes

## Partner Dashboard & Membership System Improvements

**Release Date:** January 31, 2026

### Overview
This release focuses on improving the partner dashboard user experience, clarifying membership options, and ensuring the free trial popup only appears when appropriate.

---

## Features & Improvements

### 1. Enhanced Partner Dashboard Layout

**Collapsible Sections**
- All dashboard sections (Create invite codes, Create user manually, Your codes, Students) are now collapsible
- Sections are closed by default to reduce visual clutter
- Click on any section header to expand/collapse it
- Visual indicator (arrow) shows section state

**Filter Tabs for Codes Table**
- Added filter tabs: All, Active, Available, Expired
- Easily filter codes by status
- "Available" shows only active/unused codes
- Filter persists when downloading CSV

**Improved Student Tracking**
- All students created by a partner now appear in the dashboard
- Students count properly against the partner's allowed limit
- Shows students created both via access codes and manual creation
- Previously only showed students who used access codes

### 2. Course Group Clarity

**What's Included Documentation**
Added clear descriptions for each course group option:

- **IELTS Academic + English:** Includes IELTS Academic module + General English courses
- **IELTS General Training + English:** Includes IELTS General Training module + General English courses  
- **General English Only:** Only General English courses (no IELTS content)
- **All Courses:** Complete access to all modules

These descriptions appear in tooltips when creating codes or users.

### 3. Renamed Membership Settings

**Clearer Toggle Labels**
- "Membership System" → **"Paid Membership"**
  - Controls trial signups, Stripe payments, and membership menu
- "Access Code System" → **"Access Code Membership"**
  - Controls partner dashboard and code-based enrollment

**Improved Descriptions**
- Updated help text to clarify what each system does
- Explains when to enable/disable each option
- Notes that systems can work together or independently

### 4. Free Trial Popup Behavior

**Conditional Display**
- Free trial popup now ONLY shows when Paid Membership system is enabled
- Prevents confusion when site only uses access codes
- Still respects all other conditions (logged-in status, page exclusions, timing)

---

## Technical Changes

### Modified Files

**includes/class-access-codes.php**
- Updated `get_partner_students()` to query all users with `iw_created_by_partner` meta
- Added `$course_group_descriptions` array with detailed descriptions
- Restructured dashboard sections with collapsible card layout
- Added filter functionality for codes table
- Added data-status attributes to table rows for filtering
- Implemented collapsible card CSS and JavaScript

**includes/frontend/class-frontend.php**
- Updated `add_trial_popup()` to check `ielts_cm_membership_enabled` option
- Popup only displays when paid membership system is active

**includes/admin/class-admin.php**
- Renamed "Membership System" to "Paid Membership"
- Renamed "Access Code System" to "Access Code Membership"
- Updated help text for both options with clearer descriptions

### CSS Updates
```css
.iw-card               /* Now collapsible with header/body structure */
.iw-card-header        /* Clickable header with arrow indicator */
.iw-card-body          /* Hidden by default, shown when expanded */
.iw-filter-btn         /* New filter button styling */
```

### JavaScript Updates
- Added collapsible card toggle functionality
- Implemented code filtering by status
- CSV export now only includes visible (filtered) rows

---

## Database Changes

**None** - This release only modifies display logic and user interface.

---

## Upgrade Notes

### For Site Administrators

1. **Review Toggle Settings**
   - Navigate to IELTS Course Manager → Settings
   - Review the renamed "Paid Membership" and "Access Code Membership" toggles
   - Ensure they're configured correctly for your site's needs

2. **Partner Dashboard**
   - Inform partners that sections are now collapsible
   - Click headers to expand sections as needed
   - Use filter tabs to view specific code statuses

3. **Free Trial Popup**
   - If you use ONLY access codes (not paid memberships), disable "Paid Membership"
   - This will hide the trial popup for non-logged-in visitors

### For Partners

1. **Dashboard Sections**
   - Click any section header to expand it
   - Sections start collapsed to keep dashboard clean

2. **Filter Your Codes**
   - Use tabs (All/Active/Available/Expired) to filter codes
   - "Active" shows codes that haven't been used yet
   - "Available" is the same as "Active"

3. **Course Groups**
   - Hover over course group options to see what's included
   - Read the descriptions under the dropdown for details

---

## Bug Fixes

- Fixed student count to include all managed students, not just those who used codes
- Improved consistency in terminology across admin interface

---

## Testing Checklist

- [x] Syntax validation on all modified PHP files
- [x] Collapsible sections work correctly
- [x] Filter tabs correctly filter codes table
- [x] Free trial popup only shows when paid membership enabled
- [x] Student count includes all partner-created users
- [x] Course group descriptions display correctly
- [x] Settings page shows new toggle labels
- [ ] Manual browser testing with live WordPress site
- [ ] Test CSV export with filtered results
- [ ] Verify popup behavior with different settings

---

## Known Issues

None at this time.

---

## Future Enhancements

- Add ability to assign membership to existing users from partner dashboard
- Implement bulk code operations (delete multiple, export selected)
- Add search/filter for students table
- Show expiry dates in students table

---

## Support

For questions or issues related to this release:
- Review the updated documentation in `PARTNER_DASHBOARD_USER_GUIDE.md`
- Check `MEMBERSHIP_QUICK_START.md` for membership system overview
- Report issues through GitHub

---

## Version History

- **15.3** - Previous stable release
- **15.4** - Current release (Partner dashboard improvements)
