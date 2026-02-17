# User Organization Migration for Hybrid Sites

## Overview

This document explains the automatic migration that retroactively assigns organization IDs to existing users on hybrid sites.

## Problem Statement

For hybrid sites that enable organization-based filtering, users need to have the `iw_created_by_partner` meta field set to properly isolate partner data. However, users created before this system was implemented may not have this field, causing them to not appear in partner dashboards.

## Solution

A migration function (`migrate_users_without_organization()`) has been implemented that:

1. **Only runs on hybrid sites** - Non-hybrid sites don't use organization filtering, so this migration is skipped
2. **Finds users without organization** - Identifies users with access code membership roles who lack the `iw_created_by_partner` meta field
3. **Assigns default organization** - Sets the meta field to SITE_PARTNER_ORG_ID (1), which is the default organization for all partner admins

## When It Runs

The migration runs automatically when:
- A site administrator (user with `manage_options` capability) visits any WordPress admin page
- Hybrid mode is enabled (`ielts_cm_hybrid_site_enabled` option is true)
- The migration hasn't been completed yet (checked via `iw_users_without_org_migration_v4_done` option)

## What Gets Migrated

The migration targets users with these WordPress roles:
- `access_academic_module`
- `access_general_module`
- `access_general_english`
- `access_entry_test`

These are users created through:
- Access code registration
- Manual user creation by partner admins
- Stripe payment webhooks

## Technical Implementation

### Safety Features

1. **Locking Mechanism**: Uses a transient lock (`iw_users_without_org_migration_v4_lock`) to prevent concurrent execution
2. **One-Time Execution**: Uses an option flag to ensure the migration only runs once
3. **Admin-Only**: Only runs for users with `manage_options` capability
4. **Hybrid-Only**: Only runs when hybrid mode is enabled

### Database Changes

The migration adds the following user meta:
- **Key**: `iw_created_by_partner`
- **Value**: `1` (SITE_PARTNER_ORG_ID - the default organization)

### Performance

- Single optimized query using `role__in` parameter
- Processes users in memory (not batched for typical site sizes)
- Logs results to error log for monitoring

## Verification

After the migration runs, you can verify it completed successfully by:

1. Checking the WordPress error log for a message like:
   ```
   Users without org migration v4 completed: Assigned X users to organization 1
   ```

2. Checking the WordPress options table for:
   ```
   iw_users_without_org_migration_v4_done = 1
   ```

3. Checking user meta for any user with an access code role:
   ```php
   $org_id = get_user_meta($user_id, 'iw_created_by_partner', true);
   // Should return 1 for migrated users
   ```

## Rollback

If needed, you can reset the migration to run again:

```php
delete_option('iw_users_without_org_migration_v4_done');
delete_transient('iw_users_without_org_migration_v4_lock');
```

Then visit any admin page to trigger the migration again.

## Related Documentation

- `HYBRID_SITE_ORGANIZATION_MANAGEMENT.md` - General organization management guide
- `HYBRID_SITE_IMPROVEMENTS_COMPLETE.md` - Overview of hybrid site features
- `PARTNER_ADMIN_SITE_WIDE_FIX.md` - Technical details of organization system

## Code Location

- **File**: `includes/class-access-codes.php`
- **Method**: `IELTS_CM_Access_Codes::migrate_users_without_organization()`
- **Hook**: `admin_init` action

## Version History

- **V4 Migration** (2026-02-17): Initial implementation for retroactive user organization assignment
