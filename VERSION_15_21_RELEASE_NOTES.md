# Version 15.21 Release Notes

## Release Date
February 8, 2026

## Summary
This release improves the hybrid site experience by streamlining the access code purchasing flow and ensuring course extension options are consistently available to all users.

## Changes

### Hybrid Site Improvements

#### 1. Removed "Create Invite Codes" Section in Hybrid Mode
- **What changed**: The "Create Invite Codes" section is now hidden when operating in hybrid mode
- **Why**: In hybrid mode, partners must first purchase codes before creating them. The separate "Create" section was confusing and redundant
- **Impact**: Cleaner, more intuitive partner dashboard interface in hybrid mode
- **Files modified**: `includes/class-access-codes.php`

#### 2. Inline Payment for Access Code Purchases
- **What changed**: Access code purchases now use inline payment processing (Stripe) directly on the partner dashboard
- **Why**: Previously required redirect to external payment page. Inline payment matches the user experience on the paid memberships site
- **Impact**: 
  - Faster, smoother purchase experience for partners
  - Consistent payment UX across the platform
  - Reduced cart abandonment
- **Files modified**: 
  - `includes/class-access-codes.php` - Updated UI to show inline Stripe card element
  - `includes/class-stripe-payment.php` - Added `create_code_purchase_payment_intent()` and `handle_code_purchase_payment()` methods
- **New AJAX endpoints**: `ielts_cm_create_code_purchase_payment_intent`

#### 3. Course Extension Tab Always Available
- **What changed**: The "Extend My Course" tab now appears for all users who have ever had a membership, regardless of whether it's currently active or expired
- **Why**: Users on hybrid sites should be able to extend their course access at any time
- **Impact**: 
  - Users with expired access code memberships can now easily renew
  - Improved user retention and revenue opportunities
- **Files modified**: `includes/class-shortcodes.php`
- **Technical details**: Tab visibility now checks for both `_ielts_cm_membership_type` OR `iw_membership_expiry` (access code expiry)

## Technical Details

### Database Changes
- None

### New Functions
- `IELTS_CM_Stripe_Payment::create_code_purchase_payment_intent()` - Creates Stripe payment intent for code purchases
- `IELTS_CM_Stripe_Payment::handle_code_purchase_payment()` - Processes successful code purchase payments via webhook

### Modified Functions
- Partner dashboard shortcode (`partner_dashboard_shortcode()`) - Updated to show inline payment form
- My Account shortcode (`display_my_account()`) - Updated tab visibility logic

### Webhook Processing
- Stripe webhook now handles `payment_type === 'access_code_purchase'` metadata
- Automatically creates access codes upon successful payment
- Logs payment transaction in payments table

## Upgrade Notes

### For Site Administrators
- No manual intervention required
- Existing access codes and memberships are not affected
- Test the inline payment flow after upgrading

### For Partners
- The "Create Invite Codes" section will no longer appear in hybrid mode
- All code purchases must now go through the "Purchase Access Codes" section
- Payment is processed inline - no redirect required

### For Students
- "Extend My Course" option now available even after membership expiry
- Can extend access at any time using inline payment

## Security Considerations
- Payment intent creation includes comprehensive validation
- Nonce verification on all AJAX requests
- Server-side price validation against configured pricing tiers
- User permission checks (partner admin or site admin required for code purchases)
- Hybrid mode verification before allowing code purchases

## Testing Recommendations
1. Test access code purchase with Stripe in hybrid mode
2. Verify "Create Invite Codes" section is hidden in hybrid mode
3. Verify "Create Invite Codes" section still appears in non-hybrid mode
4. Test course extension for active membership users
5. Test course extension for expired membership users
6. Verify access code users see "Extend My Course" tab
7. Test webhook payment confirmation creates codes correctly

## Known Issues
- None

## Future Enhancements
- PayPal integration for code purchases (currently Stripe only)
- Bulk discount visualization in code purchase UI
- Email notifications for successful code purchases
