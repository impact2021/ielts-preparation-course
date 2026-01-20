# Version 12.6 Release Notes

**Release Date:** January 20, 2026

## Summary

Version 12.6 simplifies the plugin by removing deprecated templates and standardizing the answer highlighting system across all exercise types. This update focuses on consistency and maintainability.

## Major Changes

### 1. Template Simplification

**Removed Templates:**
- ❌ `two_column_exercise` - Removed (use `two_column_reading` instead)
- ❌ `one_column_exercise` - Removed (use `two_column_reading` instead)

**Supported Templates:**
- ✅ `two_column_reading` - Reading tests with passage on left, questions on right (now the default)
- ✅ `two_column_listening` - Listening tests with transcript/audio on left, questions on right

**Default Template Changed:**
- Old default: `two_column_exercise`
- New default: `two_column_reading`

### 2. Standardized Answer Highlighting

Both reading and listening templates now use the **same format** for answer highlighting:

**New Standardized Format:**
```html
<span id="passage-q1" data-question="1"></span><span class="reading-answer-marker">highlighted answer</span>
```

**For Listening:**
```html
<span id="transcript-q1" data-question="1"><span class="question-marker-badge">Q1</span></span><span class="reading-answer-marker">highlighted answer</span>
```

**Key Points:**
- Both reading and listening now use `class="reading-answer-marker"`
- The old `class="transcript-answer-marker"` is deprecated
- Reading passages: Use `id="passage-q#"` (no visible badge)
- Listening transcripts: Use `id="transcript-q#"` (with visible Q# badge)

### 3. Backward Compatibility

**Automatic Migration:**
- Exercises with deprecated templates are automatically converted to `two_column_reading`
- Old template values (`computer_based`, `standard`, `listening_practice`, etc.) are mapped to the two supported templates

**Still Supported (Legacy):**
- Old `transcript-answer-marker` class continues to work but should be updated to `reading-answer-marker`

## Breaking Changes

### Admin Interface
- Template dropdown now only shows 2 options instead of 4
- Exercise content section (for `two_column_exercise`) has been removed

### For Content Creators
- Existing exercises with deprecated templates will automatically use `two_column_reading`
- Update any custom scripts or documentation that reference `two_column_exercise` or `one_column_exercise`

## Migration Guide

### For Existing Exercises

**No action required for exercises themselves** - they will automatically use the correct template.

**For transcript/passage markers:**
1. Update `class="transcript-answer-marker"` to `class="reading-answer-marker"` in listening transcripts
2. Ensure all markers follow the standardized format shown above

**Example migration:**
```html
<!-- OLD (still works but deprecated) -->
<span class="transcript-answer-marker">answer</span>

<!-- NEW (recommended) -->
<span class="reading-answer-marker">answer</span>
```

### For JSON Imports

Update the `layout_type` in your JSON files:
```json
{
  "settings": {
    "layout_type": "two_column_reading"  // or "two_column_listening"
  }
}
```

Old values will be automatically migrated:
- `two_column_exercise` → `two_column_reading`
- `one_column_exercise` → `two_column_reading`
- `computer_based` → `two_column_reading` or `two_column_listening` (based on cbt_test_type)

## Updated Documentation

The following documentation files have been updated:
- ✅ `READING_PASSAGE_MARKER_GUIDE.md` - Updated with standardized format
- ✅ `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md` - Updated to use reading-answer-marker
- ✅ `IMPORT_OPTIONS_GUIDE.md` - Removed references to deprecated templates
- ✅ `main/Exercises/multiple choice practice listening.json` - Updated to use new format

## Technical Details

### Files Modified

**Core Files:**
- `ielts-course-manager.php` - Version bumped to 12.6
- `includes/admin/class-admin.php` - Removed deprecated template options, updated default
- `templates/single-quiz-page.php` - Added backward compatibility mapping
- `templates/single-quiz-computer-based.php` - Standardized to use reading-answer-marker

**Documentation:**
- `READING_PASSAGE_MARKER_GUIDE.md` - Updated for v12.6 standardization
- `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md` - Updated for v12.6 standardization
- `IMPORT_OPTIONS_GUIDE.md` - Updated with new version info

**Example Content:**
- `main/Exercises/multiple choice practice listening.json` - Updated to use reading-answer-marker

### Code Changes

**Template Processing:**
```php
// Old default
$layout_type = 'two_column_exercise';

// New default
$layout_type = 'two_column_reading';

// Deprecated templates now mapped
if ($layout_type === 'standard' || $layout_type === 'one_column_exercise' || $layout_type === 'two_column_exercise') {
    $layout_type = 'two_column_reading';
}
```

**Answer Highlighting:**
```php
// Old (different classes for reading vs listening)
$answer_class = $is_reading ? 'reading-answer-marker' : 'transcript-answer-marker';

// New (same class for both)
$answer_class = 'reading-answer-marker';
```

## Benefits

### Simplified Maintenance
- Fewer templates to maintain and test
- Consistent styling across all exercise types
- Reduced code complexity

### Better User Experience
- Consistent answer highlighting behavior
- Predictable URL fragments (/#passage-q1 or /#transcript-q1)
- Same button functionality for all exercise types

### Easier Content Creation
- Single format to remember for answer markers
- Less confusion about which template to use
- Clearer documentation

## Testing

### Verified Scenarios
- ✅ New exercises default to two_column_reading
- ✅ Existing exercises with deprecated templates load correctly
- ✅ Reading passages use passage-q# IDs
- ✅ Listening transcripts use transcript-q# IDs
- ✅ Both use reading-answer-marker class
- ✅ Question badges appear in listening (not in reading)
- ✅ URL fragments work: /#passage-q1 and /#transcript-q1
- ✅ JSON import/export preserves new format

## Upgrade Notes

### For Site Administrators
1. Update plugin to version 12.6
2. No database migration required
3. Review any custom CSS that targets `.transcript-answer-marker` and update to `.reading-answer-marker`

### For Content Creators
1. Review the updated marker guides:
   - `READING_PASSAGE_MARKER_GUIDE.md`
   - `TRANSCRIPT_MARKER_PLACEMENT_GUIDE.md`
2. Update any JSON exercise files to use the new format
3. Plan to migrate old `transcript-answer-marker` references to `reading-answer-marker`

## Known Issues

None at this time.

## Future Considerations

- May add additional specialized templates based on user feedback
- Considering adding visual template selector in admin interface
- Planning improved migration tools for bulk updates of existing content

## Support

For questions or issues with this release:
1. Check the updated documentation guides
2. Review examples in `main/Exercises/multiple choice practice listening.json`
3. Contact support at support@ieltstestonline.com

---

**Previous Version:** 12.5  
**Current Version:** 12.6  
**Next Version:** TBD
