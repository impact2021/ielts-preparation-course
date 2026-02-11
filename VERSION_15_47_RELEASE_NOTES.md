# Version 15.47 Release Notes

**Release Date:** February 11, 2026  
**Version:** 15.47  
**Previous Version:** 15.46

## Critical Bug Fix: Next Unit Navigation Button

### Problem
Users completing the final resource/quiz in a unit were unable to see or click the button to move to the next unit, despite the logic correctly identifying the completion state and finding the next unit.

### Root Cause
The issue was in the HTML rendering of the next unit navigation link. While the PHP logic was correct in:
- Detecting when a user finished the last resource of the last lesson in a unit
- Finding the next unit in the course sequence
- Generating the appropriate HTML

The button was using `class="nav-link"` instead of `class="button button-primary"`, which prevented it from being styled as a visible button. The CSS rules in `frontend.css` expected the button classes to be present.

### What Was Fixed

#### Template Changes
Updated three template files to properly display the next unit button:

1. **templates/single-resource-page.php**
2. **templates/single-quiz.php**
3. **templates/single-quiz-computer-based.php**

#### Specific Changes
**Before:**
```php
<a href="<?php echo esc_url(get_permalink($next_unit->ID)); ?>" class="nav-link">
    <?php printf(__('That is the end of this unit. Move on to Unit %s', 'ielts-course-manager'), esc_html($unit_number)); ?>
</a>
```

**After:**
```php
<span><?php _e('That is the end of this unit', 'ielts-course-manager'); ?></span>
<a href="<?php echo esc_url(get_permalink($next_unit->ID)); ?>" class="button button-primary">
    <?php printf(__('Move to Unit %s', 'ielts-course-manager'), esc_html($unit_number)); ?>
</a>
```

#### Key Improvements
1. **Separated message from button** - The completion message ("That is the end of this unit") is now in a `<span>`, while the action button is separate
2. **Added button classes** - Changed from `class="nav-link"` to `class="button button-primary"` to ensure proper WordPress button styling
3. **Simplified button text** - Changed from "That is the end of this unit. Move on to Unit X" to just "Move to Unit X" for clearer call-to-action
4. **Consistent implementation** - Applied the same fix across all three template types (resources, regular quizzes, computer-based quizzes)

### Visual Result

**Before Fix:**
```
┌──────────────────────────────────────┐
│  That is the end of this unit        │
│  (Button was invisible)              │
└──────────────────────────────────────┘
```

**After Fix:**
```
┌──────────────────────────────────────┐
│  That is the end of this unit        │
│                                      │
│  ┌────────────────────────────┐     │
│  │  Move to Unit 2            │     │
│  └────────────────────────────┘     │
└──────────────────────────────────────┘
```

### CSS Support
The existing CSS from version 15.44 already supports the button styling:

```css
.nav-completion-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    /* ... styling ... */
}

.nav-completion-message a.button {
    margin-top: 0;
}
```

This CSS was designed to stack elements vertically with proper spacing, which now works correctly with the button classes.

### Files Modified
1. `templates/single-resource-page.php` - Fixed next unit button on resource pages
2. `templates/single-quiz.php` - Fixed next unit button on regular quiz pages
3. `templates/single-quiz-computer-based.php` - Fixed next unit button on computer-based quiz pages
4. `ielts-course-manager.php` - Updated version to 15.47

### Impact
- ✅ Users can now clearly see the "Move to Unit X" button when completing a unit
- ✅ Seamless course navigation restored
- ✅ Improved user experience and reduced confusion
- ✅ Consistent behavior across resources and quizzes

### Testing Verification
To verify this fix:
1. Log in as a student enrolled in a course
2. Navigate to a unit with multiple lessons
3. Complete all resources/quizzes in the unit
4. On the final resource/quiz, verify you see:
   - A completion message: "That is the end of this unit"
   - A clearly visible button: "Move to Unit X" (where X is the next unit number)
5. Click the button to verify it navigates to the next unit

### Notes
- This fix completes the work started in version 15.44, which added the CSS support
- The PHP logic for detecting last resources and finding next units was already correct
- Only the HTML rendering needed adjustment to use proper button classes

### Upgrade Notes
- No database changes required
- No configuration changes needed
- Changes take effect immediately upon plugin update
- Compatible with all existing themes and customizations

## Version History
- **15.47** - Fixed next unit button visibility (current)
- **15.46** - Previous version
- **15.44** - Added CSS support for vertical button stacking
