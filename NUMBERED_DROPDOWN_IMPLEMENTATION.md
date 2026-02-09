# Numbered Dropdown Implementation

## Overview

This feature adds support for numbered dropdown placeholders in `closed_question_dropdown` questions, allowing inline question numbering similar to official IELTS tests.

## Problem Statement

Users wanted to display question numbers inline with dropdown questions, like this:
```
There are clearly some 1.[dropdown] where watching television does not negatively affect children. 
Some educational programmes, for example, can help 2.[dropdown].
```

## Solution

### New Syntax

Users can now use either:

1. **Unnumbered (existing)**: `[dropdown]`
   ```json
   "question": "I went [dropdown] to the store."
   ```

2. **Numbered (new)**: `1.[dropdown]`, `2.[dropdown]`, etc.
   ```json
   "question": "I went 1.[dropdown] to the store and bought 2.[dropdown]."
   ```

### Implementation Details

The implementation:
- Detects numbered dropdown syntax using regex: `/\d+\.\s*\[dropdown\]/i`
- Preserves the numbers in the output when rendering
- Maintains full backward compatibility with unnumbered syntax
- Works across all quiz template types

### Files Modified

1. `templates/single-quiz.php`
2. `templates/single-quiz-computer-based.php`
3. `templates/single-quiz-listening-exercise.php`
4. `templates/single-quiz-listening-practice.php`

### Code Changes

Each file was updated with the following logic:

```php
// Check if using numbered dropdown syntax (e.g., "1.[dropdown]", "2.[dropdown]")
$has_numbered_dropdowns = preg_match('/\d+\.\s*\[dropdown\]/i', $question_text);

while ($dropdown_num <= $correct_answer_count) {
    if ($has_numbered_dropdowns) {
        // Check for numbered placeholder
        $numbered_pattern = '/(\d+)\.\s*\[dropdown\]/i';
        if (!preg_match($numbered_pattern, $processed_text)) {
            break;
        }
    } else {
        // Check for unnumbered placeholder
        if (stripos($processed_text, '[dropdown]') === false) {
            break;
        }
    }
    
    // Build select field...
    
    if ($has_numbered_dropdowns) {
        // Replace "1.[dropdown]" with "1. <select>..."
        $new_text = preg_replace('/(\d+)\.\s*\[dropdown\]/i', '$1. ' . $select_field, $processed_text, 1);
    } else {
        // Replace "[dropdown]" with "<select>..."
        $new_text = preg_replace('/\[dropdown\]/i', $select_field, $processed_text, 1);
    }
    
    $processed_text = $new_text;
    $dropdown_num++;
}
```

## Usage Examples

### Example 1: Simple Numbered Dropdowns

```json
{
  "type": "closed_question_dropdown",
  "question": "The student 1.[dropdown] to the library and 2.[dropdown] three books.",
  "correct_answer_count": 2,
  "mc_options": [
    {"text": "went", "is_correct": true, "feedback": "Correct!"},
    {"text": "go", "is_correct": false, "feedback": "Wrong tense."},
    {"text": "borrowed", "is_correct": true, "feedback": "Correct!"},
    {"text": "borrow", "is_correct": false, "feedback": "Wrong tense."}
  ],
  "correct_answer": "field_1:0|field_2:2"
}
```

### Example 2: IELTS Speaking Dialogue

```json
{
  "type": "closed_question_dropdown",
  "question": "<strong>Examiner:</strong> Do you think parents should allow children to watch television?<br><strong>Candidate:</strong> Well, there are clearly some 1.[dropdown] where it doesn't negatively affect children. Educational programmes can help 2.[dropdown].",
  "correct_answer_count": 2,
  "mc_options": [
    {"text": "situations", "is_correct": true, "feedback": "Correct!"},
    {"text": "times", "is_correct": false, "feedback": "Not the best fit."},
    {"text": "children to learn", "is_correct": true, "feedback": "Correct!"},
    {"text": "children learn", "is_correct": false, "feedback": "Missing 'to'."}
  ],
  "correct_answer": "field_1:0|field_2:2"
}
```

## Testing

### Files for Testing
- `TEMPLATES/example-dropdown-closed-question.json` - Includes numbered example
- `TEST-DROPDOWN-NUMBERED.json` - Simple test case

### What to Test
1. **Numbered syntax works**: Numbers appear inline before dropdowns
2. **Unnumbered syntax still works**: Existing questions render correctly
3. **Validation**: Answers are validated correctly for both syntaxes
4. **All templates**: Test across different quiz template types

## Documentation

Updated documentation files:
- `DROPDOWN-QUESTION-FAQ.md` - Added section on numbered dropdowns with examples

## Backward Compatibility

✅ Fully backward compatible:
- All existing `[dropdown]` questions work exactly as before
- No changes required to existing exercises
- Users can mix numbered and unnumbered questions in the same exercise

## Bug Fixes

As part of this implementation, also fixed:
- PHP syntax error in `single-quiz.php` (duplicate if statement on line 973-974)

## Security

- No security vulnerabilities introduced
- CodeQL analysis: No issues detected
- Uses existing sanitization functions (`esc_attr`, `esc_html`, `wp_kses`)

## Future Enhancements

Potential future improvements:
- Auto-numbering based on `starting_question_number` setting
- Support for custom number formats (e.g., "Q1.", "Question 1:")
- Visual styling options for numbered dropdowns

## Support

For questions or issues:
- See `DROPDOWN-QUESTION-FAQ.md` for usage examples
- Check `TEMPLATES/example-dropdown-closed-question.json` for working examples
- Test with `TEST-DROPDOWN-NUMBERED.json`
