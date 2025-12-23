# Export Format Fix for Dropdown, Summary, and Table Completion Questions

## Problem
When dropdown_paragraph, summary_completion, and table_completion questions are created in WordPress (not via text import), they are stored as separate individual questions. When exported to XML, this format was incorrect and didn't match the expected structure.

## Expected Format

### Dropdown Paragraph
- **Single question** with `___1___`, `___2___`, etc. placeholders
- `dropdown_options` array containing options for each numbered dropdown
- Correct answer format: `1:A|2:B` (position:letter pairs)

Example:
```
Question: "The water cycle involves water ___1___ from oceans. It ___2___ into clouds."
dropdown_options: {
  1: {position: 1, options: [{text: "evaporates", is_correct: true}, ...]},
  2: {position: 2, options: [{text: "condenses", is_correct: true}, ...]}
}
correct_answer: "1:A|2:B"
```

### Summary/Table Completion
- **Single question** with `[field 1]`, `[field 2]`, etc. placeholders
- `summary_fields` array containing answers for each numbered field
- Each field has: answer, correct_feedback, incorrect_feedback, no_answer_feedback

Example:
```
Question: "EVs use [field 1] batteries. Costs fell [field 2] percent."
summary_fields: {
  1: {answer: "RECHARGEABLE", ...},
  2: {answer: "80", ...}
}
```

## Solution
Added transformation logic in `includes/admin/class-admin.php` that runs during XML export:

1. **`transform_questions_for_export()`** - Main transformation function
   - Detects consecutive questions of the same type
   - Skips questions already in correct format (have dropdown_options or summary_fields)
   - Groups consecutive questions and transforms them

2. **`transform_dropdown_paragraph_group()`** - For dropdown questions
   - Combines multiple questions into one
   - Replaces blanks with `___N___` placeholders
   - Creates dropdown_options array with position and options
   - Generates correct answer in `N:LETTER` format

3. **`transform_summary_table_group()`** - For summary/table questions
   - Combines multiple questions into one
   - Replaces blanks with `[field N]` placeholders
   - Creates summary_fields array with answers and feedback

## Changes Made
- `includes/admin/class-admin.php`: Added transformation functions
- `create-dropdown-paragraph-test.php`: Updated to show correct export format
- `create-summary-completion-test.php`: Updated to show correct export format
- `create-table-completion-test.php`: Updated to show correct export format

## Testing
All three question types now export with the correct format:
- ✓ Dropdown paragraphs have `___N___` placeholders and dropdown_options
- ✓ Summary completion has `[field N]` placeholders and summary_fields
- ✓ Table completion has `[field N]` placeholders and summary_fields

## Notes
- Transformation only happens during XML export
- Doesn't affect how questions are stored or displayed in WordPress
- Questions already in correct format (from text import) are not modified
- Uses improved regex pattern `/_{3,}/` to match only 3+ underscores (avoids false matches)
