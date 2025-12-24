# IELTS Quiz Template Files

## Overview
This directory contains XML template files for creating IELTS quizzes. These templates can be imported into WordPress using the standard WordPress import tool.

## Common Import Issues

### "No questions available for this quiz" Error

This error occurs when the XML file contains **spaces inside CDATA sections**. 

#### ❌ INCORRECT Format (will cause error):
```xml
<wp:meta_key>
<![CDATA[ _ielts_cm_questions ]]>
</wp:meta_key>
<wp:meta_value>
<![CDATA[ a:15:{i:0;...} ]]>
</wp:meta_value>
```

#### ✅ CORRECT Format (will work):
```xml
<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
<wp:meta_value><![CDATA[a:15:{i:0;...}]]></wp:meta_value>
```

**Key Points:**
- NO spaces after `<![CDATA[`
- NO spaces before `]]>`
- XML parsers preserve spaces in CDATA sections
- Extra spaces break PHP serialized data deserialization
- This causes WordPress to fail reading the questions data

## How to Fix Broken XML Files

Use the provided validation script:
```bash
php TEMPLATES/validate-xml.php your-file.xml
```

This will:
1. Check for spaces in CDATA sections
2. Validate PHP serialized data can be unserialized
3. Report any issues found
4. Optionally fix the issues automatically

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
