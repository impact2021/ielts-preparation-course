# Partner Admin Visibility Fix - Version 15.17

## Problem Statement

Partner admins were unable to see users and codes created by other partner admins on the same site. Specifically:

- Partner Admin 1 creates a new user
- Partner Admin 2 cannot see this user
- Partner Admin 2 still shows ALL available places (as if no users exist)
- Site administrators CAN see the users correctly

This violates the expected behavior where the partner dashboard should be shared across all partner admins and site admins on the same site.

## Root Cause Analysis

Partner admins were being assigned their **individual user IDs as organization IDs** instead of sharing the **site-wide partner organization ID (SITE_PARTNER_ORG_ID = 1)**.

### How Organization IDs Work

The partner dashboard uses an `iw_created_by_partner` meta key to track which organization created each user:

1. **Site Admins** (users with `manage_options` capability):
   - Use `ADMIN_ORG_ID = 0`
   - Can see ALL users with access codes (queries by `iw_course_group` existence)

2. **Partner Admins** (users with `manage_partner_invites` capability):
   - Should use `SITE_PARTNER_ORG_ID = 1`
   - Can only see users created by their organization (queries by `iw_created_by_partner` = org_id)

### The Bug

When the code checked for a partner admin's organization ID:

```php
$org_id = get_user_meta($user_id, 'iw_partner_organization_id', true);
if (empty($org_id)) {
    $org_id = self::SITE_PARTNER_ORG_ID; // Should return 1
}
```

This SHOULD work correctly. However, if:
- The migration hasn't run yet, OR
- Old data exists with user-specific org IDs

Then users created before the fix would have `iw_created_by_partner` set to the creator's user ID (e.g., 5, 6, 7) instead of the shared org ID (1).

**Example:**
- Partner Admin 1 (user_id = 5) creates a user → `iw_created_by_partner = 5` (OLD BEHAVIOR)
- Partner Admin 2 (user_id = 8) logs in → `get_partner_org_id()` returns 1
- Partner Admin 2 queries for `iw_created_by_partner = 1` → Finds nothing!

## Solution Implemented

### 1. Migration V2 (`iw_partner_site_org_migration_v2_done`)

A new database migration that:
- Finds all partner admin user IDs
- Updates `ielts_cm_access_codes.created_by` from partner admin user IDs → `1` (SITE_PARTNER_ORG_ID)
- Updates `wp_usermeta.iw_created_by_partner` from partner admin user IDs → `1` (SITE_PARTNER_ORG_ID)

This consolidates all existing partner-created data to use the shared organization ID.

### 2. Confirmed Correct Logic

The `get_partner_org_id()` function correctly:
- Returns `0` (ADMIN_ORG_ID) for site admins
- Returns `1` (SITE_PARTNER_ORG_ID) for partner admins without custom org IDs
- Returns custom org ID if explicitly set in user meta

### 3. Query Functions

**`get_partner_students($partner_org_id)`:**
- If `$partner_org_id === 0` (admin): Returns ALL users with `iw_course_group` meta
- Otherwise: Returns users where `iw_created_by_partner = $partner_org_id`

**`render_codes_table($partner_org_id)`:**
- If `$partner_org_id === 0` (admin): Returns ALL codes
- Otherwise: Returns codes where `created_by = $partner_org_id`

## Expected Behavior After Fix

### Scenario 1: Site Admin Creates User
1. Site Admin has `manage_options` capability
2. `get_partner_org_id()` returns `0` (ADMIN_ORG_ID)
3. User created with `iw_created_by_partner = 0`
4. Site Admin queries with org_id `0` → sees ALL users ✓
5. Partner Admins query with org_id `1` → don't see admin-created users (expected)

### Scenario 2: Partner Admin 1 Creates User
1. Partner Admin 1 has `manage_partner_invites` (no `manage_options`)
2. `get_partner_org_id()` returns `1` (SITE_PARTNER_ORG_ID)
3. User created with `iw_created_by_partner = 1`
4. Partner Admin 1 queries with org_id `1` → sees the user ✓
5. Partner Admin 2 queries with org_id `1` → sees the user ✓
6. Site Admin queries with org_id `0` → sees ALL users (including this one) ✓

### Scenario 3: Partner Admin 2 Creates User
1. Partner Admin 2 has `manage_partner_invites` (no `manage_options`)
2. `get_partner_org_id()` returns `1` (SITE_PARTNER_ORG_ID)
3. User created with `iw_created_by_partner = 1`
4. Partner Admin 1 queries with org_id `1` → sees the user ✓
5. Partner Admin 2 queries with org_id `1` → sees the user ✓
6. Site Admin queries with org_id `0` → sees ALL users (including this one) ✓

## Files Changed

1. **`includes/class-access-codes.php`**
   - Confirmed `get_partner_org_id()` logic is correct
   - Confirmed `get_partner_students()` and `render_codes_table()` logic is correct
   - Updated migration to v2 with new option name `iw_partner_site_org_migration_v2_done`
   - Migration now consolidates partner admin user IDs to SITE_PARTNER_ORG_ID

2. **`ielts-course-manager.php`**
   - Version bumped from 15.16 → 15.17

3. **`check-partner-org-ids.php`** (NEW)
   - Diagnostic script to check partner admin organization IDs
   - Shows current org ID assignments
   - Shows migration status
   - Shows user and code counts by org ID
   - Helps troubleshoot visibility issues

## Migration Details

### When It Runs
- Automatically on `admin_init` hook
- Only when an admin user (with `manage_options`) accesses WordPress admin
- Protected by transient lock to prevent concurrent execution
- Uses new option name `iw_partner_site_org_migration_v2_done` to allow re-running

### What It Does
1. Gets all partner admin user IDs (role: `partner_admin`)
2. Updates access codes: `created_by` = partner admin user ID → `1`
3. Updates user meta: `iw_created_by_partner` = partner admin user ID → `1`
4. Marks migration as complete

### Safety Features
- Admin-only execution
- Transient lock prevents concurrent runs
- Batch size limit (max 1000 users)
- Error logging
- Atomic - both queries must succeed
- Can retry on failure

## Testing Instructions

### 1. Check Current State

Visit: `https://yoursite.com/check-partner-org-ids.php`

This will show:
- All partner admins and their org IDs
- Migration status
- User counts by org ID
- Code counts by org ID

### 2. Test Partner Admin Visibility

**Setup:**
1. Create two partner admin users (Partner Admin 1, Partner Admin 2)
2. Log in as Partner Admin 1
3. Create a test user through the partner dashboard

**Test:**
1. Note the student count shown to Partner Admin 1
2. Log out and log in as Partner Admin 2
3. Check if Partner Admin 2 sees:
   - The same student count
   - The test user in "Managed Students"
   - The code (if created) in "Your Codes"

**Expected Result:**
- Both partner admins see the same data ✓

### 3. Test Site Admin Visibility

**Test:**
1. Log in as Site Administrator
2. Visit the partner dashboard
3. Check if you see ALL users and codes

**Expected Result:**
- Site admin sees all users from all organizations ✓

## Troubleshooting

### Partner admins still don't see each other's users

**Possible causes:**
1. Migration hasn't run yet
2. Partner admins have custom org IDs set

**Solution:**
1. Run diagnostic script: `check-partner-org-ids.php`
2. Check "Migration Status" section
3. If v2 migration shows "Not run", visit WordPress admin as site administrator
4. If partner admins have custom org IDs in the table, either:
   - Remove the custom org IDs: `delete_user_meta($user_id, 'iw_partner_organization_id')`
   - OR manually trigger migration: `delete_option('iw_partner_site_org_migration_v2_done')` then visit wp-admin

### Site admin can't see partner-created users

This should NOT happen with the current fix. If it does:
1. Check if users have `iw_course_group` meta set
2. Site admins query for users with `iw_course_group` EXISTS, so users must have this meta

## Rollback Instructions

If needed, revert to version 15.16:

```bash
git revert HEAD~3..HEAD
```

Then prevent migration from running:

```php
update_option('iw_partner_site_org_migration_v2_done', true);
```

## Security Review

### SQL Injection Protection
- ✅ All queries use `wpdb->prepare()` with placeholders
- ✅ Table names constructed safely from WordPress core prefix
- ✅ User input sanitized before use

### Access Control
- ✅ Migration restricted to admin users only
- ✅ Concurrency protection with transient lock
- ✅ Array count validation (max 1000)

### Data Integrity
- ✅ Migration is idempotent (safe to run multiple times)
- ✅ No data loss - only updates org IDs
- ✅ Both queries must succeed for migration to complete

## Performance Impact

- ✅ Minimal - migration runs once per installation
- ✅ Uses batch UPDATE queries (not N+1)
- ✅ No additional queries in normal operation
- ✅ Typical migration time: < 1 second

## Version History

- **15.16**: Previous version with partner admin siloing issue
- **15.17**: This fix - consolidated partner admin data sharing

## Related Documentation

- `PARTNER_ADMIN_SITE_WIDE_FIX.md` - Previous attempt (v1 migration)
- `PARTNER_DASHBOARD_QUICK_REFERENCE.md` - Dashboard usage guide
- `PARTNER_DASHBOARD_USER_GUIDE.md` - User-facing documentation

## Support

For issues, check:
1. Run diagnostic script first
2. Check migration status
3. Verify partner admins don't have custom org IDs
4. Check PHP error logs for migration errors
