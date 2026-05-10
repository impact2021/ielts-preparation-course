# Testing Guide for Version 15.19

This guide provides step-by-step instructions to test the changes in version 15.19.

## Prerequisites

- WordPress admin access
- At least 5-10 test exercises in the system
- A test user account to take exercises

## Test 1: Bulk Edit - Skill Type Field

### Purpose
Verify that administrators can bulk edit the Skill Type field for multiple exercises at once.

### Steps

1. **Navigate to exercises list:**
   - Go to `/wp-admin/edit.php?post_type=ielts_quiz`
   - You should see a list of exercises with a "Category" column showing the Skill Type

2. **Test Bulk Edit:**
   - Select 5-10 exercises using the checkboxes
   - Click "Bulk Actions" dropdown at the top
   - Select "Edit"
   - Click "Apply"
   - A bulk edit panel should appear
   - Locate the "Skill Type" dropdown in the panel
   - Select a skill type (e.g., "Reading")
   - Click "Update" button
   - Verify all selected exercises now show "Reading" in the Category column

3. **Test "No Change" option:**
   - Select multiple exercises with different skill types
   - Open bulk edit panel
   - Leave "Skill Type" set to "— No Change —"
   - Click "Update"
   - Verify that skill types remain unchanged

4. **Test setting to "Not Set":**
   - Select exercises with skill types
   - Bulk edit and set Skill Type to "Not Set"
   - Click "Update"
   - Verify exercises now show "Not set" in gray text

### Expected Results
- ✅ Bulk edit panel displays Skill Type dropdown
- ✅ Changing skill type updates all selected exercises
- ✅ "No Change" option preserves existing values
- ✅ Can set or remove skill types in bulk

## Test 2: Quick Edit - Skill Type Field

### Purpose
Verify that administrators can quickly edit the Skill Type for individual exercises.

### Steps

1. **Navigate to exercises list:**
   - Go to `/wp-admin/edit.php?post_type=ielts_quiz`

2. **Test Quick Edit:**
   - Hover over an exercise title
   - Click "Quick Edit" link
   - A quick edit panel should appear inline
   - Verify "Skill Type" dropdown is present
   - The current skill type should be pre-selected
   - Change to a different skill type
   - Click "Update" button
   - Verify the Category column updates immediately

3. **Test preserving value:**
   - Quick edit an exercise
   - Note the current skill type is pre-selected
   - Change to different value
   - Click "Update"
   - Quick edit the same exercise again
   - Verify the new value is now pre-selected

### Expected Results
- ✅ Quick edit displays Skill Type dropdown
- ✅ Current value is pre-selected
- ✅ Changes save correctly
- ✅ UI updates immediately after save

## Test 3: Band Score Calculation - Highest Score Only

### Purpose
Verify that estimated band scores only use the highest score from each exercise, not all attempts.

### Prerequisites
- Create or identify 2-3 exercises with skill types assigned
- Have a test user account ready

### Steps

1. **Setup - Take exercises multiple times:**
   - Log in as a test user
   - Take Exercise 1 (e.g., Reading skill type)
     - First attempt: Score 60%
     - Second attempt: Score 90%
   - Take Exercise 2 (same skill type)
     - First attempt: Score 70%
     - Second attempt: Score 50% (intentionally lower)

2. **Check Skills Radar Chart:**
   - Navigate to a page with `[ielts_skills_radar]` shortcode
   - View the Reading skill percentage
   - Expected: Should be average of 90% and 70% = 80%
   - NOT: Average of all attempts (60+90+70+50)/4 = 67.5%

3. **Check Band Scores Display:**
   - Navigate to a page with `[ielts_band_scores]` shortcode
   - View the Reading band score
   - Should reflect the 80% average (converted to band score)

4. **Verify with database (optional):**
   - Check `wp_ielts_cm_quiz_results` table
   - Confirm multiple attempts exist for each quiz
   - Band score should only use MAX(percentage) per quiz_id

### Expected Results
- ✅ Only highest score per exercise is used
- ✅ Lower retake scores don't hurt band score
- ✅ Skills Radar shows accurate skill levels
- ✅ Band Scores shortcode reflects best performance

## Test 4: Security Checks

### Purpose
Verify that bulk/quick edit operations are properly secured.

### Steps

1. **Test permission check:**
   - Log in as a user without edit permissions (subscriber role)
   - Try to access `/wp-admin/edit.php?post_type=ielts_quiz`
   - Should not see bulk/quick edit options or be able to save

2. **Test with editor role:**
   - Log in as Editor
   - Should be able to use bulk/quick edit
   - Changes should save successfully

### Expected Results
- ✅ Only users with `edit_post` capability can save changes
- ✅ Input is sanitized before processing
- ✅ Only valid skill types are accepted

## Test 5: Backward Compatibility

### Purpose
Verify that existing functionality still works.

### Steps

1. **Test regular exercise editing:**
   - Edit an exercise normally (not quick/bulk edit)
   - Change Skill Type in the exercise meta box
   - Save the exercise
   - Verify skill type is saved correctly

2. **Test existing exercises:**
   - View exercises that already have skill types set
   - Verify they display correctly in the list
   - Take these exercises as a student
   - Verify band scores still calculate

### Expected Results
- ✅ Regular editing still works
- ✅ Existing exercises display correctly
- ✅ No data loss or corruption
- ✅ Band scores calculate correctly for existing data

## Reporting Issues

If you encounter any problems during testing:

1. Note which test case failed
2. Describe the expected vs actual behavior
3. Include any error messages
4. Note your WordPress version and PHP version
5. Check browser console for JavaScript errors (F12)

## Success Criteria

All test cases should pass with ✅ for the release to be considered successful.
