# Dropdown Question Validation Fix - Version 13.3

## Problem Summary

The `closed_question_dropdown` question type was incorrectly marking all answers as incorrect (zero points, red border) even when the correct answer was selected. This issue affected all dropdown questions imported from JSON files.

## Root Cause

The bug had TWO critical issues:

### Issue 1: Missing Transformation Logic
When questions are created via the WordPress admin UI, the system automatically transforms the `correct_answer` field from a simple index format (e.g., `"0"`) to a field format (e.g., `"field_1:0"`). However, when questions are imported from JSON:

1. **Admin Save Path**: Transforms `"0"` → `"field_1:0"` ✅
2. **Import Path**: Does NOT transform (left as `"0"`) ❌
3. **Validation Path**: Expects `"field_1:0"` format using regex `/field_(\d+)/`

Result: The regex fails to match, validation gets no correct answer indices, and ALL answers are marked incorrect.

### Issue 2: PHP empty() Gotcha
The code used `!empty($question['correct_answer'])` to check if the correct_answer exists. However, in PHP, the string `"0"` is falsy, so:
- `empty("0")` returns `true` 
- The transformation logic would never run even if it existed

## Solution

Added transformation logic in the `transform_json_questions_to_admin_format()` function (class-admin.php, lines 7518-7584):

1. **Detects simple format**: Checks if `correct_answer` contains `"field_"` string
2. **Counts dropdowns**: Uses regex to count `[dropdown]` markers in question text
3. **Transforms format**:
   - Single dropdown: `"0"` → `"field_1:0"`
   - Multi-dropdown: `"0|1|2"` → `"field_1:0|field_2:1|field_3:2"`
4. **Fixed PHP issue**: Changed `!empty()` to `!== ''` to handle "0" correctly
5. **Prevents double-transformation**: Checks if already in field format before transforming

## Changes Made

### File: `includes/admin/class-admin.php`
- **Lines 7518-7584**: Added `elseif ($type === 'closed_question_dropdown')` block with transformation logic
- **Line 7535**: Changed condition from `!empty()` to `!== ''` to handle "0" string correctly

### File: `ielts-course-manager.php`
- **Line 6**: Updated version from 13.2 to 13.3
- **Line 23**: Updated constant `IELTS_CM_VERSION` from '13.2' to '13.3'

## Testing

### Test 1: Single Dropdown
```json
{
  "type": "closed_question_dropdown",
  "question": "This is a [dropdown]. The correct answer is First of all.",
  "correct_answer": "0"
}
```
- ✅ BEFORE: `"correct_answer": "0"` → validation fails
- ✅ AFTER: Transforms to `"field_1:0"` → validation passes

### Test 2: Multi-Dropdown
```json
{
  "type": "closed_question_dropdown",
  "question": "First [dropdown], second [dropdown], third [dropdown].",
  "correct_answer": "0|1|2"
}
```
- ✅ BEFORE: `"correct_answer": "0|1|2"` → validation fails
- ✅ AFTER: Transforms to `"field_1:0|field_2:1|field_3:2"` → validation passes

### Test 3: Already Transformed
```json
{
  "type": "closed_question_dropdown",
  "correct_answer": "field_1:0"
}
```
- ✅ No double-transformation occurs

## Impact

This fix resolves the critical validation bug for:
- All existing JSON files with `closed_question_dropdown` questions
- All future JSON imports
- All dropdown questions where the correct answer has index 0

## Backward Compatibility

- ✅ Existing questions created via admin UI: No change (already in correct format)
- ✅ Existing imported questions: Will be transformed on next edit/import
- ✅ New imported questions: Will be transformed automatically

## Version Update

**Previous Version**: 13.2  
**Current Version**: 13.3

---

**Fix Date**: 2026-01-23  
**Bug Severity**: Critical  
**Affected Component**: closed_question_dropdown validation  
**Status**: ✅ RESOLVED
