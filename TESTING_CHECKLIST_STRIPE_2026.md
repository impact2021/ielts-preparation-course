# Manual Testing Checklist for Stripe Payment Fixes

This checklist should be completed in a WordPress environment with the IELTS Course Manager plugin installed and Stripe configured.

## Prerequisites Setup

- [ ] WordPress 6.4+ installed
- [ ] IELTS Course Manager plugin installed
- [ ] Stripe test keys configured in Memberships → Payment Settings
- [ ] At least one paid membership option configured with price > 0
- [ ] Test card ready: 4242 4242 4242 4242

## Test 1: Width Display (Desktop)

**Objective:** Verify payment section displays at 100% width

**Steps:**
1. [ ] Open browser (Chrome, Firefox, or Safari)
2. [ ] Navigate to registration page with `[ielts_registration]` shortcode
3. [ ] Resize browser window to > 768px width (desktop size)
4. [ ] Observe name fields (First/Last) are side-by-side at 50% width each
5. [ ] Select a paid membership from dropdown (e.g., "Academic Full Membership")
6. [ ] Wait for payment section to appear

**Expected Results:**
- [ ] Payment section spans full width of form
- [ ] Payment section is same width as email field (not name fields)
- [ ] Payment form elements (card input) span the full width

**Actual Results:**
- Width: ___________
- Screenshot saved as: ___________

## Test 2: Width Display (Mobile)

**Objective:** Verify payment section displays correctly on mobile

**Steps:**
1. [ ] Open browser on mobile device OR use DevTools device emulation
2. [ ] Navigate to registration page
3. [ ] Select a paid membership
4. [ ] Observe payment section appearance

**Expected Results:**
- [ ] All form fields are 100% width (stacked vertically)
- [ ] Payment section is also 100% width
- [ ] No horizontal scrolling required

**Actual Results:**
- Width: ___________
- Screenshot saved as: ___________

## Test 3: Successful Payment (No API Error)

**Objective:** Verify payment processes without the API compatibility error

**Steps:**
1. [ ] Navigate to registration page
2. [ ] Fill in registration details:
   - First Name: TestFirst
   - Last Name: TestLast
   - Email: test-[timestamp]@example.com (use unique email)
   - Password: Test@12345
   - Confirm Password: Test@12345
3. [ ] Select a paid membership
4. [ ] Wait for payment section to appear
5. [ ] Enter Stripe test card:
   - Card: 4242 4242 4242 4242
   - Expiry: 12/28 (any future date)
   - CVC: 123
   - ZIP: 12345
6. [ ] Open browser DevTools → Console tab
7. [ ] Click Submit button
8. [ ] Wait for processing

**Expected Results:**
- [ ] NO error about "payment_method_types" in console
- [ ] NO error about "automatic payment methods" in console
- [ ] Payment processes successfully
- [ ] Success message displayed
- [ ] Redirect to login page or success page
- [ ] User account created in WordPress
- [ ] Membership activated for user

**Actual Results:**
- Console errors (if any): ___________
- Payment status: ___________
- User created: Yes/No
- Membership active: Yes/No
- Screenshot saved as: ___________

## Test 4: Error Handling

**Objective:** Verify error messages display correctly

**Steps:**
1. [ ] Navigate to registration page
2. [ ] Fill in details with an email that already exists
3. [ ] Select paid membership
4. [ ] Enter test card
5. [ ] Submit

**Expected Results:**
- [ ] Clear error message: "Email already exists"
- [ ] Payment section remains visible
- [ ] Form can be corrected and resubmitted

**Actual Results:**
- Error message: ___________
- Screenshot saved as: ___________

## Test 5: Browser Compatibility

**Objective:** Verify fixes work in all major browsers

### Chrome
- [ ] Width displays correctly (100%)
- [ ] Payment processes without error
- [ ] No console errors
- Version tested: ___________

### Firefox
- [ ] Width displays correctly (100%)
- [ ] Payment processes without error
- [ ] No console errors
- Version tested: ___________

### Safari
- [ ] Width displays correctly (100%)
- [ ] Payment processes without error
- [ ] No console errors
- Version tested: ___________

### Mobile Safari (iOS)
- [ ] Width displays correctly (100%)
- [ ] Payment processes without error
- [ ] No console errors
- iOS Version tested: ___________

## Test 6: Payment Methods Display

**Objective:** Verify automatic payment methods work

**Steps:**
1. [ ] Check Stripe Dashboard → Settings → Payment Methods
2. [ ] Note which payment methods are enabled
3. [ ] Navigate to registration page
4. [ ] Select paid membership
5. [ ] Observe payment section

**Expected Results:**
- [ ] Card payment option visible
- [ ] If Link enabled in Stripe, Link option visible
- [ ] If Apple Pay enabled and on Safari, Apple Pay visible
- [ ] Payment method tabs/options match Stripe Dashboard

**Actual Results:**
- Payment methods visible: ___________
- Match Stripe Dashboard: Yes/No
- Screenshot saved as: ___________

## Test 7: Stripe Dashboard Verification

**Objective:** Verify payment appears correctly in Stripe

**Steps:**
1. [ ] Complete successful payment (Test 3)
2. [ ] Log into Stripe Dashboard
3. [ ] Navigate to Payments
4. [ ] Find the test payment

**Expected Results:**
- [ ] Payment appears in Stripe Dashboard
- [ ] Status: Succeeded
- [ ] Amount matches membership price
- [ ] Metadata includes user_id and membership_type
- [ ] No errors or warnings

**Actual Results:**
- Payment found: Yes/No
- Status: ___________
- Amount: ___________
- Metadata present: Yes/No
- Screenshot saved as: ___________

## Screenshots Needed

Please take and save the following screenshots:

### Width Comparison
- [ ] Desktop: Full registration form showing payment section at 100% width
- [ ] Mobile: Form showing payment section on mobile device

### Payment Success
- [ ] Payment form filled in (before submission)
- [ ] Success message after payment
- [ ] User dashboard showing active membership

### Browser Console
- [ ] Console showing no errors during payment process

### Stripe Dashboard
- [ ] Successful payment in Stripe Dashboard
- [ ] Payment metadata details

## Summary

**Total Tests:** 7  
**Tests Passed:** _____ / 7  
**Tests Failed:** _____ / 7  
**Critical Issues:** _____  
**Minor Issues:** _____  

**Overall Status:** Pass / Fail / Pass with Notes

**Notes:**
___________________________________________
___________________________________________
___________________________________________

## Tester Information

**Tester Name:** ___________  
**Test Date:** ___________  
**WordPress Version:** ___________  
**PHP Version:** ___________  
**Plugin Version:** ___________  
**Stripe Account:** Test Mode

## Sign-off

If all critical tests pass, the fix is ready for production deployment.

**Tested By:** ___________ **Date:** ___________  
**Approved By:** ___________ **Date:** ___________

---

## Quick Reference: What Was Fixed

1. **Width Issue**: Added `class="form-field-full"` to payment section div
   - Location: `includes/class-shortcodes.php` line 1866
   - Result: Payment section now spans 100% width on desktop

2. **API Error**: Removed `paymentMethodTypes: ['card']` from JavaScript
   - Location: `assets/js/registration-payment.js` lines 91-96
   - Result: Compatible with server-side `automatic_payment_methods`

## Rollback Plan

If critical issues are found:
```bash
git revert b0d608c  # Revert visual guide
git revert 34510fd  # Revert summary
git revert 434d994  # Revert main docs
git revert 5ba6586  # Revert code changes
git push
```

Or from GitHub UI: Revert the PR merge.
