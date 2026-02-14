# Auto-Sync Memory Threshold Configuration - Release Notes

**Date**: 2026-02-14  
**Version**: 15.50  
**Type**: Enhancement  

## Overview

This release adds configurable memory threshold settings for the Auto-Sync feature, resolving issues with frequent "Memory threshold exceeded" warnings and providing administrators with better control over sync resource usage.

## What's New

### Configurable Memory Threshold

**Feature**: Memory threshold for auto-sync is now configurable through the admin interface.

**Location**: WordPress Admin → IELTS Courses → Multi-Site Sync → Automatic Sync → Memory Threshold

**Available Options**:
- 64 MB - Very limited resources (small shared hosting)
- 128 MB - Limited resources (budget hosting)
- **256 MB - Recommended (Default)** - Most sites
- 512 MB - Large content syncs (sites with many courses)
- 1024 MB (1 GB) - Heavy workloads (large-scale deployments)
- 2048 MB (2 GB) - Very large syncs (enterprise setups)

### Improvements

1. **Increased Default**: Changed default memory threshold from 100 MB to 256 MB, more appropriate for modern hosting environments

2. **Reduced Log Clutter**: Memory threshold warnings now only appear when sync work was actually interrupted (synced_count > 0), eliminating spurious "Memory threshold exceeded. Synced 0 items" messages

3. **Better Documentation**: Added comprehensive guidance on choosing the right memory threshold based on server resources and content volume

## Problem Solved

**Issue**: Users were experiencing frequent warnings in sync activity logs:
```
Memory threshold exceeded. Synced 0 items.
```

**Root Cause**: 
- Hardcoded 100 MB threshold was too low for modern PHP environments
- System checked memory before starting work, triggering warnings even with 0 items synced
- No way for administrators to adjust based on their server capabilities

## Technical Changes

### Modified Files

1. **includes/class-auto-sync-manager.php**
   - Changed `MEMORY_THRESHOLD_MB` constant to `DEFAULT_MEMORY_THRESHOLD_MB = 256`
   - Added `get_memory_threshold()` method to retrieve configurable value
   - Updated `is_memory_exceeded()` to use the configurable threshold
   - Improved warning logic to only log when `$synced_count > 0`

2. **includes/admin/class-sync-settings-page.php**
   - Added Memory Threshold dropdown UI control
   - Added validation (64 MB - 2048 MB range)
   - Added save/update logic for `ielts_cm_auto_sync_memory_threshold` option

3. **AUTO_SYNC_DOCUMENTATION.md**
   - Added Memory Threshold Options table
   - Updated server load protection description
   - Added troubleshooting guidance

4. **AUTO_SYNC_QUICK_START.md**
   - Added memory threshold troubleshooting section
   - Updated warning signs guidance

5. **MEMORY_THRESHOLD_FIX_SUMMARY.md** (New)
   - Comprehensive implementation documentation

## Benefits

✅ **No More False Warnings**: Eliminates "Synced 0 items" warnings that clutter activity logs  
✅ **Administrator Control**: Configure based on server capabilities and content volume  
✅ **Better Defaults**: 256 MB default suitable for most modern hosting  
✅ **Clear Guidance**: Documentation helps choose the right threshold  
✅ **Backwards Compatible**: Existing installations continue working with improved defaults  

## Upgrade Notes

- **Automatic**: No action required. Existing installations will use the new 256 MB default
- **Recommended**: If you previously saw frequent memory warnings, visit the Auto-Sync settings and increase the threshold to 512 MB or 1 GB
- **Safe**: The system still respects 80% of your PHP `memory_limit` as a failsafe

## Configuration Guide

### When to Increase Memory Threshold

Increase if you see:
- "Memory threshold exceeded" warnings with items actually synced
- Sync operations completing in multiple runs when content is available
- Server has ample memory but sync is conservative

### When to Decrease Memory Threshold

Decrease if you experience:
- Server memory issues during sync
- Slow server performance during auto-sync runs
- Shared hosting with strict resource limits

### Monitoring

Check the Auto-Sync activity log regularly:
- Green "Success" messages = healthy operation
- Orange "Warning" with synced items = threshold may need adjustment
- Warnings without synced items = should be eliminated in this version

## Testing Performed

✅ PHP syntax validation  
✅ Code review completed  
✅ Documentation updated  
✅ Settings UI validation (64-2048 MB range enforced)  
✅ Backwards compatibility verified  

## Related Documentation

- `AUTO_SYNC_DOCUMENTATION.md` - Complete auto-sync documentation
- `AUTO_SYNC_QUICK_START.md` - Quick start guide
- `MEMORY_THRESHOLD_FIX_SUMMARY.md` - Implementation details

## Support

For issues or questions:
1. Check the Auto-Sync activity log for specific error messages
2. Review the troubleshooting section in `AUTO_SYNC_QUICK_START.md`
3. Ensure Memory Threshold is appropriate for your server resources

---

**Previous Version**: 15.49  
**Next Version**: TBD
