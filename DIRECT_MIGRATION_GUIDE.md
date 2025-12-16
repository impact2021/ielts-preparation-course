# Direct LearnDash Migration Guide

This guide explains how to use the Direct Migration tool to migrate content from LearnDash to IELTS Course Manager when both plugins are installed on the same WordPress site.

## Overview

The Direct Migration tool provides a streamlined way to migrate all your LearnDash content without dealing with XML exports and imports. It reads directly from LearnDash's database and creates corresponding IELTS Course Manager content.

## Prerequisites

- WordPress site with both LearnDash and IELTS Course Manager plugins installed and activated
- Admin access to the WordPress dashboard
- Database backup (highly recommended)

## What Gets Migrated

### Content Types
- ✅ **Courses** (sfwd-courses → ielts_course)
- ✅ **Lessons** (sfwd-lessons → ielts_lesson)
- ✅ **Topics** (sfwd-topic → ielts_resource / lesson pages)
- ✅ **Quizzes** (sfwd-quiz → ielts_quiz)
- ✅ **Questions** (sfwd-question → internal quiz questions)

### What's Preserved
- Post titles and content
- Featured images
- Publication status (published/draft)
- Author information
- Post dates
- Course/lesson/quiz relationships
- Question bank associations
- Categories and taxonomies

### What's NOT Migrated
- User progress and completion data
- Enrollment records
- Quiz submission history
- Certificate data
- Access restrictions and drip-feed settings
- LearnDash-specific settings

## Step-by-Step Migration Process

### 1. Preparation

**Backup Your Database**
```bash
# Using WP-CLI
wp db export backup-before-migration.sql

# Or use a plugin like UpdraftPlus
```

**Verify Content Counts**
- Go to **IELTS Courses > Direct Migration**
- Review the content counts displayed
- Note down the numbers for verification later

### 2. Run the Migration

1. Navigate to **IELTS Courses > Direct Migration** in WordPress admin
2. Review the LearnDash content summary
3. Configure migration options:
   - ☑ **Skip items with duplicate titles** (recommended) - Prevents re-importing if you run migration multiple times
   - ☐ **Include draft content** - Check this if you want to migrate draft courses/lessons
4. Click **"Start Migration Now"**
5. Wait for the process to complete (may take several minutes for large sites)

**Migration Time Estimates:**
- Small (1-5 courses): 1-2 minutes
- Medium (10-25 courses): 5-10 minutes  
- Large (50+ courses): 15-30 minutes

### 3. Verify Migrated Content

After migration completes, verify the following:

**Check Post Counts**
- Go to **IELTS Courses** and verify course count matches
- Go to **Lessons** and verify lesson count matches
- Go to **Lesson Pages** and verify topic count matches
- Go to **Quizzes** and verify quiz count matches

**Verify Relationships**
1. Open a migrated course
2. Check that lessons are properly linked (in Course Lessons meta box)
3. Open a lesson and verify lesson pages are attached
4. Open a quiz and verify questions are present

**Test Quizzes**
1. View a quiz on the frontend
2. Verify questions display correctly
3. Submit a test quiz to ensure scoring works
4. Check for any Matrix Sorting or Sorting questions (these will be Essay type with notes)

**Check for Warnings**
- Review the migration log for any warnings or errors
- Pay special attention to Matrix Sorting questions that need manual review

### 4. Handle Special Cases

**Matrix Sorting Questions**
These are converted to Essay type because IELTS Course Manager doesn't have a sorting question type. They will include:
- Original question text
- Note: "[NOTE: This was a Matrix Sorting question in LearnDash. Manual grading required.]"
- Expected answer elements with order

**Action Required:** Review these questions and either:
- Keep as Essay type with manual grading
- Recreate as multiple Multiple Choice questions
- Provide alternative assessment method

**Missing Relationships**
If some lessons aren't linked to courses:
1. Check the migration log for warnings
2. Manually edit the lesson and assign to course(s)
3. Use the Course Lessons meta box in course editor

### 5. Clean Up Original Content

**Only after you've verified everything:**

1. **Delete LearnDash Posts**
   ```bash
   # Using WP-CLI (bulk delete)
   wp post delete $(wp post list --post_type=sfwd-courses --format=ids) --force
   wp post delete $(wp post list --post_type=sfwd-lessons --format=ids) --force
   wp post delete $(wp post list --post_type=sfwd-topic --format=ids) --force
   wp post delete $(wp post list --post_type=sfwd-quiz --format=ids) --force
   wp post delete $(wp post list --post_type=sfwd-question --format=ids) --force
   ```
   
   Or manually via WordPress admin:
   - Go to each LearnDash post type
   - Select all posts
   - Bulk Actions > Move to Trash
   - Empty trash

2. **Deactivate LearnDash**
   - Go to **Plugins**
   - Deactivate LearnDash LMS
   - Optionally delete the plugin

3. **Update Permalinks**
   - Go to **Settings > Permalinks**
   - Click **Save Changes** to flush rewrite rules

## Troubleshooting

### Migration Fails or Times Out

**Problem:** Migration stops midway or shows timeout error

**Solutions:**
1. Increase PHP limits in `wp-config.php`:
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   @ini_set('max_execution_time', '600');
   ```

2. Disable unnecessary plugins temporarily during migration

3. If you have many courses (50+), consider using WP-CLI:
   ```bash
   # Increase limits and run migration programmatically
   ```

### LearnDash Not Detected

**Problem:** Migration page says "LearnDash Not Detected"

**Solutions:**
1. Verify LearnDash is activated in **Plugins**
2. Check for LearnDash updates
3. Clear any caching plugins

### Missing Questions in Quizzes

**Problem:** Quizzes imported but no questions

**Solutions:**
1. Check if questions were in LearnDash question bank
2. Verify questions are published (not drafts)
3. Check migration log for warnings about specific quizzes
4. Re-run migration with "Skip duplicates" enabled (won't duplicate existing content)

### Duplicate Content

**Problem:** Content was imported twice

**Solutions:**
1. Delete duplicate IELTS CM posts (keep originals)
2. Re-run migration with "Skip duplicates" option enabled
3. Use bulk delete in WordPress admin

## Best Practices

### Before Migration
- ☑ Backup database
- ☑ Test on staging site first if possible
- ☑ Document any custom LearnDash configurations
- ☑ Note courses with special access restrictions
- ☑ List users who need re-enrollment

### During Migration  
- ☑ Don't navigate away from migration page
- ☑ Don't deactivate plugins during migration
- ☑ Monitor for error messages
- ☑ Keep migration log for reference

### After Migration
- ☑ Test all quizzes thoroughly
- ☑ Verify course navigation
- ☑ Test student enrollment process
- ☑ Check progress tracking
- ☑ Re-enroll active students
- ☑ Communicate changes to instructors/students

## Re-running Migration

If you need to run migration again:

1. The "Skip duplicates" option will prevent re-importing existing content
2. Only new LearnDash content will be migrated
3. Original ID mappings are stored in meta: `_ld_original_id`
4. Relationships are re-calculated each time

## Comparison: Direct Migration vs XML Import

| Feature | Direct Migration | XML Import |
|---------|-----------------|------------|
| Setup | Both plugins on same site | Export from one site, import to another |
| Speed | ⚡ Fast | Slower (XML parsing) |
| Reliability | ✅ Very reliable | May have entity encoding issues |
| Question Bank | ✅ Full support | ✅ Full support |
| Relationships | ✅ Direct from DB | Via XML meta |
| Verification | ✅ Immediate | After import |
| Complexity | Simple | More steps |
| Use Case | Same site migration | Cross-site migration |

**Recommendation:** Use Direct Migration when possible (both plugins on same site). Use XML Import only when migrating between different sites.

## FAQ

**Q: Will this delete my LearnDash content?**  
A: No, the migration is non-destructive. Original LearnDash content remains untouched. You manually delete it after verification.

**Q: Can I migrate only specific courses?**  
A: Currently, the tool migrates all content. To migrate selectively, use the XML import method with filtered exports.

**Q: What happens to student progress?**  
A: Student progress, enrollments, and quiz submissions are NOT migrated. Students will need to re-enroll and start fresh.

**Q: Can I run migration multiple times?**  
A: Yes! With "Skip duplicates" enabled, it won't create duplicate content. This is useful if you have new courses in LearnDash.

**Q: Do I need to keep LearnDash after migration?**  
A: No. Once you've verified the migration and deleted original content, you can safely deactivate and remove LearnDash.

**Q: What about quiz question randomization?**  
A: IELTS Course Manager handles quiz questions differently. Review quiz settings after migration to configure question display order.

**Q: Can I migrate back to LearnDash?**  
A: This is a one-way migration. There's no automated way to migrate back. Keep your database backup if you need to revert.

## Support

If you encounter issues:

1. Check the migration log for specific error messages
2. Review this guide's troubleshooting section
3. Verify LearnDash is active and up-to-date
4. Check WordPress error logs
5. Post issues on GitHub: https://github.com/impact2021/ielts-preparation-course/issues

Include in your report:
- WordPress and PHP versions
- LearnDash version
- Number of courses/lessons being migrated
- Migration log output
- Any error messages

## Technical Details

### Database Operations
The migration performs the following:
1. Queries LearnDash post types directly
2. Creates new IELTS CM posts with `wp_insert_post()`
3. Copies post meta with proper key mapping
4. Maintains relationships via meta keys
5. Converts question bank to serialized question arrays
6. Preserves featured images and taxonomies

### Question Type Conversion
```
LearnDash          → IELTS CM
────────────────────────────────
single            → multiple_choice
multiple          → multiple_choice
free_answer       → fill_blank
fill_in_blank     → fill_blank
essay             → essay
sort_answer       → essay (manual)
matrix_sort_answer → essay (manual)
```

### Meta Key Mapping
```
LearnDash                    → IELTS CM
────────────────────────────────────────────
course_id                   → _ielts_cm_course_id
lesson_id                   → _ielts_cm_lesson_id
ld_quiz_questions           → _ielts_cm_questions (converted)
quiz_pass_percentage        → _ielts_cm_pass_percentage
```

## Conclusion

The Direct Migration tool provides a reliable, efficient way to move from LearnDash to IELTS Course Manager. By following this guide and taking proper precautions, you can successfully migrate your content while maintaining all important relationships and structures.

Remember: Always backup first, test thoroughly, and verify everything before deleting original content!
