# IELTS Quiz Template Files

## Overview
This directory contains XML template files for creating IELTS quizzes. These templates can be imported into WordPress using the standard WordPress import tool.

## Common Import Issues

### "No questions available for this quiz" Error

This error can occur due to several issues with the XML file:

#### Issue 1: Spaces inside CDATA sections

**❌ INCORRECT Format (will cause error):**
```xml
<wp:meta_key>
<![CDATA[ _ielts_cm_questions ]]>
</wp:meta_key>
<wp:meta_value>
<![CDATA[ a:15:{i:0;...} ]]>
</wp:meta_value>
```

**✅ CORRECT Format (will work):**
```xml
<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
<wp:meta_value><![CDATA[a:15:{i:0;...}]]></wp:meta_value>
```

**Key Points:**
- NO spaces after `<![CDATA[`
- NO spaces before `]]>`
- XML parsers preserve spaces in CDATA sections
- Extra spaces break PHP serialized data deserialization

#### Issue 2: PHP Serialization String Length Mismatches

PHP serialized data uses format `s:LENGTH:"content"` where LENGTH must exactly match the byte length of the content string. If these don't match, PHP's `unserialize()` function fails.

**Example of broken serialization:**
```
s:142:"Questions 21-25: Answer the following questions..."
```
If the actual string is only 137 bytes, unserialization fails with "Error at offset XXX".

**Solution:** Use `fix-serialization-lengths.py` to automatically recalculate and fix all string lengths.

#### Issue 3: Problematic UTF-8 Characters

Smart quotes, em-dashes, and other special UTF-8 characters can break serialization when their byte lengths aren't properly counted.

**Solution:** Use `fix-xml-with-php.php` to replace problematic characters with ASCII equivalents and re-serialize.

## Available Tools

### validate-xml.php
Validates XML files and checks for common issues:
```bash
php TEMPLATES/validate-xml.php your-file.xml [--fix]
```

Checks:
1. Spaces in CDATA sections
2. PHP serialized data validity
3. Required postmeta fields
4. Post type verification

### fix-serialization-lengths.py
Fixes string length mismatches in PHP serialized data:
```bash
python3 TEMPLATES/fix-serialization-lengths.py your-file.xml [output.xml]
```

Automatically recalculates and updates all string byte lengths in serialized data.

### fix-xml-with-php.php
Fixes UTF-8 character issues in serialized data:
```bash
php TEMPLATES/fix-xml-with-php.php your-file.xml [output.xml]
```

Replaces problematic UTF-8 characters and re-serializes data.

## Fixing Workflow

1. **Validate** the XML file:
   ```bash
   php TEMPLATES/validate-xml.php your-file.xml
   ```

2. **Fix** based on the error:
   - For string length mismatches:
     ```bash
     python3 TEMPLATES/fix-serialization-lengths.py your-file.xml
     ```
   - For UTF-8 character issues:
     ```bash
     php TEMPLATES/fix-xml-with-php.php your-file.xml
     ```

3. **Validate again** to confirm:
   ```bash
   php TEMPLATES/validate-xml.php your-file-fixed.xml
   ```

4. **Replace** the original file if valid:
   ```bash
   mv your-file-fixed.xml your-file.xml
   ```

## Template Files

### Individual Question Types
- `TEMPLATE-true-false.xml` - True/False/Not Given questions
- `TEMPLATE-multiple-choice.xml` - Multiple choice questions (headings)
- `TEMPLATE-matching.xml` - Matching questions
- `TEMPLATE-matching-classifying.xml` - Classification matching
- `TEMPLATE-short-answer.xml` - Short answer questions
- `TEMPLATE-dropdown-paragraph.xml` - Paragraph completion with dropdowns
- `TEMPLATE-summary-completion.xml` - Summary completion
- `TEMPLATE-table-completion.xml` - Table completion
- `TEMPLATE-headings.xml` - Matching headings
- `TEMPLATE-locating-information.xml` - Locating information
- `TEMPLATE-multi-select.xml` - Multi-select questions

### Full Tests
- `TEMPLATE-FULL-TEST.xml` - Complete IELTS Reading test with 3 passages and 40 questions

## Creating Custom XML Files

When creating XML exports:

1. **Use proper CDATA formatting** - No spaces around content
2. **Validate serialized data** - Ensure PHP arrays serialize correctly
3. **Test before distributing** - Import the XML to verify it works
4. **Use consistent formatting** - Follow the existing template structure

## XML Structure

Required postmeta fields for quizzes:
- `_ielts_cm_questions` - Serialized array of questions
- `_ielts_cm_reading_texts` - Serialized array of reading passages
- `_ielts_cm_pass_percentage` - Pass percentage (e.g., 60)
- `_ielts_cm_layout_type` - Layout type (`standard` or `computer_based`)
- `_ielts_cm_timer_minutes` - Time limit in minutes
- `_ielts_cm_scoring_type` - Scoring type (e.g., `percentage`)
- `_ielts_cm_exercise_label` - Exercise label (e.g., `exercise`)

## Support

If you encounter issues:
1. Check CDATA formatting (no spaces)
2. Run the validation script
3. Compare with working templates
4. Verify PHP serialized data is valid
