# Radio Button Option Letter Spacing Fix - Version 15.41

## Problem Statement
When "Automatically add A, B, C, etc. to options" is enabled for multiple choice radio buttons, the option text appeared **WAY too far to the right**. The user reported that disabling flex in the browser inspector fixed the spacing, but that would break the text wrapping functionality for long options.

## Root Cause
The `.ielts-single-quiz .option-label` container used `gap: 10px` for flex spacing. While this is convenient, it creates spacing between **ALL** flex children:

```
Radio Button → [10px gap] → Option Letter → [10px gap] → Option Text
```

This resulted in 20+ pixels of spacing before the text starts, making the text appear excessively far to the right.

## HTML Structure
```html
<label class="option-label">
    <input type="radio" ...>
    <span class="option-letter">A:</span>
    <span>Option text here</span>
</label>
```

## Solution
Removed the `gap` property and used targeted `margin-right` instead:

### CSS Changes (assets/css/frontend.css)

**1. Removed gap from container:**
```css
.ielts-single-quiz .option-label {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    /* gap: 10px; ← REMOVED */
    padding: 10px;
    /* ... */
}
```

**2. Added margin to radio button:**
```css
.ielts-single-quiz .option-label input[type="radio"] {
    margin: 0 10px 0 0;  /* Changed from margin: 0 */
    flex-shrink: 0;
    align-self: flex-start;
}
```

**3. Removed left margin from option letter:**
```css
.option-letter {
    /* margin-left: 4px; ← REMOVED */
    margin-right: 4px;
    flex: 0 0 auto;
}
```

## Result
New spacing layout:
```
Radio Button → [10px margin] → Option Letter → [4px margin] → Option Text
```

Total spacing: **14px** instead of 20px+, with the majority being between radio and letter (not before text).

## Benefits
✅ **Proper Spacing:** Text appears close to letter prefix  
✅ **Text Wrapping:** Still works perfectly (flex: 1 preserved on text span)  
✅ **Backward Compatible:** Options without letters still work correctly  
✅ **No Breaking Changes:** Purely visual CSS fix  
✅ **Targeted Fix:** Only affects `.ielts-single-quiz` (other quiz types were already correct)

## Why Other Quiz Types Don't Have This Issue
Computer-based quiz and listening quiz types never used `gap` - they already used `margin-right` on the radio button:

```css
.ielts-computer-based-quiz .option-label input[type="radio"] {
    margin-right: 10px;  /* ← Already correct */
    flex-shrink: 0;
    align-self: flex-start;
}
```

## Testing
Created a visual comparison showing:
- **OLD:** Excessive spacing with gap property
- **NEW:** Proper spacing with margin approach
- **Verification:** Text wrapping still works for long options

## Files Modified
- `assets/css/frontend.css` (3 changes)

## Version History
- **v15.30:** Fixed text wrapping by changing option text to `flex: 1`
- **v15.33:** Fixed letter prefix spacing by adding `flex: 0 0 auto` to `.option-letter`
- **v15.41:** **THIS FIX** - Fixed excessive spacing by removing `gap` and using targeted margins

All three fixes work together to provide:
1. Proper text wrapping (v15.30)
2. Letter prefix doesn't grow (v15.33)
3. Minimal, clean spacing (v15.41)

## Browser Compatibility
All CSS properties used are widely supported:
- `margin` properties - All browsers
- `flex` properties - All modern browsers, IE 10+

## Impact
- **Visual Improvement:** Clean, professional appearance
- **User Experience:** Text is readable and properly positioned
- **Consistency:** All quiz types now have similar spacing behavior
- **No Regression:** Text wrapping functionality fully preserved
