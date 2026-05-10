# Version 15.37 Release Notes

## Changes Implemented

### 1. Entry Test Membership Toggle - Access Code Sites Only ✅

**What Changed:**
- The "Enable Entry Test Membership" option in the Partner Dashboard Settings now only appears for Access Code sites
- This option is hidden for Paid Membership and Hybrid sites
- Default state is OFF (unchecked) as required

**Location:**
- `/wp-admin/admin.php?page=ielts-partner-dashboard`

**Technical Details:**
- Added conditional check for `ielts_cm_access_code_enabled` option
- Only renders the Entry Test toggle row when the site is in Access Code mode
- No changes to functionality - just visibility control

---

### 2. Dropdown Question Type - How It Works ℹ️

**User Question:**
> "I have this example with two `[dropdown]` placeholders, but there's only one set of options showing. How would the second dropdown work?"

**Answer:**
This is working **exactly as designed**. The dropdown question type is intentionally built so that **all dropdowns in a question share the same set of options**.

**Your Example:**
```
<strong>Examiner:</strong> Do you think parents should allow children to watch television?
<strong>Candidate:</strong> Well, that's an interesting question. There are clearly some 1.[dropdown] 
where watching television does not negatively affect children. Some educational programmes, for 
example, can help 2.[dropdown] them.
```

**How It Works:**
1. **Both dropdowns show the SAME options:**
   - situations
   - times
   - children learn
   - children to learn

2. **Different correct answers for each dropdown:**
   - First dropdown (1.[dropdown]): Correct answer is "situations" (index 0)
   - Second dropdown (2.[dropdown]): Correct answer is "children to learn" (index 3)

3. **This is specified in the `correct_answer` field:**
   - Format: `"field_1:0|field_2:3"`
   - Meaning: "First dropdown uses option 0, second dropdown uses option 3"

**Why This Design?**
This approach is perfect for IELTS-style exercises where students choose from the same word bank to complete multiple blanks. It's documented in `DROPDOWN-QUESTION-FAQ.md` (lines 147-179) with this exact example.

**If You Need Different Options:**
The current system doesn't support different option sets per dropdown. All dropdowns in a question must share the same options. This is a design decision, not a bug.

**No Code Changes Needed** - This feature is working as intended.

---

### 3. Plugin Version in Admin Bar ✅

**What Changed:**
- Plugin version now displays in the WordPress admin bar
- Shows as "IELTS v15.37" in the top black bar
- Only visible on admin pages (not on frontend)

**Why This Is Useful:**
- Quick version identification without navigating to plugins page
- Helpful when managing multiple sites to quickly see which version is installed

**Technical Details:**
- Uses WordPress `admin_bar_menu` hook with priority 100
- Only displays when `is_admin()` is true (admin pages only)

---

### 4. Version Numbers Updated ✅

**What Changed:**
- Plugin header version: 15.36 → **15.37**
- `IELTS_CM_VERSION` constant: 15.36 → **15.37**

---

## Files Changed

1. **ielts-course-manager.php**
   - Updated version header and constant
   
2. **includes/class-access-codes.php**
   - Added conditional display logic for Entry Test toggle
   
3. **includes/class-ielts-course-manager.php**
   - Added admin bar version display functionality

---

## Testing Recommendations

### Test Entry Test Toggle Visibility:
1. Go to **Settings → General** (or IELTS Settings)
2. Check the current site type
3. Go to **Partner Dashboard Settings**
4. **If Access Code site:** Entry Test toggle should be visible
5. **If Paid Membership or Hybrid:** Entry Test toggle should be hidden

### Test Admin Bar Version:
1. Log in to WordPress admin
2. Look at the top black admin bar
3. Should see "IELTS v15.37" displayed
4. Go to frontend (while logged in)
5. Version should NOT appear in the admin bar on frontend

### Test Dropdown Questions:
1. Create or edit a quiz
2. Add a "Closed Question Dropdown" question type
3. Add text with multiple `[dropdown]` placeholders
4. Add your option set (same options will appear in all dropdowns)
5. Set correct answers using the format `field_1:X|field_2:Y`
6. Preview quiz to confirm all dropdowns show the same options

---

## Security Review

✅ **No security vulnerabilities detected**
- Code review completed
- CodeQL security scan completed
- All changes follow WordPress security best practices

---

## Summary

All four requirements from the problem statement have been successfully addressed:

1. ✅ Entry Test toggle now only appears for Access Code sites, default OFF
2. ℹ️ Dropdown questions work as designed - shared options is expected behavior
3. ✅ Plugin version displays in admin bar on admin pages
4. ✅ Version numbers updated to 15.37

**Total Lines Changed:** 29 insertions, 3 deletions across 3 files
**Impact:** Minimal, surgical changes to existing functionality
