# Quick Summary: Version 15.12 Changes

## What Was Fixed

### 1. Listening Exercise "Show me" Button
**Before:** Clicking "Show me" didn't update the URL with #q1, #q2, etc.
**After:** URL now updates with question anchor, allowing users to share direct links to specific questions

**File Changed:** `assets/js/frontend.js` (line 1505)

---

### 2. Partner Dashboard: Welcome Email Copy
**Before:** Partners had no record of student login credentials
**After:** Partners can now receive a copy of the welcome email (checkbox enabled by default)

**What Partners Receive:**
- Student's email address
- Username and password
- Login URL
- Confirmation that student received the email

**Files Changed:** `includes/class-access-codes.php` (form + email function)

---

### 3. Partner Dashboard: Student Count Display
**Before:** Couldn't see how many active/expired students at a glance
**After:** Counts shown in tab buttons: "Active (X)" and "Expired (Y)"

**Files Changed:** `includes/class-access-codes.php`

---

### 4. Partner Dashboard: Cleaner Students Table
**Before:** 7 columns with scattered information
**After:** 4 columns with organized, hierarchical information

#### New Column Structure:
1. **User Details** (compact)
   - Username (bold)
   - Full name (smaller)
   - Email (smaller)

2. **Membership**
   - Course access level

3. **Expiry**
   - Expiry date
   - Last login (smaller)

4. **Actions**
   - Edit (full width)
   - Resend Email (full width)
   - Revoke (full width, red)

**Files Changed:** `includes/class-access-codes.php`

---

### 5. Partner Dashboard: Simplified Codes Filter
**Before:** 4 filter tabs (All, Active, Available, Expired)
**After:** 2 filter tabs (Used [default], Unused)

**Rationale:** "All" showed too much mixed data, "Expired" wasn't useful

**Files Changed:** `includes/class-access-codes.php`

---

### 6. Partner Dashboard: Quick Remaining Places View
**Before:** Had to look elsewhere to see remaining student slots
**After:** Shows "Remaining places: X" right in the section header

**Files Changed:** `includes/class-access-codes.php`

---

### 7. Code Quality Improvements
- Added accessibility attributes (`scope="col"` on table headers)
- Extracted inline styles to CSS class (`.iw-btn-full-width`)
- No syntax errors
- No security vulnerabilities (CodeQL verified)

---

## Version Update
**From:** 15.11 → **To:** 15.12

---

## Impact Summary

### For Students
- Can now share links to specific listening exercise questions
- Better organized feedback display

### For Partners
- Improved student management with cleaner, more readable interface
- Quick access to important metrics (active/expired counts)
- Optional email copy for record-keeping
- Simpler, more focused filtering options
- Better visibility of remaining student slots

### For Developers
- Improved code maintainability
- Better accessibility compliance
- Consistent styling approach

---

## Testing Performed
- ✅ PHP syntax validation (no errors)
- ✅ JavaScript syntax validation (no errors)
- ✅ Code review completed (addressed feedback)
- ✅ CodeQL security scan (no vulnerabilities)
- ✅ All changes committed and pushed

---

## Files Modified
1. `assets/js/frontend.js` (3 lines added)
2. `ielts-course-manager.php` (2 lines changed)
3. `includes/class-access-codes.php` (110 lines changed)

**Total:** 3 files, 115 lines modified

---

## Upgrade Process
1. Update plugin files
2. Clear browser cache
3. Test partner dashboard (if applicable)
4. Test listening exercises

**No database migrations required**
**Backward compatible with existing data**
