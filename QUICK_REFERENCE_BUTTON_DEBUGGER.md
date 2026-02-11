# Next Unit Button Debugger - Quick Reference

## Enable Debugger

Add to URL: `?debug_nav=1`

Example: `https://yoursite.com/quiz/?debug_nav=1`

## What It Shows

✅ **Green checkmarks** = Condition is TRUE  
❌ **Red X marks** = Condition is FALSE

## Button Shows When

ALL three conditions are true:
1. ✓ No next item in lesson (last item)
2. ✓ Is last lesson in unit
3. ✓ Has next unit available

## Common Fixes

### "Button should show but doesn't"
- Check browser Console for errors
- Verify CSS file is loaded
- Clear cache
- Check `.button` and `.button-primary` CSS classes

### "Not last lesson but should be"
- Check "All Lessons in Course" list
- Verify lesson order (menu_order)
- Ensure lesson is in correct course

### "No next unit found"
- Check "All Units" list
- Verify units are published
- Check unit order (menu_order)

## Report Issues

Include:
1. Screenshot of debugger
2. Page URL
3. What you expected vs what you see

## Disable Debugger

Remove `?debug_nav=1` from URL
