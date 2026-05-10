# Quick Reference: v15.47 Fix

## What Was Fixed
**Next unit button not showing at end of units**

## The Change
```diff
- <a href="..." class="nav-link">
-     That is the end of this unit. Move on to Unit 2
- </a>

+ <span>That is the end of this unit</span>
+ <a href="..." class="button button-primary">
+     Move to Unit 2
+ </a>
```

## Files Changed
1. `templates/single-resource-page.php`
2. `templates/single-quiz.php`
3. `templates/single-quiz-computer-based.php`
4. `ielts-course-manager.php` (version → 15.47)

## Testing
1. Complete all items in a unit
2. Check last item shows:
   - ✅ Message: "That is the end of this unit"
   - ✅ Button: "Move to Unit X"
3. Click button → goes to next unit

## Status
✅ **COMPLETE**
- Code review: Passed
- Security scan: Passed
- Documentation: Complete
- Ready for deployment

## Documentation
- `VERSION_15_47_RELEASE_NOTES.md` - Full release notes
- `FIX_EXPLANATION_NEXT_UNIT_BUTTON_V15_47.md` - Technical details
- `VISUAL_GUIDE_V15_47.md` - Visual guide
- `COMPLETE_FIX_SUMMARY_V15_47.md` - Executive summary
