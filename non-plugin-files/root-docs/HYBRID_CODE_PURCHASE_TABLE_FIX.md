# Hybrid Code Purchase Fix - Missing Table Issue

## Problem Statement
A partner on the hybrid system purchased codes. Stripe successfully processed the payment, but no access codes were created in the database.

## Root Cause Analysis

### The Issue
The `wp_ielts_cm_access_codes` table had no runtime safety check. The table creation only happened during plugin activation via `IELTS_CM_Database::create_tables()`.

**Failure Scenario:**
1. Plugin is updated to a new version
2. Site admin doesn't reactivate the plugin
3. Partner makes a Stripe payment for codes
4. Stripe webhook fires and calls `handle_code_purchase_payment()`
5. Code attempts to insert into `wp_ielts_cm_access_codes` table
6. Table doesn't exist, insertion fails silently
7. Payment is logged (payment table has safety check), but codes are never created

### Why This Happened
The payment table (`wp_ielts_cm_payments`) has a runtime safety check via `ensure_payment_table_exists()` that creates the table if missing. The access codes table had NO such check.

**Existing Code Comparison:**
- ✅ Payment table: Has `ensure_payment_table_exists()` called before every insert
- ❌ Access codes table: Only passive check that logged error but didn't create table
- Result: Payment logged successfully, code creation failed silently

## Solution Implemented

### Changes Made
**File**: `includes/class-stripe-payment.php`

1. **Added New Method** (lines 82-122): `ensure_access_codes_table_exists()`
   ```php
   private function ensure_access_codes_table_exists() {
       global $wpdb;
       $table_name = $wpdb->prefix . 'ielts_cm_access_codes';
       
       // Check if table exists
       $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
       
       if (!$table_exists) {
           // Create table using same SQL as class-database.php
           // ... (full table creation with proper schema)
       }
   }
   ```

2. **Modified** `handle_code_purchase_payment()` method (line 1415):
   - **Before**: Passive check that only logged warnings
   - **After**: Active call to `ensure_access_codes_table_exists()`

### How This Fixes the Problem
1. When Stripe webhook fires after successful payment
2. `handle_code_purchase_payment()` is called
3. **NEW**: `ensure_access_codes_table_exists()` is called
4. Table is created if missing (using proper WordPress `dbDelta`)
5. Code insertion proceeds successfully
6. Codes are created and visible to partner

## Code Review Feedback

### Issue 1: Use of $wpdb->prepare() for SHOW TABLES
**Reviewer Comment**: Using `$wpdb->prepare()` with `SHOW TABLES LIKE` is unnecessary.

**Decision**: Keep as-is for consistency with existing codebase
- Line 53 uses the exact same pattern for payment table check
- This is an existing pattern throughout the file
- Making minimal changes as per requirements
- Consistency more important than micro-optimization

### Issue 2: Schema Duplication
**Reviewer Comment**: Table schema duplicated from `class-database.php`, creating maintenance burden.

**Decision**: Keep as-is for minimal changes
- Payment table uses the same duplication pattern (line 60-73)
- Refactoring would require changing multiple locations
- Instructions specify "minimal modifications"
- Future improvement: Create shared method in `class-database.php`

## Security Analysis

### SQL Injection Risk
✅ **SAFE**: Uses `$wpdb->prepare()` for parameterized queries
✅ **SAFE**: Table name uses `$wpdb->prefix` (sanitized by WordPress)
✅ **SAFE**: Schema matches class-database.php exactly

### Access Control
✅ **SAFE**: Method is `private`, can only be called internally
✅ **SAFE**: Only called from webhook handler (already has signature verification)
✅ **SAFE**: No user input affects table creation

### Database Safety
✅ **SAFE**: Uses WordPress `dbDelta()` for safe table creation
✅ **SAFE**: `CREATE TABLE IF NOT EXISTS` prevents errors if table exists
✅ **SAFE**: Includes proper error logging

## Testing Plan

### Manual Testing Steps

#### Test 1: New Installation (Table Doesn't Exist)
1. Install plugin but DON'T activate
2. Configure Stripe keys
3. Login as partner user
4. Purchase codes using Stripe payment
5. **Expected**: Table created automatically, codes generated
6. Verify in database:
   ```sql
   SHOW TABLES LIKE 'wp_ielts_cm_access_codes';
   SELECT * FROM wp_ielts_cm_access_codes ORDER BY id DESC LIMIT 5;
   ```

#### Test 2: Plugin Update Scenario
1. Start with working installation
2. Manually drop access codes table:
   ```sql
   DROP TABLE wp_ielts_cm_access_codes;
   ```
3. Purchase codes as partner user
4. **Expected**: Table recreated, codes generated successfully
5. Verify table exists and codes are present

#### Test 3: Normal Operation (Table Exists)
1. Ensure table exists
2. Purchase codes as partner user
3. **Expected**: No table recreation, codes created normally
4. Check logs for "Access codes table verified" message

### Log Verification
Check WordPress error logs for:
```
IELTS Webhook: handle_code_purchase_payment START
IELTS Webhook: IELTS_CM_Access_Codes class found
[IF TABLE MISSING] IELTS Access Codes: Access codes table does not exist, creating it now
[IF TABLE MISSING] IELTS Access Codes: Access codes table created successfully
IELTS Webhook: Access codes table verified, generating [N] codes...
IELTS Webhook: Code created successfully: [CODE]
SUCCESS: Payment logged to database for user [ID]
```

### Database Verification Queries
```sql
-- Check table structure
DESCRIBE wp_ielts_cm_access_codes;

-- Verify recent code purchases
SELECT c.code, c.course_group, c.created_by, c.status, c.created_date,
       p.amount, p.transaction_id, p.payment_status
FROM wp_ielts_cm_access_codes c
LEFT JOIN wp_ielts_cm_payments p ON p.membership_type LIKE CONCAT('access_codes_%')
ORDER BY c.created_date DESC 
LIMIT 10;

-- Check for any failed insertions (orphaned payments without codes)
SELECT p.*, u.user_email
FROM wp_ielts_cm_payments p
JOIN wp_users u ON u.ID = p.user_id
WHERE p.membership_type LIKE 'access_codes_%'
AND p.payment_status = 'completed'
AND NOT EXISTS (
    SELECT 1 FROM wp_ielts_cm_access_codes c 
    WHERE DATE(c.created_date) = DATE(p.created_at)
    AND c.created_by = (SELECT meta_value FROM wp_usermeta WHERE user_id = p.user_id AND meta_key = 'iw_partner_organization_id')
)
ORDER BY p.created_at DESC;
```

## Backward Compatibility

### Impact on Different Site Types
✅ **Non-Hybrid Sites**: No impact, works as before
✅ **Hybrid Sites**: Fixes code generation failure
✅ **New Installations**: Works correctly from first use
✅ **Updated Installations**: Automatically recovers from missing table

### Existing Functionality
✅ **Payment Processing**: No changes to payment flow
✅ **Code Generation Logic**: No changes to code format or creation
✅ **Email Notifications**: No changes
✅ **Organization Isolation**: No changes

## Rollback Plan
If this change causes issues:
1. Revert commit: `git revert [commit-hash]`
2. Manually create missing tables: Run `IELTS_CM_Database::create_tables()`
3. No data loss - payment records preserved

## Future Improvements
1. **Centralize table creation**: Extract SQL to `class-database.php::get_access_codes_table_sql()`
2. **Add table health check**: Admin dashboard showing table status
3. **Automated recovery**: Cron job to verify and recreate missing tables
4. **Unit tests**: Add PHPUnit tests for table creation logic

## Related Documentation
- `HYBRID_CODE_PURCHASE_FIX_V15_28.md` - Previous hybrid system fixes
- `includes/class-database.php` - Original table definitions
- `includes/class-access-codes.php` - Access code management

## Deployment Notes
- **No database migration needed**: Table created on-demand
- **No settings changes needed**: Works with existing configuration
- **No user action required**: Fix is automatic
- **Safe to deploy**: Backward compatible, fail-safe design

## Success Criteria
✅ Stripe payments for codes succeed AND codes are created
✅ Payment logged to database
✅ Codes visible in partner dashboard
✅ Confirmation email sent with codes
✅ No error logs related to missing table
✅ Works on fresh installs and updated installations
