# Version 16.6 Release Notes

**Release Date**: February 2026  
**Type**: Bug Fix Release

## Overview
This release removes the Master Access Code option from the hybrid site partner dashboard. The master code feature is for partner sites only.

## Changes

### 1. Master Access Code Hidden on Hybrid Sites
**Issue**: The "Master Access Code" card was displayed on the hybrid site partner dashboard, but this feature is only applicable to partner (non-hybrid) sites.

**Fix**:
- The Master Access Code card is now only rendered when the site is **not** in hybrid mode (`ielts_cm_hybrid_site_enabled` is false)
- `get_master_code()` is no longer called on hybrid sites

**Impact**: Hybrid site partner admins will no longer see the Master Access Code section in their dashboard. Partner site admins (non-hybrid) are unaffected.

---

## Technical Changes

### Files Modified
- `ielts-course-manager.php` — Version bumped to 16.6
- `includes/class-access-codes.php` — Master Access Code card wrapped in `!$is_hybrid_mode` condition; `get_master_code()` call skipped for hybrid sites
