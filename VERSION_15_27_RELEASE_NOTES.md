# Version 15.27 Release Notes

## Critical Bug Fix: Hybrid Mode Access Code Creation

### Overview
This release fixes a critical bug in hybrid mode where access codes were not being created or displayed after successful payment, even though the payment was processed correctly.

### Problem Statement
When users without a custom organization ID purchased access codes on hybrid sites:
- Payment was successful
- Codes were NOT created in the database
- Email confirmation was NOT sent
- Debug panel showed: "Total Codes in Database: 0"

### Root Cause
The webhook payment handlers were using `$user_id` as the default `partner_org_id` when creating codes. However, the partner dashboard filters codes by `SITE_PARTNER_ORG_ID` (value: 1). This mismatch caused:
- Codes created with `created_by = 11665` (user's ID)
- Dashboard filtering for `created_by = 1` (SITE_PARTNER_ORG_ID)
- Result: Codes were invisible in the dashboard

### Solution
Modified both Stripe and PayPal webhook handlers to use `SITE_PARTNER_ORG_ID` (1) as the default organization ID instead of the user's ID. This matches the expected value used by the dashboard filtering logic.

### Technical Changes

#### File: includes/class-stripe-payment.php
**Function**: `handle_code_purchase_payment()`  
**Lines**: 1135-1152

**Before**:
```php
$partner_org_id = $user_id; // Default to user_id
$org_id = get_user_meta($user_id, 'iw_partner_organization_id', true);
if (!empty($org_id) && is_numeric($org_id)) {
    $partner_org_id = (int) $org_id;
}
```

**After**:
```php
$org_id = get_user_meta($user_id, 'iw_partner_organization_id', true);
if (!empty($org_id) && is_numeric($org_id)) {
    $partner_org_id = (int) $org_id;
    error_log("IELTS Webhook: Partner Org ID: $partner_org_id (from user meta)");
} else {
    if (class_exists('IELTS_CM_Access_Codes')) {
        $partner_org_id = IELTS_CM_Access_Codes::SITE_PARTNER_ORG_ID;
    } else {
        $partner_org_id = 1; // Defensive fallback
    }
    error_log("IELTS Webhook: Partner Org ID: $partner_org_id (using SITE_PARTNER_ORG_ID - no custom org_id set for user)");
}
```

#### File: includes/class-access-codes.php
**Function**: `ajax_complete_paypal_code_purchase()`  
**Lines**: 3068-3077

Similar change for PayPal webhook handler using `self::SITE_PARTNER_ORG_ID`.

#### File: ielts-course-manager.php
**Lines**: 6, 23
- Updated version from 15.26 to 15.27

### Impact Assessment

#### ✅ Hybrid Sites (FIXED)
- Codes are now created with the correct organization ID
- Codes are now visible in the partner dashboard
- Email confirmations are now sent successfully
- Debug panel shows correct code counts

#### ✅ Non-Hybrid Sites (NO IMPACT)
- Code purchase functionality is already gated by hybrid mode check
- These changes only execute on hybrid sites
- No risk to non-hybrid site functionality

#### ✅ Custom Organization IDs (BACKWARD COMPATIBLE)
- Users with custom org IDs set in user meta continue to work
- Existing logic preserved: custom org ID takes precedence
- Only affects users without a custom org ID set

### Security Review

✅ **SQL Injection**: All values properly validated and cast to integers  
✅ **Authorization**: No changes to authorization checks  
✅ **Information Disclosure**: Logging statements are appropriate  
✅ **Business Logic**: Minimal, targeted change with no side effects

### Testing Verification

**Test Case 1**: User with custom org_id = 5
- Expected: `partner_org_id = 5`
- Result: ✓ PASS

**Test Case 2**: User with org_id not set (empty string)
- Expected: `partner_org_id = 1` (SITE_PARTNER_ORG_ID)
- Result: ✓ PASS

**Test Case 3**: User with org_id not set (null)
- Expected: `partner_org_id = 1` (SITE_PARTNER_ORG_ID)
- Result: ✓ PASS

**Test Case 4**: User with org_id = 0 (falsy value)
- Expected: `partner_org_id = 1` (SITE_PARTNER_ORG_ID)
- Result: ✓ PASS

### Upgrade Instructions

1. **Backup your database** before upgrading
2. Update to version 15.27
3. **For hybrid sites**: No additional steps required - fix is automatic
4. **For non-hybrid sites**: No action needed - no impact

### Known Limitations

**Existing codes with wrong organization ID**: This fix only affects NEW code purchases. If you have existing codes created with the wrong organization ID before this fix, they will remain invisible. To fix existing codes, you can:

1. Update codes manually in the database:
   ```sql
   UPDATE wp_ielts_cm_access_codes 
   SET created_by = 1 
   WHERE created_by NOT IN (0, 1) 
   AND status = 'active';
   ```
   
2. Or contact support for assistance with data migration.

### Support

If you experience any issues after upgrading to 15.27, please:
1. Check that hybrid mode is enabled in settings
2. Review the debug panel on the partner dashboard
3. Check server error logs for webhook processing
4. Contact support with the debug information

---

**Release Date**: February 8, 2026  
**Version**: 15.27  
**Priority**: Critical (for hybrid sites)  
**Type**: Bug Fix
