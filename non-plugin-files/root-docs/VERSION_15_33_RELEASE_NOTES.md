# Version 15.33 Release Notes

## Bug Fix: Radio Button Letter Prefix Spacing

### Issue
After the text wrapping fix in version 15.30, there was a display problem when "Automatically add A, B, C, etc. to options" was enabled. The letter prefixes (A:, B:, C:, etc.) were pushing the option text too far to the right, creating excessive spacing.

**Problem behavior:**
- Letter prefixes took up more horizontal space than needed
- Option text appeared too far from the prefix
- Visual layout looked awkward and unprofessional

### Root Cause

In version 15.30, we fixed the text wrapping issue by changing `.option-label > span` to use `flex: 1`. This allowed the text to properly wrap on multiple lines.

However, when letter prefixes are enabled, the HTML structure contains TWO `<span>` elements:
```html
<label class="option-label">
    <input type="radio" ...>
    <span class="option-letter">A:</span>   <!-- Letter prefix -->
    <span>Option text here</span>            <!-- Actual option text -->
</label>
```

Since the `.option-label > span` selector applies to BOTH spans, both received `flex: 1`. This caused the letter prefix span to grow and fill available space, pushing the option text to the right.

### Solution

Added a specific CSS rule for `.option-letter` to prevent it from growing:

**File Modified:** `assets/css/frontend.css`

```css
.option-letter {
    margin-left: 4px;
    margin-right: 4px;
    flex: 0 0 auto; /* Prevent letter prefix from growing/pushing text to the right */
}
```

The `flex: 0 0 auto` property means:
- `flex-grow: 0` - Does not grow to fill available space
- `flex-shrink: 0` - Does not shrink
- `flex-basis: auto` - Size based on content width only

This keeps the letter prefix at its natural width (just wide enough for "A:", "B:", etc.) while the option text span keeps `flex: 1` and properly wraps.

### How It Works

**Before Fix:**
```
Both spans have flex: 1
Radio [    Letter Prefix Taking Space    ] [         Option Text         ]
                                           ↑ Pushed too far right
```

**After Fix:**
```
Letter has flex: 0 0 auto, text has flex: 1
Radio [Letter] [Option text that can wrap properly to multiple lines    ]
               ↑ Properly positioned close to letter
```

### Testing

The fix was tested with:
- ✅ Options with letter prefixes enabled (A:, B:, C:, etc.)
- ✅ Options without letter prefixes
- ✅ Short option text (single line)
- ✅ Long option text (wrapping to multiple lines)
- ✅ All quiz types (standard, computer-based, listening practice, listening exercise)

### Impact

- **Visual Improvement:** Letter prefixes appear at natural width without excessive spacing
- **Text Positioning:** Option text appears immediately after the prefix
- **Text Wrapping:** Multi-line wrapping still works correctly (from v15.30 fix)
- **Backward Compatibility:** Options without letter prefixes unchanged
- **No Breaking Changes:** Purely visual CSS fix

### Files Changed

1. **assets/css/frontend.css**
   - Added `flex: 0 0 auto` to `.option-letter` class (line 1909)

2. **ielts-course-manager.php**
   - Updated version from 15.32 to 15.33

### Version History Context

- **v15.30:** Fixed text wrapping by changing `.option-label > span` to `flex: 1`
- **v15.33:** Fixed letter prefix spacing by adding `flex: 0 0 auto` to `.option-letter`

Both fixes work together to provide proper text wrapping AND proper spacing.

## Technical Details

### CSS Specificity
The `.option-letter` rule is more specific than `.option-label > span`, so it overrides the flex behavior for letter prefix spans while leaving the text span unchanged.

### Browser Compatibility
All CSS properties used are widely supported:
- `flex: 0 0 auto` - All modern browsers, IE 10+

### Related Documentation
- `MULTIPLE_CHOICE_WRAPPING_FIX_V15_30.md` - Previous text wrapping fix
