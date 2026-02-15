# Remove Time-Based Awards - Release Notes

**Date**: 2026-02-14  
**Version**: 15.51  
**Type**: Bug Fix  

## Overview

This release removes time-based awards (Early Bird and Night Owl) that were causing incorrect award notifications for users in different timezones around the world.

## Problem

The IELTS Preparation Course is used globally across multiple timezones, but the Early Bird and Night Owl awards were based on server time rather than the user's local time. This caused users to receive incorrect awards based on their timezone location.

**Example Issue**: 
- A user in New Zealand completing an exercise at 10 AM local time would receive a "Night Owl" award (for completing work after 9 PM) because the server time was 9 PM UTC-7.
- Users in different timezones could never earn certain awards due to timezone misalignment.

## Changes Made

### Awards Removed

The following time-based awards have been removed from the system:

1. **Early Bird** (Badge)
   - Previous Description: "Complete an exercise before 9 AM"
   - Removed due to timezone dependency

2. **Night Owl** (Badge)
   - Previous Description: "Complete an exercise after 9 PM"
   - Removed due to timezone dependency

### Technical Changes

**File Modified**: `includes/class-awards.php`

1. Removed Early Bird and Night Owl from the awards array (lines 44-45)
2. Removed time-based award checking logic (lines 273-281)
3. Updated badge count from 15 to 13
4. Total awards reduced from 50 to 48

## Impact

- **Total Awards**: Reduced from 50 to 48
- **Badge Count**: Reduced from 15 to 13
- **Shield Count**: Unchanged at 20
- **Trophy Count**: Unchanged at 15

All remaining 48 awards are timezone-independent and will work correctly for users worldwide.

## Upgrade Notes

- Users who previously earned Early Bird or Night Owl awards will retain those awards in their history
- These awards will no longer appear in the available awards list
- No database migration is required
- The change is backward compatible

## Benefits

✅ Fair award system for all users regardless of timezone  
✅ Eliminates confusing award notifications  
✅ Maintains all other functional awards  
✅ Cleaner, more accurate gamification experience  

## Alternative Considered

An alternative solution would have been to implement timezone-aware award tracking using the user's local time. However, this was rejected because:
- Requires storing user timezone preferences
- Adds complexity to the award system
- Would still not work correctly for users traveling across timezones
- Time-based awards don't align with the core learning objectives of the course

Removing the time-based awards provides the cleanest and most maintainable solution.
