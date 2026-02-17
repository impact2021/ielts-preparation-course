# Visual Guide: Organization ID Field on User Edit Page

## Overview

This guide shows the visual appearance and location of the new Organization ID field on the WordPress user edit page.

## Location

The Organization ID field appears in the **Course Enrollment** section on the user edit page, below the Expiry Date field.

## Visual Representation

```
┌────────────────────────────────────────────────────────────────┐
│ Edit User                                                       │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  [Username, Email, First Name, Last Name fields...]            │
│                                                                 │
├────────────────────────────────────────────────────────────────┤
│ Course Enrollment                                               │
├────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Course                                                         │
│  ┌──────────────────────────────────────────────────┐         │
│  │ Academic Module                          ▼       │         │
│  └──────────────────────────────────────────────────┘         │
│  Select the course to enroll this user in.                    │
│                                                                 │
│  Expiry Date                                                    │
│  ┌──────────────────────────────────────────────────┐         │
│  │ 2026-12-31                           📅          │         │
│  └──────────────────────────────────────────────────┘         │
│  Leave empty for lifetime access.                              │
│                                                                 │
│  ┌─ ONLY VISIBLE WHEN HYBRID MODE IS ENABLED ────────┐       │
│  │                                                     │       │
│  │  Organization ID                                   │       │
│  │  ┌──────────────────────────────────────────────┐ │       │
│  │  │ 2                                   ▲▼       │ │       │
│  │  └──────────────────────────────────────────────┘ │       │
│  │  Assign this user to an organization (1-999).     │       │
│  │  Users in the same organization can be managed    │       │
│  │  together by partner admins. Leave empty to use   │       │
│  │  default organization (1).                        │       │
│  │                                                     │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                 │
│  [Update User Button]                                          │
│                                                                 │
└────────────────────────────────────────────────────────────────┘
```

## Field Specifications

### Organization ID Field

**Type:** HTML5 Number Input
- **Min Value:** 1
- **Max Value:** 999
- **Step:** 1 (integers only)
- **Default:** 1 (when left empty)

### Field Appearance

1. **Label:** "Organization ID"
2. **Input Box:** Standard WordPress form field width
3. **Up/Down Arrows:** Browser-native number controls
4. **Description Text:** Multi-line helper text below field

### Example Values

```
┌─────────────────────────────┐
│ Empty (defaults to 1)       │
├─────────────────────────────┤
│ Organization ID              │
│ ┌───────────────────────┐   │
│ │                    ▲▼ │   │
│ └───────────────────────┘   │
│ Assign this user to...      │
└─────────────────────────────┘

┌─────────────────────────────┐
│ Organization 2 (Company A)  │
├─────────────────────────────┤
│ Organization ID              │
│ ┌───────────────────────┐   │
│ │ 2                  ▲▼ │   │
│ └───────────────────────────┘│
│ Assign this user to...      │
└─────────────────────────────┘

┌─────────────────────────────┐
│ Organization 5 (Tutor)      │
├─────────────────────────────┤
│ Organization ID              │
│ ┌───────────────────────┐   │
│ │ 5                  ▲▼ │   │
│ └───────────────────────────┘│
│ Assign this user to...      │
└─────────────────────────────┘
```

## Visibility Conditions

### When Field is VISIBLE:
```
✅ WordPress Admin logged in
✅ Editing a user profile
✅ User has 'edit_users' capability
✅ Hybrid mode is ENABLED
```

### When Field is HIDDEN:
```
❌ User does not have 'edit_users' capability
   OR
❌ Hybrid mode is DISABLED
```

## User Interaction Flow

### Scenario 1: Setting Organization ID

```
1. Admin navigates to Users → All Users
2. Clicks on user to edit
3. Scrolls to "Course Enrollment" section
4. Enters organization ID (e.g., "2")
5. Clicks "Update User"
6. ✅ Organization ID saved to database
```

### Scenario 2: Clearing Organization ID (Default)

```
1. Admin edits user
2. Clears organization ID field (empty)
3. Clicks "Update User"
4. ✅ User assigned to default organization (1)
```

### Scenario 3: Invalid Input

```
1. Admin enters "0" (reserved value)
2. Clicks "Update User"
3. ❌ Value silently rejected
4. Organization ID remains unchanged
```

## Browser Compatibility

The HTML5 number input is supported by all modern browsers:

- ✅ Chrome/Edge: Full support with native controls
- ✅ Firefox: Full support with native controls
- ✅ Safari: Full support with native controls
- ✅ Mobile browsers: Touch-optimized controls

## Accessibility Features

1. **Label Association:** Proper `<label for="">` connection
2. **Semantic HTML:** Uses `<input type="number">` for numeric data
3. **Help Text:** Clear description of purpose and valid range
4. **Validation:** HTML5 attributes prevent invalid input
5. **Keyboard Navigation:** Standard tab order and controls

## Integration Points

The field integrates with:

1. **WordPress User Meta:** Saves to `iw_created_by_partner` meta key
2. **Partner Dashboard:** Filters users by organization ID
3. **Access Code System:** Uses same organization ID for new users
4. **Migration System:** Works with automatic organization assignment

## Technical Notes

### Database

```sql
-- User Meta Table
SELECT user_id, meta_value as org_id 
FROM wp_usermeta 
WHERE meta_key = 'iw_created_by_partner';
```

### WordPress Functions Used

```php
// Display
$current_org_id = get_user_meta($user->ID, 'iw_created_by_partner', true);

// Save
update_user_meta($user_id, 'iw_created_by_partner', $org_id);
```

## Comparison: Before and After

### BEFORE (No Organization Field)

```
Course Enrollment
─────────────────
Course:         [Academic Module ▼]
Expiry Date:    [2026-12-31 📅]

[Update User]
```

### AFTER (With Organization Field - Hybrid Mode ON)

```
Course Enrollment
─────────────────
Course:           [Academic Module ▼]
Expiry Date:      [2026-12-31 📅]
Organization ID:  [2 ▲▼]
                  Assign this user to an organization...

[Update User]
```

## Related Screenshots Needed

For complete documentation, consider adding actual screenshots:

1. **User Edit Page (Hybrid ON)** - Showing the field
2. **User Edit Page (Hybrid OFF)** - Field hidden
3. **Field with Different Values** - Empty, 1, 2, 999
4. **Mobile View** - How field appears on mobile devices
5. **After Save** - Confirmation that value was saved

## Support Information

- **Documentation:** `USER_ORGANIZATION_MANUAL_ASSIGNMENT.md`
- **Security Review:** `SECURITY_REVIEW_ORG_FIELD.md`
- **Organization Guide:** `HYBRID_SITE_ORGANIZATION_MANAGEMENT.md`

## Troubleshooting Visual Issues

### Issue: Field not appearing
**Check:** Is hybrid mode enabled in Settings?

### Issue: Field appears but is disabled
**Check:** Do you have `edit_users` capability?

### Issue: Up/Down arrows not working
**Check:** This is browser-specific behavior, try typing numbers directly

### Issue: Can't enter decimal numbers
**This is expected:** Only whole numbers (1-999) are allowed

---

*This visual guide supplements the technical documentation with UI/UX details.*
