# Partnership Dashboard Button Fix

## Issue Fixed
The Edit, Revoke, Resend Email, Delete Code, and Download CSV buttons in the partnership dashboard were not working.

## Root Cause
The `IWDashboard` JavaScript object was defined inside `jQuery(document).ready())`, creating a timing issue:
- HTML buttons used inline `onclick` handlers (e.g., `onclick="IWDashboard.editStudent(123)"`)
- These handlers tried to call `IWDashboard` methods immediately when clicked
- However, `IWDashboard` was only created after the DOM was ready
- This resulted in "IWDashboard is not defined" errors

## Solution
Moved the `IWDashboard` object definition **outside** of `jQuery(document).ready())`:
- Now `IWDashboard` is available immediately when the script loads
- Buttons can call the methods without timing issues
- Event handlers and initialization code remain inside `jQuery(document).ready())`

## Code Changes

### Before (Broken)
```javascript
<script>
jQuery(document).ready(function($) {
    // ... other code ...
    
    window.IWDashboard = {
        editStudent: function(userId) { ... },
        revokeStudent: function(userId) { ... },
        // ... other methods ...
    };
    
    // Event handlers
});
</script>
```

### After (Fixed)
```javascript
<script>
// IWDashboard available immediately
window.IWDashboard = {
    editStudent: function(userId) { ... },
    revokeStudent: function(userId) { ... },
    // ... other methods ...
};

// Event handlers and initialization still in ready function
jQuery(document).ready(function($) {
    // Event handlers
});
</script>
```

## Buttons Fixed
1. **Edit Button** - Opens prompt to change student expiry date
2. **Revoke Button** - Removes student's course access
3. **Resend Email Button** - Sends new welcome email with password
4. **Delete Code Button** - Deletes unused access codes
5. **Download CSV Button** - Downloads codes as CSV file

## Testing
To test the fix:
1. Navigate to the partnership dashboard page (with `[iw_partner_dashboard]` shortcode)
2. Go to the "Managed Students" section
3. Click the **Edit** button on any student - should open a prompt
4. Click the **Revoke** button - should show confirmation dialog
5. Click the **Resend Email** button - should show confirmation dialog
6. Go to "Your Codes" section
7. Click **Download CSV** - should download a CSV file
8. Click **Delete** on an unused code - should show confirmation dialog

All buttons should work immediately without errors in the browser console.

## Technical Notes
- Changed `$` references to `jQuery` in IWDashboard methods for consistency
- Maintained `$` prefix for variable names (e.g., `$row`, `$table`) as this is a naming convention
- All AJAX calls maintain nonce-based CSRF protection
- No security vulnerabilities introduced

## Files Modified
- `includes/class-access-codes.php` - Moved IWDashboard object definition

## Related Documentation
- `PARTNER_DASHBOARD_USER_GUIDE.md` - User guide for partnership dashboard
- `PARTNER_DASHBOARD_VISUAL_SUMMARY.md` - Visual summary of dashboard features
