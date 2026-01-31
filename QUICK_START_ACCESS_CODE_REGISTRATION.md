# Quick Start Guide - Access Code Registration

## What Was Implemented?

A new shortcode `[ielts_access_code_registration]` that allows users to register with pre-generated access codes **without any payment options**.

## 🚀 How to Use It (5 Minutes)

### Step 1: Enable the System (If Not Already)
1. Go to **WordPress Admin → IELTS Courses → Settings**
2. Find "Access Code Membership System"
3. Enable it
4. Save changes

### Step 2: Create Access Codes
1. Go to page with `[iw_partner_dashboard]` shortcode (or create one)
2. Expand "Create Invite Codes"
3. Create some test codes:
   - **Quantity:** 5
   - **Course Group:** Academic Module
   - **Access Days:** 365
4. Click "Create Invite Codes"
5. Copy one of the generated codes (e.g., ABC12345)

### Step 3: Create Registration Page
1. Go to **WordPress Admin → Pages → Add New**
2. **Title:** "Register with Access Code"
3. **Content:** Add this shortcode:
   ```
   [ielts_access_code_registration redirect="/courses"]
   ```
4. Publish the page
5. Copy the page URL

### Step 4: Test It!
1. **Log out** of WordPress (important!)
2. Visit your new registration page
3. Fill in the form:
   - **Access Code:** [paste the code from Step 2]
   - **First Name:** Test
   - **Last Name:** User
   - **Email:** testuser@example.com
   - **Password:** testpassword123
   - **Confirm Password:** testpassword123
4. Click "Create Account"

### Expected Result ✅
- Account created
- You're logged in automatically
- Redirected to /courses (or home)
- You now have access to courses based on the code's course group

## 📝 What This Shortcode Does

### ✅ Includes
- Access code field
- User information fields (name, email, password)
- Validation and error messages
- Auto-login after registration

### ❌ Does NOT Include
- Payment fields
- Stripe integration
- Membership type selection dropdown
- Pricing information
- Any paid membership features

## 🔐 How It's Different from Paid Registration

| Feature | `[ielts_registration]` (Paid) | `[ielts_access_code_registration]` (New) |
|---------|-------------------------------|------------------------------------------|
| Payment | Required (Stripe) | Not allowed |
| Access Code | Not used | Required |
| Membership Selection | Yes (dropdown) | No (from code) |
| WordPress Role | academic_full, etc. | access_academic_module, etc. |

**Key Point:** These are completely separate systems. They don't interfere with each other.

## 📖 Documentation Files

All in the root directory:

1. **ACCESS_CODE_REGISTRATION_SHORTCODE.md**
   - Complete usage guide
   - All features explained
   - Troubleshooting tips

2. **VERSION_15_11_RELEASE_NOTES.md**
   - What changed in version 15.11
   - Technical details
   - Migration notes

3. **TESTING_GUIDE_ACCESS_CODE_REGISTRATION.md**
   - 16 test scenarios
   - Database queries to verify
   - Complete testing checklist

4. **IMPLEMENTATION_SUMMARY_V15_11.md**
   - How it was implemented
   - Code structure
   - Integration points

5. **SECURITY_SUMMARY_V15_11.md**
   - Security measures
   - Review findings
   - Production readiness

## 🎯 Common Use Cases

### Educational Institution
```
1. Institution buys 100 access codes
2. Admin creates codes (Academic Module, 365 days)
3. Students get unique codes
4. Students register using [ielts_access_code_registration]
5. Students immediately access courses
```

### Corporate Training
```
1. Company representative becomes partner
2. Partner creates codes for employees
3. Share registration page + codes with employees
4. Employees self-register
5. Access expires after training period
```

### Promotional Campaign
```
1. Create codes with 30-day duration
2. Distribute via email/social media
3. Users register with codes
4. Access expires after 30 days
```

## 🔍 Troubleshooting

### "Access code registration is currently not available"
**Solution:** Enable Access Code Membership System in IELTS Courses → Settings

### "Invalid or already used access code"
**Solutions:**
- Check code is typed correctly (8 characters, uppercase)
- Verify code exists in Partner Dashboard
- Check code status is 'active' (not already used)

### "Email already exists"
**Solution:** User may have already registered. Direct them to login page instead.

### Form not showing
**Solutions:**
- Check you're logged out
- Verify shortcode spelling: `[ielts_access_code_registration]`
- Check WordPress user registration is enabled

## 📊 Verifying It Worked

After a successful registration, check:

### In WordPress Admin
1. Go to **Users → All Users**
2. Find the new user
3. Check their role: Should be `access_academic_module`, `access_general_module`, or `access_general_english`

### In Partner Dashboard
1. Go to **Partner Dashboard → Managed Students**
2. New user should appear in the list
3. Access code should show as "Used"

### User Experience
1. User should be logged in automatically
2. User should see enrolled courses
3. User should be able to access course content

## 🚨 Important Notes

1. **One-Time Use:** Each access code can only be used once
2. **Logged-In Users:** Can't use this form (they're already registered)
3. **Separate System:** This doesn't interfere with paid memberships
4. **Version:** Make sure you're on version 15.11+

## 💡 Tips

### Customize Redirect
```
[ielts_access_code_registration redirect="/welcome"]
```

### Add Instructions
```html
<h2>Welcome Students!</h2>
<p>Enter the access code your instructor gave you:</p>

[ielts_access_code_registration redirect="/my-courses"]

<p><small>Don't have a code? <a href="/contact">Contact us</a></small></p>
```

### Multiple Pages
Create different pages for different audiences:
- `/register-students` - For students
- `/register-corporate` - For corporate clients
- `/register-promo` - For promotional campaigns

All use the same shortcode, just different page content/design.

## ✅ Checklist for Going Live

- [ ] Access Code Membership System enabled
- [ ] Test access codes created
- [ ] Registration page created and published
- [ ] Tested full registration flow
- [ ] Verified user gets correct role
- [ ] Verified user enrolled in courses
- [ ] Tested with each course group type
- [ ] Error scenarios tested
- [ ] Page URL shared with partners/users

## 📞 Need Help?

1. Check the detailed documentation files listed above
2. Review the testing guide for common issues
3. Verify your WordPress and plugin versions
4. Check error logs: `wp-content/debug.log`

## 🎉 That's It!

You now have a complete access code registration system that's:
- ✅ Secure
- ✅ Easy to use
- ✅ Separate from paid memberships
- ✅ Well-documented
- ✅ Production-ready

Enjoy your new feature! 🚀
