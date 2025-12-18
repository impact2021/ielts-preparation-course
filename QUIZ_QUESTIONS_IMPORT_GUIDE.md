# Quiz Questions Import Guide

## Problem: Exercises Created But No Questions

If you've imported LearnDash content and your quizzes (exercises) were created but have no questions, this guide will help you fix the issue.

## Understanding the Issue

When importing from LearnDash XML exports, quiz questions are stored as separate items (`sfwd-question` post type) that need to be:
1. Included in the XML export
2. Properly linked to their parent quizzes
3. Converted to IELTS Course Manager format

## Solution: Proper XML Export

### Step 1: Export Questions with Your Content

The most common cause is that questions weren't included in the XML export. To fix this:

1. Go to **Tools > Export** in your LearnDash site
2. Select **"All content"** OR manually select these post types:
   - ☑ Courses (sfwd-courses)
   - ☑ Lessons (sfwd-lessons)
   - ☑ Topics (sfwd-topic)
   - ☑ Quizzes (sfwd-quiz)
   - **☑ Questions (sfwd-question)** ← **Critical!**
3. Download the XML file

### Step 2: Verify Questions Are In The XML

Before importing, verify your XML contains questions:

1. Open the XML file in a text editor (Notepad++, VS Code, etc.)
2. Search for `<wp:post_type>sfwd-question</wp:post_type>`
3. You should find multiple occurrences (one per question)
4. If you don't find any, go back to Step 1 and re-export

**Example of what you should see:**
```xml
<item>
    <title>Question 1 Title</title>
    <wp:post_type>sfwd-question</wp:post_type>
    <wp:postmeta>
        <wp:meta_key>quiz_id</wp:meta_key>
        <wp:meta_value>123</wp:meta_value>
    </wp:postmeta>
    <wp:postmeta>
        <wp:meta_key>_question_type</wp:meta_key>
        <wp:meta_value>single</wp:meta_value>
    </wp:postmeta>
    <!-- More meta data -->
</item>
```

### Step 3: Import With Enhanced Logging

1. Go to **IELTS Courses > Import from LearnDash**
2. Upload your corrected XML file
3. Click **"Import XML File"**
4. After import, click **"View Detailed Import Log"** in the success message
5. Look for these log entries:
   - "Converting X quiz questions" - Shows total questions found
   - "Added X questions to quiz ID: Y" - Shows questions assigned to each quiz
   - Warning messages about skipped questions (if any)

## Troubleshooting Common Issues

### Issue 1: "No questions found to convert"

**Cause:** The XML file doesn't contain `sfwd-question` post types.

**Solution:** 
1. Re-export from LearnDash, making sure to select Questions
2. Verify the XML contains `<wp:post_type>sfwd-question</wp:post_type>`
3. Re-import

### Issue 2: "Skipping question - no quiz_id found"

**Cause:** Questions in the XML don't have proper linking meta to their parent quiz.

**Possible Causes & Solutions:**

**A. Questions created before quizzes in LearnDash**
- LearnDash might not have properly saved the question-quiz relationship
- In LearnDash admin, edit each quiz and re-save it to rebuild relationships
- Re-export and import

**B. Corrupted meta data**
- The `quiz_id` meta was lost or corrupted
- You'll need to manually recreate the questions in IELTS Course Manager, OR
- Fix the XML manually (advanced users only)

**C. Questions stored in ProQuiz format**
- Some LearnDash installations use ProQuiz database tables
- These questions may not export properly via XML
- Consider using the direct converter instead (see below)

### Issue 3: Questions display but answers are wrong

**Cause:** Question format conversion issue.

**Solution:**
- Edit the quiz in WordPress admin
- Review each question's options and correct answer
- The importer converts:
  - Single/Multiple choice → Multiple choice (options as newline-separated text)
  - Fill in the blank → Fill blank (single correct answer)
  - Essay → Essay (open-ended)

### Issue 4: "Quiz already exists - skipping"

**Cause:** You ran the import twice and the skip duplicates option was checked.

**Solution:**
- If you want to re-import and add questions:
  1. Delete the imported quizzes (IELTS Courses > Exercises)
  2. Or manually edit each quiz and add questions using the quiz editor
  3. Or re-import with "Skip items with duplicate titles" unchecked

## Alternative: Use Direct Conversion (Recommended)

If you have both LearnDash and IELTS Course Manager installed on the **same site**, use the direct converter instead of XML export/import:

### Advantages
- Reads questions directly from database (including ProQuiz)
- No XML export needed
- Better question format conversion
- Real-time progress monitoring

### How to Use
1. Go to **IELTS Courses > Convert from LearnDash**
2. Select the courses you want to convert
3. Click **"Convert Selected Courses"**
4. Questions are automatically extracted and converted

See [LEARNDASH_CONVERSION_GUIDE.md](LEARNDASH_CONVERSION_GUIDE.md) for details.

## Checking Question Import Success

After import, verify questions were added:

### Method 1: Via WordPress Admin
1. Go to **IELTS Courses > Exercises**
2. Click "Edit" on any quiz
3. Scroll to the **"Quiz Questions"** meta box
4. You should see the imported questions listed

### Method 2: Via Frontend
1. Navigate to a course page
2. Click on a lesson
3. Start a quiz
4. Questions should display with all options

### Method 3: Via Database (Advanced)
```sql
-- Check question count for a specific quiz
SELECT meta_value FROM wp_postmeta 
WHERE post_id = YOUR_QUIZ_ID 
AND meta_key = '_ielts_cm_questions';
```

## Import Log Interpretation

The import log provides detailed information:

### Success Messages (Green ✓)
```
Converting 150 quiz questions
Added 5 questions to quiz ID: 123
```
This means questions were successfully processed.

### Warning Messages (Yellow ⚠️)
```
Skipping question 'Question Title' - no quiz_id found in meta
Warning: Quiz 'Quiz Title' (ID: 456) has no questions
```
These indicate issues that need attention.

### Error Messages (Red ❌)
```
Failed to convert question: Question Title
```
These indicate serious issues with question data.

## Manual Question Re-creation (Last Resort)

If automatic import fails completely, you can manually recreate questions:

1. Keep your original LearnDash site accessible
2. For each quiz in IELTS Course Manager:
   - Click Edit
   - In "Quiz Questions" meta box, click "Add Question"
   - Copy question text and options from LearnDash
   - Set correct answer and points
   - Save

While tedious, this ensures perfect accuracy.

## XML Structure Reference

For advanced users who want to manually fix XML files, here's the expected structure:

### Quiz Item
```xml
<item>
    <title>Quiz Title</title>
    <wp:post_id>100</wp:post_id>
    <wp:post_type>sfwd-quiz</wp:post_type>
    <wp:postmeta>
        <wp:meta_key>course_id</wp:meta_key>
        <wp:meta_value>50</wp:meta_value>
    </wp:postmeta>
</item>
```

### Question Item
```xml
<item>
    <title>Question Title</title>
    <content:encoded><![CDATA[Question text goes here]]></content:encoded>
    <wp:post_id>101</wp:post_id>
    <wp:post_type>sfwd-question</wp:post_type>
    <wp:postmeta>
        <wp:meta_key>quiz_id</wp:meta_key>
        <wp:meta_value>100</wp:meta_value>  <!-- Must match quiz post_id -->
    </wp:postmeta>
    <wp:postmeta>
        <wp:meta_key>_question_type</wp:meta_key>
        <wp:meta_value>single</wp:meta_value>
    </wp:postmeta>
    <wp:postmeta>
        <wp:meta_key>_question_points</wp:meta_key>
        <wp:meta_value>1</wp:meta_value>
    </wp:postmeta>
    <wp:postmeta>
        <wp:meta_key>_question_answer_data</wp:meta_key>
        <wp:meta_value><![CDATA[a:3:{i:0;a:2:{s:6:"answer";s:8:"Option A";s:7:"correct";b:1;}i:1;a:2:{s:6:"answer";s:8:"Option B";s:7:"correct";b:0;}i:2;a:2:{s:6:"answer";s:8:"Option C";s:7:"correct";b:0;}}]]></wp:meta_value>
    </wp:postmeta>
</item>
```

**Critical Fields:**
- `quiz_id` meta must match the parent quiz's `wp:post_id`
- `_question_type`: `single`, `multiple`, `fill_blank`, `essay`
- `_question_answer_data`: Serialized PHP array with answers
- `_question_points`: Points for the question (default: 1)

## Getting Help

If you're still having issues after following this guide:

1. **Check Import Log**: Look for specific error messages
2. **Verify XML Structure**: Use the reference above
3. **Try Direct Converter**: If both plugins are on same site
4. **Post an Issue**: Include:
   - Import log messages (especially warnings/errors)
   - How many quizzes vs how many questions
   - XML file size
   - LearnDash version

## Version Requirements

This enhanced question import system was introduced in:
- IELTS Course Manager v1.13 (current version with enhanced question import)
- LearnDash 3.x or 4.x (for export)
- PHP 7.4 or later
- WordPress 5.8 or later

## Summary Checklist

Before importing, verify:
- [ ] XML export includes sfwd-question post type
- [ ] Opened XML and verified `<wp:post_type>sfwd-question</wp:post_type>` exists
- [ ] Questions have `quiz_id` meta pointing to valid quiz
- [ ] Database backed up
- [ ] Sufficient PHP memory (256M+) for large imports

After importing, check:
- [ ] Import log shows "Converting X quiz questions"
- [ ] Import log shows "Added X questions to quiz ID: Y" for each quiz
- [ ] No red error messages in log
- [ ] Quizzes display questions when viewed in admin
- [ ] Questions display correctly on frontend
- [ ] Correct answers are marked properly

## Related Documentation

- [LEARNDASH_IMPORT_GUIDE.md](LEARNDASH_IMPORT_GUIDE.md) - Full import guide
- [LEARNDASH_CONVERSION_GUIDE.md](LEARNDASH_CONVERSION_GUIDE.md) - Direct conversion (recommended)
- [V1.12_IMPLEMENTATION_SUMMARY.md](V1.12_IMPLEMENTATION_SUMMARY.md) - Question display fixes
