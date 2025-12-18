# LearnDash to IELTS Course Manager Conversion Guide

This guide will help you convert your content from LearnDash to IELTS Course Manager when both plugins are installed on the same site.

## Overview

**NEW in v1.3:** IELTS Course Manager now includes a direct conversion tool that reads LearnDash courses from your database and converts them to IELTS Course Manager format. No XML export/import needed!

The conversion tool automatically maps:

- **LearnDash Courses** → IELTS Courses
- **LearnDash Lessons** → IELTS Lessons
- **LearnDash Topics** → IELTS Lesson Pages
- **LearnDash Quizzes** → IELTS Quizzes

## Quick Start

### Prerequisites

- LearnDash must be installed and active on your site
- Both LearnDash and IELTS Course Manager should be on the same WordPress installation
- You should have admin access to the site

### Converting Your Courses

1. **Navigate to Converter Page**
   - Go to **IELTS Courses > Convert from LearnDash**
   
2. **Select Courses**
   - You'll see a list of all your LearnDash courses
   - Select the courses you want to convert
   - Or use "Select All" to convert all courses at once
   
3. **Start Conversion**
   - Click the **"Convert Selected Courses"** button
   - A modal window will appear showing real-time progress
   
4. **Monitor Progress**
   - Watch as each course is converted
   - The progress bar shows how many courses are done
   - A live log displays each step of the conversion
   
5. **Review Results**
   - After completion, you'll see a summary of what was converted
   - Any errors or warnings will be displayed
   - Click "Close" to return to the converter page

## What Gets Converted

### Courses
- Course title and content
- Course excerpt/description
- Featured image
- Publication status (published/draft)
- Original creation date

### Lessons
- Lesson title and content
- Lesson excerpt
- Association with parent course
- Menu order (lesson sequence)
- Publication status

### Topics (Lesson Pages)
- Topic title and content
- Topic excerpt
- Association with parent lesson
- Menu order (page sequence)
- Publication status

### Quizzes
- Quiz title and description
- Association with course
- Pass percentage (if set)
- Menu order
- Publication status
- **Note:** Quiz questions use different formats between LearnDash and IELTS Course Manager and will need manual review after conversion

## Important Notes

### Safe to Re-run
- Already converted content is automatically detected
- If you re-run the converter, it will skip courses that have already been converted
- This makes it safe to run the converter multiple times

### Original Content Preserved
- Your LearnDash content remains completely unchanged
- The converter only creates new IELTS Course Manager content
- You can safely keep LearnDash active while testing the conversion

### Quiz Questions
- LearnDash and IELTS Course Manager use different quiz systems
- Quiz structure (title, description, pass percentage) is converted
- **Quiz questions need to be manually recreated** in IELTS Course Manager format
- After conversion, edit each quiz and add questions using the IELTS CM quiz editor

### User Progress Not Converted
- Student enrollment data is not converted
- Student progress and quiz results are not converted
- Students will need to re-enroll in the new IELTS Course Manager courses
- This is by design to ensure a clean slate with the new system

### After All Courses Are Converted
- Verify all courses, lessons, and content are present in IELTS Course Manager
- Test the course structure and navigation
- Recreate quiz questions
- Once you're satisfied, you can safely **delete LearnDash**
- Keep a backup of your database before deleting LearnDash

## Conversion Process Details

### Course Structure Preservation
The converter maintains the hierarchical structure:
```
LearnDash Course
├── Lesson 1
│   ├── Topic 1 (Page 1)
│   ├── Topic 2 (Page 2)
│   └── Topic 3 (Page 3)
├── Lesson 2
│   ├── Topic 4 (Page 1)
│   └── Topic 5 (Page 2)
└── Quiz 1

Converts to:

IELTS Course
├── Lesson 1
│   ├── Lesson Page 1
│   ├── Lesson Page 2
│   └── Lesson Page 3
├── Lesson 2
│   ├── Lesson Page 1
│   └── Lesson Page 2
└── Quiz 1 (questions need manual entry)
```

### Metadata Preserved
Each converted item stores:
- `_ld_original_id`: The original LearnDash post ID
- `_converted_from_learndash`: Timestamp of conversion

This metadata is used to:
- Prevent duplicate conversions
- Allow you to trace converted content back to the original
- Enable safe re-running of the converter

## Best Practices

### Before Converting

1. **✓ Backup Your Database**
   - Always create a full database backup before converting
   - Use your hosting panel's backup tool or a plugin like UpdraftPlus
   
2. **✓ Test on Staging First** (if possible)
   - If you have a staging site, test the conversion there first
   - Verify the results before converting on your live site
   
3. **✓ Clean Up LearnDash Content** (optional)
   - Remove draft courses you don't need
   - Delete trashed content
   - This makes the conversion cleaner

### During Conversion

1. **Start Small**
   - For your first conversion, try 1-2 courses
   - Verify the results before converting more
   - This helps you understand the process

2. **Monitor the Log**
   - Watch the conversion log for warnings or errors
   - Note any issues for follow-up

3. **Don't Close the Browser**
   - Keep the browser tab open during conversion
   - The conversion runs via AJAX and needs the connection

### After Conversion

1. **✓ Verify Course Count**
   - Go to IELTS Courses > All Courses
   - Check that the number matches your LearnDash courses
   
2. **✓ Spot-Check Content**
   - Open several courses in the WordPress admin
   - Verify lessons appear in the "Course Lessons" meta box
   - Check that lesson pages are attached to lessons
   - Verify content looks correct
   
3. **✓ Recreate Quiz Questions**
   - Edit each quiz in IELTS Course Manager
   - Add questions using the IELTS CM quiz question interface
   - Set correct answers and points
   - Test quizzes on the frontend
   
4. **✓ Test on Frontend**
   - View courses as a student would see them
   - Navigate through lessons
   - Try enrolling in a course
   - Test progress tracking
   
5. **✓ Update Permalinks**
   - Go to Settings > Permalinks
   - Click "Save Changes" (don't need to change anything)
   - This flushes WordPress rewrite rules
   
6. **✓ Clear Caches**
   - Clear any caching plugins
   - Clear CDN cache if you use one
   - Clear browser cache

## Troubleshooting

### "LearnDash Not Detected" Error

**Problem:** The converter page says LearnDash is not detected

**Solutions:**
1. Make sure LearnDash is installed and activated
2. Check that LearnDash courses exist (go to LearnDash > Courses)
3. Verify LearnDash is the correct version (should have `sfwd-courses` post type)

### No Courses Listed

**Problem:** The converter shows "No LearnDash courses found"

**Solutions:**
1. Verify you have courses in LearnDash (check LearnDash > Courses)
2. Make sure courses are not all in the trash
3. Check database directly if needed: `SELECT * FROM wp_posts WHERE post_type = 'sfwd-courses'`

### Conversion Fails or Times Out

**Problem:** Conversion stops or browser shows timeout

**Solutions:**
1. Convert fewer courses at a time (try 5-10 instead of all at once)
2. Increase PHP execution time in php.ini: `max_execution_time = 300`
3. Increase PHP memory limit: `memory_limit = 256M`
4. Check PHP error logs for specific errors

### Lessons Not Appearing in Courses

**Problem:** Courses are converted but lessons don't appear

**Solutions:**
1. Check the conversion log for errors
2. Verify LearnDash course-lesson relationships were correct
3. Manually edit the lesson and re-assign it to the course
4. Check post meta: `SELECT * FROM wp_postmeta WHERE meta_key = '_ielts_cm_course_ids'`

### Topics/Pages Not Appearing in Lessons

**Problem:** Lessons are converted but topics (lesson pages) don't appear

**Solutions:**
1. Check the conversion log for warnings
2. Verify LearnDash lesson-topic relationships were correct
3. Manually edit the lesson page and re-assign it to the lesson

### Already Converted But Need to Re-convert

**Problem:** You want to re-convert a course (maybe content was updated)

**Solutions:**
1. Delete the IELTS Course Manager course, lessons, and pages for that course
2. Re-run the converter on that course
3. Or: Manually update the converted content if changes are minor

## Comparing with XML Import/Export

### When to Use Direct Conversion (NEW - Recommended)
- ✅ LearnDash and IELTS Course Manager are on the **same site**
- ✅ You want a simple, one-click conversion process
- ✅ You want to convert courses without downloading/uploading files
- ✅ You want real-time progress monitoring
- ✅ You want to keep LearnDash active while testing

### When to Use Structure Rebuild (Alternative)
- When XML exports from LearnDash lose course structure
- When you need to rebuild course hierarchy from scratch
- See [STRUCTURE_REBUILD_GUIDE.md](STRUCTURE_REBUILD_GUIDE.md) for details

### When to Use XML Export/Import with Conversion (NEW in v1.15)
- When moving courses between **different sites**
- When backing up IELTS Course Manager content
- When you have a LearnDash XML export and need to import it
- **Important:** Must use conversion script (`convert-xml.php`) to convert `sfwd-question` to `ielts_quiz`
- See [XML_CONVERSION_README.md](XML_CONVERSION_README.md) for conversion instructions
- See [LEARNDASH_IMPORT_GUIDE.md](LEARNDASH_IMPORT_GUIDE.md) for import instructions
- See [Export to XML](edit.php?post_type=ielts_course&page=ielts-export) for exporting from IELTS CM

## Migration Timeline Example

For a site with **25 courses** and **hundreds of lessons**:

1. **Preparation (30 minutes)**
   - Backup database
   - Review LearnDash content
   - Read this guide

2. **Test Conversion (15 minutes)**
   - Convert 1-2 test courses
   - Verify results
   - Check lessons and pages

3. **Full Conversion (1-2 hours)**
   - Convert all courses (in batches if needed)
   - Monitor for errors
   - Take notes on any issues

4. **Quiz Recreation (3-4 hours)**
   - Recreate questions for each quiz
   - Test each quiz
   - Verify grading works

5. **Testing & Verification (1 hour)**
   - Test all courses on frontend
   - Verify enrollment works
   - Test progress tracking
   - Have another user test

6. **Go Live (30 minutes)**
   - Final cache clear
   - Update any documentation
   - Inform users of migration
   - Monitor for issues

**Total:** ~6-8 hours for complete migration of 25 courses

## FAQ

**Q: Will this delete my LearnDash content?**
A: No, your LearnDash content remains unchanged. The converter only creates new IELTS Course Manager content.

**Q: Can I keep LearnDash active after conversion?**
A: Yes, you can keep LearnDash active while you test and verify the converted content. Once you're satisfied, you can deactivate and delete LearnDash.

**Q: What if I need to update a course after conversion?**
A: Update the content in IELTS Course Manager, not LearnDash. The converter doesn't sync changes; it only does a one-time conversion.

**Q: Can I convert the same course twice?**
A: The converter detects already-converted courses and skips them. To re-convert, first delete the IELTS Course Manager version.

**Q: Will student progress be preserved?**
A: No, user enrollment and progress data is not converted. This ensures a clean start with the new system.

**Q: How long does conversion take?**
A: Small conversions (1-5 courses): 1-5 minutes
Medium (10-15 courses): 5-15 minutes
Large (25+ courses): 15-30 minutes

**Q: Can I convert while students are using the site?**
A: Yes, but it's better to do conversions during low-traffic times. The conversion doesn't affect existing LearnDash functionality.

**Q: What about course prerequisites or access settings?**
A: These are not converted. You'll need to reconfigure access restrictions in IELTS Course Manager if needed.

## Support

If you encounter issues during conversion:

1. Check the conversion log in the modal for specific errors
2. Review this guide's troubleshooting section
3. Post issues on GitHub: https://github.com/impact2021/ielts-preparation-course/issues
4. Include:
   - Error messages from the conversion log
   - Number of courses being converted
   - PHP version and memory limits
   - Any relevant screenshots

## Additional Resources

- [IELTS Course Manager Documentation](PLUGIN_README.md)
- [WordPress Post Types](https://wordpress.org/support/article/post-types/)
- [LearnDash Documentation](https://www.learndash.com/support/docs/)
