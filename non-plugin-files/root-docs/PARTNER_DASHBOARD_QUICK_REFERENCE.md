# Partner Dashboard - Quick Reference

## Shortcode
```
[iw_partner_dashboard]
```

## Dashboard Sections (Top to Bottom)

### 1. System Status Card
```
┌─────────────────────────────────────────┐
│ 📊 System Status                        │
├─────────────────────────────────────────┤
│ Active Students: 15 / 100               │
│ Status: ✓ Under limit                   │
└─────────────────────────────────────────┘
```

### 2. Create Invite Codes Card
```
┌─────────────────────────────────────────┐
│ 🎫 Create Invite Codes                  │
├─────────────────────────────────────────┤
│ Quantity: [1-10] ▼                      │
│ Days Valid: [30/60/90/180/365] ▼       │
│ Course Group: [academic_english] ▼      │
│                                         │
│ [ Create Codes ]                        │
│                                         │
│ Generated Codes:                        │
│ ┌─────────────────────────────────────┐ │
│ │ IELTS-A1B2C3D4                      │ │
│ │ IELTS-E5F6G7H8                      │ │
│ └─────────────────────────────────────┘ │
│ [ Copy Codes ]                          │
└─────────────────────────────────────────┘
```

### 3. Create User Manually Card
```
┌─────────────────────────────────────────┐
│ 👤 Create User Manually                 │
├─────────────────────────────────────────┤
│ Email: [___________________]            │
│ First Name: [______________]            │
│ Last Name: [_______________]            │
│ Days of Access: [30] ▼                  │
│ Course Group: [academic_english] ▼      │
│                                         │
│ [ Create User ]                         │
└─────────────────────────────────────────┘
```

### 4. All Invite Codes Card
```
┌──────────────────────────────────────────────────────────────┐
│ 📋 All Invite Codes                         [ Download CSV ] │
├──────────────────────────────────────────────────────────────┤
│ Code          │Group      │Days│Status │Used By  │Created   │
├──────────────────────────────────────────────────────────────┤
│ IELTS-A1B2... │Academic   │ 30 │active │ -       │2026-01-31│[🗑]
│ IELTS-E5F6... │General    │ 60 │used   │john_doe │2026-01-30│
│ IELTS-I9J0... │English    │ 90 │active │ -       │2026-01-29│[🗑]
└──────────────────────────────────────────────────────────────┘
```

### 5. Managed Students Card
```
┌───────────────────────────────────────────────────────────────┐
│ 👥 Managed Students (15)                                      │
├───────────────────────────────────────────────────────────────┤
│ Username     │Email            │Group    │Expiry     │Actions │
├───────────────────────────────────────────────────────────────┤
│ john_doe     │john@example.com │Academic │2026-02-28 │[Revoke]│
│ jane_smith   │jane@example.com │General  │2026-03-15 │[Revoke]│
│ bob_wilson   │bob@example.com  │English  │2026-04-01 │[Revoke]│
└───────────────────────────────────────────────────────────────┘
```

## Course Groups

| Value | Label | Membership Type |
|-------|-------|----------------|
| `academic_english` | IELTS Academic + English | `academic_full` |
| `general_english` | IELTS General Training + English | `general_full` |
| `english_only` | General English Only | `english_full` |
| `all_courses` | All Courses | `academic_full` |

## AJAX Actions

| Action | Purpose | Parameters |
|--------|---------|------------|
| `iw_create_invite` | Generate codes | quantity, days_valid, course_group |
| `iw_create_user_manually` | Create user | user_email, user_first_name, user_last_name, user_days, user_course_group |
| `iw_revoke_student` | Revoke access | user_id |
| `iw_delete_code` | Delete code | code_id |

## User Metadata Set

When a user is created or enrolled:

```php
// Partner tracking
update_user_meta($user_id, '_iw_user_manager', $partner_id);
update_user_meta($user_id, '_iw_user_expiry', $expiry_date);
update_user_meta($user_id, '_iw_user_group', $course_group);

// IELTS membership
update_user_meta($user_id, '_ielts_cm_membership_type', $membership_type);
update_user_meta($user_id, '_ielts_cm_membership_status', 'active');
update_user_meta($user_id, '_ielts_cm_membership_expiry', $expiry_date);
```

## Database Queries

### Get Partner's Students
```php
$students = get_users(array(
    'meta_key' => '_iw_user_manager',
    'meta_value' => $partner_id
));
```

### Get All Codes
```php
global $wpdb;
$codes = $wpdb->get_results("
    SELECT * FROM {$wpdb->prefix}ielts_cm_access_codes 
    ORDER BY created_date DESC
");
```

### Create Code
```php
$wpdb->insert(
    $wpdb->prefix . 'ielts_cm_access_codes',
    array(
        'code' => $code,
        'course_group' => $group,
        'duration_days' => $days,
        'created_by' => $partner_id,
        'status' => 'active'
    )
);
```

## Permissions Required

### Access Dashboard
- `manage_partner_invites` capability (Partner Admin role)
- OR `manage_options` capability (Administrator role)

### Partner Admin Role
```php
add_role('partner_admin', 'Partner Admin', array(
    'read' => true,
    'manage_partner_invites' => true
));
```

## Email Template

**Subject:** Welcome to IELTS Course

**Body:**
```
Welcome! Your account has been created.

Username: {username}
Password: {password}

Please login to access your courses.
```

## Security Checks

All AJAX handlers include:
1. ✅ Nonce verification: `check_ajax_referer('iw_create_invite')`
2. ✅ Capability check: `current_user_can('manage_partner_invites')`
3. ✅ Input sanitization: `sanitize_text_field()`, `sanitize_email()`
4. ✅ Output escaping: `esc_html()`, `esc_attr()`

## CSV Export Format

```csv
Code,Course Group,Days,Status,Used By,Created
IELTS-A1B2C3D4,IELTS Academic + English,30,active,,2026-01-31
IELTS-E5F6G7H8,IELTS General Training + English,60,used,john_doe,2026-01-30
```

## Common Use Cases

### Scenario 1: Bulk Enrollment
1. Generate 10 codes for 30 days
2. Download CSV
3. Email codes to students
4. Students register with codes

### Scenario 2: Direct Enrollment
1. Get student's email
2. Use "Create User Manually"
3. System sends welcome email
4. Student logs in with credentials

### Scenario 3: Trial Access
1. Create codes for 7-14 days
2. Assign to trial course group
3. Distribute to prospects
4. Monitor usage in dashboard

### Scenario 4: Remove Access
1. Find student in "Managed Students"
2. Click "Revoke"
3. Confirm action
4. Access removed immediately

## Status Messages

### Success
```
✓ 5 code(s) created successfully!
✓ User created successfully! Username: john_example_com_12345
✓ Student access revoked.
✓ Code deleted.
```

### Error
```
✗ Permission denied.
✗ Quantity must be between 1 and 10.
✗ Maximum student limit reached.
✗ Failed to create user: Email already exists.
```

## Styling Classes

| Class | Purpose |
|-------|---------|
| `.iw-dashboard` | Main container |
| `.iw-card` | Card sections |
| `.iw-form-table` | Form layouts |
| `.iw-btn` | Action buttons |
| `.iw-success` | Success messages |
| `.iw-error` | Error messages |
| `.iw-table` | Data tables |

## JavaScript Functions

```javascript
// Namespaced under IWDashboard object
IWDashboard.createInvite()     // Create codes
IWDashboard.createUser()       // Create user
IWDashboard.deleteCode()       // Delete code
IWDashboard.revokeStudent()    // Revoke student
IWDashboard.copyCodes()        // Copy to clipboard
IWDashboard.showMessage()      // Display messages
```

## Testing Checklist

- [ ] Dashboard loads for partner admin
- [ ] Dashboard loads for administrator
- [ ] Dashboard denies access for subscriber
- [ ] Create 1 code works
- [ ] Create 10 codes works
- [ ] Generated codes display
- [ ] Copy codes works
- [ ] Create user sends email
- [ ] User can log in with credentials
- [ ] User is enrolled in courses
- [ ] Codes table shows all codes
- [ ] Delete code works
- [ ] Students table shows enrolled users
- [ ] Revoke student works
- [ ] CSV download works
- [ ] CSV imports to Excel correctly
- [ ] Student limit enforced
- [ ] Success messages display
- [ ] Error messages display
