# Version 15.36 Release Notes

**Release Date:** February 10, 2026

## Bug Fix: Access Code Visibility in Partner Dashboard

### Problem
When partners created new access codes through the "Create Invite Codes" form, the newly created codes were not visible in the "Your codes" table after the page reloaded. This caused confusion as partners couldn't see the codes they just created.

### Root Cause
The issue was caused by a filter initialization mismatch:

1. **New codes have status `'active'`** - When codes are created, they are stored in the database with `status='active'` (available for use)
2. **Default filter showed 'used' codes** - The "Your codes" table was configured to display only codes with `status='used'` by default
3. **Newly created codes were hidden** - After page reload, the filter would hide all `active` codes, showing only `used` codes

This created a user experience problem where:
- Partner creates codes → sees success message
- Page reloads → table shows "Used" codes by default
- Newly created codes (status='active') are hidden
- Partner thinks codes weren't created

### Solution
Changed the default filter from 'used' to 'available' (unused codes):

**File:** `includes/class-access-codes.php`

**Changes Made:**

1. **Updated filter button HTML** (line 1240-1241):
   - Moved `class="active"` from "Used" button to "Unused" button
   - This ensures the UI reflects the correct default state

2. **Updated JavaScript initialization** (line 1540):
   - Changed from `IWDashboard.filterCodes('used')`
   - To `IWDashboard.filterCodes('available')`
   - This ensures newly created codes (status='active') are visible by default

### Impact
- ✅ Partners can now immediately see newly created codes in the "Your codes" table
- ✅ Better user experience - available codes are shown by default (more useful than showing used codes)
- ✅ Partners can still click "Used" button to see codes that have been redeemed
- ✅ No database changes required
- ✅ No breaking changes - existing functionality preserved

### Testing Recommendations
1. Log in as a partner admin
2. Navigate to Partner Dashboard
3. Create new invite codes (e.g., 5 codes for Academic Module, 90 days)
4. After page reloads, verify codes appear in the "Your codes" table
5. Verify "Unused" filter button is active by default
6. Click "Used" button to verify filtering works correctly
7. Click "Unused" button to return to available codes view

### Technical Details
- **Version:** 15.35 → 15.36
- **Files Modified:** 
  - `ielts-course-manager.php` (version bump)
  - `includes/class-access-codes.php` (filter default change)
- **Lines Changed:** 4 lines total
- **Backward Compatible:** Yes
- **Database Changes:** None

### User-Facing Changes
- "Your codes" table now shows **Unused** codes by default (previously showed Used codes)
- This is more intuitive as partners typically want to see available codes to share with students

---

**For Support:** If you encounter any issues with code visibility after this update, please check:
1. The "Unused" button should be active/highlighted by default
2. Newly created codes should appear immediately after page reload
3. Clicking "Used" shows previously redeemed codes
4. Clicking "Unused" returns to available codes view
