# Quick Reference: Organization ID Field

## For Administrators

### How to Use

1. **Navigate:** Users → All Users → Click on user
2. **Find:** Scroll to "Course Enrollment" section
3. **Enter:** Type organization ID (1-999) or leave empty for default
4. **Save:** Click "Update User"

### Quick Examples

| Action | Enter | Result |
|--------|-------|--------|
| Default organization | Leave empty | Organization 1 |
| Company A | 2 | Organization 2 |
| Company B | 3 | Organization 3 |
| Independent tutor | 5 | Organization 5 |

### Requirements

- ✅ You must be logged in as administrator
- ✅ Hybrid mode must be enabled
- ✅ Valid range: 1-999 only

### Common Tasks

#### Assign new user to organization
```
1. Create or edit user
2. Set Organization ID: 2
3. Update User
```

#### Move user to different organization
```
1. Edit user
2. Change Organization ID: 2 → 3
3. Update User
```

#### Reset to default organization
```
1. Edit user
2. Clear Organization ID field
3. Update User
```

## For Developers

### Database

```php
// Get organization ID
$org_id = get_user_meta($user_id, 'iw_created_by_partner', true);

// Set organization ID
update_user_meta($user_id, 'iw_created_by_partner', $org_id);
```

### Constants

```php
IELTS_CM_Access_Codes::ADMIN_ORG_ID = 0;          // Reserved for admins
IELTS_CM_Access_Codes::SITE_PARTNER_ORG_ID = 1;  // Default organization
```

### Validation

```php
// Valid range: 1-999
if ($org_id >= 1 && $org_id <= 999) {
    // Valid
}
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Field not visible | Enable hybrid mode in Settings |
| Can't edit field | Need 'edit_users' capability |
| Value not saving | Check value is between 1-999 |
| User not in partner dashboard | Verify org ID matches partner's org |

## Documentation

- **Full Guide:** USER_ORGANIZATION_MANUAL_ASSIGNMENT.md
- **Security:** SECURITY_REVIEW_ORG_FIELD.md
- **Visual Guide:** VISUAL_GUIDE_ORG_FIELD.md
- **Implementation:** IMPLEMENTATION_COMPLETE_ORG_FIELD.md

## Support

For issues:
1. Check documentation files above
2. Verify hybrid mode is enabled
3. Confirm you have admin permissions
4. Test in staging environment

---

**Version:** 1.0 | **Date:** 2026-02-17
