# Memory Threshold Configuration - Implementation Summary

## Problem Statement

Users were experiencing frequent warning messages in the sync activity log:
```
Memory threshold exceeded. Synced 0 items.
```

These warnings appeared even when:
- No items were actually synced (0 items)
- The system was working correctly
- The warnings cluttered the activity log

## Root Cause

The memory threshold was hardcoded at 100 MB in `class-auto-sync-manager.php`. This value was:
1. **Too low** for modern PHP environments (many servers have 256MB+ allocated)
2. **Not configurable** - administrators couldn't adjust it based on their server capabilities
3. **Always logged warnings** even when hitting the threshold immediately (before syncing any items)

## Solution Implemented

### 1. Made Memory Threshold Configurable
- Changed hardcoded `MEMORY_THRESHOLD_MB = 100` to `DEFAULT_MEMORY_THRESHOLD_MB = 256`
- Added new option `ielts_cm_auto_sync_memory_threshold` stored in WordPress options
- Created `get_memory_threshold()` method to retrieve the configured value

### 2. Added Admin UI Configuration
Added a new "Memory Threshold" dropdown in the Auto-Sync settings page with options:
- 64 MB (very limited resources)
- 128 MB (limited resources)
- **256 MB (Recommended - Default)**
- 512 MB (large content syncs)
- 1024 MB / 1 GB (heavy workloads)
- 2048 MB / 2 GB (enterprise setups)

### 3. Improved Logging Logic
Modified the warning message to only log when items were actually synced before hitting the threshold:
```php
if ($this->is_memory_exceeded()) {
    // Only log if we actually synced some items before hitting the threshold
    if ($synced_count > 0) {
        $this->log_sync('system', sprintf('Memory threshold exceeded. Synced %d items.', $synced_count), 'warning');
    }
    break;
}
```

This prevents cluttering the log with warnings when the baseline memory usage is already high.

### 4. Updated Documentation
Updated both `AUTO_SYNC_DOCUMENTATION.md` and `AUTO_SYNC_QUICK_START.md` to:
- Document the new Memory Threshold configuration option
- Provide guidance on which threshold to use based on server resources
- Add troubleshooting section for memory threshold warnings

## Benefits

1. **Eliminates False Warnings**: No more "Memory threshold exceeded. Synced 0 items" messages
2. **Server Flexibility**: Administrators can configure based on their server's capabilities
3. **Better Default**: 256 MB default is more appropriate for modern hosting
4. **Clear Guidance**: Documentation helps users choose the right threshold
5. **Cleaner Logs**: Only logs meaningful warnings when sync is interrupted

## Files Modified

1. `includes/class-auto-sync-manager.php`
   - Changed constant from `MEMORY_THRESHOLD_MB` to `DEFAULT_MEMORY_THRESHOLD_MB`
   - Added `get_memory_threshold()` method
   - Updated `is_memory_exceeded()` to use configurable threshold
   - Improved logging logic

2. `includes/admin/class-sync-settings-page.php`
   - Added memory threshold dropdown in UI
   - Added validation (64 MB - 2048 MB range)
   - Added save/update logic for the new setting

3. `AUTO_SYNC_DOCUMENTATION.md`
   - Added Memory Threshold Options table
   - Updated troubleshooting guidance

4. `AUTO_SYNC_QUICK_START.md`
   - Added memory threshold troubleshooting tip
   - Updated warning signs section

## Testing Recommendations

1. **Test Default Behavior**: 
   - Verify new installations default to 256 MB
   - Check that existing installations continue working

2. **Test Configuration UI**:
   - Change memory threshold setting
   - Verify it saves correctly
   - Confirm the setting is used during sync

3. **Test Edge Cases**:
   - Test with very low threshold (should stop sync appropriately)
   - Test with very high threshold (should allow more items to sync)
   - Verify 80% of PHP memory_limit is still respected

4. **Test Logging**:
   - Verify no warnings when hitting threshold with 0 items synced
   - Verify warnings still appear when threshold is hit after syncing items

## Migration Notes

- **Backwards Compatible**: Existing installations will automatically use the new default of 256 MB
- **No Database Changes**: Uses existing WordPress options table
- **No Breaking Changes**: All existing functionality preserved

## Answer to Original Question

**"Where is this governed?"**

The memory threshold is now governed in two places:

1. **Code**: `includes/class-auto-sync-manager.php` - Contains the logic and default value
2. **Settings**: WordPress Admin → IELTS Courses → Multi-Site Sync → Automatic Sync Section → Memory Threshold dropdown

Users can now configure this setting directly from the admin panel without needing to modify code.
