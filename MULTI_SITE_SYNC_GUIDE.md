# Multi-Site Content Sync Guide

## Overview

Version 2.0 of the IELTS Course Manager introduces a powerful multi-site content synchronization system. This feature allows you to maintain a **primary site** where you manage all course content, and automatically push updates to multiple **subsites** while preserving student progress.

## Key Features

- ✅ **Centralized Content Management** - Manage all content from a single primary site
- ✅ **Multiple Subsites** - Push content to unlimited subsites
- ✅ **Progress Preservation** - Student completion status remains intact during updates
- ✅ **Selective Sync** - Push individual courses, lessons, or exercises as needed
- ✅ **Automatic Updates** - Content changes detected and tracked automatically
- ✅ **Secure Communication** - Token-based authentication between sites
- ✅ **Sync Status Tracking** - Monitor sync history and status for each subsite

## Architecture

### Site Roles

1. **Primary Site** - The central content management site
   - Creates and edits all course content
   - Pushes updates to subsites
   - Cannot receive content from other sites
   
2. **Subsite** - Sites that receive content from the primary
   - Receives content updates from primary site
   - Maintains student enrollment and progress data
   - Cannot push content to other sites
   
3. **Standalone** - Sites not participating in sync
   - Default mode for all sites
   - Operates independently without sync features

## Setup Guide

### Step 1: Configure the Primary Site

1. Navigate to **IELTS Courses > Multi-Site Sync**
2. Under "Site Configuration", select **Primary Site** radio button
3. Click **Save Site Role**
4. The page will now show options to add subsites

### Step 2: Configure Each Subsite

For each subsite that will receive content:

1. Navigate to **IELTS Courses > Multi-Site Sync**
2. Under "Site Configuration", select **Subsite** radio button
3. Click **Save Site Role**
4. Copy the **Authentication Token** and **Site URL** displayed
5. Keep this information for use in Step 3

### Step 3: Connect Subsites to Primary

On the primary site:

1. Navigate to **IELTS Courses > Multi-Site Sync**
2. Scroll to **Add Subsite** section
3. Fill in the form:
   - **Site Name**: A friendly name for the subsite (e.g., "New York Campus")
   - **Site URL**: The full URL of the subsite (copied from Step 2)
   - **Authentication Token**: The token from the subsite (copied from Step 2)
4. Click **Add Subsite**

### Step 4: Test the Connection

1. In the **Connected Subsites** table, find your newly added subsite
2. Click the **Test** button
3. Verify you see "Connection successful!" message

## Using Content Sync

### Pushing Content to Subsites

On the primary site, when editing any course, lesson, lesson page, or exercise:

1. Look for the **Push to Subsites** meta box in the right sidebar
2. Review the sync information:
   - Number of connected subsites
   - Last sync time (if previously synced)
   - Last sync status
3. Click the **Push to Subsites** button
4. Confirm the action in the dialog
5. Wait for the sync to complete
6. Review the results showing success/failure for each subsite

### What Gets Synced

When you push content, the following data is synchronized:

#### For Courses
- Course title, content, and excerpt
- Course categories
- Featured image
- Course settings and metadata
- Associated lessons (structure)

#### For Lessons
- Lesson title, content, and excerpt
- Course association
- Lesson pages and exercises (structure)
- Lesson settings and metadata

#### For Lesson Pages (Resources)
- Page title, content, and excerpt
- Lesson association
- Resource URL (if external)
- Page settings and metadata

#### For Exercises (Quizzes)
- Exercise title, content, and excerpt
- All questions and answers
- Correct answers and feedback
- Passing percentage
- Exercise settings and metadata

### What is NOT Synced

The following data remains local to each subsite and is NOT overwritten:

- ✅ Student enrollment data
- ✅ User progress and completion status
- ✅ Quiz submission results
- ✅ User accounts and profiles
- ✅ Course access dates and expiration

## Progress Preservation Details

### How Progress is Preserved

When content is pushed to a subsite:

1. **New Content**: If the content doesn't exist on the subsite, it's created fresh
2. **Updated Content**: If the content exists, it's updated with the following rules:
   - Content, title, and settings are updated
   - Student completion status is checked before the update
   - If a student had completed the item, it remains marked as complete
   - If a student had taken a quiz, their results are preserved

### Completion Percentage Recalculation

When new content is added to a course:

1. **On Primary Site**: Completion percentage reflects new total items
2. **On Subsites**: After sync, completion percentage automatically adjusts
3. **Example**: 
   - Student completed 5 of 10 lessons (50%)
   - You add 2 new lessons (now 12 total)
   - After sync: Student shows 5 of 12 lessons complete (41.67%)
   - The 5 completed lessons remain marked as complete

### Edge Cases Handled

- **Item Updated After Completion**: Student's completion status is preserved
- **Content Removed**: Not supported - items should be marked as draft, not deleted
- **Duplicate Content**: Detected using original content ID tracking
- **Failed Sync**: Logged for review, doesn't affect student data

## Sync Monitoring

### Sync History

For each content item on the primary site:

- View last sync time in the "Push to Subsites" meta box
- See sync status (success/failed) for the last sync
- Access detailed sync logs in the database if needed

### Connected Subsites Table

The primary site's Multi-Site Sync page shows:

- All connected subsites
- Status (active/inactive)
- Last sync time for each subsite
- Quick actions (Test, Remove)

## Troubleshooting

### Connection Test Fails

**Symptoms**: "Connection failed" when testing a subsite

**Solutions**:
1. Verify the subsite URL is correct (no trailing slash)
2. Ensure the authentication token matches exactly
3. Check that the subsite has "Subsite" role selected
4. Verify the subsite is accessible from the primary site
5. Check for firewall or security plugins blocking REST API requests

### Sync Shows "Failed" Status

**Symptoms**: Sync completes but shows "failed" for one or more subsites

**Possible Causes**:
1. Authentication token mismatch
2. Network connectivity issues
3. Subsite REST API disabled
4. Plugin not active on subsite
5. Insufficient disk space on subsite

**Solutions**:
1. Test the connection using the Test button
2. Regenerate the authentication token on the subsite
3. Verify REST API is enabled on subsite
4. Check server error logs for detailed messages

### Content Not Appearing on Subsite

**Symptoms**: Sync shows success, but content not visible on subsite

**Possible Causes**:
1. Content is in draft status
2. User doesn't have permission to view content
3. Theme or caching issue

**Solutions**:
1. Check post status on subsite (may be draft)
2. Verify user has appropriate role/permissions
3. Clear site cache if caching plugin is active
4. Check if content appears in admin dashboard

### Student Progress Lost

**Symptoms**: Students report lost progress after content update

**This should NOT happen** if sync is working correctly. If this occurs:

1. Check sync logs to verify progress preservation was attempted
2. Review database records in `ielts_cm_progress` table
3. Verify the content ID mapping is correct
4. Contact support with sync log details

## Security Considerations

### Authentication Tokens

- Tokens are 32-character random strings
- Store securely - they provide full sync access
- Regenerate tokens if compromised
- Use unique token for each subsite

### Network Security

- All communication uses WordPress REST API
- Tokens are sent in HTTP headers (not URL)
- Consider using HTTPS for all sites
- Use secure server-to-server communication

### Access Control

- Only users with 'manage_options' capability can configure sync
- Only users with 'edit_posts' capability can push content
- Subsites cannot push content back to primary
- Subsites cannot modify their token via API

## Best Practices

### Content Management

1. **Make all content changes on primary site** - Don't edit synced content on subsites
2. **Test before pushing** - Preview content on primary before syncing
3. **Push regularly** - Keep subsites up-to-date with frequent syncs
4. **Use draft status** - Create new content as draft, publish when ready to sync

### Subsite Management

1. **Document subsite details** - Keep a record of each subsite's purpose and token
2. **Test connections regularly** - Use the Test button to verify connectivity
3. **Monitor sync status** - Check last sync time for each subsite
4. **Remove inactive subsites** - Clean up connections no longer in use

### Student Experience

1. **Schedule syncs during off-hours** - Minimize impact on active students
2. **Communicate updates** - Let students know when content changes
3. **Preserve access** - Ensure enrolled students maintain course access
4. **Monitor completion** - Verify completion percentages adjust correctly

## FAQ

### Can a site be both primary and subsite?

No. A site must choose one role. This prevents circular sync loops and ensures clear content ownership.

### Can I sync in both directions?

No. Content flows one way: primary → subsites. This ensures content consistency and prevents conflicts.

### What happens if I delete content on the primary?

Deletion is not synced. Mark content as "draft" instead if you want to hide it from students while preserving existing progress.

### Can I sync only specific courses?

Yes. Use the "Push to Subsites" button on individual courses, lessons, or exercises. There's no "sync all" feature - sync is always selective.

### Do I need to sync in a specific order?

For best results, sync in this order:
1. Courses
2. Lessons (within courses)
3. Lesson pages and exercises (within lessons)

However, the system will handle dependencies automatically.

### Can students enroll on subsites?

Yes. Subsites maintain their own enrollment system. Only the content is synced, not user accounts or enrollments.

### What if subsites have different students?

Perfect! Each subsite maintains its own student list and progress data. Content is shared, but student data is completely separate.

### Can I have different pricing on each subsite?

Yes. If you use a payment plugin, each subsite can have its own pricing and payment settings.

## Technical Details

### API Endpoints

All endpoints are available at: `https://yoursite.com/wp-json/ielts-cm/v1/`

**POST /sync-content**
- Receives content from primary site
- Requires authentication token in header
- Preserves student progress during updates

**GET /test-connection**
- Tests connectivity and authentication
- Returns site information if successful

**GET /site-info**
- Returns site name, URL, role, and plugin version
- Useful for debugging connection issues

### Database Tables

**ielts_cm_site_connections**
- Stores connected subsite information
- Fields: id, site_name, site_url, auth_token, status, last_sync, created_date

**ielts_cm_content_sync**
- Tracks sync history and content changes
- Fields: id, content_id, content_type, content_hash, site_id, sync_date, sync_status

### Content Hash Algorithm

Content changes are detected using SHA-256 hashes of:
- Post title, content, excerpt, modified date
- Type-specific metadata (course lessons, quiz questions, etc.)

When the hash changes, content is marked for sync.

## Support

For issues or questions about multi-site sync:

1. Check the troubleshooting section above
2. Review sync logs in the database
3. Test connections using the Test button
4. Contact support with:
   - Plugin version
   - WordPress version
   - Sync error messages
   - Affected content types

## Changelog

- **v2.0** - Initial release of multi-site sync feature
