# Version 15.19 Release Notes

## Changes Summary

This release addresses three key issues related to bulk editing, band score calculations, and version management.

### 1. Bulk Edit for Skill Type Field

**Problem:** Managing hundreds of exercises manually by editing each one individually to set the Skill Type field was time-consuming and inefficient.

**Solution:** Added bulk edit and quick edit functionality for the Skill Type field on the WordPress admin exercises list page (`/wp-admin/edit.php?post_type=ielts_quiz`).

**Features:**
- **Bulk Edit**: Select multiple exercises and set their Skill Type in one operation
- **Quick Edit**: Click "Quick Edit" on any exercise to quickly change its Skill Type without opening the full editor
- Available options: Reading, Writing, Listening, Speaking, Vocabulary, Grammar, or Not Set

**How to use:**
1. Navigate to Exercises list page
2. Select multiple exercises using checkboxes
3. Click "Bulk Actions" → "Edit" → "Apply"
4. In the bulk edit panel, select the desired Skill Type
5. Click "Update" to apply changes to all selected exercises

### 2. Fixed Band Score Calculation

**Problem:** The estimated band score calculation was using ALL attempts for each exercise/test, including lower scores. If a student retook an exercise and scored lower, it would negatively impact their estimated band score.

**Solution:** Updated the `get_user_skill_scores()` method in `includes/class-gamification.php` to use only the HIGHEST score for each exercise.

**Technical Details:**
- Changed from `AVG(percentage)` to `AVG(best_percentage)` with a subquery
- The subquery groups by `quiz_id` and uses `MAX(percentage)` to get the best attempt
- Then averages these best scores across all exercises for each skill

**Impact:**
- Skills Radar Chart now shows more accurate skill levels
- Band Scores display (`[ielts_band_scores]` shortcode) reflects best performance
- Predicted band scores are based on student's best efforts only

### 3. Version Number Update

**Updated:**
- Plugin version: 15.18 → 15.19
- `IELTS_CM_VERSION` constant updated to match

## Files Modified

1. `ielts-course-manager.php` - Version number updates
2. `includes/class-gamification.php` - Band score calculation fix
3. `includes/admin/class-admin.php` - Bulk/quick edit functionality

## Backward Compatibility

All changes are backward compatible. No database migrations or configuration changes required.

## Testing Recommendations

1. **Bulk Edit Testing:**
   - Navigate to `/wp-admin/edit.php?post_type=ielts_quiz`
   - Select 5-10 exercises
   - Use Bulk Actions → Edit to set Skill Type
   - Verify changes applied correctly

2. **Band Score Testing:**
   - Take an exercise twice with different scores
   - Verify Skills Radar shows only the higher score
   - Check `[ielts_band_scores]` shortcode displays correctly

3. **Quick Edit Testing:**
   - Click Quick Edit on any exercise
   - Change the Skill Type
   - Verify the change is saved and displayed correctly
