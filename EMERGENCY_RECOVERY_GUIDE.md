# EMERGENCY RECOVERY GUIDE - Sync Timeout Fix

## 🚨 IMMEDIATE ACTIONS REQUIRED

Your subsites are currently inaccessible due to stuck sync operations. Follow these steps **RIGHT NOW** to recover:

### Step 1: Clear Stuck Sync Locks (IMMEDIATE)

1. **Log into your WordPress primary site admin**
2. **Navigate to**: Courses → Sync Status
3. **Click**: "Clear Stuck Sync Locks" button (red button next to "Check Sync Status")
4. **Confirm** the action when prompted
5. **Wait** for success message showing locks cleared

**Result**: All sync locks will be cleared immediately, allowing subsites to become accessible again.

### Step 2: Verify Subsite Access (1 minute later)

1. **Try accessing each subsite** in your browser
2. **If still timing out**: Wait 30 seconds and repeat Step 1
3. **If accessible**: Subsites are now recovered! 🎉

### Step 3: Prevent Future Issues

**DO NOT** sync large courses (>10 lessons) all at once anymore!

**New Limits Implemented**:
- Maximum timeout per request: **30 seconds** (was 120s)
- Maximum lessons synced at once: **10 lessons** (was unlimited)
- Concurrent sync prevention: **Only 1 sync at a time**
- Circuit breaker: **Stops after 3 consecutive failures**

**Best Practices Going Forward**:
1. **Sync individual lessons** instead of entire courses
2. **Sync small batches** - max 10 lessons at a time
3. **Check "Clear Stuck Sync Locks"** if sync takes >2 minutes
4. **Avoid syncing during peak traffic** times

---

## What Was Fixed

### Emergency Fixes Applied (Both Commits)

#### 1. Sync Lock Mechanism ✅
- Prevents multiple sync operations from running simultaneously
- User-specific locks expire after 5 minutes automatically
- Returns error immediately if sync already in progress

#### 2. Timeout Reduction (75% Faster) ✅
- **Before**: 30-120 second timeouts (could block for 2 minutes!)
- **After**: 10-30 second timeouts (maximum 30 second block)
- Subsites can now respond much faster

#### 3. Circuit Breaker Pattern ✅
- Detects when subsites are failing
- Stops attempting sync after 3 consecutive errors
- Prevents cascading failures across all subsites

#### 4. Course Sync Limiter ✅
- Limits to **10 lessons maximum** per sync operation
- Shows warning if more lessons need syncing
- Must sync remaining lessons individually
- Prevents massive operations that cause timeouts

#### 5. Early Bailout Logic ✅
- Checks if child content failed before continuing
- Checks if main content failed for all subsites
- Stops immediately instead of continuing with more syncs
- Reduces time subsites are blocked

#### 6. Manual Lock Clearing UI ✅
- **New button** in Sync Status page
- **AJAX handler** to clear database locks
- **Admin-only** access for security
- **Confirmation dialog** before clearing

---

## Technical Details

### Root Cause Analysis

**The Problem**:
1. Syncing a course triggered hundreds of sequential HTTP requests
2. Each request could take up to 120 seconds to timeout
3. Requests were blocking (waited for response before continuing)
4. Example: 1 course + 20 lessons + 100 resources = 121 requests × 120s = **4+ hours** of potential blocking!

**Why Subsites Were Unreachable**:
- Subsite REST API endpoints were busy processing sync requests
- WordPress has limited PHP workers (usually 5-10)
- All workers were consumed by stuck sync operations
- New page requests couldn't be processed → timeouts

### The Fix

**Immediate Relief** (Reduces 75% of blocking time):
- Timeout: 120s → 30s maximum
- Limits: Unlimited lessons → 10 lessons per operation
- Result: 75% less time subsites are blocked per sync

**Protection Mechanisms**:
- Sync locks prevent concurrent operations
- Circuit breaker stops on repeated failures
- Early bailout when subsites are down
- Manual override to clear stuck locks

**Long-term Solution** (Phase 2 - Not Implemented Yet):
- Background job queue for async syncing
- Progress monitoring with visual feedback
- Per-subsite staggered sync
- Estimated time: 4-8 hours development

---

## Monitoring & Prevention

### How to Check Sync Status

1. Go to: **Courses → Sync Status**
2. Click: **"Check Sync Status"**
3. Wait for table to populate
4. Green checkmarks = synced correctly
5. Red X = needs syncing

### Warning Signs of Sync Issues

- Sync button spinning for >2 minutes
- Admin pages taking >30 seconds to load
- Subsites returning 504 Gateway Timeout errors
- "A sync operation is already in progress" error message

### Recovery Actions

If you see warning signs:
1. **Stop the sync** - close the browser tab
2. **Wait 30 seconds** - let current operation timeout
3. **Clear sync locks** - use the button
4. **Try again** - but sync smaller batches

---

## FAQ

**Q: Will clearing sync locks delete my content?**
A: No! It only clears the "in progress" flags. No content is deleted.

**Q: Can I still sync courses?**
A: Yes, but in smaller batches. Sync 10 lessons at a time max.

**Q: What if subsites are still unreachable?**
A: Wait 60 seconds, clear locks again, and check if any PHP processes are still running.

**Q: How do I sync a course with 50 lessons?**
A: Sync the course first (without lessons), then sync lessons individually or in groups of 10.

**Q: Will this happen again?**
A: Not if you follow the new limits. The system now prevents it by limiting batch sizes.

**Q: Do I need to update subsites too?**
A: No, only the primary site code was changed. Subsites will automatically benefit.

---

## Support

If subsites are **still unreachable after 5 minutes** of clearing locks:

1. Check server logs for stuck PHP processes
2. Restart PHP-FPM or Apache (requires server access)
3. Check database for stuck queries: `SHOW PROCESSLIST;`
4. Contact hosting support if issue persists

The code changes are now live and should prevent this from happening again!
