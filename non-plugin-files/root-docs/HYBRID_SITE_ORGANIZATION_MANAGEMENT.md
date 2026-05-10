# Hybrid Site - Organization Management Guide

## Overview

This guide explains how to manage multiple partner organizations on a hybrid IELTS site. In hybrid mode, different companies can use the same site while keeping their students and access codes completely isolated from each other.

## Key Concepts

### Organization IDs

- **0**: Reserved for site administrators (can see ALL data across ALL organizations)
- **1**: Default organization (partner admins without a custom org ID)
- **2+**: Custom organizations for separating different companies

### How It Works

- **Non-Hybrid Sites**: All partner admins see all students and codes (no filtering)
- **Hybrid Sites**: Partner admins only see data from their assigned organization
- Partner admins in the **same organization** can collaborate and see each other's work
- Partner admins in **different organizations** cannot see each other's data

## Use Cases

### Scenario 1: Single Company with Multiple Admins

**Setup:**
- Company A has two admins: John and Sarah
- Both should manage the same group of students

**Solution:**
1. Assign both John and Sarah to organization ID **2** (or leave as default **1**)
2. Result: John and Sarah see the same students and codes

### Scenario 2: Multiple Companies on Same Site

**Setup:**
- Company A (admins: John, Sarah)
- Company B (admin: Mike)
- Companies should have separate student lists

**Solution:**
1. Assign John and Sarah to organization ID **2**
2. Assign Mike to organization ID **3**
3. Result:
   - John and Sarah see Company A's students
   - Mike sees only Company B's students
   - They cannot see each other's data

### Scenario 3: Mixed Setup

**Setup:**
- Company A with 2 admins
- Company B with 1 admin
- 3 independent tutors (each their own organization)

**Solution:**
1. Company A admins → org ID **2**
2. Company B admin → org ID **3**
3. Tutor 1 → org ID **4**
4. Tutor 2 → org ID **5**
5. Tutor 3 → org ID **6**

## Step-by-Step Setup Guide

### 1. Enable Hybrid Mode

1. Go to **WordPress Admin → IELTS Courses → Settings**
2. Check **"Enable Hybrid Site Mode"**
3. Configure Stripe if you want partners to purchase codes
4. Click **Save Changes**

### 2. Create Partner Admin Users

1. Go to **Users → Add New**
2. Create user accounts for each partner admin
3. Set their **Role** to **Partner Admin**
4. Click **Add New User**

Repeat for all partner admins you need.

### 3. Assign Organizations

1. Go to **Access code settings → Organizations**
2. You'll see a table of all partner admins
3. For each partner admin:
   - Enter an **Organization ID** (1-999)
   - Or leave empty to use default (1)
4. Click **Update Organization Assignments**

#### Organization ID Guidelines:

- **Same company** = Same org ID
- **Different companies** = Different org IDs
- **Independent partners** = Unique org ID for each

### 4. Verify Setup

1. Login as Partner Admin from Company A
2. Create a test student or code
3. Login as Partner Admin from Company B
4. Verify they **cannot** see Company A's data
5. Login as another admin from Company A (if applicable)
6. Verify they **can** see the test data

## Common Questions

### Q: Can I change organization assignments later?

**A:** Yes! Just go to **Organizations** page and update the IDs. The data stays with the original organization ID, so students and codes won't change ownership.

### Q: What happens if I don't assign an organization ID?

**A:** Partner admins without a custom org ID default to organization **1**. They'll see all data from org 1 (including other partners without custom IDs).

### Q: Can site administrators see all data?

**A:** Yes! WordPress administrators (with full admin rights) always see ALL students and codes from ALL organizations.

### Q: Does this work for non-hybrid sites?

**A:** No. Organization filtering is **only active in hybrid mode**. Non-hybrid sites show all data to all partner admins regardless of org assignment.

### Q: How do I know if I'm in hybrid mode?

**A:** Visit the **Organizations** page. It will show a notice at the top indicating whether hybrid mode is enabled.

### Q: Can students see other students?

**A:** No. Students only see their own progress and courses. This isolation is between partner admins only.

## Technical Details

### Database Structure

**Organization ID is stored in:**
- User meta: `iw_partner_organization_id` (for partner admins)
- Access codes table: `created_by` field
- Student meta: `iw_created_by_partner` field

### Filtering Logic

**For hybrid sites:**
```php
// Partner admins see only their organization's data
if ($partner_org_id == ADMIN_ORG_ID) {
    // Site admin - see all
} else {
    // Filter by organization ID
}
```

**For non-hybrid sites:**
```php
// All partner admins see all data
// No filtering applied
```

### Migration

When upgrading from a non-filtered to organization-based system:
- Existing students keep their `iw_created_by_partner` value
- Existing codes keep their `created_by` value
- Partner admins default to org ID 1 if not assigned

## Troubleshooting

### Partner admin can't see their students

1. Check they're assigned to correct organization ID
2. Verify students have `iw_created_by_partner` matching the org ID
3. Confirm hybrid mode is enabled

### Two partners who should collaborate can't see each other's work

1. Check they're assigned to the **same** organization ID
2. Verify on **Organizations** page
3. Update if needed

### All partners see all data

1. Check if hybrid mode is enabled (Settings page)
2. If not in hybrid mode, this is expected behavior
3. Enable hybrid mode to activate organization filtering

## Security Considerations

- Organization IDs are stored as user meta
- Only site administrators can modify organization assignments
- Partner admins cannot change their own organization ID
- Organization ID 0 is protected and cannot be assigned to partner admins

## Performance

- Organization filtering uses indexed database queries
- Student counts are cached to avoid N+1 queries
- Minimal performance impact even with many organizations

## Best Practices

1. **Plan your organizations** before assigning IDs
2. **Document** which org ID belongs to which company
3. **Use sequential IDs** (2, 3, 4...) for clarity
4. **Test** organization isolation after setup
5. **Communicate** with partners about data visibility

## Examples

### Example 1: Language School with 3 Locations

```
Organization 2: Downtown Campus (admins: Alice, Bob)
Organization 3: Suburban Campus (admin: Carol)  
Organization 4: Online Division (admin: Dave)
```

Each campus sees only their students, but Downtown has 2 admins who collaborate.

### Example 2: Reseller Model

```
Organization 2: Reseller ABC Ltd (admin: John)
Organization 3: Reseller XYZ Corp (admins: Sarah, Mike)
Organization 4: Reseller 123 Inc (admin: Tom)
```

Each reseller manages their own students independently. XYZ Corp has 2 staff members who share access.

### Example 3: Mixed Setup

```
Organization 1: Default (legacy partners without assignment)
Organization 2: Premium Partner A
Organization 3: Premium Partner B
Organization 4: Trial Partner C
```

Legacy partners continue using shared view, while new partners get isolated environments.

## Related Documentation

- `PARTNER_DASHBOARD_USER_GUIDE.md` - General partner dashboard usage
- `PARTNER_ADMIN_SITE_WIDE_FIX.md` - Technical details of organization system
- `HYBRID_SITE_IMPROVEMENTS_COMPLETE.md` - Hybrid mode features

## Support

If you need help with organization management:
1. Check this guide first
2. Review the Organizations page in WordPress admin
3. Test with a sample partner admin account
4. Verify hybrid mode is properly enabled
