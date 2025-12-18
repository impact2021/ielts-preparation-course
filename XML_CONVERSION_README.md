# LearnDash XML Conversion to IELTS Exercise Format

## What Was Done

The LearnDash quiz export XML file (`ieltstestonline.WordPress.2025-12-17.xml`) has been converted to match the IELTS Course Manager exercise format.

## Conversion Summary

- **Original file**: `ieltstestonline.WordPress.2025-12-17-original.xml` (backup)
- **Converted file**: `ieltstestonline.WordPress.2025-12-17.xml`
- **Items converted**: 4,547 quiz questions
- **Post type changed**: `sfwd-question` → `ielts_quiz`

## Changes Made

The conversion script (`convert-xml.php`) performed the following transformations:

1. **Post Type Conversion**
   - Changed all `<wp:post_type>sfwd-question</wp:post_type>` to `<wp:post_type>ielts_quiz</wp:post_type>`

2. **URL Updates**
   - Updated all question URLs from `/sfwd-question/` to `/ielts-quiz/`
   - Updated GUIDs to match the new URL structure

3. **Slug Sanitization**
   - Ensured all post slugs are properly formatted for WordPress

## Files

- `ieltstestonline.WordPress.2025-12-17.xml` - **USE THIS FILE** for import into IELTS Course Manager
- `ieltstestonline.WordPress.2025-12-17-original.xml` - Original backup file (do not delete)
- `convert-xml.php` - Conversion script used to transform the XML

## How to Import

1. Log into your WordPress admin panel
2. Navigate to **IELTS Courses > Import from LearnDash**
3. Upload the file: `ieltstestonline.WordPress.2025-12-17.xml`
4. Click **"Import XML File"**
5. Wait for the import process to complete
6. Review the import log for any warnings or errors

## What Gets Imported

The converted XML file contains 4,547 exercises (formerly LearnDash quiz questions) with:

- Exercise titles
- Exercise content/descriptions
- Exercise metadata (points, question types, etc.)
- Links to original quizzes (via `ld_quiz_*` meta keys)
- Original creation and modification dates
- All associated metadata

## Important Notes

1. **Metadata Preserved**: All original LearnDash metadata is preserved in the converted file, including:
   - `question_points` - Points for each question
   - `question_type` - Type of question (single, multiple, etc.)
   - `ld_quiz_*` - Links to parent quizzes
   - Custom metadata fields

2. **Relationships**: The imported exercises will need to be linked to courses/lessons after import. The IELTS Course Manager importer handles this automatically based on the metadata.

3. **Question Answers**: Answer options and correct answers are stored in the metadata and will be imported automatically.

4. **File Size**: The converted file is slightly smaller (13 MB vs 14 MB) due to the shorter post type names.

## Verification

To verify the conversion was successful, you can run:

```bash
# Count ielts_quiz post types (should be 4547)
grep -c "<wp:post_type>ielts_quiz</wp:post_type>" ieltstestonline.WordPress.2025-12-17.xml

# Verify no sfwd-question remains (should be 0)
grep -c "<wp:post_type>sfwd-question</wp:post_type>" ieltstestonline.WordPress.2025-12-17.xml
```

## Troubleshooting

If you encounter issues during import:

1. **Check File Size**: Ensure your PHP upload limit is at least 20 MB
2. **Memory Limit**: Set PHP memory limit to at least 256M
3. **Execution Time**: Set max_execution_time to at least 300 seconds
4. **Batch Import**: If import fails, consider splitting the XML into smaller batches

See `LEARNDASH_IMPORT_GUIDE.md` for detailed import instructions and troubleshooting.

## Related Documentation

- `LEARNDASH_IMPORT_GUIDE.md` - Full import guide
- `LEARNDASH_CONVERSION_GUIDE.md` - Direct database conversion (alternative method)
- `QUIZ_QUESTIONS_IMPORT_GUIDE.md` - Question import troubleshooting

## Conversion Script

The `convert-xml.php` script can be reused if you need to convert additional LearnDash exports. Simply:

1. Place your XML file in the same directory
2. Update the `$input_file` variable in the script
3. Run: `php convert-xml.php`

## Support

If you encounter issues:

1. Check the import log for specific error messages
2. Refer to the troubleshooting section in `LEARNDASH_IMPORT_GUIDE.md`
3. Post issues on GitHub: https://github.com/impact2021/ielts-preparation-course/issues

Include:
- Error messages from the import log
- Number of exercises being imported
- PHP version and memory limits
- Any relevant screenshots
