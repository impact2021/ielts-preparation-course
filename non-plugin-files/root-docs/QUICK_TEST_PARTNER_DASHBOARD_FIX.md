# Quick Test Guide - Partner Dashboard Fix (v15.18)

## What Was Fixed

**Problem:** Partner admins couldn't see users and codes created by the site admin (user 9893).

**Root Cause:** Organization ID filtering was blocking data visibility.

**Solution:** Removed org_id filtering entirely - everyone sees everything now.

## Quick Test Steps

### Step 1: Deploy v15.18

1. Deploy the updated plugin code
2. As site administrator, visit any wp-admin page
3. This triggers the V3 migration automatically

### Step 2: Verify Migration (Optional)

Visit: `https://yoursite.com/check-partner-org-ids.php`

Check that:
- ✓ V3 Migration status shows "Complete"
- ✓ All org IDs are either 0 or 1 (not 9893 or other user IDs)

### Step 3: Test Data Visibility

**Test A: Partner Admin Sees Site Admin's Data**

1. Note how many students/codes exist (as site admin)
2. Log out and log in as a partner admin
3. Visit the partner dashboard
4. **Expected:** Partner admin sees the SAME count of students and codes

**Test B: Site Admin Sees Partner Admin's Data**

1. Log in as partner admin
2. Create a test student or code
3. Log out and log in as site admin
4. Visit the partner dashboard
5. **Expected:** Site admin sees the new student/code

**Test C: Partner Admins See Each Other's Data**

1. Log in as Partner Admin 1
2. Create a test student
3. Note the student count
4. Log out and log in as Partner Admin 2
5. **Expected:** Partner Admin 2 sees the same student count including the new student

### Step 4: Verify Remaining Places

1. Check the "Remaining Places" count as site admin
2. Log in as a partner admin
3. Check the "Remaining Places" count
4. **Expected:** Both show the same number (they share the same pool)

## What Changed Technically

### Before (Broken)
```php
// Different queries for admins vs partner admins
if ($partner_org_id === ADMIN_ORG_ID) {
    // Show all users
} else {
    // Filter by org_id ❌ This blocked visibility
}
```

### After (Fixed)
```php
// Everyone sees all users
$users = get_users(array(
    'meta_key' => 'iw_course_group',
    'meta_compare' => 'EXISTS'
));
// ✓ No filtering
```

## Expected Results

| User Type | Can See... |
|-----------|------------|
| Site Admin | ALL students and codes (created by anyone) |
| Partner Admin 1 | ALL students and codes (created by anyone) |
| Partner Admin 2 | ALL students and codes (created by anyone) |

**All users share:**
- Same student count
- Same code list
- Same "remaining places" pool

## Troubleshooting

### Partner admin still can't see data

**Check:**
1. Has V3 migration run? Check the diagnostic script
2. Is the plugin version 15.18? Check at bottom of wp-admin
3. Clear browser cache and hard reload

**Solution:**
```php
// In WordPress console or wp-cli:
delete_option('iw_partner_site_org_migration_v3_done');
// Then visit wp-admin as site administrator to re-run migration
```

### Data counts are different

This should NOT happen after the fix. If it does:
1. Check PHP error logs for migration errors
2. Run the diagnostic script to see org_id distribution
3. Contact support with diagnostic output

## Success Criteria

✅ Partner admins see students created by site admin  
✅ Site admin sees students created by partner admins  
✅ All partner admins see each other's students  
✅ Student counts match across all users  
✅ Remaining places pool is shared  
✅ Diagnostic shows only org_ids 0 and 1 (no 9893)

## Files Changed

- `includes/class-access-codes.php` - Removed org_id filtering
- `check-partner-org-ids.php` - Added V3 migration status
- `ielts-course-manager.php` - Version bump to 15.18
- `VERSION_15_18_ORG_ID_CLEANUP.md` - Full documentation

## Rollback (If Needed)

```bash
# Revert to v15.17
git checkout v15.17

# Prevent migration from running
update_option('iw_partner_site_org_migration_v3_done', true);
```

Note: Rollback will restore the old behavior where partner admins can't see admin-created data.
