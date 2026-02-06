# Partnership Dashboard Fix - Implementation Summary

## Problem Statement

The partnership dashboard had two critical issues:

1. **Multiple partner admins saw different data** - Each partner admin only saw students/codes they personally created, not those created by other admins in the same organization
2. **Partner admins could access WordPress backend** - There was no explicit blocking to prevent partner admins from accessing `/wp-admin`

## Solution Overview

### 1. Partner Organization System

Introduced a **partner organization ID** concept that allows multiple partner admins to be grouped together and share the same view of:
- Students (managed users)
- Invite codes
- Remaining student spaces
- All dashboard data

#### How It Works

- **New user meta key**: `iw_partner_organization_id`
- **Admin organization ID**: Special value `0` (constant: `ADMIN_ORG_ID`) indicates site admins who see ALL data
- **Partner org ID**: Multiple partner admins can share the same org ID to see the same data
- **Backward compatibility**: Partner admins without an org ID assignment default to their own user ID (existing behavior)

#### Key Functions

**`get_partner_org_id($user_id = null)`**
- Retrieves the partner organization ID for a user
- Returns `ADMIN_ORG_ID` (0) for site admins
- Returns org ID from user meta or user_id as fallback
- Uses `user_can($user_id, 'manage_options')` to properly check capabilities

### 2. Backend Access Control

Added explicit blocking of partner admins from WordPress backend.

**`block_partner_admin_backend()`**
- Hooked to `admin_init`
- Checks if user has `manage_partner_invites` but NOT `manage_options`
- Redirects to home page or custom partner dashboard URL
- Respects AJAX requests (doesn't block AJAX functionality)
- **Security features**:
  - Uses `esc_url_raw()` to sanitize redirect URL
  - Uses `wp_validate_redirect()` to prevent open redirect attacks
  - Uses `wp_safe_redirect()` for secure redirects

### 3. Data Filtering Updates

All data retrieval functions now use partner organization ID:

#### Dashboard Display
- `partner_dashboard_shortcode()` - Uses org ID instead of user ID
- Shows student count for the organization, not individual user

#### Codes Table
- `render_codes_table($partner_org_id)` - Filters codes by org ID
- Admins see ALL codes (no filter when org_id = ADMIN_ORG_ID)
- Uses prepared SQL statements for security

#### Student Management
- `get_partner_students($partner_org_id)` - Returns students for organization
- Admins get all students with access codes
- Partner admins get only students in their organization

#### Code & User Creation
- `ajax_create_invite()` - Stores codes with partner org ID
- `ajax_create_user_manually()` - Assigns users to partner org ID
- Student registration inherits org ID from code creator

## Security Enhancements

### 1. SQL Injection Prevention
- Consistent use of `wpdb->prepare()` for all database queries
- Table names constructed safely from `$wpdb->prefix`
- All user inputs properly parameterized

### 2. Open Redirect Prevention
- URL validation with `esc_url_raw()`
- Redirect validation with `wp_validate_redirect()`
- Safe redirects with `wp_safe_redirect()`

### 3. Proper User Capability Checking
- Uses `user_can($user_id, 'capability')` to check specific user's capabilities
- Avoids `current_user_can()` when checking other users

### 4. Constants for Maintainability
- `ADMIN_ORG_ID = 0` - Admin organization identifier
- `META_PARTNER_ORG_ID = 'iw_partner_organization_id'` - User meta key
- `CODES_TABLE_LIMIT = 100` - Query limit for codes table

## Usage Instructions

### For Site Administrators

#### Setting Up Partner Organizations

To group multiple partner admins to share the same dashboard:

```php
// Example: Group two partner admins into organization ID 100
$partner_admin_1_id = 5;
$partner_admin_2_id = 6;
$organization_id = 100;

update_user_meta($partner_admin_1_id, 'iw_partner_organization_id', $organization_id);
update_user_meta($partner_admin_2_id, 'iw_partner_organization_id', $organization_id);
```

Or via WordPress admin:
1. Go to Users → All Users
2. Edit a partner admin user
3. Add custom field: `iw_partner_organization_id` with value (e.g., `100`)
4. Repeat for other admins in the same organization

#### Configuring Redirect URL

To set a custom redirect URL for partner admins who try to access wp-admin:

```php
update_option('iw_partner_dashboard_url', 'https://yoursite.com/partner-dashboard/');
```

### For Partner Admins

#### What Changes
- All partner admins with the same `iw_partner_organization_id` will see:
  - Same student list
  - Same invite codes
  - Same remaining student spaces
  - Same organization limits

#### What Stays the Same
- Dashboard interface is unchanged
- All features work exactly as before
- Codes and users function normally

#### Backend Access
- Partner admins can no longer access `/wp-admin`
- Attempting to visit backend will redirect to home or custom dashboard URL
- AJAX requests from dashboard continue to work normally

## Database Schema Notes

### `wp_ielts_cm_access_codes` table

The `created_by` field now stores:
- **Partner organization ID** for codes created after this fix
- **User ID** for legacy codes (backward compatible)
- **Special value 0** when created by site admin

### User Meta

New meta key: `iw_partner_organization_id`
- Stores the organization ID for partner admins
- Not set for regular users
- Not needed for site admins

Existing meta key: `iw_created_by_partner`
- Now stores partner organization ID (not individual user ID)
- Used to filter students by organization

## Testing Checklist

### Test Shared View
- [ ] Create two partner admin users
- [ ] Assign same `iw_partner_organization_id` to both
- [ ] Login as first partner admin, create invite codes
- [ ] Login as second partner admin, verify they see the same codes
- [ ] Create user manually as first admin
- [ ] Verify second admin sees the same student
- [ ] Verify both see same remaining spaces count

### Test Backend Blocking
- [ ] Login as partner admin
- [ ] Try to access `/wp-admin`
- [ ] Verify redirect to home or configured URL
- [ ] Verify AJAX requests from dashboard still work

### Test Admin View
- [ ] Login as site administrator
- [ ] Verify admin sees ALL codes from all organizations
- [ ] Verify admin sees ALL students from all organizations
- [ ] Verify admin can still create codes and users

### Test Backward Compatibility
- [ ] Partner admin without `iw_partner_organization_id` set
- [ ] Verify they see only their own students/codes (old behavior)
- [ ] Set org ID, verify they now see organization data

## Rollback Instructions

If issues arise, you can rollback by:

1. Remove the `admin_init` hook by deactivating the fix:
```php
remove_action('admin_init', array($access_codes_instance, 'block_partner_admin_backend'));
```

2. Clear organization assignments:
```php
// Get all users with org ID meta
$users = get_users(array(
    'meta_key' => 'iw_partner_organization_id'
));
foreach ($users as $user) {
    delete_user_meta($user->ID, 'iw_partner_organization_id');
}
```

3. Restore from git:
```bash
git checkout main -- includes/class-access-codes.php
```

## Support Notes

### Common Questions

**Q: How do I create a new partner organization?**
A: Choose any unique integer ID (e.g., 100, 200) and assign it to partner admin users via user meta `iw_partner_organization_id`.

**Q: Can I change a partner admin's organization?**
A: Yes, just update their `iw_partner_organization_id` meta value. They'll immediately see the new organization's data.

**Q: What happens if I delete the org ID meta?**
A: The partner admin will fall back to seeing only their own data (user_id becomes effective org_id).

**Q: Can a partner admin be in multiple organizations?**
A: No, each partner admin has one org ID. However, multiple partner admins can be in the same organization.

**Q: Will old codes still work?**
A: Yes, all existing codes continue to work. The `created_by` field already contains user IDs which serve as org IDs for backward compatibility.

## Performance Considerations

- Database queries are indexed on existing columns (`created_by`, meta keys)
- No additional database tables created
- Minimal performance impact (same query patterns, different parameter values)
- Prepared statements ensure query optimization by database

## Future Enhancements

Potential improvements for future versions:

1. **Admin UI for managing organizations** - GUI to create, edit, and assign organizations
2. **Organization names** - Store friendly names instead of just IDs
3. **Organization metadata** - Additional settings per organization
4. **Audit logging** - Track which admin created what within an organization
5. **Organization-level permissions** - Fine-grained access control per org
