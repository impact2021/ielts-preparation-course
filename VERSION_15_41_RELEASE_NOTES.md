# Version 15.41 Release Notes

## Overview
This release adds the ability to set menu order for units directly in the unit settings page, allowing administrators to control the navigation order.

## Changes

### Menu Order Support for Units

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
- Unit navigation already respects `menu_order` in `templates/single-course.php` (line 320)
- No database changes required - uses WordPress's built-in menu_order field

**Files Modified:**
- `includes/class-post-types.php`
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

### 3. Backwards Compatibility
- [ ] Verify existing units without menu_order set (defaulting to 0) still work
- [ ] Verify navigation works correctly for units with same menu_order value

## Notes
- This is a minimal change that leverages WordPress's built-in functionality
- No changes to existing unit navigation logic were required
- Menu order field is optional - units without a set order will default to 0
