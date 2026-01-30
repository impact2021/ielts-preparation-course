# Automatic Sync - Quick Start Guide

## What Is It?

Automatic content synchronization keeps your subsites up-to-date by automatically pushing changed content at regular intervals.

## Quick Setup (5 Steps)

### Step 1: Navigate to Settings
**WordPress Admin → IELTS Courses → Multi-Site Sync**

Scroll down to the **"Automatic Sync"** section.

### Step 2: Enable Auto-Sync
☑ Check **"Automatically sync content changes"**

### Step 3: Choose Interval
Select how often to check for changes:

**Recommended**: Start with **15 minutes**

```
┌─────────────────────────────┐
│ Sync Interval:              │
│ ▼ 15 minutes     [Dropdown] │
└─────────────────────────────┘
```

### Step 4: Save Settings
Click **"Save Auto-Sync Settings"**

### Step 5: Test It
Click **"Run Sync Now"** and check the log below for results.

## That's It!

Auto-sync is now active. Changed content will automatically push to subsites every 15 minutes.

---

## Configuration Options

### Interval Recommendations

| Your Situation | Recommended Interval |
|---------------|---------------------|
| 🔥 **Frequent updates** (multiple times/day) | 5-15 minutes |
| 📅 **Regular updates** (daily changes) | 30-60 minutes |
| 📚 **Stable content** (weekly changes) | 2-6 hours |
| 🐌 **Rarely updated** (monthly changes) | 12-24 hours |

### When to Use 5 Minutes

✅ **Good if you have**:
- Multiple content editors
- Time-sensitive content
- Small content volume (<50 items)
- Adequate server resources

❌ **Not recommended if you have**:
- Large content volume (>200 items)
- Limited server resources
- Infrequent content changes
- Low site traffic

---

## Understanding the Status Display

```
┌──────────────────────────────────────┐
│ Status: ● Active                     │
│ Last run: 5 minutes ago              │
│ Next run: in 10 minutes              │
└──────────────────────────────────────┘
```

**Active** (Green ●): Auto-sync is running
**Inactive** (Gray ○): Auto-sync is off

---

## Understanding the Activity Log

```
┌──────────────────────────────────────────────────────────┐
│ Date/Time    Type     Status    Message                  │
├──────────────────────────────────────────────────────────┤
│ 2 min ago   system   Success   Auto-sync completed...   │
│ 17 min ago  lesson   Success   Synced: Unit 1 Intro     │
│ 17 min ago  quiz     Success   Synced: Practice Test 1  │
│ 32 min ago  system   Success   No content changes...    │
└──────────────────────────────────────────────────────────┘
```

**Status Colors**:
- 🟢 **Success**: Everything worked
- 🔴 **Failed**: Error occurred
- 🟡 **Warning**: Partial success
- 🔵 **Running**: In progress
- ⚫ **Skipped**: No action needed

---

## Server Load Info

### Is 5 Minutes Too Intensive?

**No!** Here's why:

1. **Only changed content syncs** (usually 0-5 items)
2. **Fast hash checks** (milliseconds)
3. **Batch limited** (max 50 items)
4. **Memory protected** (auto-stops)

### Actual Resource Usage

**For 100 content items, 5-minute interval**:
- CPU time: ~2-3 min/day total
- Memory: 5-15 MB per run
- Duration: 2-10 seconds per run

This is **very light** on most servers.

### When It Might Be Too Much

❌ Consider longer intervals if:
- Shared hosting with strict limits
- >500 content items
- Server already under heavy load
- Very limited memory (<128MB)

---

## Safety Features

### 1. Auto-Disable on Errors
After 5 consecutive failures, auto-sync turns off automatically.

**Why?** Prevents error loops and server stress.

**What to do?** Check logs, fix issue, re-enable.

### 2. Memory Protection
Stops syncing if memory usage gets too high.

**Why?** Prevents "out of memory" errors.

**What to do?** Use longer interval or increase PHP memory_limit.

### 3. Batch Processing
Maximum 50 items per run.

**Why?** Prevents timeouts.

**What to do?** Nothing! Remaining items sync on next run.

---

## Common Actions

### Test Auto-Sync
1. Click **"Run Sync Now"**
2. Check log for results
3. Verify status shows "Success"

### Adjust Interval
1. Change dropdown selection
2. Click **"Save Auto-Sync Settings"**
3. New interval takes effect immediately

### Disable Auto-Sync
1. Uncheck **"Automatically sync content changes"**
2. Click **"Save Auto-Sync Settings"**
3. Cron job is removed

### Clear Log
1. Scroll to activity log
2. Click **"Clear Log"** button
3. Log is emptied

---

## Monitoring

### What to Watch

**First Week**:
- Check log daily
- Verify "Success" status
- Note "Last run" updates

**Ongoing**:
- Check log weekly
- Watch for failure patterns
- Monitor server resources

### Warning Signs

🚨 **Take action if you see**:
- Multiple consecutive failures
- "Memory threshold exceeded" messages
- Very long execution times (>60 sec)
- Server slowdowns during sync time

**Solution**: Increase interval or check server resources.

---

## Troubleshooting Quick Fixes

### Problem: Not Running
**Fix**: Ensure enabled, site is Primary, subsites connected

### Problem: Too Many Failures
**Fix**: Test subsite connections, check network

### Problem: Content Not Syncing
**Fix**: Run manual sync, check specific error in log

### Problem: Slow Performance
**Fix**: Increase interval, reduce content volume

---

## Tips for Success

### 1. Start Conservative
Begin with 30-60 minute interval, reduce if needed.

### 2. Test First
Use "Run Sync Now" before enabling scheduled sync.

### 3. Monitor Logs
Check activity log after first few runs.

### 4. Match to Need
Don't use 5 minutes just because you can. Match interval to actual update frequency.

### 5. Off-Peak Scheduling
If worried about load, use longer intervals like 6-12 hours during quiet times.

---

## Example Scenarios

### Scenario 1: Busy Training Site
- **Update frequency**: Multiple times daily
- **Content volume**: 150 items
- **Server**: VPS with good resources
- **Recommendation**: **10-15 minute interval**

### Scenario 2: Documentation Site
- **Update frequency**: Few times weekly
- **Content volume**: 50 items
- **Server**: Shared hosting
- **Recommendation**: **2-6 hour interval**

### Scenario 3: Course Catalog
- **Update frequency**: Daily
- **Content volume**: 300 items
- **Server**: Dedicated server
- **Recommendation**: **30-60 minute interval**

---

## Getting Help

1. Check **Activity Log** for error details
2. Review **Status** section for warnings
3. Read full documentation: `AUTO_SYNC_DOCUMENTATION.md`
4. Test with manual sync
5. Contact support with log screenshots

---

**Quick Reference Card**

```
┌─────────────────────────────────────────┐
│ AUTOMATIC SYNC QUICK REFERENCE          │
├─────────────────────────────────────────┤
│ Location: IELTS Courses → Multi-Site    │
│          Sync → Automatic Sync          │
│                                         │
│ Default Interval: 15 minutes            │
│ Min Interval: 5 minutes                 │
│ Max Interval: 24 hours (1440 min)      │
│                                         │
│ Batch Size: 50 items/run                │
│ Memory Limit: 100 MB or 80% PHP limit   │
│ Auto-disable: After 5 failures          │
│                                         │
│ Cron Hook: ielts_cm_auto_sync_content   │
│ Log Table: wp_ielts_cm_auto_sync_log    │
└─────────────────────────────────────────┘
```

---

**Version**: 15.4
**Status**: ✅ Production Ready
