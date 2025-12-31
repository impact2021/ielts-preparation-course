# Version 10.0 Migration Guide

## Overview
Version 10.0 introduces a simplified layout type system and fixes critical feedback display issues. **No manual migration is required** - all changes are automatically handled by the plugin.

## What's Changed

### Visual Changes You'll Notice

#### 1. Admin Interface (Exercise Editor)
**Before:**
```
Layout Type: [Dropdown]
  - Standard Layout
  - Computer-Based IELTS Layout (Two Columns)

[When Computer-Based is selected]
Test Type: (●) This is for the reading test  ( ) This is for the listening test
☐ Open as Popup/Fullscreen Modal
```

**After:**
```
Layout Type: [Dropdown]
  - 2 Column Reading Test
  - 2 Column Listening Test
  - 2 Column Exercise
  - 1 Column Exercise

[When any 2-column option is selected]
☑ Open as Popup/Fullscreen Modal
```

#### 2. Feedback Display (Student View)
**Before:** Correct answers appeared white/blank in two-column layouts ❌

**After:** Correct answers appear green, incorrect answers appear red ✅

### Default Settings for New Exercises
- **Layout Type:** 2 Column Exercise (was: Standard Layout)
- **Popup Modal:** Enabled by default (was: Disabled)

## Automatic Conversion

When you open an existing exercise for editing, it will be automatically converted:

| Old Layout | Old Test Type | → | New Layout |
|------------|---------------|---|------------|
| Standard Layout | N/A | → | 1 Column Exercise |
| Computer-Based | Reading | → | 2 Column Reading Test |
| Computer-Based | Listening | → | 2 Column Listening Test |
| Computer-Based | (none) | → | 2 Column Exercise |
| Listening Practice | N/A | → | 2 Column Listening Test |
| Listening Exercise | N/A | → | 2 Column Listening Test |

**Important:** The conversion only happens when you save the exercise. Until then, the old settings remain in the database.

## What You Need to Do

### Immediate Actions (Optional)
Nothing is required, but you may want to:

1. **Review Your Exercises**
   - Open the Exercises list in WordPress admin
   - Edit a few exercises to verify they display correctly
   - The layout type will be automatically converted when you view the edit screen

2. **Test Student Experience**
   - View exercises as a student
   - Verify feedback colors display correctly (green/red)
   - Test popup/fullscreen functionality

### Recommended Actions (For Best Experience)

1. **Update Exercise Descriptions**
   - If you have documentation that mentions "Computer-Based Layout" or "Test Type"
   - Update it to reference the new layout type names

2. **Train Content Creators**
   - Inform your team about the new simplified interface
   - Share the new layout type options:
     - Use "2 Column Reading Test" for exercises with reading passages
     - Use "2 Column Listening Test" for exercises with audio
     - Use "2 Column Exercise" for general practice without specific content
     - Use "1 Column Exercise" for simple question-only exercises

3. **Gradually Update Exercises**
   - While not required, you may want to edit and re-save exercises over time
   - This will update them to use the new layout type values in the database
   - Only do this if you're making other changes anyway

## Troubleshooting

### Issue: Exercise doesn't display correctly after update

**Solution:**
1. Edit the exercise in WordPress admin
2. Check the Layout Type dropdown
3. Select the appropriate layout type (it should already be selected correctly)
4. Click "Update" to save
5. View the exercise again on the frontend

### Issue: Feedback colors still not showing correctly

**Solution:**
1. Clear your browser cache
2. Clear any WordPress caching plugins
3. Clear CDN cache if applicable
4. Hard reload the page (Ctrl+F5 or Cmd+Shift+R)

### Issue: Old exercises show wrong layout type in admin

**Solution:**
This is expected! The conversion happens automatically when you load the edit screen. Simply:
1. Open the exercise editor
2. The layout type will be auto-converted
3. Save the exercise if you want to persist the new value

## Technical Notes (For Developers)

### Database Fields
- `_ielts_cm_layout_type`: Stores the layout type
  - New values: `one_column_exercise`, `two_column_exercise`, `two_column_reading`, `two_column_listening`
  - Old values still work: `standard`, `computer_based`, `listening_practice`, `listening_exercise`
  
- `_ielts_cm_cbt_test_type`: No longer used for new exercises
  - Still read for backward compatibility with `computer_based` layouts
  - Not saved for new exercises

- `_ielts_cm_open_as_popup`: Still used, now defaults to '1'

### Code Hooks
No new hooks were added in this version. Existing hooks continue to work.

### Template Overrides
If you've overridden any quiz templates in your theme:
- Review the changes in the plugin's template files
- Update your overrides to use `$layout_type` instead of `$cbt_test_type`
- Map layout types: check for `two_column_listening` instead of `cbt_test_type === 'listening'`

### CSS Classes
No changes to CSS classes. All styling remains compatible.

## Rollback Instructions

If you need to rollback to version 9.2:

1. Deactivate the plugin
2. Replace plugin files with version 9.2
3. Reactivate the plugin

**Note:** Exercises edited in version 10.0 will display correctly in 9.2 because old code handles unknown layout types by defaulting to standard layout.

## Support

If you encounter any issues:
1. Check the VERSION_10_SUMMARY.md file for detailed technical information
2. Review the WordPress admin documentation
3. Open an issue on GitHub
4. Contact support

## Frequently Asked Questions

**Q: Do I need to re-create my exercises?**
A: No! All existing exercises work automatically.

**Q: Will my students see any difference?**
A: Yes - feedback colors will now display correctly (green for correct, red for incorrect).

**Q: Can I still use the old "Standard Layout"?**
A: It's now called "1 Column Exercise" but functions the same way.

**Q: What if I have hundreds of exercises?**
A: They'll all work automatically. No bulk update needed.

**Q: Will XML imports/exports still work?**
A: Yes! The plugin maintains full compatibility with both old and new layout type values.

**Q: Is there a performance impact?**
A: No. The conversion is lightweight and happens in-memory.

---

**Version:** 10.0  
**Last Updated:** 2024  
**Minimum Requirements:** WordPress 5.8+, PHP 7.2+
