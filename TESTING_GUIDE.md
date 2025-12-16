# Testing Guide for Lesson Ordering Feature

This guide will help you test the new drag-and-drop lesson ordering feature and verify that duration/difficulty fields have been removed.

## Prerequisites

- WordPress 5.0 or higher
- The IELTS Course Manager plugin must be activated
- At least one course created with multiple lessons assigned to it

## Testing Steps

### 1. Verify Removed Fields

#### Course Edit Page
1. Go to **IELTS Courses > All Courses**
2. Click to edit any existing course
3. ✅ **Verify**: The "Course Settings" meta box should NOT contain:
   - Duration (hours) field
   - Difficulty Level dropdown
4. ✅ **Verify**: You should see a message: "Use the Course Lessons meta box below to manage and reorder lessons for this course."

#### Lesson Edit Page
1. Go to **IELTS Courses > Lessons**
2. Click to edit any existing lesson
3. ✅ **Verify**: The "Lesson Settings" meta box should NOT contain:
   - Duration (minutes) field
4. ✅ **Verify**: You should only see the "Assign to Courses" multi-select dropdown

### 2. Test Drag-and-Drop Lesson Ordering

#### Setup Test Data
1. Go to **IELTS Courses > All Courses**
2. Edit or create a course
3. Ensure at least 3-5 lessons are assigned to this course (create new lessons if needed)
4. In each lesson's edit page, make sure to assign it to your test course

#### Test Reordering
1. Go back to edit your course
2. Scroll down to the **Course Lessons** meta box
3. ✅ **Verify**: You should see a list of all lessons assigned to this course
4. ✅ **Verify**: Each lesson should show:
   - A drag handle icon (≡)
   - Lesson title
   - Current order number
   - "Edit" button
5. **Try to reorder**: Click and drag a lesson to a new position
6. ✅ **Verify**: As you drag, you should see:
   - The lesson becomes semi-transparent
   - A placeholder shows where the lesson will be dropped
7. **Drop the lesson** in the new position
8. ✅ **Verify**: After dropping, you should see:
   - A green success message: "Lesson order updated successfully!"
   - The order numbers update automatically
   - The message fades out after 3 seconds

### 3. Verify Frontend Display

#### Course Page
1. View the course on the frontend (use the permalink or create a page with `[ielts_course id="X"]` shortcode)
2. ✅ **Verify**: The course page should NOT display:
   - Duration
   - Difficulty level
3. ✅ **Verify**: The lessons table should display in the correct order (matching what you set in the admin)
4. ✅ **Verify**: The lessons table should show:
   - Status (if enrolled)
   - Lesson title
   - Description
   - Action button (if enrolled)
5. ✅ **Verify**: The "Duration" column should be removed

### 4. Test Edge Cases

#### No Lessons Assigned
1. Create a new course without any lessons
2. Edit the course
3. ✅ **Verify**: The "Course Lessons" meta box should show:
   - Message: "No lessons have been assigned to this course yet..."

#### Multiple Courses
1. Create a lesson and assign it to multiple courses
2. Edit each course
3. ✅ **Verify**: The lesson appears in the "Course Lessons" meta box for each course
4. ✅ **Verify**: Reordering in one course doesn't affect the order in other courses

#### Browser Compatibility
Test the drag-and-drop in:
- Chrome/Edge
- Firefox
- Safari

### 5. Console Errors

1. Open browser developer tools (F12)
2. Check the Console tab while:
   - Loading the course edit page
   - Dragging and dropping lessons
3. ✅ **Verify**: No JavaScript errors appear

## Expected Results Summary

✅ **Removed**: Duration and difficulty fields from Course Settings  
✅ **Removed**: Duration field from Lesson Settings  
✅ **Added**: Course Lessons meta box with drag-and-drop functionality  
✅ **Working**: AJAX auto-save when reordering lessons  
✅ **Updated**: Frontend templates no longer show duration/difficulty  
✅ **Updated**: Documentation reflects new workflow  

## Troubleshooting

### Drag-and-drop not working
- Check that jQuery UI Sortable is loaded (view page source, search for "jquery-ui-sortable")
- Clear browser cache
- Check browser console for JavaScript errors

### Lessons not appearing in the meta box
- Verify the lesson is published (not draft)
- Verify the lesson is assigned to the course in the Lesson Settings
- Try editing and re-saving the lesson

### AJAX save failing
- Check browser console for errors
- Verify you have permission to edit posts
- Check that WordPress AJAX is working (try another AJAX feature)

## Report Issues

If you encounter any issues, please provide:
1. WordPress version
2. PHP version
3. Browser and version
4. Screenshot of the issue
5. Any console errors
6. Steps to reproduce
