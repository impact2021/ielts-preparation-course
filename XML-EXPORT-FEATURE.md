# XML Export Feature for IELTS Exercises

## Overview

This feature allows you to export individual IELTS exercises to XML format with complete field data, making it easy to backup, transfer, or archive exercises.

## How to Use

1. Navigate to the **Exercises** list page in WordPress admin (IELTS Courses → Exercises)
2. Find the exercise you want to export
3. Hover over the exercise row to reveal action links
4. Click **"Export to XML"**
5. Your browser will download an XML file named `exercise-[slug]-[date].xml`

## What's Included in the Export

The XML export includes **ALL fields** for the exercise, even if they are currently empty:

### Post Data
- Title
- Content (description/instructions)
- Excerpt
- Post status (publish, draft, etc.)
- Publication dates
- Modified dates
- Author information
- Post slug
- Menu order

### Exercise Settings
- **Questions** (`_ielts_cm_questions`)
  - All question types (short answer, multiple choice, true/false, etc.)
  - Question text
  - Options and correct answers
  - Feedback (correct, incorrect, no answer)
  - Points
  - Instructions
  - Reading text associations
- **Reading Texts** (`_ielts_cm_reading_texts`)
  - Title
  - Content
- **Pass Percentage** (`_ielts_cm_pass_percentage`)
- **Layout Type** (`_ielts_cm_layout_type`)
  - Standard or Computer-Based IELTS layout
- **Exercise Label** (`_ielts_cm_exercise_label`)
  - Exercise, End of lesson test, or Practice test
- **Open as Popup** (`_ielts_cm_open_as_popup`)
- **Scoring Type** (`_ielts_cm_scoring_type`)
  - Percentage, IELTS Academic Reading, IELTS General Training Reading, or IELTS Listening
- **Timer Minutes** (`_ielts_cm_timer_minutes`)

### Associations
- **Course IDs** (`_ielts_cm_course_ids`) - Array of associated courses
- **Lesson IDs** (`_ielts_cm_lesson_ids`) - Array of associated lessons
- **Course ID** (`_ielts_cm_course_id`) - Single course ID (legacy field for backward compatibility)
- **Lesson ID** (`_ielts_cm_lesson_id`) - Single lesson ID (legacy field for backward compatibility)

## XML Format

The export uses the **WordPress WXR (WordPress eXtended RSS)** format, which is the standard format for WordPress imports and exports. This ensures compatibility with WordPress import tools.

### Example Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" 
     xmlns:content="http://purl.org/rss/1.0/modules/content/" 
     xmlns:wp="http://wordpress.org/export/1.2/" 
     version="2.0">
<channel>
    <title>Site Name</title>
    <!-- ... channel metadata ... -->
    
    <item>
        <title><![CDATA[Exercise Title]]></title>
        <!-- ... post data ... -->
        
        <wp:postmeta>
            <wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
            <wp:meta_value><![CDATA[a:2:{...}]]></wp:meta_value>
        </wp:postmeta>
        
        <wp:postmeta>
            <wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>
            <wp:meta_value><![CDATA[a:1:{...}]]></wp:meta_value>
        </wp:postmeta>
        
        <!-- ... all other metadata fields ... -->
    </item>
</channel>
</rss>
```

## Empty Fields

**Important**: Empty fields are **always included** in the export with empty values. This ensures that when you import the exercise elsewhere, you have a complete representation of all available fields, even if they weren't used in the original exercise.

For example:
- If no timer is set, `_ielts_cm_timer_minutes` will still be included with an empty value
- If popup mode is not enabled, `_ielts_cm_open_as_popup` will still be included with an empty value

## Security

The export feature includes:
- **Nonce verification** to prevent CSRF attacks
- **Capability checks** to ensure only users with `edit_post` permission can export
- **Post type validation** to ensure only `ielts_quiz` posts can be exported

## Use Cases

1. **Backup**: Download XML backups of important exercises
2. **Transfer**: Move exercises between WordPress installations
3. **Archive**: Keep offline copies of exercise content
4. **Version Control**: Track changes to exercises over time
5. **Documentation**: Share exercise structures with team members

## Technical Details

- **File naming**: `exercise-{post-slug}-{YYYY-MM-DD}.xml`
- **Encoding**: UTF-8
- **Content type**: `application/xml`
- **Serialization**: PHP serialize() for arrays and objects
- **AJAX endpoint**: `wp-ajax.php?action=ielts_cm_export_exercise_xml`

## Developer Notes

The export is handled by the `IELTS_CM_Admin::ajax_export_exercise_xml()` method, which:
1. Validates the request (nonce, capabilities, post type)
2. Retrieves all post data and metadata
3. Generates XML using `generate_exercise_xml()`
4. Sets appropriate HTTP headers
5. Outputs the XML content

Complex data structures (questions, reading texts, arrays) are serialized using PHP's `serialize()` function to maintain data integrity.

## Version

This feature was added in version 4.4 of the IELTS Course Manager plugin.

## Support

For issues or questions about the XML export feature, please contact the plugin maintainers or submit an issue in the repository.
