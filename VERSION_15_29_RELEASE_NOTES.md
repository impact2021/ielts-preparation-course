# Version 15.29 Release Notes

**Release Date:** February 9, 2026  
**Version:** 15.29  
**Previous Version:** 15.28

## Overview

This release addresses two critical issues affecting hybrid sites and multi-site content synchronization:

1. **Enhanced Webhook Debugging** - Partners can now see detailed webhook event logs when purchasing access codes, making it much easier to diagnose why codes aren't appearing
2. **Complete Content Deletion Sync** - When pages or lessons are removed from the master site, they are now properly removed from all subsites

## Issue #1: Enhanced Webhook Event Logging and Debugging

### Problem Statement

When partners purchased access codes on hybrid sites, there was insufficient debugging information if the codes didn't appear. The system would log errors to the server error log, but partners had no visibility into what was happening with their purchases. The complaint was: "Your debugger is a piece of shit and does not give any valuable information like no webhook returned or anything like that."

### Root Cause

The webhook handling system was logging to error_log, but there was:
- No database persistence of webhook events
- No UI to show partners what webhooks were received
- No easy way to diagnose webhook configuration issues
- Limited visibility into webhook processing failures

### Solution Implemented

#### 1. New Webhook Event Log Table

Added a new database table `ielts_cm_webhook_log` to track all webhook events:

```sql
CREATE TABLE wp_ielts_cm_webhook_log (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(100) NOT NULL,
    event_id varchar(255) DEFAULT NULL,
    payment_intent_id varchar(255) DEFAULT NULL,
    payment_type varchar(50) DEFAULT NULL,
    user_id bigint(20) DEFAULT NULL,
    amount decimal(10,2) DEFAULT NULL,
    status varchar(20) DEFAULT 'received',
    error_message text DEFAULT NULL,
    raw_payload longtext DEFAULT NULL,
    processed_at datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

**Key Features:**
- Logs every webhook received from Stripe
- Tracks processing status (received, processed, failed)
- Stores error messages for failed webhooks
- Links webhooks to specific users and payment types

#### 2. Enhanced Webhook Handler

Updated `includes/class-stripe-payment.php` to:
- Log all webhook events to the database
- Track signature verification failures
- Record configuration errors
- Update event status as processing progresses
- Capture errors during payment processing

**Example Log Flow:**
```
1. Webhook received → status: 'received'
2. Signature verified → Continue processing
3. Payment processed → status: 'processed', processed_at: timestamp
   OR
   Error occurred → status: 'failed', error_message: details
```

#### 3. Enhanced Debug Panel in Partner Dashboard

Updated `includes/class-access-codes.php` to display webhook events in the debug panel:

**New Debug Information Displayed:**
- ✅ Last 5 webhook events for the user
- ✅ Event type (e.g., payment_intent.succeeded)
- ✅ Payment type (e.g., access_code_purchase)
- ✅ Amount and timestamp
- ✅ Status with color coding:
  - Green ✓ = Processed successfully
  - Red ✗ = Failed with error message
  - Orange ⏳ = Received but not processed
- ⚠️ Warning if no webhooks found (indicates configuration issue)

**Visual Example:**
```
Recent Webhook Events:
✓ payment_intent.succeeded | access_code_purchase | Amount: $50.00 | 2026-02-09 10:30:00
✗ payment_intent.succeeded | access_code_purchase | Amount: $25.00 | 2026-02-09 09:15:00
  Error: IELTS_CM_Access_Codes class not found
```

#### 4. Helpful Configuration Warnings

If no webhook events are found, the debug panel now shows:
```
⚠️ Important: If you just made a purchase and no codes appeared, check that:
1. Stripe webhook is configured correctly
2. Webhook secret is set in Hybrid Site Settings
3. Stripe is sending events to the correct webhook URL
```

### Benefits

✅ **Immediate Visibility** - Partners can see webhook status without accessing server logs  
✅ **Faster Diagnosis** - Clear error messages explain exactly what went wrong  
✅ **Configuration Validation** - Instantly see if webhooks are reaching the site  
✅ **Better Support** - Support team can ask for screenshot of debug panel instead of server access  
✅ **Audit Trail** - Complete history of all webhook events for troubleshooting  

### Files Modified

1. `includes/class-database.php`
   - Added `webhook_log_table` property
   - Created `ielts_cm_webhook_log` table schema
   - Added `get_webhook_log_table()` method
   - Added `log_webhook_event()` static method
   - Updated `drop_tables()` to include webhook log

2. `includes/class-stripe-payment.php`
   - Enhanced `handle_webhook()` to log all events
   - Added logging for signature verification failures
   - Added logging for configuration errors
   - Added status updates (received → processed/failed)
   - Wrapped processing in try-catch to capture errors

3. `includes/class-access-codes.php`
   - Enhanced debug panel to query and display webhooks
   - Added color-coded status indicators
   - Added helpful warnings when no webhooks found
   - Improved error message visibility

---

## Issue #2: Content Deletion Synchronization to Subsites

### Problem Statement

When pages or lessons were removed from the master site courses, they were not being removed from subsites. The sync would add new content but not remove deleted content, causing subsites to have outdated content that no longer existed on the master.

### Root Cause

The synchronization system had two gaps:

1. **Lesson Sync Only**: The `sync_course_lessons()` function removed lessons deleted from courses, but there was no equivalent for pages within lessons
2. **No Page Tracking**: When syncing lessons, the system didn't track which pages (resources/quizzes) should exist in the lesson

### Solution Implemented

#### 1. Added Page Tracking to Lesson Sync

Updated `includes/class-multi-site-sync.php` to include page IDs when syncing lessons:

```php
// Get current page IDs for lessons
// This allows subsites to remove pages that are no longer in the lesson
if ($content_type === 'lesson') {
    $pages = $this->get_lesson_pages($content_id);
    $data['current_page_ids'] = wp_list_pluck($pages, 'ID');
}
```

**New Method Added:**
```php
private function get_lesson_pages($lesson_id) {
    // Get all resources and quizzes for this lesson
    $resources = $this->get_lesson_resources($lesson_id);
    $exercises = $this->get_lesson_exercises($lesson_id);
    
    // Combine them to get all page content
    return array_merge($resources, $exercises);
}
```

#### 2. Added Page Deletion Handler

Created new `sync_lesson_pages()` method in `includes/class-sync-api.php`:

```php
private function sync_lesson_pages($lesson_id, $primary_page_ids) {
    global $wpdb;
    
    // Get all pages currently associated with this lesson on the subsite
    $subsite_pages = $wpdb->get_results(...);
    
    // Find pages that should be removed
    foreach ($subsite_pages as $page) {
        $original_id = intval($page->original_id);
        
        // If page no longer exists in primary, remove it
        if (!isset($primary_pages_map[$original_id])) {
            wp_trash_post($page->post_id);
            error_log("IELTS Sync: Trashed page {$page->post_id} from lesson {$lesson_id}");
        }
    }
}
```

#### 3. Integrated Page Sync into Content Processing

Updated `process_incoming_content()` in `includes/class-sync-api.php`:

```php
// Handle page synchronization for lessons
// Remove pages/content that are no longer in the lesson on the primary site
if ($content_type === 'lesson' && isset($content_data['current_page_ids'])) {
    $this->sync_lesson_pages($post_id, $content_data['current_page_ids']);
}
```

### How It Works

1. **Master Site** - When syncing a lesson:
   - Collects all current page IDs (resources + quizzes)
   - Includes them in `current_page_ids` array
   - Sends to subsites along with lesson data

2. **Subsite** - When receiving lesson sync:
   - Receives the `current_page_ids` array
   - Queries for all pages linked to that lesson
   - Compares subsite pages to primary page list
   - Trashes any pages not in the primary list
   - Preserves data by trashing instead of deleting

3. **Logging** - Each deletion is logged:
   ```
   IELTS Sync: Trashed page 1234 (original: 5678) from lesson 91011 - no longer in primary site
   ```

### Sync Behavior

| Action on Master Site | Result on Subsites |
|----------------------|-------------------|
| Add new page to lesson | Page created on subsites |
| Edit existing page | Page updated on subsites |
| Delete page from lesson | Page trashed on subsites ✅ NEW |
| Add new lesson to course | Lesson created on subsites |
| Delete lesson from course | Lesson trashed on subsites |

### Safety Features

✅ **Trash, Don't Delete** - Content is moved to trash, not permanently deleted  
✅ **Preserves User Progress** - Student completion data is preserved  
✅ **Original ID Tracking** - Uses `_ielts_cm_original_id` to match content  
✅ **Detailed Logging** - All deletions are logged for audit trail  

### Files Modified

1. `includes/class-multi-site-sync.php`
   - Added `get_lesson_pages()` method
   - Updated `serialize_content()` to include page IDs for lessons
   - Enhanced lesson data payload

2. `includes/class-sync-api.php`
   - Added `sync_lesson_pages()` method
   - Updated `process_incoming_content()` to call page sync
   - Added logging for page deletions

---

## Database Changes

### New Table

**ielts_cm_webhook_log** - Tracks all Stripe webhook events

```sql
CREATE TABLE wp_ielts_cm_webhook_log (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(100) NOT NULL,
    event_id varchar(255) DEFAULT NULL,
    payment_intent_id varchar(255) DEFAULT NULL,
    payment_type varchar(50) DEFAULT NULL,
    user_id bigint(20) DEFAULT NULL,
    amount decimal(10,2) DEFAULT NULL,
    status varchar(20) DEFAULT 'received',
    error_message text DEFAULT NULL,
    raw_payload longtext DEFAULT NULL,
    processed_at datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY event_type (event_type),
    KEY payment_intent_id (payment_intent_id),
    KEY status (status),
    KEY created_at (created_at)
);
```

**Installation:** Table is created automatically on plugin activation or when `IELTS_CM_Database::create_tables()` is called.

---

## Testing Performed

### Webhook Logging Tests

✅ Test webhook event logging on success  
✅ Test webhook event logging on failure  
✅ Test signature verification failure logging  
✅ Test configuration error logging  
✅ Test debug panel displays webhooks correctly  
✅ Test color coding for different statuses  
✅ Test warning message when no webhooks found  

### Content Deletion Sync Tests

✅ Test page deletion from lesson syncs to subsites  
✅ Test lesson deletion from course syncs to subsites  
✅ Test multiple page deletions in single sync  
✅ Test preservation of user progress during deletion  
✅ Test logging of deletion events  
✅ Test trash vs permanent delete behavior  

---

## Upgrade Instructions

### For All Sites

1. The database table will be created automatically on first admin page load
2. No manual intervention required
3. Existing functionality is not affected

### For Hybrid Sites (Access Codes)

After upgrading:
1. Visit the Partner Dashboard to see the enhanced debug panel
2. If webhook events don't show, verify webhook configuration in Hybrid Site Settings
3. Test a small code purchase to verify webhook logging is working

### For Multi-Site Networks (Primary/Subsite)

After upgrading:
1. Push any lessons to subsites to trigger page synchronization
2. Check subsite content to verify deleted pages are removed
3. Review sync logs for deletion events

---

## Compatibility

- **WordPress:** 5.8+
- **PHP:** 7.2+
- **Previous Versions:** Fully backward compatible
- **Database:** New table added, no changes to existing tables
- **API:** No breaking changes to REST endpoints

---

## Impact Assessment

### Hybrid Sites
**Positive Impact** ✅
- Much easier to diagnose webhook issues
- Faster support resolution
- Better partner experience
- Reduced support tickets

**No Negative Impact** ✅
- All changes are additive
- Existing functionality unchanged
- Performance impact minimal (single DB insert per webhook)

### Multi-Site Networks
**Positive Impact** ✅
- Content now stays in sync (additions AND deletions)
- Subsites are true carbon copies of master
- Cleaner subsite content without orphaned pages

**No Negative Impact** ✅
- Trash instead of delete preserves data
- User progress is maintained
- Existing sync functionality enhanced, not replaced

### Standalone Sites
**No Impact** ✅
- Webhook logging available but not required
- Sync features not used on standalone sites
- No performance or functionality changes

---

## Known Limitations

1. **Webhook Log Table Size**
   - Events are logged indefinitely
   - Consider adding cleanup job in future version
   - For high-volume sites, monitor table size

2. **Trash Management**
   - Deleted content is trashed, not permanently deleted
   - Admins should periodically empty trash
   - Future enhancement: auto-cleanup option

3. **Sync Timing**
   - Deletions only sync when content is pushed
   - Manual push may be needed to clean up existing orphans
   - Auto-sync will handle ongoing changes

---

## Future Enhancements

Potential improvements for future versions:

1. **Webhook Log Cleanup**
   - Add scheduled job to clean old webhook logs
   - Configurable retention period
   - Export functionality for audits

2. **Enhanced Debug UI**
   - Full webhook history viewer in admin
   - Filter by date, status, type
   - Retry failed webhooks manually

3. **Deletion Sync Improvements**
   - Bulk cleanup of orphaned content
   - Configurable trash vs delete
   - Deletion preview before sync

4. **Sync Status Dashboard**
   - Real-time sync monitoring
   - Content divergence detection
   - One-click full resync option

---

## Support

If you encounter any issues after upgrading:

1. **Check Error Logs**
   - WordPress debug.log for detailed errors
   - Webhook log table for webhook issues

2. **Verify Configuration**
   - Hybrid Site Settings for webhook secret
   - Site role settings for sync configuration

3. **Contact Support**
   - Provide debug panel screenshot
   - Include relevant error log entries
   - Note recent changes or purchases

---

## Summary

Version 15.29 significantly improves the debugging experience for hybrid site code purchases and ensures complete content synchronization for multi-site networks. The enhanced webhook logging provides transparency into payment processing, while the improved deletion sync keeps subsites perfectly aligned with the master site.

**Key Achievements:**
- ✅ Partners can now see exactly what's happening with webhook events
- ✅ Subsites now properly remove deleted content
- ✅ Both fixes include comprehensive logging for troubleshooting
- ✅ Zero negative impact on existing functionality
- ✅ Improved user experience and reduced support burden
