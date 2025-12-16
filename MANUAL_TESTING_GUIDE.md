# Manual Testing Guide for Data Persistence and Quiz Completion Features

This guide will help you test the new data persistence on uninstall feature and the updated quiz completion requirements.

## Prerequisites

- WordPress 5.0 or higher
- The IELTS Course Manager plugin must be activated
- Admin access to WordPress
- At least one course with lessons and quizzes

## Feature 1: Data Persistence on Uninstall

### Testing the Settings Page

1. **Access Settings**
   - Go to **IELTS Courses > Settings**
   - ✅ **Verify**: You should see a settings page titled "IELTS Course Manager Settings"

2. **Check Default Behavior**
   - ✅ **Verify**: The checkbox "Delete all plugin data when uninstalling" should be **unchecked** by default
   - ✅ **Verify**: There should be a description explaining the feature

3. **Test Settings Save**
   - Check the "Delete all plugin data when uninstalling" checkbox
   - Click "Save Changes"
   - ✅ **Verify**: You should see a success message "Settings saved."
   - Refresh the page
   - ✅ **Verify**: The checkbox should remain checked

4. **Test Uninstall with Data Preservation (Default)**
   - Uncheck the "Delete all plugin data when uninstalling" checkbox
   - Click "Save Changes"
   - Note down the number of courses, lessons, quizzes you have
   - Go to **Plugins > Installed Plugins**
   - Deactivate the IELTS Course Manager plugin
   - Click "Delete" on the plugin
   - Confirm deletion
   - Reinstall and activate the plugin
   - ✅ **Verify**: All your courses, lessons, quizzes, and progress data should still be present

5. **Test Uninstall with Data Deletion**
   - Go to **IELTS Courses > Settings**
   - Check the "Delete all plugin data when uninstalling" checkbox
   - Click "Save Changes"
   - Go to **Plugins > Installed Plugins**
   - Deactivate the IELTS Course Manager plugin
   - Click "Delete" on the plugin
   - Confirm deletion
   - Reinstall and activate the plugin
   - ✅ **Verify**: All courses, lessons, quizzes, and progress data should be deleted (fresh start)

## Feature 2: Quiz Completion Requirement

### Setup Test Data

1. **Create a Test Course**
   - Go to **IELTS Courses > Add New Course**
   - Create a course with:
     - 3 lessons
     - 2 quizzes assigned to the course

2. **Assign to Course**
   - Edit each lesson and assign them to your test course
   - Edit each quiz and assign them to your test course
   - Publish all content

### Testing Quiz Completion Requirement

1. **Enroll as Student**
   - Log in as a student user (or create a test student account)
   - Enroll in the test course

2. **Complete Only Lessons**
   - Mark all 3 lessons as complete
   - Go to your progress page
   - ✅ **Verify**: Course completion should be **less than 100%**
   - ✅ **Verify**: Progress bar should show approximately 60% (3 lessons out of 5 total items)

3. **Take First Quiz (Low Score)**
   - Complete one quiz
   - Submit it with any score (even 0%)
   - Go to your progress page
   - ✅ **Verify**: Course completion should increase (approximately 80%)
   - ✅ **Verify**: Quiz completion counts **regardless of the score**

4. **Take Second Quiz**
   - Complete the second quiz
   - Submit it with any score
   - Go to your progress page
   - ✅ **Verify**: Course completion should now be **100%**
   - ✅ **Verify**: All lessons completed + All quizzes taken = 100% completion

5. **Test with No Quizzes**
   - Create a new course with only lessons (no quizzes)
   - Complete all lessons
   - ✅ **Verify**: Course completion should be 100%

6. **Test with Only Quizzes**
   - Create a new course with only quizzes (no lessons)
   - Take all quizzes
   - ✅ **Verify**: Course completion should be 100%

## Feature 3: No Pass Grade Requirement

### Testing Quiz Score Independence

1. **Take Quiz with Failing Score**
   - Take a quiz and intentionally answer all questions wrong
   - Submit the quiz with 0% score
   - ✅ **Verify**: The quiz is counted as completed
   - ✅ **Verify**: Course completion percentage increases

2. **Take Quiz with Passing Score**
   - Take another quiz and answer all questions correctly
   - Submit the quiz with 100% score
   - ✅ **Verify**: The quiz is counted as completed (same as failing quiz)
   - ✅ **Verify**: Course completion percentage increases by the same amount

3. **Check Progress Report**
   - Go to **IELTS Courses > Progress Reports** (as admin)
   - ✅ **Verify**: All submitted quizzes are counted in the progress
   - ✅ **Verify**: No distinction between passing and failing scores for completion

## Expected Results Summary

✅ **Settings Page**: New settings page with data deletion control  
✅ **Default Behavior**: Data is preserved on uninstall by default  
✅ **Optional Deletion**: User can opt-in to delete all data  
✅ **Quiz Requirement**: Course cannot reach 100% without taking all quizzes  
✅ **No Pass Grade**: Quiz completion counts regardless of score  
✅ **Progress Calculation**: Includes both lessons and quizzes  

## Edge Cases to Test

### Multiple Quiz Attempts
1. Take the same quiz multiple times
2. ✅ **Verify**: Only one completion is counted (not multiple)
3. ✅ **Verify**: Course completion remains at expected percentage

### Course with Mixed Content
1. Create a course with 5 lessons and 3 quizzes
2. Complete only 4 lessons and 2 quizzes
3. ✅ **Verify**: Completion is (4+2)/(5+3) = 75%

### Empty Course
1. Create a course with no lessons or quizzes
2. ✅ **Verify**: Completion shows 0%
3. ✅ **Verify**: No errors occur

## Troubleshooting

### Settings not saving
- Check that you have admin permissions
- Check browser console for JavaScript errors
- Verify the nonce is valid

### Completion percentage incorrect
- Verify all lessons and quizzes are published
- Check that content is properly assigned to the course
- Refresh the progress page

### Data not preserved after uninstall
- Verify the setting was saved correctly before uninstalling
- Check that the option is stored in wp_options table

## Report Issues

If you encounter any issues, please provide:
1. WordPress version
2. PHP version
3. Steps to reproduce
4. Expected vs actual behavior
5. Screenshots if applicable
