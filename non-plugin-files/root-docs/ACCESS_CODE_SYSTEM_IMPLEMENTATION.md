# Access Code Membership System - Implementation Guide

## Overview

The Access Code Membership System provides an alternative enrollment mechanism for IELTS courses. Instead of payment-based enrollment, users can redeem access codes to gain course access.

## Components Created

### 1. Access Code Class
**File:** `includes/class-access-codes.php`

**Key Features:**
- ✅ Conditional initialization based on toggle setting
- ✅ `is_enabled()` helper method
- ✅ Admin menu with subpages (only visible when enabled)
- ✅ Settings page for access code configuration
- ✅ Documentation page
- ✅ Frontend shortcode for code redemption
- ✅ AJAX handler for code processing (skeleton)

### 2. Integration Points

**Main Plugin File:** `ielts-course-manager.php`
```php
require_once IELTS_CM_PLUGIN_DIR . 'includes/class-access-codes.php';

// In ielts_cm_init():
$access_codes = new IELTS_CM_Access_Codes();
$access_codes->init();
```

**Settings Page:** `includes/admin/class-admin.php`
- Added toggle: `ielts_cm_access_code_enabled`
- Location: IELTS Courses → Settings (right after Membership System toggle)

## Admin Menu Structure

When `ielts_cm_access_code_enabled` is **ON**, the following menu appears:

```
Access Codes (dashicons-tickets-alt, position 31)
├── Access Codes (main page)
├── Settings
└── Documentation
```

When the toggle is **OFF**, the entire menu is hidden.

## Settings Configuration

### Access Code Settings (Access Codes → Settings)
- **Code Prefix**: Prefix for generated codes (default: "IELTS")
- **Code Length**: Length of random portion (4-20 characters, default: 8)
- **Default Expiry**: Days until code expires (1-3650, default: 365)

### WordPress Options
- `ielts_cm_access_code_enabled` - Enable/disable the system
- `ielts_cm_access_code_prefix` - Code prefix
- `ielts_cm_access_code_length` - Code length
- `ielts_cm_access_code_expiry_days` - Default expiry

## Frontend Integration

### Shortcode
```
[ielts_access_code_form]
```

Displays a code redemption form where users can:
1. Enter their access code
2. Submit for validation
3. Receive success/error feedback via AJAX

### Form Features
- ✅ Input validation
- ✅ AJAX submission (no page reload)
- ✅ User authentication check
- ✅ Nonce security
- ✅ Responsive styling

## Security Implementation

### Nonce Protection
- Form submissions protected with `wp_nonce_field()`
- AJAX requests verified with `wp_verify_nonce()`

### Capability Checks
- All admin pages require `manage_options` capability
- Frontend redemption requires logged-in user

### Input Sanitization
- Access codes: `sanitize_text_field()`
- Numeric inputs: `absint()`

## Class Structure

### Constants
```php
const STATUS_ACTIVE = 'active';
const STATUS_USED = 'used';
const STATUS_EXPIRED = 'expired';
const STATUS_DISABLED = 'disabled';
```

### Key Methods

#### `is_enabled()`
Checks if access code system is enabled.
```php
public function is_enabled() {
    return (bool) get_option('ielts_cm_access_code_enabled', false);
}
```

#### `init()`
Initializes the system (only if enabled).
- Registers admin menu
- Registers settings
- Adds shortcode
- Sets up AJAX handlers

#### `add_admin_menu()`
Creates admin menu structure (only if enabled).

#### `access_code_form_shortcode()`
Renders frontend redemption form.

#### `handle_code_redemption()`
AJAX handler for code validation and redemption.

## Usage Examples

### Enable the System
1. Go to **IELTS Courses → Settings**
2. Check **"Enable Access Code Membership System"**
3. Click **Save Changes**
4. The **Access Codes** menu will appear in the admin sidebar

### Configure Settings
1. Go to **Access Codes → Settings**
2. Set your code prefix (e.g., "IELTS2024")
3. Configure code length and expiry
4. Click **Save Changes**

### Add Redemption Form to a Page
1. Edit any page
2. Add the shortcode: `[ielts_access_code_form]`
3. Publish the page
4. Users can now redeem codes on that page

## Next Steps for Full Implementation

The current implementation is a **skeleton/framework**. To complete the access code system, you'll need to implement:

### 1. Database Tables
Create tables for:
- `wp_ielts_cm_access_codes` - Store access codes
- `wp_ielts_cm_code_redemptions` - Track redemptions

### 2. Code Generation
Implement in `access_codes_page()`:
- Generate unique access codes
- Assign codes to courses
- Bulk generation
- Export functionality

### 3. Code Validation
Implement in `handle_code_redemption()`:
- Check if code exists
- Verify code hasn't been used
- Check expiration date
- Validate course assignment

### 4. Course Enrollment
After successful redemption:
- Enroll user in associated course
- Mark code as used
- Log redemption
- Send confirmation email

### 5. Management Interface
Add to `access_codes_page()`:
- List all generated codes
- Filter by status (active/used/expired)
- Search codes
- Revoke/disable codes
- View redemption history

### 6. Reporting
Add reporting features:
- Codes generated vs redeemed
- Most popular courses
- Expiration alerts
- User redemption history

### 7. Integration with Existing Membership
Decide how access codes interact with payment-based memberships:
- Can users have both?
- Does code override membership?
- Access duration handling

## Relationship with Payment-Based Membership

Both systems can operate:
- **Independently**: Either one enabled, or neither
- **Simultaneously**: Both enabled at the same time

### When Both Are Enabled
Users can enroll via:
1. Payment (existing membership system)
2. Access codes (new system)
3. Both methods can coexist

### When Only Access Codes Enabled
- Membership menu hidden
- Access Codes menu visible
- Payment system inactive
- Users can only enroll via codes

### When Only Membership Enabled
- Membership menu visible
- Access Codes menu hidden
- Users can only enroll via payment

### When Both Are Disabled
- Both menus hidden
- Manual enrollment only

## Code Architecture Consistency

The access code class follows the same pattern as `IELTS_CM_Membership`:

1. **Conditional Initialization**: Only runs if `is_enabled()` returns true
2. **is_enabled() Method**: Checks WordPress option
3. **Conditional Menu**: Menu only appears when system is enabled
4. **Settings Registration**: Uses WordPress Settings API
5. **Security First**: Nonce verification and capability checks

## Migration from Other Repository

When migrating code from your other repository:

1. **Preserve the structure** - Use the skeleton as a base
2. **Add your logic** to existing methods (don't recreate them)
3. **Keep is_enabled() checks** - Ensure features respect the toggle
4. **Follow WordPress standards** - Use existing patterns
5. **Database tables** - Create in activator if needed
6. **Update documentation** - Keep this file current

## File Checklist

- ✅ `includes/class-access-codes.php` - Main class
- ✅ `ielts-course-manager.php` - Integration point
- ✅ `includes/admin/class-admin.php` - Settings toggle
- ✅ This documentation file

## Testing Checklist

- [ ] Toggle access code system ON → Menu appears
- [ ] Toggle access code system OFF → Menu disappears
- [ ] Settings page saves configuration correctly
- [ ] Shortcode displays redemption form
- [ ] Form requires login
- [ ] Form validates input
- [ ] Both systems can run simultaneously
- [ ] Each system can run independently

## Support

This implementation provides the foundation. The actual business logic for code generation, validation, and enrollment needs to be implemented based on requirements from your other repository.

---

**Created**: 2026-01-31
**Version**: 1.0 (Skeleton Implementation)
**Status**: Ready for feature implementation
