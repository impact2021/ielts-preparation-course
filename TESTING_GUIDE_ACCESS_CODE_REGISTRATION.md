# Testing Guide: Access Code Registration Shortcode

## Test Environment Setup

### Prerequisites
1. WordPress site with IELTS Course Manager plugin version 15.11+
2. Access Code Membership System enabled:
   - Go to IELTS Courses → Settings
   - Enable "Access Code Membership System"
3. User registration enabled in WordPress
4. At least one access code created

### Creating Test Access Codes

#### Via Partner Dashboard
1. Create a partner admin user or use admin account
2. Visit page with `[iw_partner_dashboard]` shortcode
3. Create invite codes:
   - Quantity: 3
   - Course Group: Academic Module
   - Access Days: 365
4. Copy the generated codes for testing

#### Via Database (for quick testing)
```sql
INSERT INTO wp_ielts_cm_access_codes 
(code, course_group, duration_days, created_by, created_date, status) 
VALUES 
('TEST0001', 'academic_module', 365, 1, NOW(), 'active'),
('TEST0002', 'general_module', 365, 1, NOW(), 'active'),
('TEST0003', 'general_english', 30, 1, NOW(), 'active');
```

## Test Scenarios

### Test 1: Basic Form Rendering
**Objective:** Verify the shortcode renders correctly

**Steps:**
1. Create a new page
2. Add shortcode: `[ielts_access_code_registration]`
3. Publish and view page

**Expected Results:**
- ✅ Form renders with title "Register with Access Code"
- ✅ Access Code field is visible and first in the form
- ✅ All required fields are present:
  - Access Code
  - First Name
  - Last Name
  - Email Address
  - Password
  - Confirm Password
- ✅ Submit button says "Create Account"
- ✅ No payment fields visible
- ✅ No membership selection dropdown

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 2: Security - Logged In User
**Objective:** Verify logged-in users cannot access the form

**Steps:**
1. Log in as any user
2. Visit the access code registration page

**Expected Results:**
- ✅ Form is not displayed
- ✅ Message shown: "You are already logged in. Access codes can only be used during registration."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 3: System Disabled
**Objective:** Verify form doesn't work when system is disabled

**Steps:**
1. Disable Access Code Membership System in settings
2. Visit the access code registration page as logged-out user

**Expected Results:**
- ✅ Form is not displayed
- ✅ Message shown: "Access code registration is currently not available."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 4: Field Validation - Empty Fields
**Objective:** Verify all required field validations work

**Steps:**
1. Visit registration page (logged out)
2. Leave all fields empty
3. Click "Create Account"

**Expected Results:**
- ✅ Error messages displayed:
  - "First name is required."
  - "Last name is required."
  - "Email is required."
  - "Password is required."
  - "Access code is required."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 5: Email Validation
**Objective:** Test email field validation

**Steps:**
1. Fill form with:
   - Access Code: TEST0001
   - Name: John Doe
   - Email: invalid-email
   - Password: password123
2. Submit form

**Expected Results:**
- ✅ Error: "Invalid email address."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 6: Duplicate Email
**Objective:** Test duplicate email prevention

**Steps:**
1. Create a test user with email: test@example.com
2. Fill registration form with same email
3. Submit form

**Expected Results:**
- ✅ Error: "Email already exists."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 7: Password Validation
**Objective:** Test password requirements

**Test 7a: Short Password**
- Password: 12345 (5 characters)
- Expected Error: "Password must be at least 6 characters."

**Test 7b: Password Mismatch**
- Password: password123
- Confirm: password456
- Expected Error: "Passwords do not match."

**Actual Results:**
- [ ] 7a Pass
- [ ] 7a Fail: ___________________
- [ ] 7b Pass
- [ ] 7b Fail: ___________________

---

### Test 8: Invalid Access Code
**Objective:** Test invalid access code handling

**Test 8a: Non-existent Code**
- Access Code: INVALID1
- Expected Error: "Invalid or already used access code."

**Test 8b: Used Code**
1. Successfully register with code TEST0001
2. Try to register again with TEST0001
- Expected Error: "Invalid or already used access code."

**Actual Results:**
- [ ] 8a Pass
- [ ] 8a Fail: ___________________
- [ ] 8b Pass
- [ ] 8b Fail: ___________________

---

### Test 9: Successful Registration - Academic Module
**Objective:** Test complete registration flow for Academic Module

**Steps:**
1. Log out if logged in
2. Visit registration page
3. Fill form:
   - Access Code: TEST0001 (academic_module)
   - First Name: John
   - Last Name: Doe
   - Email: john.doe@example.com
   - Password: password123
   - Confirm Password: password123
4. Submit form

**Expected Results:**
- ✅ User account created
- ✅ User logged in automatically
- ✅ Redirected to specified page or home
- ✅ Check in WordPress admin:
  - User exists with email john.doe@example.com
  - User has role: access_academic_module
  - User meta _ielts_cm_membership_type = access_academic_module
  - User meta _ielts_cm_membership_status = active
  - User meta iw_course_group = academic_module
  - Expiry date set to +365 days
- ✅ Check access code in database:
  - Status changed to 'used'
  - used_by = new user ID
  - used_date is set
- ✅ User enrolled in courses:
  - All courses with category 'academic'
  - All courses with category 'english'
  - All courses with category 'academic-practice-tests'

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 10: Successful Registration - General Module
**Objective:** Test registration with General Training code

**Steps:**
1. Register with code TEST0002 (general_module)
2. Email: jane.smith@example.com

**Expected Results:**
- ✅ User has role: access_general_module
- ✅ Enrolled in courses with categories:
  - general
  - english
  - general-practice-tests

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 11: Successful Registration - General English
**Objective:** Test registration with English-only code

**Steps:**
1. Register with code TEST0003 (general_english)
2. Email: bob.jones@example.com

**Expected Results:**
- ✅ User has role: access_general_english
- ✅ Enrolled ONLY in courses with category: english
- ✅ NOT enrolled in academic or general courses

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 12: Redirect Functionality
**Objective:** Test custom redirect parameter

**Steps:**
1. Create page with: `[ielts_access_code_registration redirect="/courses"]`
2. Register successfully with a valid code

**Expected Results:**
- ✅ After registration, redirected to /courses page

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 13: Username Generation
**Objective:** Test username is correctly generated from email

**Test 13a: Simple Email**
- Email: testuser@example.com
- Expected Username: testuser

**Test 13b: Duplicate Username**
1. Create user with username: existing
2. Register with email: existing@example.com
- Expected: Username = existing_[timestamp]

**Actual Results:**
- [ ] 13a Pass
- [ ] 13a Fail: ___________________
- [ ] 13b Pass
- [ ] 13b Fail: ___________________

---

### Test 14: Security - Nonce Validation
**Objective:** Test CSRF protection

**Steps:**
1. Visit registration page
2. View page source and copy form
3. Remove or modify nonce field
4. Submit form

**Expected Results:**
- ✅ Error: "Security check failed."

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 15: Case Insensitivity
**Objective:** Test access codes work in any case

**Steps:**
1. Code in database: TEST0001
2. Enter in form: test0001 (lowercase)
3. Submit form

**Expected Results:**
- ✅ Registration succeeds
- ✅ Code is converted to uppercase automatically

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

### Test 16: Partner Association
**Objective:** Verify user is linked to code creator

**Steps:**
1. Create code as user ID 2 (partner)
2. User registers with that code
3. Check new user's metadata

**Expected Results:**
- ✅ User meta iw_created_by_partner = 2

**Actual Results:**
- [ ] Pass
- [ ] Fail (describe issue): ___________________

---

## Database Verification Queries

### Check User Creation
```sql
SELECT ID, user_login, user_email, display_name 
FROM wp_users 
WHERE user_email = 'john.doe@example.com';
```

### Check User Role
```sql
SELECT meta_value 
FROM wp_usermeta 
WHERE user_id = [USER_ID] AND meta_key = 'wp_capabilities';
```

### Check Membership Meta
```sql
SELECT meta_key, meta_value 
FROM wp_usermeta 
WHERE user_id = [USER_ID] 
AND meta_key IN (
    '_ielts_cm_membership_type',
    '_ielts_cm_membership_status',
    '_ielts_cm_membership_expiry',
    'iw_course_group',
    'iw_membership_expiry',
    'iw_membership_status',
    'iw_created_by_partner'
);
```

### Check Access Code Status
```sql
SELECT * 
FROM wp_ielts_cm_access_codes 
WHERE code = 'TEST0001';
```

### Check Course Enrollments
```sql
SELECT e.*, p.post_title 
FROM wp_ielts_cm_enrollment e
JOIN wp_posts p ON e.course_id = p.ID
WHERE e.user_id = [USER_ID];
```

## Test Summary Template

**Date:** _______________
**Tester:** _______________
**Version:** 15.11

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Form Rendering | ☐ Pass ☐ Fail | |
| 2 | Logged In User | ☐ Pass ☐ Fail | |
| 3 | System Disabled | ☐ Pass ☐ Fail | |
| 4 | Empty Fields | ☐ Pass ☐ Fail | |
| 5 | Email Validation | ☐ Pass ☐ Fail | |
| 6 | Duplicate Email | ☐ Pass ☐ Fail | |
| 7a | Short Password | ☐ Pass ☐ Fail | |
| 7b | Password Mismatch | ☐ Pass ☐ Fail | |
| 8a | Invalid Code | ☐ Pass ☐ Fail | |
| 8b | Used Code | ☐ Pass ☐ Fail | |
| 9 | Academic Registration | ☐ Pass ☐ Fail | |
| 10 | General Registration | ☐ Pass ☐ Fail | |
| 11 | English Registration | ☐ Pass ☐ Fail | |
| 12 | Redirect | ☐ Pass ☐ Fail | |
| 13a | Username Simple | ☐ Pass ☐ Fail | |
| 13b | Username Duplicate | ☐ Pass ☐ Fail | |
| 14 | Nonce Validation | ☐ Pass ☐ Fail | |
| 15 | Case Insensitive | ☐ Pass ☐ Fail | |
| 16 | Partner Association | ☐ Pass ☐ Fail | |

**Overall Status:** ☐ All Pass ☐ Issues Found

**Critical Issues:**
- 

**Minor Issues:**
- 

**Recommendations:**
- 
