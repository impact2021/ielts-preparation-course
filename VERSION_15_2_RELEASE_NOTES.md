# Version 15.2 Release Notes

## Overview
Version 15.2 fixes critical issues with course delegation to membership levels and adds support for English Only memberships.

## Major Changes

### 1. Fixed Course Delegation to Membership Levels
**Problem:** Courses in the "english" category were not showing for members even when they were checked/selected in the admin membership-courses page (`wp-admin/admin.php?page=ielts-membership-courses`).

**Root Cause:** The shortcode filtering logic was only using category-based filtering (checking for "academic" or "general" in category slugs) and completely ignoring the explicit course-to-membership mapping set in the admin.

**Solution:** 
- Updated `display_courses()` in `class-shortcodes.php` to implement a two-tier filtering approach:
  1. **Primary:** Check explicit course-to-membership mapping (`ielts_cm_membership_course_mapping` option)
  2. **Fallback:** Use category-based filtering, but include "neutral" courses (courses without specific module assignments)

**Impact:**
- ✅ Courses explicitly mapped in admin now always work correctly
- ✅ General English courses can be shared across Academic and General Training memberships
- ✅ Shortcode `[ielts_courses category="english" columns="4" orderby="title" order="ASC"]` now works as expected
- ✅ Backward compatibility maintained with existing category-based logic

### 2. English Only Membership Option
**Feature:** Added support for "English Only" membership type, allowing sites to offer General English courses separately from IELTS preparation.

**Changes:**
- Added two new membership levels:
  - `english_trial` - English Only - Free Trial
  - `english_full` - English Only Full Membership
- Added admin toggle: `ielts_cm_english_only_enabled` to enable/disable this feature
- Updated membership settings page to show/hide English Only based on toggle
- Added default durations for English Only memberships (6 hours trial, 30 days full)
- Updated `get_module_from_membership()` and `get_module_from_course()` to recognize "english" module

**Usage:**
1. Go to IELTS Memberships → Settings
2. Enable "English Only Membership" checkbox
3. Set durations for English Only trial and full memberships
4. Go to IELTS Memberships → Courses
5. Check courses for English Only membership levels as needed

### 3. Additional Fixes

#### Band Scores Table Color Fix
**Problem:** White text color in band scores table headers was being overridden by body text color.

**Solution:** Added `!important` to the color property in `.ielts-band-scores-table th` styles.

**Location:** `includes/class-shortcodes.php` line 3044

#### Progress Rings Background Gradient Removal
**Problem:** Unwanted background gradient appearing behind progress rings streak section.

**Solution:** Removed the linear gradient background from `.progress-rings-streak` and replaced with transparent background.

**Location:** `templates/progress-rings.php` line 173

## Files Changed

### Modified Files
1. `ielts-course-manager.php` - Version bump to 15.2
2. `includes/class-membership.php` - English Only membership support
3. `includes/class-shortcodes.php` - Course delegation fix and band scores color fix
4. `templates/progress-rings.php` - Background gradient removal

### Technical Details

#### class-membership.php Changes
```php
// Added to MEMBERSHIP_LEVELS constant
'english_trial' => 'English Only - Free Trial',
'english_full' => 'English Only Full Membership'

// New setting registered
register_setting('ielts_membership_settings', 'ielts_cm_english_only_enabled');

// Default durations updated
'english_trial' => array('value' => 6, 'unit' => 'hours'),
'english_full' => array('value' => 30, 'unit' => 'days')
```

#### class-shortcodes.php Changes
```php
// Enhanced filtering logic in display_courses()
if (isset($mapping[$course->ID]) && is_array($mapping[$course->ID])) {
    // Use explicit mapping
    if (in_array($membership_type, $mapping[$course->ID])) {
        $filtered_courses[] = $course;
    }
} else {
    // Fallback to category-based filtering
    // Now includes neutral courses (empty course_module)
}

// Band scores fix
color: white !important;  // Line 3044
```

## Database Schema
No database migrations required. New options are created automatically:
- `ielts_cm_english_only_enabled` (boolean, default: false)
- Existing `ielts_cm_membership_course_mapping` continues to work with new membership types

## Backward Compatibility
✅ Fully backward compatible
- Existing academic/general memberships continue to work
- Existing category-based filtering preserved as fallback
- English Only features only appear when explicitly enabled
- No breaking changes to existing shortcodes or functionality

## Upgrade Notes
1. No special upgrade steps required
2. English Only membership is disabled by default
3. Existing course-to-membership mappings are preserved
4. Sites can continue using category-based filtering or switch to explicit mapping

## Testing Recommendations

### Test Case 1: Explicit Mapping
1. Create courses with "english" category only
2. Go to IELTS Memberships → Courses
3. Check these courses for Academic and/or General Training memberships
4. Verify courses appear for members with those membership types

### Test Case 2: Category-Based Filtering (Legacy)
1. Create courses with "academic" or "general" in category slug
2. Don't add explicit mapping in admin
3. Verify courses still filter correctly by membership type

### Test Case 3: English Only Membership
1. Enable English Only in settings
2. Create test user with english_trial membership
3. Verify they see courses assigned to English Only
4. Verify they don't see academic/general specific courses

### Test Case 4: Shortcode with Category Filter
1. Use: `[ielts_courses category="english" columns="4" orderby="title" order="ASC"]`
2. Verify courses display in 4 columns, sorted by title
3. Verify only courses user has access to are shown

### Test Case 5: Band Scores Display
1. View page with `[ielts_band_scores]` shortcode
2. Verify table header text is white, not affected by body text color

### Test Case 6: Progress Rings Display
1. View page with `[ielts_progress_rings]` shortcode
2. Verify no unwanted gradient background in streak section

## Migration Guide
For sites currently using category-based filtering exclusively:

**Option A: Continue with categories (no changes needed)**
- Current behavior preserved
- Courses filter by academic/general in category slug

**Option B: Switch to explicit mapping (recommended)**
1. Go to IELTS Memberships → Courses
2. Check appropriate membership levels for each course
3. Save mapping
4. Courses will now use explicit mapping (more flexible)

**Option C: Hybrid approach**
- Use explicit mapping for shared courses (like General English)
- Continue using categories for IELTS-specific courses
- Both methods work together seamlessly

## Known Issues
None

## Support
For questions or issues, please contact support at https://www.ieltstestonline.com/

---

**Release Date:** 2026-01-27
**Version:** 15.2
**Previous Version:** 15.1
