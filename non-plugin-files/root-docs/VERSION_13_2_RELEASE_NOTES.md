# Version 13.2 Release Notes

## Bug Fixes

### Fixed Dropdown Question Answer Validation
- **Issue**: Dropdown questions (`closed_question_dropdown` type) were not correctly identifying correct answers or applying red/green coloring for correct/incorrect responses
- **Root Cause**: Array key type mismatch between JavaScript (string keys from JSON) and PHP (integer key access)
- **Fix**: 
  - Normalized array keys to integers in backend validation to ensure consistent access regardless of JSON encoding
  - Added explicit handling for both string and integer keys when extracting user answers
  - Added `correct_answer_count` field to existing dropdown questions that were missing it

### Technical Details
When answers were collected in JavaScript and sent via JSON, numeric array keys were converted to strings. The PHP backend was accessing these with integer keys, which could fail in strict type checking contexts. The fix ensures that all keys are normalized to integers for consistent validation logic.

## Files Modified
- `includes/class-quiz-handler.php` - Improved answer key handling in dropdown validation
- `main/Paragraph-order-71975858.json` - Added missing `correct_answer_count` fields
- `ielts-course-manager.php` - Version bump to 13.2

## Migration Notes
No database migration required. Existing dropdown questions will automatically benefit from the improved validation logic.

## Date
January 23, 2026
