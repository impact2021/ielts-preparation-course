# Partner Admin Site-Wide Visibility Fix

## Problem Statement

Partner admins were being siloed and only seeing users they personally created, which was incorrect behavior. The partner dashboard should be per-site, meaning:

- **All partner admins on the same site should see the same students**
- **All partner admins should share the same number of possible users**
- **No manual organization ID assignment should be required**

This was marked as **URGENT and CRITICAL**.

## Root Cause

The previous implementation had a fallback mechanism in `get_partner_org_id()`:

```php
// OLD CODE
if (empty($org_id)) {
    // Default to user's own ID for backward compatibility
    $org_id = $user_id;  // ❌ This caused siloing
}
```

This meant each partner admin defaulted to their own user ID as their organization ID, causing them to only see:
- Students with `iw_created_by_partner` = their user ID
- Codes with `created_by` = their user ID

## Solution Implemented

### 1. Added Site-Wide Partner Organization Constant

```php
const SITE_PARTNER_ORG_ID = 1;
```

This constant represents the **single organization ID shared by all partner admins** on the site.

### 2. Updated Organization ID Logic

Modified `get_partner_org_id()` to return the site-wide organization ID:

```php
// NEW CODE
if (empty($org_id)) {
    // Use site-wide partner organization ID
    // This means ALL partner admins on this site see the same students and codes
    $org_id = self::SITE_PARTNER_ORG_ID;
}
```

Now ALL partner admins (without a custom org ID set) use the same organization ID (1).

### 3. Added One-Time Data Migration

Created `migrate_partner_data_to_site_org()` to consolidate existing data:

**What it does:**
- Finds all partner admin users (role: 'partner_admin')
- Updates all access codes created by partner admins:
  - Changes `created_by` from individual user IDs → SITE_PARTNER_ORG_ID (1)
- Updates all student user meta:
  - Changes `iw_created_by_partner` from individual user IDs → SITE_PARTNER_ORG_ID (1)

**Safety Features:**
- ✅ Runs only once (tracked by `iw_partner_site_org_migration_done` option)
- ✅ Admin-only execution (`manage_options` capability required)
- ✅ Concurrency protection (transient lock)
- ✅ Batch UPDATE queries (efficient, not N+1)
- ✅ Error handling with logging
- ✅ String type handling for usermeta
- ✅ Validates count (max 1000 partner admins)
- ✅ Doesn't mark complete if no partner admins exist (allows future migration)

## Impact

### Before Fix
- Partner Admin A creates student 1 → only Admin A can see student 1
- Partner Admin B creates student 2 → only Admin B can see student 2
- Each admin has their own limit count

### After Fix
- Partner Admin A creates student 1 → **ALL partner admins** can see student 1
- Partner Admin B creates student 2 → **ALL partner admins** can see student 2
- **All admins share the same limit count**

## Technical Details

### Constants Added
```php
const SITE_PARTNER_ORG_ID = 1;  // Site-wide partner organization ID
```

### Database Changes (via migration)

**Access Codes Table:**
```sql
UPDATE wp_ielts_cm_access_codes 
SET created_by = 1 
WHERE created_by IN (5, 6, 7, ...)  -- partner admin user IDs
```

**User Meta Table:**
```sql
UPDATE wp_usermeta 
SET meta_value = '1' 
WHERE meta_key = 'iw_created_by_partner' 
AND meta_value IN ('5', '6', '7', ...)  -- partner admin user IDs as strings
```

### Functions Modified

1. **`get_partner_org_id()`**
   - Returns `SITE_PARTNER_ORG_ID` for all partner admins (instead of user ID)
   - Admins still return `ADMIN_ORG_ID` (0) to see all data
   
2. **`migrate_partner_data_to_site_org()`** (NEW)
   - Runs on `admin_init` hook
   - Consolidates existing partner admin data
   - Only executes once

### Data Flow

**Creating a new code:**
1. Partner admin creates code
2. `get_partner_org_id()` returns 1 (SITE_PARTNER_ORG_ID)
3. Code saved with `created_by = 1`

**Creating a new student:**
1. Partner admin creates student
2. `get_partner_org_id()` returns 1 (SITE_PARTNER_ORG_ID)
3. Student saved with `iw_created_by_partner = 1`

**Viewing students:**
1. Partner admin views dashboard
2. `get_partner_org_id()` returns 1 (SITE_PARTNER_ORG_ID)
3. `get_partner_students(1)` returns ALL students with `iw_created_by_partner = 1`
4. Result: Partner admin sees ALL students created by ANY partner admin

## Testing Checklist

- [ ] Login as Partner Admin A, create student 1
- [ ] Login as Partner Admin B, verify they see student 1
- [ ] Login as Partner Admin B, create student 2
- [ ] Login as Partner Admin A, verify they see student 2
- [ ] Verify both admins see the same student count
- [ ] Verify both admins see the same remaining spaces
- [ ] Create code as Partner Admin A
- [ ] Verify Partner Admin B sees the code in codes table
- [ ] Login as site administrator
- [ ] Verify admin sees ALL codes and students (from all organizations)

## Migration Notes

### When Migration Runs
- First time an admin user accesses WordPress admin area after deployment
- Uses transient lock to prevent multiple simultaneous executions
- Runs in under 1 second for typical installations

### What If Migration Fails?
- Error logged to PHP error log
- Migration option NOT set to complete
- Will retry on next admin page load
- Safe to retry multiple times (idempotent)

### Skipping Migration
If you have no partner admins yet, migration will skip but not mark as complete, allowing it to run when partner admins are added later.

### Manual Migration Trigger
If needed, you can manually trigger migration:
```php
delete_option('iw_partner_site_org_migration_done');
// Then load any admin page as an administrator
```

## Backward Compatibility

### Existing Installations
- ✅ Migration automatically updates existing data
- ✅ No manual steps required
- ✅ Existing codes and students automatically visible to all partner admins

### Custom Organization IDs
If you previously set custom organization IDs via `iw_partner_organization_id` user meta:
- ✅ Those still work
- ✅ Partner admins with custom org IDs will use their custom ID
- ✅ Partner admins without custom org IDs will use SITE_PARTNER_ORG_ID (1)

### Site Administrators
- ✅ No changes to admin behavior
- ✅ Admins still see ALL data across all organizations

## Security Review

### SQL Injection Protection
- ✅ All queries use `wpdb->prepare()` with proper placeholders
- ✅ Table names constructed safely from WordPress core prefix
- ✅ User input properly sanitized

### Access Control
- ✅ Migration restricted to admin users only
- ✅ Concurrency protection with transient lock
- ✅ Array count validation (max 1000)

### Error Handling
- ✅ Query results checked for errors
- ✅ Errors logged for debugging
- ✅ Failed migrations can retry
- ✅ No partial state (both queries must succeed)

## Rollback Instructions

If you need to revert this change:

```php
// 1. Prevent migration from running
update_option('iw_partner_site_org_migration_done', true);

// 2. Revert to previous code
git checkout e041497 -- includes/class-access-codes.php

// 3. Optional: Restore old behavior by setting custom org IDs
// For each partner admin who should only see their own data:
update_user_meta($partner_admin_id, 'iw_partner_organization_id', $partner_admin_id);
```

## Performance Impact

- ✅ Minimal - migration runs once
- ✅ Uses batch queries (not N+1)
- ✅ No additional queries in normal operation
- ✅ Same database indexes used

## Support

### Common Questions

**Q: What if I want partner admins to be in separate organizations?**
A: Set the `iw_partner_organization_id` user meta to different values for different groups.

**Q: Can I add more partner admins after deployment?**
A: Yes! New partner admins will automatically use SITE_PARTNER_ORG_ID (1).

**Q: What if migration fails?**
A: Check PHP error logs. The migration will retry on next admin page load.

**Q: How do I verify the migration ran?**
A: Check if option `iw_partner_site_org_migration_done` is set to true:
```php
echo get_option('iw_partner_site_org_migration_done') ? 'Migration complete' : 'Migration pending';
```

## Files Changed

- `includes/class-access-codes.php` - Main implementation file

## Deployment Notes

1. **Backup database before deployment** (standard practice)
2. Deploy code to production
3. First admin user to access wp-admin will trigger migration
4. Migration should complete in < 1 second
5. Verify partner admins see all students

## References

- Previous implementation: PR #684 (Partnership Dashboard Fix)
- Related documentation: `PARTNERSHIP_DASHBOARD_FIX.md`
- Security review: `SECURITY_SUMMARY_PARTNERSHIP_FIX.md`
