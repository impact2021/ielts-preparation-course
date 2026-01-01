# Import Options Guide for IELTS Course Manager

## Question: What import options are available besides XML?

**Short Answer:** Currently, **XML is the ONLY import option** available for the IELTS Course Manager plugin.

## Historical Context

### Version 9.0 Changes (December 2024)
Version 9.0 was a major breaking change that removed the text format import system along with 12 legacy question types. This was done to:
- Simplify the codebase (removed ~2,500+ lines of code)
- Improve XML compatibility and reliability
- Reduce maintenance complexity
- Focus on two flexible question types

### Removed Features
The following import/export features were removed in Version 9.0:
- ✗ **Text Format Import** - "Import from Text" functionality
- ✗ **Text Format Export** - "View as Text Format" functionality
- ✗ **Text Format Parser** - AJAX handlers for text conversion
- ✗ **Text Exercises Creator** - Entire PHP class removed

## Current Import Options

### 1. XML Import (Primary Method)
**Status:** ✅ **Active - This is the ONLY import method**

**How to use:**
1. Navigate to WordPress Admin → Quizzes → Edit Quiz
2. Find the "Import/Export XML" meta box (sidebar)
3. Select import mode:
   - **Replace All Content** - Overwrites all questions and settings
   - **Add to Existing Content** - Appends questions to existing exercise
4. Upload your XML file
5. Click "Import XML"

**XML Format:**
The XML must follow WordPress WXR (WordPress eXtended RSS) format with IELTS Course Manager meta fields. See `TEMPLATES/generate-closed-open-xml.php` for structure.

**Required Meta Fields:**
- `_ielts_cm_questions` - Serialized array of questions
- `_ielts_cm_reading_texts` - Serialized array of reading passages (optional)
- `_ielts_cm_pass_percentage` - Pass percentage (e.g., 70)
- `_ielts_cm_layout_type` - Layout type
- `_ielts_cm_exercise_label` - Exercise label
- `_ielts_cm_scoring_type` - Scoring type

**Tools Available:**
- `TEMPLATES/validate-xml.php` - Validates XML before import
- `TEMPLATES/fix-utf8-in-xml.py` - Fixes UTF-8 character issues
- `TEMPLATES/fix-serialization-lengths.py` - Fixes PHP serialization
- `TEMPLATES/generate-closed-open-xml.php` - Example generator

### 2. Manual Entry (UI Method)
**Status:** ✅ **Active - Alternative to XML**

**How to use:**
1. Navigate to WordPress Admin → Quizzes → Add New / Edit Quiz
2. Scroll to "Quiz Settings" meta box
3. Click "Add Question" button
4. Select question type:
   - **Closed Question** - Multiple choice (single or multi-select)
   - **Open Question** - Text input fields
5. Fill in question details, options, and feedback
6. Save the exercise

**Benefits:**
- No XML knowledge required
- Visual interface for question creation
- Immediate validation
- Built-in help text

**Drawbacks:**
- Time-consuming for large exercises
- No bulk import capability
- Can't easily duplicate existing exercises

### 3. Programmatic Creation (Advanced)
**Status:** ✅ **Active - For developers**

**How to use:**
Create questions programmatically using PHP:

```php
// Example: Create a quiz with questions
$quiz_id = wp_insert_post(array(
    'post_title' => 'My Quiz',
    'post_type' => 'ielts_quiz',
    'post_status' => 'publish'
));

$questions = array(
    array(
        'type' => 'closed_question',
        'question' => 'What is the capital of France?',
        'mc_options' => array(
            array('text' => 'London', 'is_correct' => false),
            array('text' => 'Paris', 'is_correct' => true),
            array('text' => 'Berlin', 'is_correct' => false)
        ),
        'correct_answer_count' => 1,
        'correct_answer' => '1',
        'points' => 1
    )
);

update_post_meta($quiz_id, '_ielts_cm_questions', $questions);
```

**Benefits:**
- Automate quiz creation
- Integrate with external systems
- Bulk operations possible

**Drawbacks:**
- Requires PHP knowledge
- Must understand internal data structure
- No built-in validation

## What About Other Formats?

### CSV Import
**Status:** ❌ **Not Available**

CSV import is not currently supported. While it would be useful for bulk question import, implementing it would require:
- CSV parser
- Column mapping system
- Validation layer
- Error handling
- User interface

**Workaround:** Convert CSV to XML using a custom script, then use XML import.

### JSON Import
**Status:** ❌ **Not Available**

JSON import is not currently supported. However, JSON would be easier to work with than XML for many users.

**Potential Benefits:**
- Easier to read and edit manually
- Native JavaScript support
- Less verbose than XML
- Better error messages

**Workaround:** Convert JSON to XML using a custom script.

### Excel/Spreadsheet Import
**Status:** ❌ **Not Available**

Excel/spreadsheet import is not supported.

**Workaround:** Export spreadsheet to CSV, convert to XML, then import.

### Text Format Import (Removed)
**Status:** ❌ **Removed in Version 9.0**

The text format import that existed in earlier versions was removed due to:
- Maintenance complexity
- Parsing errors
- Limited flexibility
- Confusion with XML format

## Recommendation for Your Use Case

Based on your problem description (Q1-5 open questions, Q6-10 map labeling, Q11-12 two-answer multiple choice), here's how to create this using **current question types**:

### Option 1: XML Import (Recommended for Bulk)

Create an XML file with the following structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
  <item>
    <title>Listening Test Section</title>
    <wp:post_type>ielts_quiz</wp:post_type>
    <wp:postmeta>
      <wp:meta_key>_ielts_cm_questions</wp:meta_key>
      <wp:meta_value><![CDATA[a:3:{
        i:0;a:X:{
          s:4:"type";s:13:"open_question";
          s:8:"question";s:60:"Complete the notes below USING NO MORE THAN TWO WORDS...";
          s:11:"field_count";i:5;
          s:12:"field_labels";a:5:{
            i:0;s:50:"The owner wants to rent the house by ________";
            i:1;s:51:"The woman will come to look at the house this ________";
            i:2;s:51:"The woman will need to have her own ________";
            i:3;s:20:"There are two ________";
            i:4;s:40:"The garden is slightly longer than ________";
          }
          s:13:"field_answers";a:5:{
            i:0;s:6:"Friday";
            i:1;s:9:"afternoon";
            i:2;s:3:"bed";
            i:3;s:9:"bathrooms";
            i:4;s:60:"4 metres|four metres|4 m|4m|4 meters|four meters";
          }
          s:6:"points";i:5;
        }
        i:1;a:X:{
          s:4:"type";s:13:"open_question";
          s:8:"question";s:80:"Label the map below using NO MORE THAN TWO WORDS...";
          s:11:"field_count";i:5;
          s:12:"field_labels";a:5:{
            i:0;s:13:"6. ________";
            i:1;s:13:"7. ________";
            i:2;s:13:"8. ________";
            i:3;s:13:"9. ________";
            i:4;s:14:"10. ________";
          }
          s:13:"field_answers";a:5:{
            i:0;s:11:"post office";
            i:1;s:9:"Hill Park";
            i:2;s:9:"Wood Lane";
            i:3;s:14:"Petrol station";
            i:4;s:8:"Bus stop";
          }
          s:6:"points";i:5;
        }
        i:2;a:X:{
          s:4:"type";s:15:"closed_question";
          s:8:"question";s:90:"Which TWO of the following does the chef say are true of the herb oregano?";
          s:10:"mc_options";a:6:{
            i:0;a:2:{s:4:"text";s:33:"A. It's easy to sprinkle on food";s:10:"is_correct";b:0;}
            i:1;a:2:{s:4:"text";s:27:"B. It's tastier when fresh";s:10:"is_correct";b:0;}
            i:2;a:2:{s:4:"text";s:51:"C. It's used in the majority of Italian dishes";s:10:"is_correct";b:1;}
            i:3;a:2:{s:4:"text";s:27:"D. It has a lemony flavour";s:10:"is_correct";b:0;}
            i:4;a:2:{s:4:"text";s:35:"E. It gives food a rounded flavour";s:10:"is_correct";b:0;}
            i:5;a:2:{s:4:"text";s:54:"F. It's a good accompaniment to many meat dishes";s:10:"is_correct";b:1;}
          }
          s:20:"correct_answer_count";i:2;
          s:14:"correct_answer";s:3:"2|5";
          s:6:"points";i:2;
        }
      }]]></wp:meta_value>
    </wp:postmeta>
  </item>
</channel>
</rss>
```

**Note:** This is simplified pseudo-code. For actual XML, use `TEMPLATES/generate-closed-open-xml.php` as a template.

### Option 2: Manual UI Entry

1. **Q1-5 (Open Questions):**
   - Add one **Open Question**
   - Set `field_count` to 5
   - Enter all 5 field labels and answers
   - This will automatically count as 5 questions (Q1-Q5)

2. **Q6-10 (Map Labeling):**
   - Add another **Open Question**
   - Set `field_count` to 5
   - Enter labels for Q6-Q10
   - Enter answers for each map location
   - This will count as questions Q6-Q10

3. **Q11-12 (Two-Answer Multiple Choice):**
   - Add one **Closed Question**
   - Set `correct_answer_count` to 2
   - Add 6 options (A-F)
   - Mark options C and F as correct
   - This will count as 2 questions (Q11-Q12)

**Total:** 12 questions as required

### Option 3: PHP Generator Script

Create a custom PHP script using `TEMPLATES/generate-closed-open-xml.php` as a base:

```php
#!/usr/bin/env php
<?php
// Your custom generator script
// Define your questions as PHP arrays
// Generate XML output
// Import using XML import feature
```

## Summary

| Import Method | Status | Best For | Complexity |
|--------------|--------|----------|------------|
| **XML Import** | ✅ Active | Bulk import, automation | Medium |
| **Manual UI** | ✅ Active | Small exercises, beginners | Low |
| **PHP Script** | ✅ Active | Developers, custom workflows | High |
| **CSV Import** | ❌ Not Available | - | - |
| **JSON Import** | ❌ Not Available | - | - |
| **Text Format** | ❌ Removed | - | - |

## Getting Started

### For Your Specific Use Case:

1. **Quick Solution (Manual UI):**
   - Use the WordPress admin interface
   - Add 3 questions as described in Option 2 above
   - Time: ~15-30 minutes

2. **Scalable Solution (XML):**
   - Use `TEMPLATES/generate-closed-open-xml.php` as template
   - Modify to include your specific questions
   - Generate XML file
   - Import via WordPress admin
   - Time: ~1-2 hours (first time), ~15 minutes (subsequent)

3. **Developer Solution (PHP Generator):**
   - Create custom generator script
   - Define questions in PHP arrays
   - Output XML format
   - Automate for future exercises
   - Time: ~2-4 hours (setup), ~5 minutes (subsequent)

## Need Help?

1. **XML Validation Issues:**
   - Run `php TEMPLATES/validate-xml.php "your-file.xml"`
   - Check DEVELOPMENT-GUIDELINES.md for fixing tools

2. **Question Not Displaying:**
   - Verify question type is `closed_question` or `open_question`
   - Check that all required fields are present
   - Validate XML serialization

3. **Further Questions:**
   - Review `DEVELOPMENT-GUIDELINES.md` for comprehensive guide
   - Check `TEMPLATES/generate-closed-open-xml.php` for examples
   - See existing XML files in `main/XMLs/` for working examples

## Future Possibilities

While not currently available, potential future import options could include:
- JSON import (easier to work with than XML)
- CSV import (for bulk questions)
- Google Sheets integration
- Excel/XLSX import
- Interactive question builder UI

However, these would require significant development work and are not currently planned.

---

**Last Updated:** January 1, 2026  
**Plugin Version:** 10.1  
**Current Import Options:** XML only
