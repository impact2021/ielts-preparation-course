# Automatic Content Synchronization - Documentation

## Overview

The IELTS Course Manager now includes an **Automatic Sync** feature that periodically checks for content changes and automatically pushes them to connected subsites. This eliminates the need for manual syncing and ensures subsites stay up-to-date.

## How to Access

**WordPress Admin → IELTS Courses → Multi-Site Sync → Automatic Sync Section**

## Key Features

### 1. Scheduled Automatic Syncing
- Runs automatically at configurable intervals
- Uses WordPress Cron (wp-cron) for scheduling
- No external cron setup required

### 2. Smart Change Detection
- Only syncs content that has actually changed
- Uses SHA-256 hash comparison to detect changes
- Avoids unnecessary sync operations

### 3. Server Load Protection
- **Batch Processing**: Max 50 items per sync run
- **Memory Monitoring**: Stops if memory usage exceeds 100MB or 80% of PHP limit
- **Auto-Disable**: Turns off after 5 consecutive failures
- **Efficient Detection**: Only changed items are processed

### 4. Activity Logging
- Records all sync activities
- Shows last 20 log entries
- Color-coded status (success, failed, warning, running, skipped)
- Detailed error messages

## Configuration Options

### Enable/Disable Auto-Sync
Toggle the "Automatically sync content changes" checkbox.

### Sync Interval Options
Choose how often to check for changes:

| Interval | Recommended For | Server Load |
|----------|----------------|-------------|
| **5 minutes** | Sites needing real-time sync | High |
| **10 minutes** | Active content updates | Medium-High |
| **15 minutes** | Regular content updates | Medium (Default) |
| **30 minutes** | Moderate update frequency | Low-Medium |
| **1 hour** | Infrequent updates | Low |
| **2 hours** | Daily content changes | Very Low |
| **6 hours** | Occasional updates | Minimal |
| **12 hours** | Twice-daily sync | Minimal |
| **24 hours** | Daily sync | Minimal |

### Recommended Intervals

**For most sites**: 15-30 minutes provides a good balance between freshness and server load.

**For high-traffic content sites**: Consider 5-10 minutes, but monitor server resources.

**For stable content**: 1-6 hours is usually sufficient.

## How It Works

### Sync Process Flow

```
1. WordPress Cron triggers at scheduled interval
   ↓
2. Check if auto-sync is enabled and site is primary
   ↓
3. Get all courses with complete hierarchy
   ↓
4. For each content item:
   - Generate current content hash
   - Compare with last successful sync hash
   - Mark as changed if hashes differ
   ↓
5. Batch process up to 50 changed items:
   - Push to all connected subsites
   - Log results (success/failure)
   - Monitor memory usage
   ↓
6. Update last run timestamp
   ↓
7. Reset or increment failure counter
```

### Change Detection Logic

For each piece of content (course, lesson, sub-lesson, exercise):

1. **Generate Current Hash**: SHA-256 hash of:
   - Title
   - Content
   - Modified date
   - Type-specific metadata (e.g., quiz questions, lesson pages)

2. **Compare with Database**: 
   - Query last successful sync for each subsite
   - Compare stored hash with current hash
   - If different → content has changed

3. **Sync Decision**:
   - If ANY subsite is out of sync → add to sync queue
   - If all subsites are up-to-date → skip

## Server Load Considerations

### Is Auto-Sync Too Intensive?

**Short answer**: No, if configured properly.

**Detailed analysis**:

#### Resource Usage Per Sync Run

**For a typical site with 100 content items:**

- **5 minute interval**: ~288 checks/day
  - Only changed items are synced
  - Most runs will find 0-5 changed items
  - Estimated load: 2-3 minutes total CPU time/day

- **15 minute interval**: ~96 checks/day
  - Balanced approach
  - Estimated load: 1-2 minutes total CPU time/day

- **1 hour interval**: ~24 checks/day
  - Minimal load
  - Estimated load: <1 minute total CPU time/day

#### Why It's Not Intensive

1. **Hash Comparison is Fast**: Database query + hash calculation is very quick
2. **Only Changed Content Syncs**: Typically 0-10% of content per run
3. **Batch Limiting**: Max 50 items prevents runaway processes
4. **Memory Monitoring**: Auto-stops if approaching limits
5. **Smart Scheduling**: WordPress Cron doesn't run unless site has traffic

#### When to Use Shorter Intervals

✅ **Good reasons**:
- Multiple content editors making frequent changes
- Need near real-time sync for time-sensitive content
- Small total content volume (<50 items)
- Sufficient server resources

❌ **Bad reasons**:
- Just because you can
- Already have hourly manual sync that works fine
- Server is already under heavy load

### Monitoring Server Load

Check these indicators:

1. **Sync Logs**: Look for timeouts or memory errors
2. **Last Run Time**: Should complete in <30 seconds typically
3. **Failure Counter**: If hitting 5 consecutive failures, investigate
4. **Server Monitoring**: CPU and memory usage during sync

### Optimization Tips

1. **Start Conservative**: Begin with 30-60 minute intervals
2. **Monitor First Week**: Watch logs for issues
3. **Adjust Gradually**: Reduce interval if needed
4. **Consider Content Volume**: More content = longer intervals

## Safety Features

### 1. Automatic Failure Handling

After **5 consecutive failures**:
- Auto-sync is automatically disabled
- Admin must manually re-enable
- Prevents runaway error loops

**Common failure causes**:
- Subsite connection issues
- Network timeouts
- Memory limits exceeded
- Database errors

### 2. Memory Protection

The system monitors memory usage:
- Stops if using >100MB
- Stops if using >80% of PHP memory_limit
- Prevents PHP memory exhausted errors

### 3. Batch Processing

Maximum 50 items per run:
- Prevents timeout errors
- Limits execution time
- Remaining items sync on next run

### 4. Error Logging

All issues are logged with:
- Timestamp
- Content type
- Error message
- Status code

## Activity Log

### Status Indicators

| Status | Color | Meaning |
|--------|-------|---------|
| **Success** | Green | Operation completed successfully |
| **Failed** | Red | Operation failed with error |
| **Warning** | Orange | Completed with issues (e.g., memory limit) |
| **Running** | Blue | Sync in progress |
| **Skipped** | Gray | No action needed |

### Log Messages

**System messages**:
- "Auto-sync started"
- "No content changes detected"
- "Auto-sync completed in X seconds. Synced: X, Failed: X"
- "Memory threshold exceeded. Synced X items."
- "Auto-sync disabled due to too many consecutive failures"

**Content messages**:
- "Synced: [Title] (ID: X)"
- "Failed: [Title] (ID: X) - [Error message]"

### Log Maintenance

- Automatically keeps last 100 entries
- Manual "Clear Log" button available
- Logs stored in `wp_ielts_cm_auto_sync_log` table

## Manual Sync

Even with auto-sync enabled, you can trigger immediate syncs:

1. Click **"Run Sync Now"** button
2. Runs the sync process immediately
3. Doesn't wait for scheduled interval
4. Useful for testing or urgent updates

## Troubleshooting

### Issue: Auto-Sync Not Running

**Check**:
1. Is auto-sync enabled? (Toggle should be checked)
2. Is site configured as Primary?
3. Are subsites connected?
4. Has WordPress Cron fired? (requires site traffic)

**Solution**:
- Verify settings are correct
- Visit site to trigger cron
- Check "Next run" timestamp
- Try manual sync to test

### Issue: High Failure Count

**Check**:
1. Sync activity log for error messages
2. Subsite connections (test each one)
3. Network connectivity
4. Server resources

**Solution**:
- Fix connection issues
- Increase PHP memory_limit if needed
- Reduce sync interval
- Clear logs and re-enable

### Issue: Some Content Not Syncing

**Check**:
1. Sync logs for specific errors
2. Content hash generation (does content exist?)
3. Batch size (are there more than 50 changed items?)

**Solution**:
- Check content exists and is published
- Run multiple manual syncs for large backlogs
- Verify subsite permissions

### Issue: Slow Performance

**Check**:
1. Number of content items
2. Sync interval
3. Server resources during sync time

**Solution**:
- Increase sync interval
- Consider off-peak scheduling
- Optimize server resources
- Review content volume

## Best Practices

### 1. Start Small
- Enable with 30-60 minute interval
- Monitor for first week
- Adjust based on needs

### 2. Regular Monitoring
- Check activity log weekly
- Review failure counters
- Verify sync timestamps

### 3. Test After Configuration
- Click "Run Sync Now"
- Check log for success
- Verify subsite received updates

### 4. Plan for Maintenance
- Before bulk content changes, consider disabling
- Re-enable after changes complete
- Use manual sync for immediate push

### 5. Match Interval to Need
- Real-time needs: 5-15 minutes
- Regular updates: 30-60 minutes
- Stable content: 2-24 hours

## Technical Details

### WordPress Cron Implementation

Auto-sync uses WordPress Cron (wp-cron):
- Pseudo-cron system triggered by site visits
- Runs in background via wp-cron.php
- No server cron configuration needed

**Important**: On low-traffic sites, cron may not fire exactly on schedule. Consider setting up system cron for precise timing.

### Database Tables

**`wp_ielts_cm_auto_sync_log`**:
```sql
id             - Auto-increment primary key
content_type   - Type of content (course, lesson, resource, quiz, system)
message        - Log message text
status         - Status code (success, failed, warning, running, skipped)
log_date       - Timestamp of log entry
```

### Performance Metrics

**Typical sync run (100 items, 5 changed)**:
- Database queries: ~200
- Memory usage: 5-15 MB
- Execution time: 2-10 seconds
- Network requests: 5 (one per changed item)

## FAQ

**Q: Will this slow down my site?**
A: No. Sync runs in background and processes only changed content.

**Q: What happens if a sync is still running when the next one is scheduled?**
A: WordPress Cron won't start a new run if the previous one is still executing.

**Q: Can I sync only specific content types?**
A: Currently no, but this could be added as an enhancement.

**Q: Does this work with WordPress Multisite?**
A: Yes, it's designed for multi-site environments.

**Q: What if my PHP memory_limit is low?**
A: The system monitors and stops before exceeding limits. Consider increasing memory_limit if needed.

**Q: Can I see what will sync before it runs?**
A: Yes, go to the Sync Status page to see which items are out of sync.

## Support

For issues or questions:
1. Check the Activity Log for error details
2. Review this documentation
3. Test with manual sync
4. Contact support with log details

---

**Version**: 15.4
**Last Updated**: January 2026
**Status**: Production Ready
