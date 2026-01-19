# Version 12.3 Release Notes

**Release Date**: January 19, 2026  
**Type**: Bug Fix

## 🐛 Bug Fixes

### Dropdown Questions - Color Indicators
**Fixed**: Dropdown questions (`closed_question_dropdown` type) now display color indicators when answers are correct or incorrect after quiz submission.

#### What Changed:
- ✅ Correct dropdown answers now show **green border and light green background** (#4caf50)
- ✅ Incorrect dropdown answers now show **red border and light red background** (#f44336)
- ✅ Navigation buttons correctly show green (correct) or red (incorrect) for each dropdown field
- ✅ Behavior now matches other question types (multiple choice, text input, etc.)

#### Technical Details:
- Updated `assets/js/frontend.js` to handle `closed_question_dropdown` type in quiz submission callback
- Added three new code blocks for correct/incorrect answer visual feedback
- Utilizes existing CSS classes that were already defined but not being applied

#### Files Changed:
- `assets/js/frontend.js` - Added dropdown question feedback handling
- `ielts-course-manager.php` - Version bump to 12.3
- `README.md` - Updated version number

## 📝 Documentation

### New Documentation Files:
- `DROPDOWN_COLOR_INDICATOR_FIX.md` - Detailed technical documentation of the fix

## 🧪 Testing

Tested with:
- Single dropdown questions
- Multiple dropdown questions (2-3 dropdowns)
- All quiz layouts (standard, computer-based, listening practice, listening exercise)
- Mixed correct/incorrect answers

Test file: `TEMPLATES/example-dropdown-closed-question.json`

## 🔄 Compatibility

- ✅ Compatible with all existing quiz layouts
- ✅ No breaking changes
- ✅ Works with WordPress 5.8+
- ✅ Works with PHP 7.2+

## 📦 Upgrade Notes

No special upgrade steps required. This is a minor bug fix release that adds missing visual feedback to dropdown questions.

---

**Previous Version**: 12.2  
**Current Version**: 12.3
