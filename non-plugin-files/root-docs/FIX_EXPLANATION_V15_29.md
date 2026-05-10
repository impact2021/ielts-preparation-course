# Fix Explanation - Version 15.29

## Problem #1: Partners Buying Access Codes Not Seeing Codes

### What Was Wrong
When partners purchased access codes on hybrid sites, if the codes didn't appear, there was NO way for them to see what happened. The webhook events from Stripe were only logged to the server error log, which partners can't access. The debug panel showed payment info but no webhook information.

### What We Fixed
**Added comprehensive webhook event logging visible to partners:**

1. **New Database Table** (`ielts_cm_webhook_log`)
   - Logs every webhook event from Stripe
   - Tracks status: received, processed, or failed
   - Stores error messages when things go wrong
   - Links events to users and payment types

2. **Enhanced Webhook Handler** (`class-stripe-payment.php`)
   - Logs every webhook to database (not just error_log)
   - Tracks signature verification failures
   - Records configuration errors
   - Updates status as processing progresses

3. **Improved Debug Panel** (`class-access-codes.php`)
   - Shows last 5 webhook events for the user
   - Color-coded status indicators (green=success, red=fail, orange=pending)
   - Displays error messages inline
   - Shows helpful warning if no webhooks received

### Example Output
Before:
```
Last Payment: access_codes_10 - $50.00 - completed (2026-02-09 10:30:00)
```

After:
```
Last Payment: access_codes_10 - $50.00 - completed (2026-02-09 10:30:00)

Recent Webhook Events:
✓ payment_intent.succeeded | access_code_purchase | Amount: $50.00 | 2026-02-09 10:30:00
✗ payment_intent.succeeded | access_code_purchase | Amount: $25.00 | 2026-02-09 09:15:00
  Error: Webhook secret not configured

⚠️ Important: If no codes appeared after purchase, check webhook configuration!
```

### Why This Helps
- Partners can SEE if webhooks are arriving
- Partners can SEE error messages explaining what went wrong
- Support team can ask for debug panel screenshot instead of server access
- Faster diagnosis and resolution of webhook issues

---

## Problem #2: Deleted Content Not Syncing to Subsites

### What Was Wrong
When pages or lessons were removed from the master site, they remained on subsites. The sync would:
- ✅ Add new content to subsites
- ✅ Update existing content on subsites
- ❌ NOT remove deleted content from subsites

This caused subsites to have outdated content that no longer existed on the master.

### What We Fixed
**Added deletion synchronization for both lessons and pages:**

1. **Track Current Pages in Lessons** (`class-multi-site-sync.php`)
   - When syncing a lesson, include list of current page IDs
   - Similar to how course sync includes current lesson IDs
   - Added `get_lesson_pages()` method to collect all pages

2. **Remove Orphaned Pages on Subsites** (`class-sync-api.php`)
   - When receiving lesson sync, compare page lists
   - Trash any pages that don't exist on primary site
   - Added `sync_lesson_pages()` method
   - Logs each deletion for audit trail

3. **Safe Deletion Process**
   - Uses `wp_trash_post()` instead of permanent delete
   - Preserves user progress data
   - Logs every deletion with page and lesson IDs

### How It Works

**Master Site sends:**
```json
{
  "content_type": "lesson",
  "id": 123,
  "title": "Lesson 1",
  "current_page_ids": [456, 789]  // ← NEW
}
```

**Subsite receives and:**
1. Updates lesson content
2. Queries its own pages for this lesson
3. Finds pages 456, 789, 999 (999 is orphaned)
4. Trashes page 999 since it's not in the primary list
5. Logs: "Trashed page 999 from lesson 123 - no longer in primary site"

### Sync Behavior Now

| Master Site Action | Subsite Result |
|-------------------|----------------|
| Add page to lesson | Page created ✅ |
| Edit page | Page updated ✅ |
| Delete page | Page trashed ✅ **NEW** |
| Add lesson to course | Lesson created ✅ |
| Delete lesson | Lesson trashed ✅ (already worked) |

### Why This Helps
- Subsites are now true carbon copies of master
- No orphaned content on subsites
- Content stays clean and up-to-date
- Student progress still preserved (trash, not delete)

---

## Files Modified

### For Webhook Logging
1. `includes/class-database.php`
   - Added webhook log table
   - Added logging method

2. `includes/class-stripe-payment.php`
   - Enhanced webhook handler with logging

3. `includes/class-access-codes.php`
   - Enhanced debug panel to show webhooks

### For Deletion Sync
1. `includes/class-multi-site-sync.php`
   - Added page tracking to lesson sync
   - Added `get_lesson_pages()` method

2. `includes/class-sync-api.php`
   - Added page deletion handler
   - Added `sync_lesson_pages()` method

### Version Update
1. `ielts-course-manager.php`
   - Updated version from 15.28 to 15.29

---

## Testing Recommendations

### Test Webhook Logging
1. Purchase access codes on hybrid site
2. Check Partner Dashboard debug panel
3. Verify webhook events are shown
4. If codes don't appear, check error message in webhook log

### Test Deletion Sync
1. On master site: Create lesson with 3 pages
2. Push to subsites - verify 3 pages on subsite
3. On master site: Delete 1 page from lesson
4. Push lesson to subsites - verify only 2 pages on subsite
5. Check subsite trash - verify deleted page is there

---

## No Breaking Changes

✅ Fully backward compatible  
✅ No changes to existing functionality  
✅ Only additions and enhancements  
✅ Works with all site types (standalone, hybrid, multi-site)  
✅ Database table created automatically  

---

## Support Impact

**Expected Reduction in Support Tickets:**
- Webhook issues: Partners can self-diagnose using debug panel
- Sync issues: Content now properly synchronized
- Both: Better logging makes support easier when needed

**Better Error Messages:**
- Before: "No codes appeared" (no context)
- After: "Webhook event failed: Webhook secret not configured" (actionable)
