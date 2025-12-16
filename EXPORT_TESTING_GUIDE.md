# Export Feature Testing Guide

This guide describes how to manually test the new XML Export feature.

## Prerequisites

1. WordPress site with IELTS Course Manager plugin installed and activated
2. Some test content created (at least one course, lesson, lesson page, and quiz)

## Test Scenarios

### Test 1: Basic Export Functionality

**Steps:**
1. Log in to WordPress admin as administrator
2. Navigate to IELTS Courses > Export to XML
3. Verify all checkboxes are checked by default (Courses, Lessons, Lesson Pages, Quizzes)
4. Click "Generate XML Export File"
5. Verify XML file downloads to your computer

**Expected Results:**
- Export page loads without errors
- All content types are checked by default
- XML file downloads successfully
- File name format: `ielts-export-YYYY-MM-DD.xml`

### Test 2: Selective Content Export

**Steps:**
1. Navigate to IELTS Courses > Export to XML
2. Uncheck "Quizzes"
3. Click "Generate XML Export File"
4. Open the downloaded XML file in a text editor
5. Search for `<wp:post_type>ielts_quiz</wp:post_type>`

**Expected Results:**
- XML file downloads successfully
- No quiz entries found in the XML
- Courses, lessons, and lesson pages are present

### Test 3: Include Drafts Option

**Steps:**
1. Create a draft course (don't publish it)
2. Navigate to IELTS Courses > Export to XML
3. Leave "Include draft posts" unchecked
4. Export and save as `export-no-drafts.xml`
5. Go back and check "Include draft posts"
6. Export and save as `export-with-drafts.xml`
7. Compare file sizes

**Expected Results:**
- `export-with-drafts.xml` should be larger
- Draft course should only appear in `export-with-drafts.xml`

### Test 4: XML Structure Validation

**Steps:**
1. Export all content with default settings
2. Open XML file in a text editor or XML validator
3. Check for:
   - Valid XML syntax (no unclosed tags)
   - Presence of WordPress WXR namespaces
   - Post metadata (wp:postmeta) for each post
   - CDATA sections properly formatted

**Expected Results:**
- XML is well-formed (passes validation)
- Contains WXR namespaces
- Post metadata is present
- CDATA sections are properly escaped

### Test 5: Export-Import Round Trip

**Steps:**
1. Create test content:
   - 1 Course with title "Test Course Export"
   - 2 Lessons assigned to the course
   - 2 Lesson pages assigned to first lesson
   - 1 Quiz assigned to the course
2. Note down the IDs and relationships
3. Export all content
4. On a second WordPress installation with IELTS Course Manager:
   - Go to IELTS Courses > Import from LearnDash
   - Upload the exported XML
   - Import with "Skip duplicates" checked
5. Verify imported content:
   - Check course exists
   - Open course edit page and verify lessons appear in Course Lessons meta box
   - Open lesson edit page and verify lesson pages appear
   - Check quiz is assigned to course

**Expected Results:**
- All content imports successfully
- Relationships are preserved (lessons linked to course, pages linked to lessons)
- Quiz is properly linked to course
- Content matches original (titles, descriptions, metadata)

### Test 6: Large Export (if applicable)

**Steps:**
1. If you have 10+ courses with multiple lessons each:
   - Export all content
   - Note the time it takes
   - Check for any timeout or memory errors
2. If export times out:
   - Try exporting fewer content types at once
   - Check server PHP limits

**Expected Results:**
- Export completes successfully (may take several seconds)
- No timeout errors
- XML file size is reasonable (under 10MB for typical sites)

## Troubleshooting

### Export Button Does Nothing

**Possible Causes:**
- JavaScript error (check browser console)
- Form validation issue

**Solution:**
- Check browser console for errors
- Ensure at least one content type is selected

### Download Fails or Shows Blank Page

**Possible Causes:**
- PHP errors in export code
- Output buffering issues

**Solution:**
- Check PHP error logs
- Try different browser
- Check WordPress debug log (if WP_DEBUG is enabled)

### Imported Content Missing Relationships

**Possible Causes:**
- Not all content types were exported together
- Metadata not properly preserved

**Solution:**
- Re-export with ALL content types selected
- Check XML file for presence of postmeta entries

## Validation Checklist

After completing tests, verify:

- [ ] Export page loads without errors
- [ ] All content types can be exported
- [ ] XML is valid and well-formed
- [ ] Export includes post metadata
- [ ] Selective export works (can choose specific content types)
- [ ] Draft inclusion option works
- [ ] Exported XML can be imported successfully
- [ ] Relationships are preserved after import
- [ ] No data loss during export-import cycle
- [ ] Performance is acceptable for site size

## Known Limitations

1. **Media Files Not Included:** Uploaded images, videos, and attachments are not included in the XML export. You'll need to manually transfer media files or use WordPress's media export tools.

2. **User Progress Not Included:** User enrollment and progress data is not exported. This is by design as it's site-specific data.

3. **Plugin Settings Not Included:** Plugin configuration settings are not exported.

4. **Large Sites May Timeout:** Sites with hundreds of courses may need to export in batches or adjust PHP limits.
