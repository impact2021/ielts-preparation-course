# IELTS Course Manager - Development Guidelines

**⚠️ READ THIS FILE BEFORE MAKING ANY CHANGES TO THE PLUGIN ⚠️**

This document consolidates ALL critical guidelines, known issues, and best practices for developing and maintaining the IELTS Course Manager WordPress plugin. Following these guidelines will prevent delays and common errors.

---

## Table of Contents

1. [Pre-Flight Checklist](#pre-flight-checklist)
2. [Critical Issues to Watch Out For](#critical-issues-to-watch-out-for)
3. [PHP Serialization Issues](#php-serialization-issues)
4. [XML Import/Export Guidelines](#xml-importexport-guidelines)
5. [Question and Feedback Requirements](#question-and-feedback-requirements)
6. [Listening Test Requirements](#listening-test-requirements)
7. [Reading Test Requirements](#reading-test-requirements)
8. [Available Tools](#available-tools)
9. [Testing and Validation](#testing-and-validation)
10. [Known Working Files](#known-working-files)
11. [Common Errors and Solutions](#common-errors-and-solutions)

---

## Pre-Flight Checklist

**Before making ANY changes to the plugin, verify:**

- [ ] You have read this entire document
- [ ] You understand the PHP serialization UTF-8 issue
- [ ] You know how to validate XML files before importing
- [ ] You understand the feedback requirements for questions
- [ ] You have access to the validation and fixing tools
- [ ] You know to test changes in a safe environment first

**Before creating or modifying ANY XML files:**

- [ ] All feedback fields are filled (correct, incorrect, no answer)
- [ ] Correct answers are clearly shown when users don't answer
- [ ] No UTF-8 special characters (en-dash, em-dash, curly quotes)
- [ ] CDATA sections have NO spaces around content
- [ ] File will be validated with `validate-xml.php` before use
- [ ] Questions are linked to correct reading passages (for reading tests)

**Before committing changes:**

- [ ] All modified XML files have been validated
- [ ] PHP serialization integrity has been verified
- [ ] Changes have been tested in WordPress
- [ ] No "No questions found" errors occur

---

## Critical Issues to Watch Out For

### 1. UTF-8 Special Characters Breaking PHP Serialization

**THE MOST COMMON CAUSE OF DELAYS AND ERRORS**

PHP serialized data uses byte counts in the format `s:LENGTH:"content"`. Multi-byte UTF-8 characters cause the byte count to be incorrect, breaking `unserialize()`.

**Characters That Will Break Everything:**

| Character | Name | UTF-8 Bytes | Code | ASCII Replacement |
|-----------|------|-------------|------|-------------------|
| – | En-dash | E2 80 93 | U+2013 | `-` (hyphen) |
| — | Em-dash | E2 80 94 | U+2014 | `--` (double hyphen) |
| ' | Left single quote | E2 80 98 | U+2018 | `'` (straight quote) |
| ' | Right single quote | E2 80 99 | U+2019 | `'` (straight quote) |
| " | Left double quote | E2 80 9C | U+201C | `"` (straight quote) |
| " | Right double quote | E2 80 9D | U+201D | `"` (straight quote) |

**Prevention:**
- ALWAYS use straight quotes and hyphens when creating content
- Run `fix-utf8-in-xml.py` if special characters slip through
- Validate XML files before importing

### 2. Incomplete Feedback Fields

**EVERY question MUST have ALL THREE feedback types:**

1. **Correct Feedback** - Shown when answer is correct
2. **Incorrect Feedback** - Shown when answer is wrong (must help locate the correct answer)
3. **No Answer Feedback** - Shown when user doesn't answer (MUST clearly show the correct answer)

**❌ WRONG - No Answer Feedback (causes confusion):**
```
"You need to answer this question. Hint: Listen for when the prison facility closed..."
```

**✅ CORRECT - No Answer Feedback (clearly shows answer):**
```
"The correct answer is: 1963. Make sure to listen carefully for key information and take notes while listening."
```

### 3. CDATA Formatting

**XML parsing is VERY sensitive to spaces in CDATA sections.**

**❌ WRONG (will cause "No questions found" error):**
```xml
<wp:meta_key>
<![CDATA[ _ielts_cm_questions ]]>
</wp:meta_key>
```

**✅ CORRECT:**
```xml
<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
```

**Rules:**
- NO spaces after `<![CDATA[`
- NO spaces before `]]>`
- Keep everything on one line when possible

### 4. Rushing Causes Errors

**You are VERY often rushing and this causes errors like "Error: No questions found in the XML file."**

**Required Actions:**
- Review EVERY XML file after making changes
- Validate EVERY XML file before importing
- Test import in WordPress before considering it complete
- Don't skip validation steps to "save time" - it wastes more time fixing errors later

### 5. PHP Serialization Byte Counts

**PHP serialization byte counts MUST match exact string lengths.**

The format `s:LENGTH:"content"` requires LENGTH to be the exact byte count (not character count) of the content.

**Tools to fix:**
- `fix-serialization-lengths.py` - Automatically recalculates all byte counts
- `fix-xml-with-php.php` - Re-serializes PHP data correctly

---

## PHP Serialization Issues

### Understanding PHP Serialization

PHP's `serialize()` creates data in this format:
```
s:10:"test string"
   ^^
   Byte count must be exact
```

### Common Problems

1. **Multi-byte characters** - UTF-8 characters use 2-3 bytes but count as 1 character
2. **Manual editing** - Changing text without updating byte counts
3. **Copy/paste** - Pasting from Word or other tools that insert special characters

### Detection

**Symptoms:**
- "No questions found in the XML file" error
- "Error at offset XXX" when importing
- Questions disappear after import

**How to detect:**
```bash
php TEMPLATES/validate-xml.php "path/to/file.xml"
```

### Resolution

**Step 1: Fix UTF-8 characters**
```bash
python3 TEMPLATES/fix-utf8-in-xml.py "input.xml" "output-fixed.xml"
```

**Step 2: Fix byte counts**
```bash
python3 TEMPLATES/fix-serialization-lengths.py "input.xml" "output-fixed.xml"
```

**Step 3: Validate**
```bash
php TEMPLATES/validate-xml.php "output-fixed.xml"
```

---

## XML Import/Export Guidelines

### Import Modes

The plugin supports two import modes:

1. **Replace All Content** - Overwrites everything (default)
2. **Add to Existing Content** - Appends questions and reading texts

### Creating XML Files

**Required Meta Fields:**
- `_ielts_cm_questions` - Serialized array of questions
- `_ielts_cm_reading_texts` - Serialized array of reading passages (for reading tests)
- `_ielts_cm_pass_percentage` - Pass percentage (e.g., 60)
- `_ielts_cm_layout_type` - Layout type (`standard`, `computer_based`, `listening_practice`, `listening_exercise`)
- `_ielts_cm_timer_minutes` - Time limit in minutes
- `_ielts_cm_scoring_type` - Scoring type (e.g., `ielts_listening_band`, `percentage`)
- `_ielts_cm_exercise_label` - Exercise label (e.g., `exercise`, `test`)

**For Listening Tests, Also Include:**
- `_ielts_cm_audio_url` - URL to MP3 audio file
- `_ielts_cm_transcript` - Audio transcript (or `_ielts_cm_audio_sections` for multi-section)
- `_ielts_cm_starting_question_number` - Starting question number (default: 1)

### Best Practices

1. **Always export a backup** before replacing content
2. **Validate before importing** - saves time and prevents errors
3. **Use append mode carefully** - ensure reading text IDs are handled correctly
4. **Test with one file first** - don't batch import untested files
5. **Keep XML files in version control** - track changes and allow rollback

### Common Import Errors

**Error:** "No questions found in the XML file"
- **Cause:** CDATA spacing, UTF-8 characters, or serialization corruption
- **Fix:** Validate and fix with tools

**Error:** "Error at offset XXX"
- **Cause:** PHP serialization byte count mismatch
- **Fix:** Run `fix-serialization-lengths.py`

**Error:** Questions reference wrong reading passages
- **Cause:** Reading text ID mismatch after append import
- **Fix:** Use replace mode or manually verify IDs

---

## Question and Feedback Requirements

### Mandatory Feedback for Every Question

**All three feedback types are REQUIRED:**

1. **Correct Feedback**
   - Congratulate the student
   - Optionally explain why the answer is correct
   - Reference the source text/audio

2. **Incorrect Feedback**
   - Help the student find the correct answer
   - Reference specific paragraphs or audio timestamps
   - Explain common mistakes

3. **No Answer Feedback**
   - **MUST clearly state the correct answer**
   - Include the format: "The correct answer is: [answer]"
   - Add a learning tip or encouragement
   - **Do NOT just hint** - students need to see the actual answer

### Example Feedback Set

```
Correct: "Correct! The passage states that the island became a natural park in 1963."

Incorrect: "Not quite. Look in paragraph 3 where the history of the island is discussed. Pay attention to the dates mentioned."

No Answer: "The correct answer is: 1963. Make sure to read the entire passage carefully and take notes of key dates and facts."
```

### Answer Display Requirements

When a student doesn't answer a question:
- The correct answer MUST be clearly visible
- Not just a hint about where to find it
- Format: "The correct answer is: [answer]"
- This helps students learn what they missed

### Question Type Specific Notes

**Summary Completion:**
- Each field in `summary_fields` is a separate question
- Provide feedback for each field
- Support multiple acceptable answers with `|` separator (e.g., "4|four")

**Matching Questions:**
- Clearly explain the matching logic in feedback
- Reference both the question and answer in explanations

**Multiple Choice:**
- Explain why wrong options are incorrect
- Reference specific text that supports the correct answer

---

## Listening Test Requirements

### Audio Requirements

**One singular audio file per test section:**
- Format: MP3 (for widest compatibility)
- Quality: Minimum 128kbps
- Hosting: Reliable CDN or server
- Size: Keep under 10MB when possible

### Transcript Requirements

**All listening tests MUST have transcripts:**
- Include speaker labels
- Use proper formatting (HTML tables for dialog)
- **Transcripts are automatically annotated** with answer markers during XML import
- Annotation format: `<strong>[Q1: answer]</strong>`

**For multi-section tests:**
- Use `_ielts_cm_audio_sections` array
- Each section has `section_number` and `transcript`
- Same audio file is used for all sections
- Transcripts appear in tabs after submission

### Layout Types

1. **Listening Practice Test** (`listening_practice`)
   - No audio controls during test (simulates real IELTS)
   - "Enable Audio" button to start
   - Progress bar shows playback position
   - Transcript revealed after submission with audio controls

2. **Listening Exercise** (`listening_exercise`)
   - Audio controls visible (for practice)
   - Warning message about real IELTS conditions
   - Transcript revealed after submission

### Automatic Transcript Annotation

**During XML import, transcripts are automatically annotated:**
- System finds each answer in the transcript
- Marks it with `<strong>[QX: answer]</strong>`
- Handles multiple acceptable answers (e.g., "4|four")
- Case-insensitive matching
- Only marks first occurrence

**This feature:**
- Saves manual annotation time
- Helps students find answers when reviewing
- Provides quality check that answers exist in transcript

### Starting Question Numbers

For multi-section tests:
- Section 1: Questions 1-10 (starting_question_number: 1)
- Section 2: Questions 11-20 (starting_question_number: 11)
- Section 3: Questions 21-30 (starting_question_number: 21)
- Section 4: Questions 31-40 (starting_question_number: 31)

Set `_ielts_cm_starting_question_number` appropriately for each section.

### Scoring

Use `ielts_listening_band` scoring type for authentic IELTS band score calculation.

---

## Reading Test Requirements

### Reading Text Structure

**Each reading passage must include:**
- Title
- Full text content
- Proper paragraph formatting
- Source citation (optional)

**Reading texts are stored as array indices:**
- Index 0 = First passage
- Index 1 = Second passage
- Index 2 = Third passage

### Question-Passage Linking

**CRITICAL: Ensure questions are linked to the correct reading passage**

Each question has a `reading_text_id` field:
- `0` = First passage
- `1` = Second passage
- `2` = Third passage

**Common error:** Questions referencing wrong passage after append import
**Prevention:** Verify reading_text_id values match intended passages

### Full Test Structure

**Standard IELTS Reading Test:**
- 3 passages (increasing difficulty)
- 40 questions total
- 60 minutes
- Questions 1-13 (Passage 1)
- Questions 14-26 (Passage 2)
- Questions 27-40 (Passage 3)

### Question Type Distribution

**Vary question types for authentic practice:**
- True/False/Not Given
- Multiple Choice
- Matching Headings
- Sentence Completion
- Summary Completion
- Matching Information
- Short Answer
- Diagram/Table Completion

### Instructions

**For groups of the same question type:**
- Instructions should only appear on the FIRST question of the group
- Subsequent questions in the group should have empty instructions
- This prevents repetitive instruction display

---

## Available Tools

### Validation Tools

**validate-xml.php**
```bash
php TEMPLATES/validate-xml.php "path/to/file.xml" [--fix]
```

**Checks:**
- CDATA formatting (spaces)
- PHP serialization validity
- Required meta fields
- Post type verification
- UTF-8 character detection

**With --fix flag:**
- Automatically fixes CDATA spacing
- Creates `filename-fixed.xml`

### Fixing Tools

**fix-utf8-in-xml.py**
```bash
python3 TEMPLATES/fix-utf8-in-xml.py "input.xml" "output-fixed.xml"
```

**Fixes:**
- Replaces en-dashes with hyphens
- Replaces em-dashes with double hyphens
- Replaces curly quotes with straight quotes
- Recalculates PHP serialization byte counts

**fix-serialization-lengths.py**
```bash
python3 TEMPLATES/fix-serialization-lengths.py "input.xml" "output-fixed.xml"
```

**Fixes:**
- Recalculates all string byte lengths in PHP serialization
- Updates `s:LENGTH:` values
- Preserves all content

**fix-xml-with-php.php**
```bash
php TEMPLATES/fix-xml-with-php.php "input.xml" "output-fixed.xml"
```

**Fixes:**
- Replaces problematic UTF-8 characters
- Re-serializes PHP data correctly
- Most thorough fix option

### Workflow

**Standard fixing workflow:**

1. **Validate**
   ```bash
   php TEMPLATES/validate-xml.php "file.xml"
   ```

2. **Fix based on error type:**
   - CDATA spacing: `php TEMPLATES/validate-xml.php "file.xml" --fix`
   - UTF-8 characters: `python3 TEMPLATES/fix-utf8-in-xml.py "file.xml" "file-fixed.xml"`
   - Byte counts: `python3 TEMPLATES/fix-serialization-lengths.py "file.xml" "file-fixed.xml"`
   - Complex issues: `php TEMPLATES/fix-xml-with-php.php "file.xml" "file-fixed.xml"`

3. **Validate again**
   ```bash
   php TEMPLATES/validate-xml.php "file-fixed.xml"
   ```

4. **Test import in WordPress**

---

## Testing and Validation

### Before Creating XML Files

1. **Content check:**
   - All feedback fields filled
   - No UTF-8 special characters
   - Correct answers clearly stated in no-answer feedback
   - Questions linked to correct reading passages

2. **Export from WordPress:**
   - Use plugin's export function
   - Replace any special characters before export
   - Test export immediately after creation

### After Creating/Modifying XML Files

1. **Validate immediately:**
   ```bash
   php TEMPLATES/validate-xml.php "file.xml"
   ```

2. **Fix any errors found**

3. **Test import:**
   - Import into test WordPress installation
   - Verify questions appear correctly
   - Check that feedback displays properly
   - Ensure reading texts/transcripts load correctly

4. **Test quiz functionality:**
   - Complete the quiz
   - Submit answers (correct, incorrect, and no answer)
   - Verify all feedback displays correctly
   - Check that correct answer is shown for unanswered questions

### Validation Checklist

- [ ] XML file validates without errors
- [ ] Import succeeds without "No questions found" error
- [ ] All questions display in WordPress
- [ ] Reading texts/transcripts appear correctly
- [ ] Audio plays (for listening tests)
- [ ] Feedback displays for all answer states
- [ ] Correct answer is clearly shown when not answered
- [ ] Question numbering is correct
- [ ] Timer works (if set)
- [ ] Scoring calculates correctly

### Export/Import Round-Trip Test

**Critical test to ensure data integrity:**

1. Import XML file into WordPress
2. Immediately export it back to XML
3. Validate the exported file
4. Compare with original (should be nearly identical)
5. Test importing the exported file again

**If round-trip fails:**
- There's a serialization or encoding issue
- Fix before distributing the XML file

---

## Known Working Files

### Listening Tests

**The following XML files have been tested and work correctly:**

**Test 1:**
- ✅ `main/XMLs/Listening Test 1 Section 1.xml` - WORKING
- ✅ `main/XMLs/Listening Test 1 Section 2.xml` - WORKING
- ✅ `main/XMLs/Listening Test 1 Section 3.xml` - WORKING
- ✅ `main/XMLs/Listening Test 1 Section 4.xml` - WORKING

**Test 2:**
- ✅ `main/XMLs/Listening Test 2 Section 1.xml` - WORKING
- ✅ `main/XMLs/Listening Test 2 Section 2.xml` - WORKING
- ✅ `main/XMLs/Listening Test 2 Section 3.xml` - WORKING (fixed UTF-8 serialization issue)
- ✅ `main/XMLs/Listening Test 2 Section 4.xml` - WORKING

**Test 3:**
- ✅ `main/XMLs/Listening Test 3 Section 1.xml` - WORKING
- ✅ `main/XMLs/Listening Test 3 Section 2.xml` - WORKING
- ✅ `main/XMLs/Listening Test 3 Section 3.xml` - WORKING
- ✅ `main/XMLs/Listening Test 3 Section 4.xml` - WORKING

**All 31 Listening Test XML files have been validated.**

**Note:** Listening Test 2 Section 3.xml had a corrupted UTF-8 en-dash serialization issue that was fixed. The file now works correctly.

### Tests 4-8 Status

**Tests 4-8 exist as .txt files (not yet XML):**
- Ready to be imported via WordPress "Create Exercises from Text" tool
- Transcript files extracted and ready
- See `main/XMLs/TESTS_4-8_STATUS.md` for conversion instructions

---

## Common Errors and Solutions

### Error: "No questions found in the XML file"

**Possible Causes:**
1. CDATA spacing issue
2. UTF-8 character breaking serialization
3. PHP serialization byte count mismatch
4. Structural corruption in serialized data

**Solution Steps:**
```bash
# Step 1: Validate
php TEMPLATES/validate-xml.php "file.xml"

# Step 2: Fix CDATA (if needed)
php TEMPLATES/validate-xml.php "file.xml" --fix

# Step 3: Fix UTF-8 (if needed)
python3 TEMPLATES/fix-utf8-in-xml.py "file.xml" "file-fixed.xml"

# Step 4: Validate again
php TEMPLATES/validate-xml.php "file-fixed.xml"

# Step 5: Test import
```

### Error: "Error at offset XXX"

**Cause:** PHP serialization byte count mismatch

**Solution:**
```bash
python3 TEMPLATES/fix-serialization-lengths.py "file.xml" "file-fixed.xml"
php TEMPLATES/validate-xml.php "file-fixed.xml"
```

### Error: Questions appear but reference wrong reading passages

**Cause:** Reading text ID mismatch (often after append import)

**Solution:**
1. Check `reading_text_id` values in questions
2. Ensure they match the array indices of reading texts
3. Use "Replace" mode instead of "Append" if IDs are wrong
4. Manually fix IDs in XML if needed

### Error: Transcript doesn't show annotations

**Cause:** Annotation feature requires import (not manual edit)

**Solution:**
1. Export the exercise to XML
2. Re-import the XML file
3. Annotation happens automatically during import

### Error: Audio doesn't play in listening test

**Possible Causes:**
1. Invalid audio URL
2. Audio file not accessible
3. Format not supported (use MP3)
4. Autoplay blocked by browser (use "Enable Audio" button)

**Solution:**
1. Verify audio URL is correct and accessible
2. Convert to MP3 if using different format
3. For practice tests, ensure user clicks "Enable Audio"
4. Check browser console for errors

### Error: Feedback not showing for unanswered questions

**Cause:** Empty or missing no-answer feedback

**Solution:**
1. Ensure all questions have `no_answer_feedback` field
2. Feedback must include "The correct answer is: [answer]"
3. Re-export and re-import to update

---

## Plugin Version Information

**Current Version:** 8.10

**Version History:**
- **8.10** - Fixed no-answer feedback, added yellow-highlighted transcripts
- **8.9** - Improved listening exercise UI, singular audio file
- **8.8 and earlier** - See git history

### Updating Version

When releasing a new version:

1. Update version in `ielts-course-manager.php` header comment
2. Update `IELTS_CM_VERSION` constant
3. Document changes in a VERSION_X.X_SUMMARY.md file
4. Test all affected functionality
5. Validate all XML files still import correctly

---

## Emergency Procedures

### If You Break Everything

1. **Don't Panic** - Most issues are fixable
2. **Check Git History** - Revert if needed
3. **Run Validation Tools** - Identify the specific issue
4. **Use Fixing Scripts** - Auto-repair when possible
5. **Ask for Help** - Provide validation output and error messages

### Quick Recovery Steps

```bash
# Reset a corrupted XML file
git checkout HEAD -- "path/to/file.xml"

# Batch validate all XML files
for file in main/XMLs/*.xml; do
    echo "Validating $file"
    php TEMPLATES/validate-xml.php "$file"
done

# Batch fix all XML files
for file in main/XMLs/*.xml; do
    echo "Fixing $file"
    python3 TEMPLATES/fix-utf8-in-xml.py "$file" "${file%.xml}-fixed.xml"
done
```

### Prevention is Better Than Recovery

- **Always work in a branch**
- **Always validate before committing**
- **Always test before merging**
- **Always keep backups of working XML files**
- **Always read this guide before starting work**

---

## Additional Resources

### Historical and Supplementary Documentation

**Note:** Most documentation has been consolidated into this DEVELOPMENT-GUIDELINES.md file. The following files remain for historical reference or specific technical details:

**Root Level:**
- `VERSION_8.10_SUMMARY.md` - Version 8.10 release notes (historical)
- `VERSION_8.9_SUMMARY.md` - Version 8.9 release notes (historical)
- `IMPLEMENTATION_COMPLETE.md` - Implementation completion summary (historical)
- `MEMBERSHIP-SYSTEM-SUMMARY.md` - Membership system documentation (separate plugin)

**docs/ Directory:**
- `docs/LISTENING-LAYOUTS.md` - Detailed technical implementation reference
- `docs/LISTENING-LAYOUTS-TEST-PLAN.md` - Test plan document
- `docs/VIDEO-FIELD-GUIDE.md` - User guide for video embedding feature
- `docs/IMPLEMENTATION-SUMMARY.md` - Historical implementation summary
- `docs/TESTING-XML-APPEND.md` - Specific test documentation

**main/XMLs/ Directory:**
- `main/XMLs/README-CONVERTER-SCRIPTS.md` - Converter scripts documentation
- `main/XMLs/TESTS_4-8_STATUS.md` - Test status documentation
- `main/XMLs/XML_GENERATION_SUMMARY.md` - XML generation history
- `main/XMLs/XML_CONVERSION_INSTRUCTIONS.md` - Conversion instructions

### Template Files

Located in `TEMPLATES/`:
- Individual question type templates (True/False, Multiple Choice, etc.)
- Full test template
- Validation and fixing scripts

---

## Summary: The Most Important Rules

1. **NO UTF-8 special characters** (en-dash, em-dash, curly quotes)
2. **ALL feedback fields MUST be filled**
3. **No-answer feedback MUST show the correct answer clearly**
4. **NO spaces in CDATA sections**
5. **ALWAYS validate XML before importing**
6. **NEVER rush** - rushing causes more delays
7. **TEST everything** before considering it complete
8. **Use the tools** - they're there to help you

---

## Contact and Support

**For Issues or Questions:**
- Open a GitHub issue in the repository
- Include the tag `[DEVELOPMENT-GUIDELINES]` in the issue title
- Reference the specific section of this document that relates to your issue

**Before Asking for Help:**
1. Read this entire guide thoroughly
2. Run the validation tools on your files
3. Try the fixing scripts to resolve the issue
4. Check the remaining documentation files for historical context
5. Review git history for similar issues and their resolutions

**When Asking for Help, Provide:**
- Validation script output (copy and paste full text)
- Error messages (full text, not screenshots)
- Steps you've already tried to resolve the issue
- XML file (if not sensitive) or a minimal reproducible example
- WordPress version and PHP version
- Plugin version and environment details

---

**Remember: Following these guidelines saves time and prevents delays. Take the time to do it right the first time.**

**Last Updated:** December 28, 2025  
**Plugin Version:** 8.10  
**Status:** Production Ready
