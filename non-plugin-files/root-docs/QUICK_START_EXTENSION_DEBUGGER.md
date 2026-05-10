# Quick Start: Hybrid Site Extension Debugger

## Problem
On hybrid sites, when users select a course extension duration from the dropdown, nothing happens - no payment options appear.

## Solution
An on-page debugger that shows exactly why the payment section isn't appearing.

## How to Use (3 Steps)

### Step 1: Access the Debugger
1. Log in as WordPress **administrator**
2. Go to your account page (where the extension dropdown is)
3. Click the **"Course Extension"** tab
4. You'll see the debugger below the dropdown (gray box with 🔧 icon)

### Step 2: Check the Status
Look at the first line of the debugger:

**✓ JavaScript should be loaded** (Green) = System is configured correctly
- If payment still doesn't work, check browser console (F12)

**✗ JavaScript NOT loaded** (Red) = Configuration issue
- Click "View Diagnostic Details" to see what's wrong

### Step 3: Fix Any Issues

| What's Wrong | Where to Fix It |
|--------------|-----------------|
| Hybrid Mode not enabled | IELTS Course → Settings → Enable Hybrid Site Mode |
| Membership System not enabled | IELTS Course → Payment Settings → Enable Membership |
| Stripe Key not configured | IELTS Course → Payment Settings → Add Stripe Key |
| Wrong membership type | User needs access code membership (starts with 'access_') |
| User on trial | Extensions only work for paid members |

## Testing

After fixing issues:
1. Select an extension from the dropdown
2. Click the **"Test Extension Selection"** button
3. Review the test results

The test will tell you exactly what's working and what's broken.

## Browser Console

Press **F12** and check the Console tab for detailed logs:
- Script loading status
- Extension selection events
- Payment section show/hide operations
- Any JavaScript errors

## Common Issues

**"ieltsPayment object is not defined"**
→ Payment JavaScript didn't load. Check that ALL conditions are met (green checkmarks).

**"No price found for duration"**
→ Go to IELTS Course → Payment Settings → Configure extension prices.

**Payment section exists but stays hidden**
→ Use the test button to diagnose. Usually a pricing issue.

## Need More Help?

See the full documentation: `HYBRID_EXTENSION_DEBUGGER.md`

## Important Notes

- ✅ This debugger is **admin-only** - regular users never see it
- ✅ This debugger is **hybrid-site-only** - doesn't affect other sites
- ✅ Safe to leave in production - minimal performance impact
- ✅ All console logging is for debugging purposes only

## Quick Reference: Required Configuration

For extensions to work on a hybrid site:

```
☑ Hybrid Mode: Enabled
☑ Membership System: Enabled  
☑ Stripe Key: Configured (pk_live_... or pk_test_...)
☑ Extension Pricing: Set (1 week, 1 month, 3 months)
☑ User Membership: Starts with 'access_'
☑ User Status: NOT on trial
```

If all 6 boxes are checked, extensions should work.
If any box is unchecked, the debugger will tell you which one.
