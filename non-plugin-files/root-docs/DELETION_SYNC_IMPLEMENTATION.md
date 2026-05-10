# Content Deletion Sync Implementation

## Overview

This document describes the implementation of automatic content deletion synchronization between primary and subsites in the IELTS Course Manager plugin.

## Problem Statement

Previously, when content (courses, lessons, resources, or quizzes) was deleted or trashed on the primary site, this change was not automatically propagated to connected subsites. The subsites would retain the deleted content until a manual sync was performed.

## Solution

Implemented automatic deletion synchronization that:
1. Detects when content is deleted/trashed on the primary site
2. Immediately notifies all connected subsites
3. Subsites automatically trash or permanently delete the corresponding content

## Technical Implementation

### 1. REST API Endpoint (Subsite)

**File**: `includes/class-sync-api.php`

**Endpoint**: `POST /wp-json/ielts-cm/v1/delete-content`

**Purpose**: Receives deletion notifications from the primary site

**Authentication**: Requires valid auth token via `X-IELTS-Auth-Token` header

**Request Body**:
```json
{
  "content_id": 123,
  "content_type": "course|lesson|resource|quiz"
}
```

**Response**:
```json
{
  "success": true,
  "message": "1 course item(s) processed successfully",
  "deleted_count": 1
}
```

**Logic**:
- Finds synced content by matching `_ielts_cm_original_id` meta
- Validates post type matches expected type
- If content is not in trash: trash it using `wp_trash_post()`
- If content is already in trash: permanently delete it using `wp_delete_post()`

### 2. Deletion Push Method (Primary Site)

**File**: `includes/class-multi-site-sync.php`

**Method**: `push_deletion_to_subsites($content_id, $content_type)`

**Purpose**: Sends deletion notifications to all connected subsites

**Process**:
1. Verifies this is a primary site
2. Gets list of all connected subsites
3. For each subsite:
   - Sends POST request to deletion endpoint
   - Includes auth token in headers
   - Logs success/failure
4. Returns array of results per subsite

### 3. WordPress Hooks (Primary Site)

**File**: `includes/class-ielts-course-manager.php`

**Hooks Registered**:
- `wp_trash_post` - Triggered when content is moved to trash
- `before_delete_post` - Triggered before permanent deletion

**Method**: `sync_content_deletion($post_id)`

**Process**:
1. Checks if this is a primary site (skip if not)
2. Gets the post being deleted
3. Checks if it's a synced content type (course, lesson, resource, quiz)
4. If yes, calls `push_deletion_to_subsites()`
5. Logs results

## Data Flow

```
Primary Site                               Subsites
━━━━━━━━━━━━                               ━━━━━━━━

User deletes content
      ↓
WordPress fires hook
(wp_trash_post or
 before_delete_post)
      ↓
sync_content_deletion()
checks if primary site
      ↓
push_deletion_to_subsites()    ──────→    REST endpoint
sends HTTP POST requests                   /delete-content
with content_id and type                          ↓
      ↓                                    receive_deletion()
Logs results                               finds synced content
                                                  ↓
                                           Trash or permanently
                                           delete content
                                                  ↓
                              ←──────     Returns success
                                           response
```

## Security Measures

1. **Authentication**: All deletion requests require valid auth token
2. **Authorization**: Only primary sites can push deletions
3. **Input Validation**: 
   - Content ID cast to integer
   - Content type sanitized and validated against whitelist
   - Post type verified to match expected type
4. **SQL Injection Prevention**: Uses `$wpdb->prepare()` with placeholders
5. **Data Preservation**: Trash before permanent deletion

## Testing the Implementation

### Prerequisites
- One primary site with sync configured
- At least one connected subsite
- Test content (course, lesson, resource, or quiz) synced to subsite

### Test Case 1: Trash Content
1. On primary site, trash a synced course/lesson/resource/quiz
2. Check error logs for sync notification: `IELTS Sync: Deletion notification for...`
3. On subsite, verify content is moved to trash
4. Expected: Content should be in trash on subsite

### Test Case 2: Permanent Deletion
1. On primary site, permanently delete a synced item (delete from trash)
2. Check error logs for sync notification
3. On subsite, verify content is permanently deleted
4. Expected: Content should be completely removed from subsite

### Test Case 3: Never Synced Content
1. On primary site, create new content but don't sync it
2. Delete the content
3. On subsite, verify no changes (content never existed there)
4. Expected: No errors, subsite unchanged

### Test Case 4: Multiple Subsites
1. Configure multiple subsites
2. Sync content to all subsites
3. Delete content on primary site
4. Verify deletion synced to all subsites
5. Expected: All subsites should have content deleted

## Error Handling

### Connection Failures
- Logged with detailed error message
- Doesn't block primary site deletion
- Subsite will be out of sync until next manual sync

### Authentication Failures
- Logged with site name and error
- Indicates token mismatch or expired token
- Requires manual investigation

### Content Not Found
- Returns success (already deleted)
- Logged as informational message
- No action needed

## Monitoring

Check error logs for these messages:

**Success on Primary**:
```
IELTS Sync: Deletion notification for course 123 sent to 2 subsite(s), 0 failed
```

**Success on Subsite**:
```
IELTS Sync: Trashed course 456 (original: 123) - deleted on primary site
IELTS Sync: Permanently deleted course 456 (original: 123) - deleted on primary site
```

**Failures**:
```
IELTS Sync: Failed to push deletion to Site Name: Connection timed out
IELTS Sync: Failed to push deletion notification: No connected subsites found
```

## Backwards Compatibility

- Existing sync functionality remains unchanged
- Manual sync still works for deletions
- Subsites without this update will not receive deletion notifications
- No database changes required

## Future Enhancements

Potential improvements:
1. Queue-based deletion sync for reliability
2. Retry mechanism for failed deletions
3. Admin notification of sync failures
4. Bulk deletion sync optimization
5. Deletion history/audit log
