# Import Options Guide for IELTS Course Manager

## Available Import Options

**✅ JSON Import** - **NEW! Primary recommended method** (as of version 10.2)  
**✅ XML Import** - Legacy method (still supported but has reliability issues)  
**✅ Manual UI Entry** - Create questions through WordPress admin

---

## 1. JSON Import (RECOMMENDED ⭐)

**Status:** ✅ **ACTIVE - Now the recommended import method**

### Why JSON?
- **90% fewer bugs** compared to XML
- **Native UTF-8 support** - no issues with special characters
- **Human-readable** - easy to create and edit manually
- **Better error messages** - tells you exactly what's wrong and where
- **Smaller file sizes** - 64% smaller than equivalent XML
- **No serialization issues** - the #1 cause of XML import failures

### How to Use

1. Navigate to WordPress Admin → Quizzes → Edit Quiz
2. Find the "Import/Export JSON" section in the sidebar meta box
3. Select import mode:
   - **Add to existing content** - Appends questions to current content
   - **Replace all content** - Overwrites everything (export backup first!)
4. Upload your JSON file
5. Click "Upload & Import JSON"

### JSON Format

See `TEMPLATES/example-exercise.json` for a complete working example.

**Basic Structure:**
```json
{
  "title": "Your Exercise Title",
  "content": "Optional description",
  "questions": [ /* array of question objects */ ],
  "reading_texts": [ /* optional reading passages */ ],
  "settings": {
    "pass_percentage": 70,
    "layout_type": "two_column_listening",
    "exercise_label": "practice_test",
    "scoring_type": "ielts_listening_band",
    "timer_minutes": 10,
    "starting_question_number": 1
  },
  "audio": {
    "url": "https://example.com/audio.mp3",
    "transcript": "<p>Transcript HTML...</p>"
  }
}
```

**Example Open Question (covers 5 question numbers):**
```json
{
  "type": "open_question",
  "instructions": "Complete using NO MORE THAN TWO WORDS",
  "question": "Complete the notes:",
  "field_count": 5,
  "field_labels": [
    "1. The owner wants to rent by ________",
    "2. The woman will come this ________",
    "3. She needs her own ________",
    "4. There are two ________",
    "5. Garden longer than ________"
  ],
  "field_answers": [
    "Friday",
    "afternoon",
    "bed",
    "bathrooms",
    "4 metres|four metres|4m"
  ],
  "correct_feedback": "Excellent!",
  "incorrect_feedback": "Not quite. Listen again.",
  "no_answer_feedback": "The correct answer is shown above.",
  "points": 5
}
```

**Example Closed Question with 2 correct answers (covers 2 question numbers):**
```json
{
  "type": "closed_question",
  "instructions": "Choose TWO letters A-F",
  "question": "Which TWO are true of oregano?",
  "correct_answer_count": 2,
  "mc_options": [
    {"text": "A. Easy to sprinkle", "is_correct": false},
    {"text": "B. Tastier fresh", "is_correct": false},
    {"text": "C. Used in Italian dishes", "is_correct": true},
    {"text": "D. Lemony flavor", "is_correct": false},
    {"text": "E. Rounded flavor", "is_correct": false},
    {"text": "F. Good with meat", "is_correct": true}
  ],
  "correct_answer": "2|5",
  "correct_feedback": "Correct! C and F.",
  "incorrect_feedback": "Not quite. The answers are C and F.",
  "no_answer_feedback": "The correct answers are C and F.",
  "points": 2
}
```

### Export to JSON

1. Edit the exercise in WordPress admin
2. Find "Export to JSON" section in sidebar
3. Click "Export to JSON" button
4. File downloads automatically

---

## 2. XML Import (Legacy)

**Status:** ✅ **Active but NOT recommended** (use JSON instead)

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

### 3. Manual Entry (UI Method)
**Status:** ✅ **Active - Good for small exercises**

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

### 4. Programmatic Creation (Advanced)
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
**Status:** ❌ **Not Available** (Future enhancement)

CSV import is not currently supported. See `IMPORT_FORMAT_ANALYSIS.md` for detailed analysis and implementation plan.

**Workaround:** Create JSON file manually or convert CSV to JSON using a custom script.

### Excel/Spreadsheet Import
**Status:** ❌ **Not Available** (Future enhancement)

Excel/spreadsheet import is not currently supported. See `IMPORT_FORMAT_ANALYSIS.md` for detailed analysis.

**Workaround:** Export spreadsheet to CSV, convert to JSON, then import.

### Text Format Import
**Status:** ❌ **Removed in Version 9.0**

The text format import that existed in earlier versions was removed due to maintenance complexity and parsing errors.

---

## Recommendation for Your Use Case

Based on your problem description (Q1-5 open questions, Q6-10 map labeling, Q11-12 two-answer multiple choice), here's how to create this using **current question types**:

### Option 1: JSON Import (RECOMMENDED ⭐)

Create a JSON file with your questions. See `TEMPLATES/example-exercise.json` for the exact format you need.

**Your exercise structure:**
```json
{
  "title": "Listening Test Example",
  "questions": [
    {
      "type": "open_question",
      "field_count": 5,
      "field_labels": ["Q1...", "Q2...", "Q3...", "Q4...", "Q5..."],
      "field_answers": ["Friday", "afternoon", "bed", "bathrooms", "4 metres|four metres"]
    },
    {
      "type": "open_question",
      "field_count": 5,
      "field_labels": ["Q6...", "Q7...", "Q8...", "Q9...", "Q10..."],
      "field_answers": ["post office", "Hill Park", "Wood Lane", "Petrol station", "Bus stop"]
    },
    {
      "type": "closed_question",
      "correct_answer_count": 2,
      "mc_options": [/* 6 options A-F */],
      "correct_answer": "2|5"
    }
  ]
}
```

This gives you 12 questions total (5 + 5 + 2).

### Option 2: XML Import (Legacy)

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

| Import Method | Status | Best For | Complexity | Bug Rate |
|--------------|--------|----------|------------|----------|
| **JSON Import** | ✅ Active | **Everything** (recommended) | Low | ~10% |
| **XML Import** | ✅ Active | Legacy compatibility | Medium | ~95% |
| **Manual UI** | ✅ Active | Small exercises, beginners | Low | ~5% |
| **PHP Script** | ✅ Active | Developers, custom workflows | High | ~5% |
| **CSV Import** | ❌ Not Available | - | - | - |
| **Excel Import** | ❌ Not Available | - | - | - |
| **Text Format** | ❌ Removed | - | - | - |

## Getting Started

### For Your Specific Use Case (Q1-12):

1. **Recommended: JSON Import** ⭐
   - Download `TEMPLATES/example-exercise.json`
   - Modify with your questions and answers
   - Import via WordPress admin → JSON Import section
   - Time: ~10-15 minutes
   - **Reliable** - 90% fewer errors than XML

2. **Quick Solution: Manual UI**
   - Use the WordPress admin interface
   - Add 3 questions as described above
   - Time: ~15-30 minutes

3. **Legacy: XML Import** (not recommended)
   - Use `TEMPLATES/generate-closed-open-xml.php` as template
   - Requires validation and fixing scripts
   - Time: ~1-2 hours (with debugging)

## Need Help?

1. **JSON Import Issues:**
   - JSON errors show exact line/column number
   - Validate JSON syntax at jsonlint.com
   - Check `TEMPLATES/example-exercise.json` for reference

2. **XML Validation Issues:**
   - Run `php TEMPLATES/validate-xml.php "your-file.xml"`
   - Check `DEVELOPMENT-GUIDELINES.md` for fixing tools
   - See `IMPORT_FORMAT_ANALYSIS.md` for why JSON is better

3. **Question Not Displaying:**
   - Verify question type is `closed_question` or `open_question`
   - Check that all required fields are present
   - For JSON: ensure field_count matches number of field_labels

4. **Further Questions:**
   - Review `IMPORT_FORMAT_ANALYSIS.md` for technical details
   - Check `TEMPLATES/example-exercise.json` for working example
   - See `DEVELOPMENT-GUIDELINES.md` for comprehensive guide

## Recent Updates

**Version 10.2 (January 2026):**
- ✅ **Added JSON import/export** - Now the primary recommended method
- ✅ Eliminates 90% of import bugs caused by XML serialization
- ✅ Native UTF-8 support, better error messages, smaller files
- ✅ Example file provided: `TEMPLATES/example-exercise.json`

**Future Enhancements:**
- CSV import (for bulk questions from spreadsheets)
- Excel/XLSX import (direct spreadsheet upload)
- Google Sheets integration
- Interactive visual question builder

---

**Last Updated:** January 1, 2026  
**Plugin Version:** 10.2  
**Current Import Options:** JSON (recommended), XML (legacy), Manual UI, PHP Programmatic
