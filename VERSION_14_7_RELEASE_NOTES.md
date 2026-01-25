# IELTS Course Manager - Version 14.7 Release Notes

## Overview
This release addresses four critical issues related to membership management, access control, and user experience.

---

## 🔧 Issue 1: Allow Direct Payment Without Free Trial

**Problem:** Users were forced to start with a free trial before being able to purchase full membership.

**Solution:** 
- Modified the registration form to display both trial and paid membership options
- Added clear visual separation using optgroups:
  - "Free Trial Options" - for immediate access
  - "Full Membership (Payment Required)" - with pricing displayed
- Updated validation logic to allow paid memberships during registration
- Implemented redirect to payment page after registration for paid memberships

**Technical Details:**
- Added pending payment tracking with user meta fields:
  - `_ielts_cm_membership_type_pending` - stores selected membership type
  - `_ielts_cm_membership_payment_pending` - flag for pending payment
- These fields should be processed by payment gateway webhook handlers

**Files Changed:** `includes/class-shortcodes.php`

---

## 🐛 Issue 2: Fix Expired Trial Membership Access Bug (CRITICAL SECURITY FIX)

**Problem:** Users with expired trial memberships could still access courses. This was a massive security issue.

**Root Cause:** The expiry check in `is_enrolled()` method only ran when `ielts_cm_membership_enabled` option was true. This allowed expired trial users to bypass access restrictions.

**Solution:**
- Removed the conditional check on membership system being enabled
- Expiry validation now **ALWAYS** runs if a user has an expiry date set
- Added security comments documenting the fix

**Code Change:**
```php
// BEFORE - Bug allowed expired users access
if (get_option('ielts_cm_membership_enabled')) {
    // Check expiry...
}

// AFTER - Security fix
// SECURITY FIX: Always check membership expiry if user has an expiry date set
$membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
$expiry_date = get_user_meta($user_id, '_ielts_cm_membership_expiry', true);

if (!empty($membership_type) && !empty($expiry_date)) {
    // Check expiry...
}
```

**Impact:** This fix ensures that expired memberships are properly enforced regardless of system settings.

**Files Changed:** `includes/class-enrollment.php`

---

## ✨ Issue 3: New Band Score Table Shortcode

**Feature:** Added a new shortcode to display user's estimated IELTS band scores in a table format.

**Shortcode:** `[ielts_band_scores]`

**Parameters:**
- `skills` - Comma-separated list of skills to display (default: "reading,listening,writing,speaking")
- `title` - Custom title for the table (default: "Your Estimated IELTS Band Scores")

**Usage Examples:**
```
[ielts_band_scores]

[ielts_band_scores title="My Current IELTS Level"]

[ielts_band_scores skills="reading,listening" title="My Receptive Skills"]
```

**Features:**
- Displays band scores (0.5 to 9.0 scale) based on quiz performance
- Shows "—" for skills with no test data
- Responsive 4-column layout (desktop) that adapts to mobile
- Beautiful gradient styling with clear visual hierarchy
- Includes helpful note about estimation accuracy

**Technical Implementation:**
- Uses existing `get_user_skill_scores()` method from `IELTS_CM_Gamification` class
- Converts percentage scores to band scores using standard IELTS mapping:
  - 95%+ = Band 9.0
  - 90%+ = Band 8.5
  - 85%+ = Band 8.0
  - 80%+ = Band 7.5
  - 70%+ = Band 7.0
  - 65%+ = Band 6.5
  - 60%+ = Band 6.0
  - And so on...

**Files Changed:** `includes/class-shortcodes.php`

---

## 🔢 Issue 4: Version Number Update

**Change:** Updated plugin version from **14.6** to **14.7**

**Updated Locations:**
1. Plugin header comment (line 6)
2. `IELTS_CM_VERSION` constant (line 23)

**Files Changed:** `ielts-course-manager.php`

---

## 📊 Technical Summary

### Files Modified
- `ielts-course-manager.php` - Version bump
- `includes/class-enrollment.php` - Security fix for expiry check
- `includes/class-shortcodes.php` - Payment options and band score shortcode

### Code Statistics
- Lines Added: 329
- Lines Removed: 47
- Net Change: +282 lines
- Files Changed: 3

### Quality Assurance
- ✅ PHP syntax validation passed
- ✅ Code review completed
- ✅ Security review completed
- ✅ All changes tested for syntax errors

---

## 🚀 Migration Notes

### For Site Administrators

**No action required** for most sites. This is a backwards-compatible update.

**If you have custom payment integration:**
- Payment webhook handlers should check for and process these user meta fields:
  - `_ielts_cm_membership_type_pending`
  - `_ielts_cm_membership_payment_pending`
- On successful payment, move pending membership to active membership

**If you want to use the new band scores shortcode:**
- Add `[ielts_band_scores]` to any page or post
- Users must be logged in and have completed some quizzes to see scores

---

## 🔒 Security Impact

**Critical Fix:** The expired trial membership bug has been resolved. This was a security vulnerability that allowed unauthorized access to paid content.

**Risk Level:** HIGH  
**Impact:** Users with expired trial memberships can no longer access courses  
**Recommendation:** Update immediately

---

## 📝 Developer Notes

### New User Meta Fields
```php
// Stores pending membership type when user selects paid membership during registration
'_ielts_cm_membership_type_pending' => string (membership type key)

// Flag indicating user has a pending payment
'_ielts_cm_membership_payment_pending' => int (1 = pending, 0 or unset = no pending payment)
```

### New Shortcode Method
```php
IELTS_CM_Shortcodes::display_band_scores($atts)
IELTS_CM_Shortcodes::convert_percentage_to_band($percentage)
```

---

## 🎯 Testing Checklist

- [x] PHP syntax validation
- [x] Code review feedback addressed
- [x] Security review completed
- [ ] Manual testing of registration with paid membership
- [ ] Manual testing of expired trial access block
- [ ] Manual testing of band scores shortcode display
- [ ] Browser compatibility testing (recommended)

---

## 📞 Support

For questions or issues related to this update, please refer to:
- GitHub Issues: https://github.com/impact2021/ielts-preparation-course/issues
- Documentation: See MEMBERSHIP_QUICK_START.md

---

**Release Date:** 2026-01-25  
**Version:** 14.7  
**Previous Version:** 14.6
