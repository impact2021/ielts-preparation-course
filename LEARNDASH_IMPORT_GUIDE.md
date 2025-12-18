# LearnDash to IELTS Course Manager Import Guide

This guide will help you migrate your content from LearnDash to IELTS Course Manager, especially when dealing with large-scale migrations (25+ courses with hundreds of lessons).

## Overview

The IELTS Course Manager includes a built-in import tool that converts LearnDash XML exports into IELTS Course Manager content. This tool automatically maps:

- **LearnDash Courses** → IELTS Courses
- **LearnDash Lessons** → IELTS Lessons
- **LearnDash Topics** → IELTS Lesson pages
- **LearnDash Quizzes** → IELTS Quizzes

## Quick Start

### For Small Imports (1-5 Courses)

1. **Export from LearnDash**
   - Go to Tools > Export in your LearnDash site
   - Select: Courses, Lessons, Topics, Quizzes, **and Questions** (sfwd-question)
   - **Important:** WordPress's native export tool requires you to select ALL these content types together in a single export to preserve their relationships
   - Download the XML file

2. **Convert XML Format (NEW in v1.15)**
   - **REQUIRED STEP:** LearnDash exports questions as `sfwd-question` post type
   - IELTS Course Manager uses `ielts_quiz` post type for exercises
   - Run the conversion script before importing:
   ```bash
   cd /path/to/ielts-preparation-course
   php convert-xml.php
   ```
   - This converts all `sfwd-question` items to `ielts_quiz` format
   - The script creates a converted file automatically
   - See [XML_CONVERSION_README.md](XML_CONVERSION_README.md) for detailed instructions

3. **Import to IELTS Course Manager**
   - Go to IELTS Courses > Import from LearnDash
   - Upload your **converted** XML file (not the original)
   - Click "Import XML File"
   - Review the results

### Exporting from IELTS Course Manager

**NEW:** If you need to export content FROM IELTS Course Manager (for backup, migration, or sharing):

1. **Navigate to Export Page**
   - Go to IELTS Courses > Export to XML
   
2. **Select Content Types**
   - Choose which content types to export (Courses, Lessons, Lesson Pages, Quizzes)
   - All types are selected by default to preserve relationships
   
3. **Generate Export**
   - Click "Generate XML Export File"
   - Save the downloaded XML file
   
4. **Use Exported File**
   - Import the XML on another IELTS Course Manager site using the Import tool
   - All relationships between courses, lessons, and resources are preserved

## XML Conversion Process (v1.15+)

**IMPORTANT:** LearnDash exports quiz questions as `sfwd-question` post type, but IELTS Course Manager uses `ielts_quiz` for exercises. You must convert the XML format before importing.

### Why Conversion is Needed

- LearnDash uses: `sfwd-question` post type
- IELTS CM uses: `ielts_quiz` post type
- Direct import of LearnDash XML will fail to create exercises properly
- The conversion updates post types, URLs, and GUIDs automatically

### Using the Conversion Script

1. **Locate the Script**
   - The `convert-xml.php` script is in the plugin root directory
   - Works with any LearnDash XML export file

2. **Run the Conversion**
   ```bash
   # Navigate to plugin directory
   cd /path/to/ielts-preparation-course
   
   # Run conversion (it will process ieltstestonline.WordPress.YYYY-MM-DD.xml)
   php convert-xml.php
   ```

3. **What It Does**
   - Reads your LearnDash XML export
   - Converts all `sfwd-question` items to `ielts_quiz`
   - Updates URLs from `/sfwd-question/` to `/ielts-quiz/`
   - Updates GUIDs to match new structure
   - Creates backup of original file (`*-original.xml`)
   - Generates converted file ready for import
   - Processes 4,500+ questions in under 2 minutes

4. **Verification**
   ```bash
   # Check conversion was successful
   grep -c "<wp:post_type>ielts_quiz</wp:post_type>" your-converted-file.xml
   
   # Should return the number of questions (e.g., 4547)
   ```

### Output Files

After running the script:
- `yourfile.xml` - **USE THIS** for import (converted version)
- `yourfile-original.xml` - Backup (automatically excluded from git)

### Custom File Names

To convert a different XML file, edit `convert-xml.php`:

```php
// Change these lines at the top of the file
$input_file = 'your-learndash-export.xml';
$output_file = 'your-learndash-export-converted.xml';
```

Then run:
```bash
php convert-xml.php
```

### Troubleshooting Conversion

**Problem:** Script shows "File not found"
- Solution: Make sure XML file is in the same directory as the script
- Or update `$input_file` path in the script

**Problem:** Script runs but shows 0 conversions
- Solution: Your XML might not contain questions
- Check: `grep -c "sfwd-question" yourfile.xml` should be > 0

**Problem:** Out of memory
- Solution: Increase PHP memory limit
- Edit php.ini: `memory_limit = 512M`

For detailed conversion documentation, see [XML_CONVERSION_README.md](XML_CONVERSION_README.md)

### For Large Imports (25+ Courses, Hundreds of Lessons)

For large-scale migrations, you'll need to take a more strategic approach to avoid timeouts and memory issues.

## Recommended Approach for Large Migrations

### Option 1: Batch Export/Import (Recommended)

This is the safest and most reliable method for large migrations.

#### Step 1: Split Your Export

Instead of exporting everything at once, export in manageable batches:

1. **Export by Course Group (5-10 courses at a time)**
   ```
   Batch 1: Courses 1-5
   Batch 2: Courses 6-10
   Batch 3: Courses 11-15
   etc.
   ```

2. **In LearnDash WordPress Admin:**
   - Go to Tools > Export
   - Select "Posts" as content type
   - Choose specific LearnDash post types:
     - ☑ Courses (sfwd-courses)
     - ☑ Lessons (sfwd-lessons)
     - ☑ Topics (sfwd-topic)
     - ☑ Quizzes (sfwd-quiz)
     - ☑ **Questions (sfwd-question)** ← Don't forget!
   - For each batch, manually note which courses you want
   - Download separate XML files for each batch

3. **Convert Each XML File**
   - Before importing, convert each XML file using the conversion script
   - Update `$input_file` in `convert-xml.php` for each batch
   - Run: `php convert-xml.php`
   - This ensures all questions are in the correct format

#### Step 2: Prepare Your Environment

Before importing, optimize your WordPress installation:

1. **Increase PHP Limits** (in wp-config.php or php.ini):
   ```php
   define('WP_MEMORY_LIMIT', '256M');
   define('WP_MAX_MEMORY_LIMIT', '512M');
   ini_set('max_execution_time', 300); // 5 minutes
   ```

2. **Backup Your Database**
   ```bash
   # Via command line
   wp db export backup-before-import.sql
   
   # Or use a plugin like UpdraftPlus
   ```

3. **Disable Unnecessary Plugins** temporarily during import to free up resources

#### Step 3: Import Each Batch

1. Navigate to **IELTS Courses > Import from LearnDash**
2. Upload your first batch XML file
3. Check "Skip items with duplicate titles" to avoid re-importing
4. Click "Import XML File"
5. Wait for completion and review the log
6. Repeat for each batch

### Option 2: Command Line Import (Advanced)

For very large imports, you can use WP-CLI for better performance and no timeout issues.

#### Step 1: Install WP-CLI

```bash
# If not already installed
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
```

#### Step 2: Create a Custom Import Script

Create a file `learndash-import.php` in your WordPress root:

```php
<?php
/**
 * WP-CLI command for LearnDash import
 * Usage: wp eval-file learndash-import.php /path/to/export.xml
 */

if (!defined('WP_CLI') || !WP_CLI) {
    die('This script must be run via WP-CLI');
}

$xml_file = $args[0] ?? null;

if (!$xml_file || !file_exists($xml_file)) {
    WP_CLI::error('Please provide a valid XML file path');
}

require_once ABSPATH . 'wp-content/plugins/ielts-course-manager/includes/class-learndash-importer.php';

$importer = new IELTS_CM_LearnDash_Importer();
$results = $importer->import_xml($xml_file, array('skip_duplicates' => true));

WP_CLI::success('Import completed!');
WP_CLI::line('Courses: ' . $results['courses']);
WP_CLI::line('Lessons: ' . $results['lessons']);
WP_CLI::line('Lesson pages: ' . $results['topics']);
WP_CLI::line('Quizzes: ' . $results['quizzes']);

// Display any errors or warnings
foreach ($results['log'] as $log_entry) {
    if ($log_entry['level'] === 'error') {
        WP_CLI::warning($log_entry['message']);
    }
}
```

#### Step 3: Run the Import

```bash
# Import a single file
wp eval-file learndash-import.php /path/to/export-batch-1.xml

# Import multiple files in sequence
for file in /path/to/exports/*.xml; do
    wp eval-file learndash-import.php "$file"
done
```

### Option 3: Direct Database Import (Expert Level)

For experts comfortable with database operations, you can manually import the WordPress XML using native WordPress tools, then run a custom script to fix the relationships.

**Note:** This method is not recommended unless you have database expertise.

## Best Practices

### Before Starting

1. **✓ Backup Everything**
   - Database backup
   - File system backup
   - LearnDash export files

2. **✓ Test First**
   - Import 1-2 courses on a staging site
   - Verify content structure
   - Check course-lesson relationships
   - Test quiz functionality

3. **✓ Clean Up LearnDash Data**
   - Remove draft/trash content before export
   - Fix any broken course structures
   - Verify all relationships are correct

### During Import

1. **Monitor Progress**
   - Watch the import log for warnings/errors
   - Check memory usage
   - Note any skipped items

2. **Import Order**
   - Import courses first (if splitting by content type)
   - Then lessons
   - Then topics and quizzes
   - This maintains relationships

3. **Batch Size**
   - 5-10 courses per batch is optimal
   - Reduce batch size if you hit memory limits
   - Increase if imports complete quickly

### After Import

1. **✓ Verify Content**
   - Check course count: `wp post list --post_type=ielts_course --format=count`
   - Check lesson count: `wp post list --post_type=ielts_lesson --format=count`
   - Spot-check random courses for completeness

2. **✓ Review Relationships**
   - Open several courses in admin
   - Verify lessons appear in Course Lessons meta box
   - Check lesson pages are attached to lessons
   - Verify quizzes are linked correctly

3. **✓ Test Quizzes**
   - LearnDash quiz questions need manual review
   - IELTS CM uses different quiz formats
   - Verify question types were mapped correctly
   - Test taking quizzes on the frontend

4. **✓ Update Permalinks**
   - Go to Settings > Permalinks
   - Click "Save Changes" to flush rewrite rules

5. **✓ Clear Caches**
   - Clear WordPress object cache
   - Clear any CDN/page caches
   - Clear browser cache

## Troubleshooting

### Broken Relationships After Import

**Problem:** Courses, lessons, and resources are imported but their links/relationships are broken. Lessons don't appear in courses, or lesson pages don't appear in lessons.

**Cause:** WordPress's native export tool cannot export multiple post types (courses, lessons, topics, quizzes) together in a single XML file unless you select them all at once. If you exported them separately (e.g., courses in one file, lessons in another), the relationships between them are lost.

**Solutions:**
1. **Best Solution:** Re-export from the source site with ALL content types selected together:
   - Go to Tools > Export
   - Select "All content" or manually check ALL post types: Courses, Lessons, Topics, Quizzes
   - Download the single XML file
   - Import this comprehensive file into IELTS Course Manager
   
2. **Alternative (for IELTS Course Manager sites):** Use the built-in Export to XML feature:
   - Go to IELTS Courses > Export to XML
   - Select all content types
   - Download the XML file
   - This ensures all relationships are preserved in the export

### Import Timeouts

**Problem:** Import stops midway or shows "Maximum execution time exceeded"

**Solutions:**
1. Reduce batch size (try 3-5 courses instead of 10)
2. Increase PHP limits (see above)
3. Use WP-CLI instead
4. Contact your hosting provider to temporarily increase limits

### Memory Exhausted Errors

**Problem:** "Allowed memory size of X bytes exhausted"

**Solutions:**
1. Increase WP_MEMORY_LIMIT to 256M or 512M
2. Reduce batch size
3. Disable unused plugins during import
4. Use WP-CLI which has better memory management

### Duplicate Content

**Problem:** Same courses imported multiple times

**Solutions:**
1. Always check "Skip items with duplicate titles"
2. Delete duplicates manually before re-importing
3. Use WordPress bulk actions to delete multiple posts at once

### Missing Relationships

**Problem:** Lessons not showing in courses, or lesson pages not showing in lessons

**Solutions:**
1. Check the import log for warnings
2. Verify relationships in post meta:
   ```bash
   wp post meta get [lesson-id] _ielts_cm_course_ids
   ```
3. Re-run the import with the same file (will update relationships)

### Quiz Questions Not Working

**Problem:** Quizzes imported but questions are broken

**Solutions:**
1. LearnDash and IELTS CM use different quiz systems
2. Quiz questions need manual review after import
3. Check the quiz metadata:
   ```bash
   wp post meta get [quiz-id] _ielts_cm_questions
   ```
4. Manually recreate quiz questions in IELTS CM format

## Migration Checklist

Use this checklist to ensure a smooth migration:

### Pre-Migration
- [ ] Backup LearnDash site database
- [ ] Backup IELTS CM site database
- [ ] Export all LearnDash content to XML
- [ ] Split export into batches (if needed)
- [ ] Set up staging site for testing
- [ ] Increase PHP limits
- [ ] Test import on staging with 1-2 courses

### Migration
- [ ] Disable unnecessary plugins
- [ ] Import batch 1
- [ ] Verify batch 1 results
- [ ] Import remaining batches
- [ ] Monitor for errors/warnings

### Post-Migration
- [ ] Verify course count matches
- [ ] Verify lesson count matches
- [ ] Check course-lesson relationships
- [ ] Check lesson-page relationships
- [ ] Review and fix quiz questions
- [ ] Update permalinks
- [ ] Clear all caches
- [ ] Test enrollment workflow
- [ ] Test progress tracking
- [ ] Test quiz submission
- [ ] Re-enable plugins
- [ ] Train content managers on new system

## FAQ

**Q: Will user progress be imported?**
A: No, user progress and enrollment data is not imported. Students will need to re-enroll in courses.

**Q: What about course access settings?**
A: Course access restrictions are not imported. You'll need to reconfigure these in IELTS Course Manager.

**Q: Can I re-import if something goes wrong?**
A: Yes, but you should delete the previously imported content first to avoid duplicates. Use the "Skip items with duplicate titles" option to prevent duplicates.

**Q: How long does a typical import take?**
A: Small imports (1-5 courses): 1-5 minutes
Medium imports (10-15 courses): 5-15 minutes
Large imports (25+ courses): 30-60 minutes (batched)

**Q: What if I only want to import specific courses?**
A: You can manually edit the XML file to remove unwanted courses, or use the WordPress export tool to select specific content.

**Q: Do I need to keep LearnDash installed?**
A: No, once the migration is complete and verified, you can deactivate and remove LearnDash.

## Support

If you encounter issues during migration:

1. Check the import log for specific errors
2. Review this guide's troubleshooting section
3. Post issues on GitHub: https://github.com/impact2021/ielts-preparation-course/issues
4. Include:
   - Error messages
   - Import log
   - Batch size attempted
   - PHP memory/execution limits
   - Number of courses/lessons being imported

## Additional Resources

- [WordPress XML Export Documentation](https://wordpress.org/support/article/tools-export-screen/)
- [WP-CLI Commands](https://developer.wordpress.org/cli/commands/)
- [IELTS Course Manager Documentation](PLUGIN_README.md)
