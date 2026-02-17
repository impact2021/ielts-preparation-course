# User Organization Assignment - Edit User Page Feature

## Overview

This feature allows site administrators to manually assign users to organizations directly from the WordPress user edit page. This is particularly useful for retroactively adding existing users to organizations on hybrid sites.

## When to Use This Feature

- **Hybrid Sites Only**: This feature only appears when hybrid mode is enabled (`ielts_cm_hybrid_site_enabled` option is set to true)
- **Retroactive Assignment**: When you need to add existing users to specific organizations
- **Manual User Management**: When creating or editing users manually instead of through access codes
- **Organization Migration**: When moving users between organizations

## How to Access

1. Log in as a WordPress administrator (user with `edit_users` capability)
2. Navigate to **Users → All Users**
3. Click on any user to edit their profile
4. Scroll down to the **Course Enrollment** section
5. You'll see the **Organization ID** field (only if hybrid mode is enabled)

## Using the Organization ID Field

### Field Details

- **Location**: User edit page, under "Course Enrollment" section
- **Field Type**: Number input
- **Valid Range**: 1-999
- **Default Value**: 1 (if left empty)
- **Visibility**: Only shown when hybrid mode is enabled

### Setting an Organization ID

1. Enter a number between 1 and 999 in the **Organization ID** field
2. Leave empty to assign the user to the default organization (ID: 1)
3. Click **Update User** to save the changes

### Organization ID Guidelines

| Organization ID | Purpose | Description |
|----------------|---------|-------------|
| **0** | Reserved | Site administrators only (cannot be manually assigned) |
| **1** | Default | Default organization for all partner admins |
| **2-999** | Custom | Custom organizations for separating different companies/groups |

### Examples

#### Example 1: Assign User to Default Organization
```
Organization ID: [leave empty or enter 1]
```
User will be assigned to organization 1 (default).

#### Example 2: Assign User to Company A
```
Organization ID: 2
```
User will be assigned to organization 2 (Company A).

#### Example 3: Assign User to Independent Tutor
```
Organization ID: 5
```
User will be assigned to organization 5 (Independent Tutor).

## Technical Details

### Database Storage

- **User Meta Key**: `iw_created_by_partner`
- **Value Type**: Integer (1-999)
- **Storage Location**: WordPress `wp_usermeta` table

### Validation Rules

1. **Empty Value**: Defaults to organization ID 1
2. **Valid Range**: 1-999 (inclusive)
3. **Invalid Values**: 
   - 0 (reserved for site admins)
   - Values < 1
   - Values > 999
   - Non-numeric values

### Permission Requirements

- User must have `edit_users` capability (typically WordPress Administrators)
- Feature only visible when hybrid mode is enabled

## Integration with Existing Features

This feature works seamlessly with:

1. **Automatic Migration** (`migrate_users_without_organization()`): 
   - Automatically assigns default organization to users without one
   - This manual feature allows overriding/updating after automatic migration

2. **Access Code System**:
   - Users created via access codes get auto-assigned to creating partner's organization
   - Can be manually reassigned using this feature if needed

3. **Partner Dashboards**:
   - Partner admins see users based on organization ID matching
   - Changing a user's organization ID moves them between partner dashboards

## Use Cases

### Use Case 1: Retroactive Organization Assignment

**Scenario**: Site had users before hybrid mode was enabled. Now need to assign them to specific organizations.

**Solution**:
1. Enable hybrid mode
2. Go to each user's profile
3. Assign appropriate organization ID
4. Save changes

### Use Case 2: Moving Users Between Organizations

**Scenario**: User was created under wrong organization and needs to be moved to correct one.

**Solution**:
1. Edit user profile
2. Change organization ID to target organization
3. Save changes
4. User now appears in target organization's partner dashboard

### Use Case 3: Assigning Manual Registrations

**Scenario**: Admin creates user account manually and needs to assign to partner organization.

**Solution**:
1. Create user account
2. Set organization ID during creation or edit afterward
3. User is now managed by appropriate partner organization

## Security Considerations

- Only site administrators (with `edit_users` capability) can modify organization IDs
- Organization ID 0 is protected and cannot be manually assigned
- Valid range enforcement (1-999) prevents invalid data
- Input sanitization and validation prevent injection attacks

## Troubleshooting

### Issue: Organization ID field not visible

**Solution**: 
- Verify hybrid mode is enabled: Go to **IELTS Courses → Settings** and check "Enable Hybrid Site Mode"
- Verify you're logged in as an administrator with `edit_users` capability

### Issue: Organization ID not saving

**Solution**:
- Ensure value is between 1-999
- Check that you clicked "Update User" button
- Verify you have `edit_users` capability

### Issue: User not appearing in partner dashboard

**Solution**:
- Verify organization ID matches partner admin's organization
- Confirm user has an access code membership role assigned
- Check hybrid mode is enabled

## Related Documentation

- `USER_ORGANIZATION_MIGRATION.md` - Automatic migration for existing users
- `HYBRID_SITE_ORGANIZATION_MANAGEMENT.md` - Complete organization management guide
- `PARTNER_DASHBOARD_USER_GUIDE.md` - Partner dashboard usage

## Code References

- **File**: `includes/class-membership.php`
- **Display Method**: `user_membership_fields()`
- **Save Method**: `save_user_membership_fields()`
- **Meta Key**: `iw_created_by_partner`
- **Validation**: Lines 324-342 in `class-membership.php`

## Version History

- **V1** (2026-02-17): Initial implementation of manual organization ID assignment on user edit page
