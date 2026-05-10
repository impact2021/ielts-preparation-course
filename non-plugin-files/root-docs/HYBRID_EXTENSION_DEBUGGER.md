# Hybrid Site Course Extension Debugger

## Overview
This debugger helps identify and fix issues with the course extension dropdown on hybrid sites. When users select an extension duration but the payment section doesn't appear, this tool provides diagnostic information to find the root cause.

## When to Use This Debugger
- Course extension dropdown exists but selecting an option does nothing
- Payment section doesn't appear when an extension is selected
- Users can't complete extension purchases on hybrid sites
- Need to verify hybrid site configuration is correct

## How to Access the Debugger

### Prerequisites
1. You must be logged in as a WordPress administrator
2. You must be on a **hybrid site** (non-hybrid sites won't show the debugger)
3. Navigate to your account page (typically via the `[ielts_account]` shortcode)

### Location
The debugger appears on the **Course Extension** tab in your account, right below the "Select Extension Duration" dropdown.

## Understanding the Debugger

### Visual Status Indicator
At the top of the debugger, you'll see one of two status messages:

**✓ JavaScript should be loaded** (Green)
- This means all conditions are met for the extension system to work
- If you still don't see the payment section, check browser console for errors

**✗ JavaScript NOT loaded** (Red)
- This means one or more conditions are not met
- The payment JavaScript won't load, so the dropdown won't work
- Expand "View Diagnostic Details" to see which condition failed

### Diagnostic Details

Click "View Diagnostic Details" to see a complete breakdown:

| Check | What It Means | Fix If Red |
|-------|--------------|------------|
| **Hybrid Mode Enabled** | Is hybrid site mode turned on? | Go to IELTS Course settings and enable Hybrid Site Mode |
| **Membership System Enabled** | Is the membership system active? | Go to IELTS Course → Payment Settings and enable membership system |
| **Stripe Key Configured** | Is Stripe publishable key set? | Go to IELTS Course → Payment Settings and add your Stripe publishable key |
| **Is Access Code Membership** | Does user's membership start with 'access_'? | User might be on wrong membership type |
| **Is Trial Membership** | Is user on a trial? | Extensions are only for paid members, not trials |
| **Current Membership Type** | User's actual membership type value | Should start with 'access_' for extensions to work |
| **Extension Pricing** | Configured prices for extensions | Verify pricing is set correctly |

### Issue Warnings

If JavaScript won't load, the debugger shows specific issues found:
- ⚠️ Hybrid mode is not enabled
- ⚠️ Membership system is not enabled  
- ⚠️ Stripe publishable key is not configured
- ⚠️ User membership type does not start with 'access_'
- ⚠️ User is on a trial membership

Fix the issues listed to enable the extension payment system.

### Test Extension Selection Button

Click the **"Test Extension Selection"** button to:
1. Check if ieltsPayment JavaScript object is defined
2. Verify selected extension value is correct
3. Confirm price lookup works properly
4. Test if payment section appears when triggered
5. Get specific error messages if something fails

**How to use:**
1. Select an extension duration from the dropdown
2. Click "Test Extension Selection"
3. Review the test results that appear below the button

The test will show:
- ✓ What's working correctly
- ❌ What's broken and why
- Specific error messages and suggestions

## Browser Console Debugging

The debugger also logs detailed information to the browser console (F12 → Console tab):

### On Page Load
```
🔧 IELTS Course Extension Debugger
  Diagnostics: {object with all diagnostic info}
  JavaScript should be loaded: true/false
  ieltsPayment object available: true/false
  ieltsPayment.extensionPricing: {pricing object}
```

### When Extension Script Loads
```
🚀 IELTS Payment Extension Script Loaded
  Stripe available: true/false
  ieltsPayment object: {full object}
  Extension form found: true/false
  Extension payment section found: true/false
```

### When User Selects Extension
```
🔍 Extension Selection Changed
  Selected membership type: extension_1_week
  Calculated price: 5.00
  Extension pricing available: {pricing object}
  Extracted duration: 1_week
  Price lookup result: 5.00
```

### When Payment Section Shows/Hides
```
📝 showPaymentSectionExtension called with price: 5.00
  Payment section element found: true
  Payment section slideDown() called
  
💳 initializePaymentElementExtension called with price: 5.00
  Stripe object is available
  Creating Stripe elements with amount: 500 cents
  Elements instance created
  Payment element created
  ✓ Payment element mounted successfully
```

## Common Issues and Fixes

### Issue: Debugger Doesn't Appear
**Cause:** You're not logged in as an admin, or not on a hybrid site
**Fix:** Log in as WordPress administrator, verify hybrid mode is enabled

### Issue: "JavaScript NOT loaded" Status
**Cause:** One or more required conditions not met
**Fix:** Check diagnostic details, fix any red ❌ items

### Issue: "ieltsPayment object is not defined"
**Cause:** Payment JavaScript didn't load because conditions weren't met
**Fix:** Verify all green checkmarks in diagnostic details

### Issue: "No price found for duration"
**Cause:** Extension pricing not configured or misconfigured
**Fix:** Go to IELTS Course → Payment Settings → Course Extension Pricing, verify prices are set

### Issue: Payment Section Exists But Stays Hidden
**Cause:** JavaScript event listener not attached or price lookup failed
**Fix:** 
1. Check browser console for JavaScript errors
2. Use "Test Extension Selection" button to diagnose
3. Verify extension pricing is configured correctly

### Issue: User Membership Type Doesn't Start with 'access_'
**Cause:** User is on a regular paid membership, not an access code membership
**Fix:** Extensions are specifically for access code memberships on hybrid sites. Regular paid members should use the renewal flow instead.

## Configuration Requirements

For the extension system to work on a hybrid site, you need:

1. **WordPress Admin → IELTS Course → Settings**
   - ☑ Enable Hybrid Site Mode

2. **WordPress Admin → IELTS Course → Payment Settings**
   - ☑ Enable Membership System
   - Stripe Publishable Key: `pk_live_...` or `pk_test_...`
   
3. **WordPress Admin → IELTS Course → Payment Settings → Course Extension Pricing**
   - 1 Week Extension: $5.00 (or your price)
   - 1 Month Extension: $10.00 (or your price)
   - 3 Months Extension: $15.00 (or your price)

4. **User Requirements**
   - User must be logged in
   - User must have an access code membership (type starts with 'access_')
   - User must NOT be on a trial membership

## Testing the Extension Flow

Once all conditions are met:

1. Log in as a user with an access code membership (non-trial)
2. Go to your account page
3. Click the "Course Extension" tab
4. Select an extension duration from dropdown
5. ✓ Payment section should slide down
6. ✓ Card details form should appear
7. ✓ "Complete Payment & Extend" button should appear
8. Enter test card details (Stripe test mode)
9. Submit to complete extension

If any step fails, use the debugger to identify the issue.

## For Developers

### Where the Debugger Lives

**PHP Component:** `includes/class-shortcodes.php` lines ~3095-3253
- Displays diagnostic UI
- Runs server-side checks
- Only shown to admins on hybrid sites

**JavaScript Component:** `assets/js/registration-payment.js`
- Enhanced console logging
- Tracks script initialization
- Logs every step of the payment flow

### Key Functions

**PHP:**
- `$hybrid_mode_enabled` - Checks if hybrid mode is on
- `$is_access_code_membership` - Checks if user membership starts with 'access_'
- `$show_extension_payment` - Final decision on loading JavaScript

**JavaScript:**
- `getPriceForMembershipType()` - Extracts price from ieltsPayment.extensionPricing
- `showPaymentSectionExtension()` - Shows payment section
- `initializePaymentElementExtension()` - Creates Stripe payment element

### Conditional Script Loading

The payment JavaScript only loads when **ALL** conditions are true:
```php
$show_extension_payment = 
    $hybrid_mode_enabled && 
    $is_access_code_membership && 
    !$is_trial &&
    get_option('ielts_cm_membership_enabled') &&
    !empty($stripe_publishable);
```

If any condition is false, the script won't load and dropdown won't work.

## Removing the Debugger

The debugger is **admin-only** and **hybrid-site-only**, so it:
- ✓ Never shows to regular users
- ✓ Never shows on non-hybrid sites
- ✓ Doesn't impact production performance
- ✓ Can be left in place safely

However, if you want to remove it:

1. Edit `includes/class-shortcodes.php`
2. Find the section starting with `// HYBRID SITE ONLY: On-page debugger`
3. Delete from that comment down to `<?php endif; // End admin-only debugger ?>`
4. Optional: Remove enhanced console.log() statements from `assets/js/registration-payment.js`

## Support

If the debugger shows everything is correct but the extension still doesn't work:

1. **Check Browser Console** (F12 → Console) for JavaScript errors
2. **Clear Browser Cache** and reload the page
3. **Test in Incognito/Private Window** to rule out extension conflicts
4. **Verify Stripe Keys** are from the same account (test/live)
5. **Check WordPress Error Logs** for server-side errors

For persistent issues, gather:
- Screenshot of debugger diagnostic details
- Browser console logs
- WordPress debug.log entries
- Steps to reproduce

## Version History

- **Version 1.0** (February 2026) - Initial debugger implementation
  - On-page diagnostic UI
  - Browser console logging
  - Test button functionality
  - Admin-only display on hybrid sites
