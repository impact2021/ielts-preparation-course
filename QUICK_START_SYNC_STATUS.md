# Quick Start Guide: Sync Status Page

## What It Does

Displays synchronization status of all your course content (courses, lessons, sub-lessons, exercises) across all connected subsites in one comprehensive view.

## Where to Find It

```
WordPress Admin Dashboard
    └── IELTS Courses
        └── Sync Status  ← Click here!
```

## What You'll See

### Page Header
```
┌─────────────────────────────────────────────────────┐
│         Content Sync Status                         │
│                                                     │
│  [🔄 Check Sync Status]  ✓ Sync status updated     │
└─────────────────────────────────────────────────────┘
```

### Summary Dashboard (appears after checking)
```
┌────────────┬────────────┬────────────┬────────────┐
│  Synced    │ Out of Sync│Never Synced│   Total    │
│    42      │      3     │     5      │    50      │
│  (green)   │  (yellow)  │   (red)    │            │
└────────────┴────────────┴────────────┴────────────┘
```

### Status Table
```
Content Item                  Type        Site A         Site B
─────────────────────────────────────────────────────────────────
▶ IELTS Course 1             Course      ✓ Synced       ✓ Synced
  ▸ Lesson 1                 Lesson      ✓ Synced       ⟳ Out of Sync
    • Sub-lesson A           Sub-lesson  ✓ Synced       ⟳ Out of Sync
    • Exercise 1             Exercise    ✓ Synced       ⚠ Never Synced
  ▸ Lesson 2                 Lesson      ✓ Synced       ✓ Synced
```

## Status Badge Colors

| Badge | Meaning | Action Needed |
|-------|---------|---------------|
| 🟢 **Synced** | Content is up-to-date | None |
| 🟡 **Out of Sync** | Content needs updating | Push to subsite |
| 🔴 **Never Synced** | Content missing on subsite | Push to subsite |

## How to Use

### Step 1: Access the Page
1. Log into WordPress Admin
2. Navigate to **IELTS Courses → Sync Status**

### Step 2: View Current Status
- The page loads with the last known sync status
- Each content item shows status for each connected subsite
- Color coding makes issues easy to spot

### Step 3: Check for Updates
1. Click the **"Check Sync Status"** button
2. Wait for the check to complete (shows loading animation)
3. View the summary dashboard with statistics
4. Page automatically refreshes with updated status

### Step 4: Take Action
- **Green badges**: Everything good! No action needed
- **Yellow badges**: Content changed, push updates to subsites
- **Red badges**: Content never pushed, sync it for the first time

## Prerequisites

Before you can use this page:

✓ Configure site as **Primary** in Multi-Site Sync settings
✓ Add at least one **subsite connection**
✓ Create some **course content** (optional but recommended)

## Common Scenarios

### Scenario 1: All Content Synced
```
Summary: 50 Synced | 0 Out of Sync | 0 Never Synced
Action: No action needed. Everything is in sync!
```

### Scenario 2: Some Content Out of Sync
```
Summary: 42 Synced | 5 Out of Sync | 3 Never Synced
Action: Review yellow/red badges and push updates
```

### Scenario 3: New Subsite Added
```
Summary: 0 Synced | 0 Out of Sync | 50 Never Synced
Action: Push all content to the new subsite
```

## Tips

💡 **Check regularly**: After making content changes, check sync status

💡 **Use filters**: Look for red/yellow badges to find problems quickly

💡 **Timestamp info**: Hover over status to see when last synced

💡 **Bulk sync**: For many items, use Multi-Site Sync page for bulk operations

## Troubleshooting

### Issue: Page shows warning message
**Cause**: Site not configured as Primary or no subsites connected
**Fix**: Go to Multi-Site Sync settings, set as Primary, add subsites

### Issue: Status shows "Unknown"
**Cause**: Status hasn't been checked yet
**Fix**: Click "Check Sync Status" button

### Issue: All items show "Never Synced"
**Cause**: Content hasn't been pushed to subsites yet
**Fix**: Use Multi-Site Sync page or content editors to push content

### Issue: Status wrong after manual sync
**Cause**: Database not updated yet
**Fix**: Click "Check Sync Status" to refresh

## What's Next?

After identifying out-of-sync content:

1. Go to **IELTS Courses → Multi-Site Sync**
2. Or edit individual content items
3. Push updates to subsites
4. Return to Sync Status page
5. Click "Check Sync Status" to verify

## Need More Help?

See detailed documentation:
- `SYNC_STATUS_PAGE_DOCUMENTATION.md` - Complete feature guide
- `SYNC_STATUS_VISUAL_GUIDE.md` - Visual mockups and UI details
- `SYNC_STATUS_IMPLEMENTATION_SUMMARY.md` - Technical details

---

**Quick Access Path**: IELTS Courses → Sync Status
**Page Location**: `?page=ielts-cm-sync-status`
**Capability Required**: `manage_options` (Administrator)
