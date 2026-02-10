# Version 15.41 Release Notes

## Overview
This release adds two improvements:
1. The ability to set menu order for units directly in the unit settings page, allowing administrators to control the navigation order
2. Repositioned and recolored the "Report an Error" button to be less obtrusive and match site branding

## Changes

### 1. Menu Order Support for Units

**Issue**: Unit navigation was working based on menu order, but there was no way to set the menu order in the admin interface. Administrators had to rely on the default WordPress ordering (usually by date) or use third-party plugins.

**Solution**: Added 'page-attributes' support to the `ielts_course` post type, which enables the "Order" field in the unit edit page sidebar.

**How to Use**:
1. Navigate to IELTS Courses in WordPress admin
2. Edit any unit (course)
3. In the right sidebar, find the "Page Attributes" section
4. Set the "Order" field to your desired number (lower numbers appear first)
5. Update the unit
6. Repeat for other units to establish your desired navigation order

**Technical Details**:
- Modified `includes/class-post-types.php` to add `'page-attributes'` to the supports array
- Unit navigation already respects `menu_order` in `templates/single-course.php`
- No database changes required - uses WordPress's built-in menu_order field

### 2. Report Error Button Improvements

**Issue**: The circular "Report an Error" button was positioned at the bottom of the page, obscuring the "Next" navigation link. It also used a hardcoded blue color that didn't match site branding.

**Solution**: 
- Repositioned the button to be higher up on the page (120px from bottom instead of 30px)
- Moved it slightly more to the right edge (20px from right instead of 30px)
- Changed z-index to 9998 (from 9999) to ensure it stays below critical navigation elements
- Updated button colors to use the site's Primary Color setting from admin settings
- Button and submit form now dynamically use `ielts_cm_vocab_header_color` option

**Visual Changes**:
- Button is now positioned higher and more to the right, avoiding interference with bottom navigation
- Button background color matches the Primary Color setting (default: #E56C0A instead of #0073e6)
- Submit button in the modal also uses the primary color for consistency
- Hover effect changed to opacity-based for better color consistency

**Files Modified:**
- `includes/class-post-types.php`
- `includes/frontend/class-frontend.php`
- `ielts-course-manager.php` (version bump)

## Version Numbers
- Plugin Version: 15.40 → 15.41
- IELTS_CM_VERSION constant: 15.40 → 15.41

## Testing Recommendations

### 1. Verify Menu Order Field Appears
- [ ] Navigate to IELTS Courses → Edit any unit
- [ ] Verify "Page Attributes" section appears in right sidebar
- [ ] Verify "Order" field is present and editable

### 2. Test Navigation Order
- [ ] Create or edit 3+ units with different menu order values (e.g., 10, 20, 30)
- [ ] As a logged-in user enrolled in these units, navigate to first unit
- [ ] Verify "Previous" and "Next" navigation buttons respect menu_order
- [ ] Change menu_order values and verify navigation updates accordingly

### 3. Test Report Error Button Position and Color
- [ ] Navigate to any course, lesson, or quiz page as a logged-in user
- [ ] Verify the Report Error button (?) appears in the bottom-right corner
- [ ] Verify it's positioned higher than before (not obscuring bottom nav)
- [ ] Verify the button color matches your Primary Color setting
- [ ] Click the button to open the modal
- [ ] Verify the "Submit Report" button also uses the primary color
- [ ] Change the Primary Color in Settings and verify button updates

### 4. Backwards Compatibility
- [ ] Verify existing units without menu_order set (defaulting to 0) still work
- [ ] Verify navigation works correctly for units with same menu_order value

## Notes
- Menu order field is optional - units without a set order will default to 0
- Report error button color automatically updates when Primary Color is changed in settings
- Both changes are minimal and leverage existing WordPress and plugin functionality
