# Version 15.4 - Testing & Verification Guide

## Quick Summary
Version 15.4 improves the partner dashboard UI and clarifies membership system options.

---

## What Changed

### 1. Partner Dashboard (visible at `/partner-dashboard` or wherever `[iw_partner_dashboard]` shortcode is used)

**Before:**
- All sections always expanded
- No way to filter codes
- Only showed students who used access codes
- No explanations of course groups

**After:**
- All sections collapsed by default (click header to expand)
- Filter tabs: All / Active / Available / Expired
- Shows ALL students created by partner
- Course group descriptions explain what's included

### 2. Settings Page (IELTS Course Manager → Settings)

**Before:**
- "Membership System"
- "Access Code System"

**After:**
- "Paid Membership" (clearer name)
- "Access Code Membership" (clearer name)
- Better descriptions explaining each system

### 3. Free Trial Popup (for non-logged-in visitors)

**Before:**
- Always showed (if not dismissed)

**After:**
- Only shows when "Paid Membership" is enabled
- Disabled if using only access codes

---

## Testing Checklist

### ✅ Automated Tests (Completed)
- [x] PHP syntax validation
- [x] Code review
- [x] Heading capitalization consistency
- [x] Version number updated

### ⚠️ Manual Testing Required

#### A. Partner Dashboard
Access: Log in as user with `manage_partner_invites` capability or administrator

1. **Collapsible Sections**
   - [ ] All 4 sections start collapsed
   - [ ] Click "Create Invite Codes" header → section expands
   - [ ] Click header again → section collapses
   - [ ] Arrow indicator rotates correctly
   - [ ] Repeat for other 3 sections

2. **Create Invite Codes**
   - [ ] Expand "Create Invite Codes" section
   - [ ] See course group descriptions under dropdown
   - [ ] Descriptions explain what's in each option
   - [ ] Create 2-3 codes successfully
   - [ ] Codes appear in "Your Codes" table

3. **Filter Tabs**
   - [ ] Expand "Your Codes" section
   - [ ] Click "All" tab → shows all codes
   - [ ] Click "Active" tab → shows only active codes
   - [ ] Click "Available" tab → shows only unused codes
   - [ ] Click "Expired" tab → shows expired codes (if any)
   - [ ] Active tab highlights in blue

4. **Student Count**
   - [ ] Create a user manually
   - [ ] Check "Active Students" count increases
   - [ ] Expand "Managed Students" section
   - [ ] Manually created user appears in table
   - [ ] Generate code and have someone use it
   - [ ] Code user also appears in table
   - [ ] Count includes both types of students

5. **CSV Export**
   - [ ] Filter to "Active" codes only
   - [ ] Click "Download CSV"
   - [ ] Open CSV file
   - [ ] Only active codes in export (not all codes)

#### B. Settings Page
Access: IELTS Course Manager → Settings

1. **Toggle Names**
   - [ ] First toggle says "Paid Membership"
   - [ ] Second toggle says "Access Code Membership"
   - [ ] Descriptions are clear and helpful

2. **Toggle Behavior**
   - [ ] Disable "Paid Membership"
   - [ ] Save settings
   - [ ] Verify Memberships menu disappears
   - [ ] Re-enable "Paid Membership"
   - [ ] Save settings
   - [ ] Verify Memberships menu reappears

#### C. Free Trial Popup
Access: View site as non-logged-in visitor

1. **With Paid Membership Enabled**
   - [ ] Enable "Paid Membership" in settings
   - [ ] Log out
   - [ ] Visit any page
   - [ ] Trial popup appears (after delay)

2. **With Paid Membership Disabled**
   - [ ] Disable "Paid Membership" in settings
   - [ ] Log out
   - [ ] Visit any page
   - [ ] Trial popup does NOT appear

3. **Popup Still Works**
   - [ ] Enable "Paid Membership"
   - [ ] See popup appear
   - [ ] Click minimize button (−)
   - [ ] Popup minimizes to badge at bottom-right
   - [ ] Click badge to expand
   - [ ] Popup expands again

---

## Expected Behavior

### Partner Dashboard Flow

```
1. Partner logs in
2. Navigates to partner dashboard page
3. Sees 4 collapsed sections:
   - Create Invite Codes
   - Create User Manually  
   - Your Codes
   - Managed Students
4. Clicks "Create Invite Codes" header
5. Section expands showing form
6. Selects course group, sees description
7. Fills in quantity and days
8. Clicks "Generate Codes"
9. Codes created and shown
10. Clicks "Your Codes" header
11. Section expands showing table
12. Uses filter tabs to view specific codes
13. Downloads CSV with filtered results
```

### Settings Flow

```
1. Admin navigates to Settings
2. Sees "Paid Membership" toggle
3. Sees "Access Code Membership" toggle
4. Reads descriptions to understand each
5. Enables both for full functionality
   OR
   Enables only one based on needs
6. Saves settings
```

---

## Common Issues & Solutions

### Issue: Sections won't collapse/expand
**Check:**
- JavaScript errors in browser console
- jQuery is loaded
- `.iw-card-header` click handler registered

### Issue: Filter tabs don't work
**Check:**
- Codes table has `data-status` attributes
- Filter button click handlers registered
- Console for JavaScript errors

### Issue: Student count seems wrong
**Check:**
- Users have `iw_created_by_partner` meta set
- Meta value matches current partner's user ID
- Database query in `get_partner_students()`

### Issue: Popup still shows when disabled
**Check:**
- Settings saved correctly
- Browser cache cleared
- `ielts_cm_membership_enabled` option value

---

## Rollback Instructions

If issues arise and you need to rollback:

```bash
# Via Git
git checkout <previous-commit-hash>

# Or restore specific files
git checkout HEAD~1 includes/class-access-codes.php
git checkout HEAD~1 includes/frontend/class-frontend.php
git checkout HEAD~1 includes/admin/class-admin.php
git checkout HEAD~1 ielts-course-manager.php
```

Then update version back to 15.3 if needed.

---

## Browser Compatibility

Test in:
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (if available)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

Minimum supported: Modern browsers with ES6 support

---

## Performance Notes

**No performance impact expected:**
- Collapsible UI is pure CSS/JS (no server calls)
- Filter tabs work client-side (no AJAX)
- Student query uses existing user meta (indexed)
- No new database tables or queries

---

## Security Review

**Changes reviewed for security:**
- ✅ Nonce verification on all AJAX calls
- ✅ Capability checks before operations
- ✅ Input sanitization maintained
- ✅ Output escaping in all HTML
- ✅ No SQL injection vectors
- ✅ No XSS vulnerabilities added

---

## Deployment Checklist

Before deploying to production:

- [ ] All automated tests pass
- [ ] Manual testing completed
- [ ] No console errors
- [ ] Works on multiple browsers
- [ ] Settings can be toggled safely
- [ ] Partner dashboard functions correctly
- [ ] Popup shows/hides as expected
- [ ] Database backup taken
- [ ] Rollback plan documented
- [ ] Stakeholders notified

After deployment:

- [ ] Verify on production site
- [ ] Monitor for errors (PHP/JS)
- [ ] Check with actual partners
- [ ] Collect feedback
- [ ] Update documentation if needed

---

## Support Resources

- **Release Notes:** `VERSION_15_4_RELEASE_NOTES.md`
- **Partner Guide:** `PARTNER_DASHBOARD_USER_GUIDE.md`
- **Membership Guide:** `MEMBERSHIP_QUICK_START.md`
- **Quick Reference:** `PARTNER_DASHBOARD_QUICK_REFERENCE.md`

---

## Version Info

- **Previous Version:** 15.3
- **Current Version:** 15.4
- **Release Date:** January 31, 2026
- **Branch:** copilot/update-partner-dashboard-layout
