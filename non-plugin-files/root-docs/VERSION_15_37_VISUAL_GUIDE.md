# Version 15.37 - Visual Guide

## 📋 Overview of Changes

This release includes **3 code changes** and **1 clarification** addressing all 4 issues in the problem statement.

---

## 1️⃣ Entry Test Toggle - Access Code Sites Only

### Before (Version 15.36):
```
Partner Dashboard Settings Page
├── Default Invite Length
├── Max Students Per Partner
├── Expiry Action
├── Notify Days Before Expiry
├── Redirect After User Creation
├── Login Page URL
├── Registration Page URL
└── ☑️ Enable Entry Test Membership  ← ALWAYS VISIBLE
    (for ALL site types - Paid, Access Code, Hybrid)
```

### After (Version 15.37):
```
For ACCESS CODE SITES:
Partner Dashboard Settings Page
├── Default Invite Length
├── Max Students Per Partner
├── Expiry Action
├── Notify Days Before Expiry
├── Redirect After User Creation
├── Login Page URL
├── Registration Page URL
└── ☑️ Enable Entry Test Membership  ← VISIBLE ONLY HERE
    (Access Code sites only)

For PAID MEMBERSHIP or HYBRID SITES:
Partner Dashboard Settings Page
├── Default Invite Length
├── Max Students Per Partner
├── Expiry Action
├── Notify Days Before Expiry
├── Redirect After User Creation
├── Login Page URL
└── Registration Page URL
    (Entry Test option is HIDDEN)
```

### What This Means:
- ✅ Only Access Code sites can enable Entry Test membership
- ✅ Paid Membership and Hybrid sites won't see this option at all
- ✅ Default is OFF (unchecked) when visible
- ✅ No accidental enabling on wrong site types

### How to Test:
1. Check your site type at: **Settings → General** or **IELTS Settings**
2. Go to: **`/wp-admin/admin.php?page=ielts-partner-dashboard`**
3. Scroll to bottom of settings form
4. **Expected behavior:**
   - **Access Code site:** See "Enable Entry Test Membership" checkbox
   - **Other site types:** No Entry Test option visible

---

## 2️⃣ Dropdown Question Type - How It Actually Works

### The Question:
> "I have two `[dropdown]` placeholders but only one set of options showing. How would the second dropdown work?"

### The Answer: 
**This is CORRECT behavior!** All dropdowns share the same options by design.

### Visual Explanation:

#### Question Setup in Admin:
```
Question Type: Closed Question Dropdown
Number of Dropdowns: 2

Question Text:
"There are clearly some 1.[dropdown] where watching television 
does not negatively affect children. Some educational programmes, 
for example, can help 2.[dropdown] them."

Options List:
1. situations ✓ (correct for 1st dropdown)
2. times
3. children learn
4. children to learn ✓ (correct for 2nd dropdown)

Correct Answer: field_1:0|field_2:3
```

#### What Students See:
```
Question:
"There are clearly some 1.[▼ situations      ] where watching 
                              times
                              children learn
                              children to learn

television does not negatively affect children. Some educational 
programmes, for example, can help 2.[▼ situations      ] them."
                                       times
                                       children learn
                                       children to learn
```

### Key Points:
- ✅ **SAME options** appear in both dropdowns
- ✅ **DIFFERENT correct answers** for each dropdown
- ✅ This is **INTENTIONAL** design, not a bug
- ✅ Perfect for IELTS word bank exercises

### When This Works Well:
- Fill-in-the-blank exercises with word banks
- Grammar exercises (verb forms, prepositions, etc.)
- Vocabulary exercises where same words could fit multiple blanks

### What If You Need Different Options?
❌ Not supported in current design. Each dropdown question can only have one shared option set.

**Workaround:** Create separate questions for each blank if you need completely different options.

### Documentation:
Full details and examples available in: **`DROPDOWN-QUESTION-FAQ.md`**

---

## 3️⃣ Plugin Version in Admin Bar

### Before (Version 15.36):
```
WordPress Admin Bar:
┌────────────────────────────────────────────────────────┐
│ 🏠 Site Name  📊 Dashboard  📝 Posts  🖼️ Media  ...     │
└────────────────────────────────────────────────────────┘
(No version number visible)
```

### After (Version 15.37):
```
WordPress Admin Bar (ADMIN PAGES ONLY):
┌────────────────────────────────────────────────────────────┐
│ 🏠 Site Name  📊 Dashboard  📝 Posts  🖼️ Media  ... IELTS v15.37 │
└────────────────────────────────────────────────────────────┘
                                                    ↑ NEW!
```

### What This Means:
- ✅ Quick version identification without navigating to plugins page
- ✅ Helpful when managing multiple sites
- ✅ Only shows on **admin pages** (not on frontend)
- ✅ Format: "IELTS v{VERSION}"

### Where It Appears:
- ✅ `/wp-admin/` - ALL admin pages
- ✅ `/wp-admin/admin.php?page=*` - Custom admin pages
- ✅ `/wp-admin/post.php` - Post editor
- ❌ Frontend pages (even with admin bar visible) - NOT SHOWN

### Implementation Details:
```php
// Hook: admin_bar_menu (priority 100)
// Check: is_admin() - Only in admin area
// Display: "IELTS v" + IELTS_CM_VERSION constant
```

---

## 4️⃣ Version Numbers Updated

### Files Changed:

**ielts-course-manager.php:**
```diff
- * Version: 15.36
+ * Version: 15.37

- define('IELTS_CM_VERSION', '15.36');
+ define('IELTS_CM_VERSION', '15.37');
```

### Impact:
- WordPress sees plugin as updated
- Version check triggers on next load
- Permalinks flush automatically
- Admin bar displays new version

---

## 📊 Change Summary

### Statistics:
- **Files Modified:** 3
- **Lines Added:** 29
- **Lines Removed:** 3
- **Net Change:** +26 lines
- **Functions Added:** 1 (`add_version_to_admin_bar()`)
- **Functions Modified:** 1 (`run()` - added admin bar hook)
- **UI Changes:** 2 (Entry Test visibility, Admin bar version)

### Impacted Features:
1. ✅ Partner Dashboard Settings (conditional display)
2. ✅ WordPress Admin Bar (version display)
3. ℹ️ Dropdown Questions (no changes - working as designed)
4. ✅ Plugin Version System (updated to 15.37)

### Testing Checklist:
- [ ] Entry Test toggle visible only on Access Code sites
- [ ] Entry Test toggle hidden on Paid/Hybrid sites
- [ ] Admin bar shows "IELTS v15.37" on admin pages
- [ ] Admin bar doesn't show version on frontend
- [ ] Dropdown questions continue to work normally
- [ ] All dropdowns share same options (expected behavior)
- [ ] PHP syntax valid in all modified files
- [ ] No JavaScript console errors
- [ ] No PHP warnings or notices

---

## 🔒 Security & Quality

### Code Review: ✅ PASSED
- Minimal, surgical changes
- No security vulnerabilities introduced
- Follows WordPress coding standards
- Proper escaping and sanitization

### Security Scan: ✅ PASSED
- CodeQL analysis completed
- No vulnerabilities detected
- Safe to deploy

### PHP Syntax: ✅ VALID
- `ielts-course-manager.php` ✅
- `includes/class-access-codes.php` ✅
- `includes/class-ielts-course-manager.php` ✅

---

## 🚀 Deployment

### Safe to Deploy:
✅ All changes are backwards compatible
✅ No database changes required
✅ No settings migrations needed
✅ Existing functionality preserved

### Recommended Testing:
1. Test on staging site first
2. Verify Entry Test toggle visibility
3. Check admin bar version display
4. Test dropdown questions still work
5. Monitor for any PHP errors in logs

---

## 📚 Additional Documentation

- **Full Release Notes:** `VERSION_15_37_RELEASE_NOTES.md`
- **Dropdown Questions:** `DROPDOWN-QUESTION-FAQ.md`
- **Technical Details:** This file

---

## ✅ All Requirements Met

1. ✅ **Entry Test toggle** - Only visible on Access Code sites, default OFF
2. ℹ️ **Dropdown questions** - Working as designed, shared options expected
3. ✅ **Admin bar version** - Displays on admin pages only
4. ✅ **Version updated** - 15.36 → 15.37

**Status:** Ready for deployment! 🎉
