# Version 11.10 Visual Confirmation Guide

This document helps you verify that the visual fixes in Version 11.10 are working correctly.

## Change 1: Question Feedback Styling

### What to Look For

When you submit a quiz with open questions (multi-field answers), the feedback should now appear in colored boxes, not just colored numbers.

### ✅ CORRECT Appearance

**For Correct Answers:**
```
┌─────────────────────────────────────────────────────────┐
│ ✓ Question 1: Correct!                                 │
│   Well done! Your answer matches the transcript.       │
│   [Show in transcript]                                 │
└─────────────────────────────────────────────────────────┘
```
- **Entire block** has light green background (very subtle, ~10% opacity)
- **Left border** is bright green (4px thick)
- **"Question 1:"** text is dark green
- **Checkmark (✓)** is bright green
- **Padding** around all text (12px)

**For Incorrect Answers:**
```
┌─────────────────────────────────────────────────────────┐
│ ✗ Question 1: The correct answer is: LONDON           │
│   Review the transcript to find where this is          │
│   mentioned. [Show in transcript]                      │
└─────────────────────────────────────────────────────────┘
```
- **Entire block** has light red background (very subtle, ~10% opacity)
- **Left border** is bright red (4px thick)
- **"Question 1:"** text is dark red
- **Cross (✗)** is bright red
- **Padding** around all text (12px)

### ❌ WRONG Appearance (Old Version)

If you still see this, the CSS changes haven't loaded:
```
✓ Question 1: Correct! Well done!
```
- No background color
- No border
- No padding
- Only the ✓ icon and "Question 1:" have color
- Rest of text is plain black

### How to Test

1. Open any quiz with listening questions (open question type)
2. Fill in some answers (some correct, some wrong, leave some empty)
3. Submit the quiz
4. Scroll down to see feedback messages
5. Look for the colored background boxes

## Change 2: Transcript Answer Highlighting

### What to Look For

When you click "Show in transcript" or view transcripts after submitting, the yellow highlighting should appear **only on the actual answer**, not on entire sentences.

### ✅ CORRECT Appearance

**Example 1: Short answer embedded in sentence**
```
Woman: Yes of course. It's Q1 Anne Hawberry, and I live in London.
                           └────────────┘
                           Only "Anne Hawberry" is yellow
```

**Example 2: Answer with comma**
```
Man: The program costs Q4 £7.95, which includes...
                        └────┘
                        Only "£7.95" is yellow
```

**Example 3: Answer with period and new sentence**
```
Woman: I arrived Q1 two months ago. Since then, I've...
                   └────────────┘
                   Only "two months ago" is yellow
```

**Example 4: Numeric answer**
```
It takes Q5 three weeks to complete.
          └──────────┘
          Only "three weeks" is yellow
```

### ❌ WRONG Appearance (Old Version)

If you see this, the PHP changes haven't been applied:

**Old behavior - highlights too much:**
```
Woman: It's Q1 Anne Hawberry, and I live in London. I came here to study.
           └──────────────────────────────────────────────────────────┘
           Entire sentence up to 100 characters is yellow
```

### How to Test

1. Open any listening test that has been completed
2. Look at the transcript section
3. Find the Q1, Q2, Q3 badges (yellow squares with question numbers)
4. Look at the yellow highlighting next to each badge
5. Verify it's highlighting ONLY the answer, not full sentences

## Quick Visual Checklist

Use this checklist to verify both fixes are working:

### Question Feedback
- [ ] Green background box appears for correct answers
- [ ] Red background box appears for incorrect answers  
- [ ] Green left border (4px) on correct feedback
- [ ] Red left border (4px) on incorrect feedback
- [ ] "Question X:" text is green for correct, red for incorrect
- [ ] Padding visible around feedback text
- [ ] Icons (✓ and ✗) are colored and visible

### Transcript Highlighting
- [ ] Yellow Q badges appear in transcript
- [ ] Yellow highlighting appears next to badges
- [ ] Highlighting is SHORT (typically 1-5 words, max ~50 characters)
- [ ] Highlighting stops at commas, semicolons, or periods
- [ ] Multiple answers have individual highlights
- [ ] Can click "Show in transcript" and jump to highlighted answer

## Browser Cache Warning

⚠️ **IMPORTANT:** You must clear your browser cache to see these changes!

### How to Clear Cache

**Chrome/Edge:**
- Press `Ctrl+Shift+Delete` (Windows) or `Cmd+Shift+Delete` (Mac)
- Select "Cached images and files"
- Click "Clear data"
- Refresh the quiz page (`F5` or `Cmd+R`)

**Firefox:**
- Press `Ctrl+Shift+Delete` (Windows) or `Cmd+Shift+Delete` (Mac)
- Select "Cache"
- Click "Clear Now"
- Refresh the quiz page (`F5` or `Cmd+R`)

**Safari:**
- Safari menu → Preferences → Advanced
- Check "Show Develop menu"
- Develop menu → Empty Caches
- Refresh the quiz page (`Cmd+R`)

### Hard Refresh (Alternative)

Instead of clearing cache, try a hard refresh:
- **Windows:** `Ctrl+F5` or `Ctrl+Shift+R`
- **Mac:** `Cmd+Shift+R`

## Troubleshooting

### Issue: No colored backgrounds on feedback

**Possible causes:**
1. Browser cache not cleared → Clear cache and refresh
2. CSS file not updated → Verify `frontend.css` was uploaded
3. CSS/JS minification or caching plugin → Clear server-side cache

**How to verify CSS loaded:**
1. Right-click on page → Inspect
2. Go to "Network" tab
3. Refresh page
4. Look for `frontend.css` in the list
5. Check the "Size" column - should show actual file size, not "(disk cache)"

### Issue: Transcript highlighting still shows full sentences

**Possible causes:**
1. Template files not updated → Verify `.php` files were uploaded
2. Server-side caching → Clear WordPress cache
3. PHP opcode cache → Restart PHP-FPM or clear OPcache

**How to verify:**
1. View page source of quiz
2. Look for `transcript-answer-marker` spans
3. Check the length of text inside these spans
4. Should be short (under 50 characters typically)

### Issue: Some feedback boxes work, others don't

**Explanation:**
The new styling only applies to **open question** type feedback. Other question types may use different CSS classes. This is expected behavior.

## Success Indicators

You'll know everything is working correctly when:

1. ✅ Feedback messages look like colored "cards" or "boxes" with backgrounds
2. ✅ Transcript highlights are small and precise, not spanning entire sentences
3. ✅ Left borders are visible and colored (green/red)
4. ✅ "Question X:" text is colored to match the feedback type
5. ✅ The layout looks polished and professional

## Need Help?

If something doesn't look right:
1. Take a screenshot showing the issue
2. Note which browser and version you're using
3. Mention which quiz you're testing with
4. Describe what you expected vs what you see
5. Share the screenshot and details with support

---

**Version:** 11.10  
**Date:** January 11, 2026  
**Changes:** Question feedback styling + Transcript highlighting precision
