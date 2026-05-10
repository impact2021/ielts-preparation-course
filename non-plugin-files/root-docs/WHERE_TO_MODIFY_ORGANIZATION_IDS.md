# Where to Modify Organization IDs in Hybrid Sites

## Answer: Hybrid site settings → Organizations

### Quick Navigation

```
WordPress Admin Menu
↓
Hybrid site settings (menu item)
↓
Organizations (submenu)
↓
Assign organization IDs to partner admins
```

## IMPORTANT: Hybrid Sites Only

**Organization IDs are ONLY for HYBRID SITES** where partners purchase codes with Stripe payments.

They are **NOT for Access Code Membership sites** where partners manually create free codes.

## Step-by-Step Instructions

### 1. Access the Organizations Page

**Path:** WordPress Admin → **Hybrid site settings** → **Organizations**

### 2. Assign Organization IDs

In the Organizations page, you'll see a table with all partner admin users.

**For each partner admin:**
- Enter a number (1-999) in the "Organization ID" field
- Same company = Same number
- Different companies = Different numbers
- Leave empty = Default to organization 1

### 3. Save Changes

Click **"Update Organization Assignments"** button at the bottom.

## Organization ID Guidelines

| ID | Purpose | Who Sees What |
|----|---------|---------------|
| 0 | Site administrators only | See ALL data from ALL organizations |
| 1 | Default organization | See all data from org 1 |
| 2 | Custom organization | See only data from org 2 |
| 3 | Custom organization | See only data from org 3 |
| ... | ... | ... |

## Example Setup

### Scenario: Two Companies Using Your Site

**Company A** - Language School Downtown
- Admin 1: John Smith → Org ID: `2`
- Admin 2: Sarah Jones → Org ID: `2`

**Company B** - Online Tutoring Inc.
- Admin 1: Mike Wilson → Org ID: `3`

**Result:**
- John and Sarah collaborate (both see org 2 students/codes)
- Mike is isolated (only sees org 3 students/codes)
- Site admin sees everyone (org 0)

## Visual Guide

### Admin Menu Structure

```
┌─────────────────────────────────────┐
│ WordPress Admin                     │
├─────────────────────────────────────┤
│                                     │
│  Dashboard                          │
│  Posts                              │
│  Media                              │
│  Pages                              │
│  Comments                           │
│  ...                                │
│  ┌───────────────────────────────┐ │
│  │ Hybrid site settings      ◄───┼─┤ CLICK HERE (Hybrid Sites Only)
│  ├───────────────────────────────┤ │
│  │  Settings                     │ │
│  │  Documentation                │ │
│  │  Organizations            ◄───┼─┤ THEN HERE
│  └───────────────────────────────┘ │
│  Access code settings (different!) │
│  Users                              │
│  Tools                              │
│  Settings                           │
│                                     │
└─────────────────────────────────────┘
```

**IMPORTANT:** Don't confuse with "Access code settings" - that's a different menu for the Access Code Membership system.

### Organizations Page Layout

```
┌──────────────────────────────────────────────────────────────┐
│  Manage Partner Organizations                                │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ℹ Hybrid Mode Enabled: Partners filtered by organization    │
│                                                              │
│  Assign partner admins to organizations. Partners in the     │
│  same organization will share access to codes and students.  │
│                                                              │
│  Organization ID Guidelines:                                 │
│  • 0: Reserved for site administrators (see all data)        │
│  • 1: Default organization (all without custom org ID)       │
│  • 2+: Custom organizations for separating companies         │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │                                                        │ │
│  │  Partner Admin     Email            Org ID    Stats   │ │
│  │  ────────────────────────────────────────────────────│ │
│  │  John Smith       john@co.com       [ 2 ]  15 students│ │
│  │  Sarah Jones      sarah@co.com      [ 2 ]  15 students│ │
│  │  Mike Wilson      mike@other.com    [ 3 ]   8 students│ │
│  │                                                        │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  [Update Organization Assignments]  ◄─── Click to save       │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## Alternative Access Methods

### Method 1: From Hybrid Settings Page
1. Go to **Hybrid site settings** → **Settings**
2. Scroll to find organization information
3. Click link to Organizations page

### Method 2: From Admin Sidebar
1. Find **Hybrid site settings** in the sidebar
2. Click **Organizations** submenu

### Method 3: Direct URL
Navigate to: `wp-admin/admin.php?page=ielts-partner-organizations`

## Important Distinction

### Hybrid Sites vs Access Code Sites

| Feature | Hybrid Sites | Access Code Membership Sites |
|---------|--------------|------------------------------|
| Partners purchase codes | ✅ Yes (with Stripe/PayPal) | ❌ No |
| Partners create free codes | ❌ No | ✅ Yes |
| Organization filtering | ✅ Yes | ❌ No (not needed) |
| Menu location | Hybrid site settings | Access code settings |
| Organizations page | ✅ Available | ❌ Not available |

**Organization IDs are ONLY for Hybrid Sites!**

## Prerequisites

Before you can modify organization IDs:

1. **Hybrid Mode Must Be Enabled**
   - Go to: IELTS Courses → Settings
   - Enable "Hybrid Site Mode"
   
2. **Partner Admins Must Exist**
   - Create users with "Partner Admin" role
   - Users → Add New → Role: Partner Admin

3. **Access Code System Must Be Enabled**
   - The Organizations menu appears automatically when enabled

## Common Issues

### "I don't see the Organizations menu"

**Check:**
- Are you logged in as a site administrator? (not partner admin)
- **Is Hybrid Mode enabled?** The menu only appears for hybrid sites
- Look under "Hybrid site settings" NOT "Access code settings"
- If you only see "Access code settings", you have an Access Code Membership site, not a hybrid site

### "What's the difference between Hybrid Sites and Access Code sites?"

**Hybrid Sites:**
- Partners PURCHASE codes with Stripe/PayPal
- Used when you want to sell access codes to companies
- Has "Hybrid site settings" menu
- Needs organization filtering for multi-company isolation

**Access Code Membership Sites:**
- Partners CREATE codes for free (manual assignment)
- Used when you want to give partners free access to distribute
- Has "Access code settings" menu  
- Doesn't need organization filtering (single partner or fully trusted partners)

### "Organization filtering isn't working"

**Check:**
- Is Hybrid Mode enabled?
- Look for the notice at top of Organizations page
- If it says "not in hybrid mode", enable it first

### "My changes aren't saving"

**Check:**
- Click "Update Organization Assignments" button
- Look for green success message
- Refresh the page to see updated values

## What Happens After Assignment

Once you assign organization IDs:

1. **Immediate Effect:** Filtering activates instantly
2. **Partner Dashboard:** Each partner sees only their org's data
3. **Student Management:** Students stay with their original org
4. **Code Management:** Codes filtered by organization

## Can I Change Organization IDs Later?

**Yes!** Organization IDs can be changed anytime:
- Update the number in the field
- Click "Update Organization Assignments"
- Changes apply immediately
- Students and codes remain with their original organization

## Summary

**Question:** In hybrid sites, where do I modify the organisation ID?

**Answer:** WordPress Admin → **Hybrid site settings** → **Organizations**

**⚠️ IMPORTANT:** Organization IDs are **ONLY for Hybrid Sites** where partners purchase codes. They are NOT for Access Code Membership sites where partners create free codes.

**Quick Access:**
- Look in the WordPress admin sidebar
- Find "**Hybrid site settings**" menu (only visible when hybrid mode enabled)
- Click "**Organizations**" submenu
- Assign IDs and save

**Don't Confuse With:**
- "Access code settings" menu - That's for the Access Code Membership system (different feature)

That's it! 🎉

## Need More Help?

- **Quick Start:** `QUICK_START_ORGANIZATION_IDS.md`
- **Full Guide:** `HYBRID_SITE_ORGANIZATION_MANAGEMENT.md`
- **Technical Details:** `IMPLEMENTATION_COMPLETE_HYBRID_ORG_ISOLATION.md`
