# Quick Start Guide

## Problem: "No questions available for this quiz" Error

This error occurs when XML template files have **spaces in CDATA sections**.

### How to Fix Your XML Files

1. **Check your XML file:**
   ```bash
   php TEMPLATES/validate-xml.php your-file.xml
   ```

2. **If errors are found, auto-fix them:**
   ```bash
   php TEMPLATES/validate-xml.php your-file.xml --fix
   ```

3. **This creates `your-file-fixed.xml`** - import this fixed version

### Example: Fixing the Provided XML

The XML you provided has this problem:
```xml
<wp:meta_key>
<![CDATA[ _ielts_cm_questions ]]>    ← Spaces here cause the error
</wp:meta_key>
```

Should be:
```xml
<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
```

## Using the Full Test Template

The `TEMPLATE-FULL-TEST.xml` file provides a complete IELTS Reading test structure:

- **3 reading passages** (increasing difficulty)
- **40 questions total**
  - Questions 1-13: True/False/Not Given (Passage 1)
  - Questions 14-22: Matching Headings (Passage 2)
  - Questions 23-31: Multiple Choice (Passage 3)
  - Questions 32-40: Sentence Completion (Passage 3)
- **60 minutes** timer
- **Computer-based** layout

### Steps to Use:

1. Copy `TEMPLATE-FULL-TEST.xml`
2. Replace placeholder content with your actual passages and questions
3. Validate: `php TEMPLATES/validate-xml.php your-test.xml`
4. Import into WordPress

## Prevention

When creating XML files:

1. **Never add spaces** inside `<![CDATA[` and `]]>` tags
2. **Always validate** before importing: `php TEMPLATES/validate-xml.php file.xml`
3. **Use the templates** in this directory as examples

## Need Help?

See `TEMPLATES/README.md` for detailed documentation.
