# Debugger Display Conditions - Verification

## Question
"And this will only show the debugger on the hybrid site on the course extension page, right?"

## Answer: YES ✓

The debugger **only** displays when **ALL** of the following conditions are met:

## Required Conditions

### 1. ✅ Hybrid Site Only
**Code Location:** `includes/class-shortcodes.php` line 3098
```php
if ($hybrid_mode_enabled && current_user_can('manage_options')):
```

**What it checks:**
- `$hybrid_mode_enabled = get_option('ielts_cm_hybrid_site_enabled', false);` (line 3052)
- This option must be enabled in: **WordPress Admin → IELTS Course → Settings → Enable Hybrid Site Mode**

**Result:** If hybrid mode is disabled, the debugger **never renders** - not even the HTML.

---

### 2. ✅ Admin Users Only
**Code Location:** `includes/class-shortcodes.php` line 3098
```php
if ($hybrid_mode_enabled && current_user_can('manage_options')):
```

**What it checks:**
- `current_user_can('manage_options')` - WordPress capability check
- Only users with administrator role have this capability

**Result:** Regular users (even logged-in members) **never see the debugger**.

---

### 3. ✅ Course Extension Tab/Page Only
**Code Location:** `includes/class-shortcodes.php` lines 3023-3024

The debugger is embedded within:
```php
<!-- Extend My Course Tab -->
<div class="ielts-tab-content" id="extend-course">
```

**Tab structure:**
- Line 2830: Tab button defined as `<button class="ielts-tab-button" data-tab="extend-course">`
- Line 3024: Tab content container `<div class="ielts-tab-content" id="extend-course">`
- Lines 3095-3253: Debugger code is inside this tab content

**Result:** Debugger only appears when the "Extend My Course" tab is active.

---

### 4. ✅ Access Code Membership with Extension Form Shown
**Code Location:** `includes/class-shortcodes.php` lines 3068-3095

The debugger appears **after** the extension dropdown, which only shows when:
```php
<?php elseif (!$hybrid_mode_enabled): ?>
    <!-- Non-hybrid site message -->
<?php else: ?>
    <!-- Hybrid site: Show extension form -->
    <form name="ielts_extension_form">
        <select id="ielts_membership_type_extension">
        <!-- Debugger appears RIGHT HERE -->
```

**Additional conditions for extension form:**
- User has access code membership: `$is_access_code_membership = strpos($membership_type, 'access_') === 0;` (line 3049)
- User is NOT on trial: `!$is_trial` (checked earlier in code flow)

**Result:** Debugger only shows when the extension form itself is visible.

---

## Display Flow Diagram

```
User visits account page
    │
    ├─→ NOT hybrid site? 
    │   └─→ ❌ Debugger NEVER loads (condition fails at line 3098)
    │
    ├─→ User NOT admin?
    │   └─→ ❌ Debugger NEVER loads (condition fails at line 3098)
    │
    ├─→ User on trial membership?
    │   └─→ Shows "Become a Full Member" tab
    │       └─→ ❌ Debugger NOT shown (wrong tab)
    │
    └─→ User has paid membership?
        ├─→ NOT access code membership?
        │   └─→ Shows generic "contact us" message
        │       └─→ ❌ Debugger NOT shown (extension form not shown)
        │
        └─→ Access code membership on hybrid site?
            └─→ Shows "Extend My Course" tab
                └─→ Extension form appears
                    └─→ ✅ Debugger appears (ALL conditions met)
```

---

## Code Location Summary

| Condition | File | Line(s) | Check |
|-----------|------|---------|-------|
| Hybrid Site | `includes/class-shortcodes.php` | 3052, 3098 | `$hybrid_mode_enabled` |
| Admin Only | `includes/class-shortcodes.php` | 3098 | `current_user_can('manage_options')` |
| Extension Tab | `includes/class-shortcodes.php` | 3024, 3095 | Inside `id="extend-course"` div |
| Extension Form | `includes/class-shortcodes.php` | 3068-3095 | Inside `else` block for hybrid sites |

---

## User Scenarios

### ✅ Scenario 1: Admin on Hybrid Site, Paid Access Code Member
```
✓ Hybrid mode enabled
✓ User is admin
✓ User has paid access code membership (e.g., access_academic_30_days)
✓ User navigates to "Extend My Course" tab

Result: DEBUGGER VISIBLE
```

### ❌ Scenario 2: Admin on NON-Hybrid Site
```
✗ Hybrid mode disabled
✓ User is admin
✓ User has paid access code membership

Result: DEBUGGER NOT SHOWN (fails hybrid check)
```

### ❌ Scenario 3: Regular User on Hybrid Site
```
✓ Hybrid mode enabled
✗ User is NOT admin (regular member)
✓ User has paid access code membership

Result: DEBUGGER NOT SHOWN (fails admin check)
```

### ❌ Scenario 4: Admin on Profile Tab
```
✓ Hybrid mode enabled
✓ User is admin
✓ User has paid access code membership
✗ User viewing "Profile" tab (not "Extend My Course")

Result: DEBUGGER NOT SHOWN (wrong tab)
```

### ❌ Scenario 5: Admin with Trial Membership
```
✓ Hybrid mode enabled
✓ User is admin
✗ User has trial membership (shows "Become a Full Member" tab instead)

Result: DEBUGGER NOT SHOWN (on different tab for trials)
```

### ❌ Scenario 6: Admin with Regular Paid Membership
```
✓ Hybrid mode enabled
✓ User is admin
✗ User has regular paid membership (NOT access code)

Result: DEBUGGER NOT SHOWN (extension form not shown)
```

---

## Pages Where Debugger Will NEVER Appear

- ❌ Homepage
- ❌ Course listing pages
- ❌ Individual lesson pages
- ❌ Quiz pages
- ❌ Non-hybrid sites (any page)
- ❌ Registration page
- ❌ Login page
- ❌ Admin dashboard (WordPress backend)
- ❌ Profile tab (on account page)
- ❌ Progress tab (on account page)
- ❌ "Become a Full Member" tab (trial users)

---

## Pages Where Debugger CAN Appear

Only **ONE** location:
- ✓ Account page (`[ielts_account]` shortcode)
  - ✓ "Extend My Course" tab
  - ✓ When all conditions are met (hybrid + admin + access code + paid)

---

## Visual Context

```
┌─────────────────────────────────────────────────────────┐
│  My Account Page (only on hybrid sites)                 │
├─────────────────────────────────────────────────────────┤
│  Tabs: [Profile] [Extend My Course*] [Progress]         │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │ Extend My Course Tab (active)                     │  │
│  ├──────────────────────────────────────────────────┤  │
│  │ Your membership: Access Code Academic             │  │
│  │ Expires: March 15, 2026                           │  │
│  │                                                    │  │
│  │ Select Extension Duration: [Dropdown ▼]           │  │
│  │                                                    │  │
│  │ ┌─────────────────────────────────────────────┐  │  │
│  │ │ 🔧 Extension Payment Debugger (Admin Only)  │  │  │
│  │ │ Status: ✓ JavaScript should be loaded       │  │  │
│  │ │ [View Diagnostic Details ▼]                 │  │  │
│  │ │ [Test Extension Selection]                  │  │  │
│  │ └─────────────────────────────────────────────┘  │  │
│  │                                                    │  │
│  │ [Payment section will appear here...]            │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

**Note:** The gray box with 🔧 icon only appears for admins on hybrid sites.

---

## Testing Checklist

To verify the debugger only shows in the correct location:

- [ ] **Non-hybrid site:** Log in as admin → No debugger anywhere
- [ ] **Hybrid site, non-admin:** Log in as regular user → No debugger
- [ ] **Hybrid site, admin, Profile tab:** Navigate to Profile → No debugger
- [ ] **Hybrid site, admin, trial membership:** See "Become Full Member" tab → No debugger
- [ ] **Hybrid site, admin, regular membership:** See generic extension message → No debugger
- [ ] **Hybrid site, admin, access code membership, Extension tab:** ✓ Debugger appears

---

## Conclusion

**YES**, the debugger **ONLY** shows on:
1. ✅ **Hybrid sites** (`$hybrid_mode_enabled` must be true)
2. ✅ **Course extension page/tab** (inside `id="extend-course"` tab content)
3. ✅ **To admin users only** (`current_user_can('manage_options')`)

Regular users, non-hybrid sites, and other tabs/pages will **never** see the debugger.

The implementation is precisely targeted and has **zero impact** on:
- Non-hybrid sites (any configuration)
- Regular users (even on hybrid sites)
- Any other page or tab in the system

---

## Code Reference

**Primary conditional check (line 3098):**
```php
if ($hybrid_mode_enabled && current_user_can('manage_options')):
    // Debugger code here (lines 3098-3253)
endif;
```

This is a **hard gate** - if either condition is false, the debugger code doesn't execute at all, and no HTML is rendered.
