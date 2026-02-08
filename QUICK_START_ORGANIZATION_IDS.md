# Quick Start: Modifying Organization IDs in Hybrid Sites

## ⚠️ IMPORTANT: Hybrid Sites Only!

**Organization IDs are ONLY for HYBRID SITES** where partners purchase access codes with Stripe/PayPal payments.

They are **NOT for Access Code Membership sites** where partners manually create free codes.

## Where to Modify Organization IDs

**Location:** WordPress Admin → **Hybrid site settings** → **Organizations**

### Navigation Steps

1. Log in to WordPress Admin
2. In the left sidebar, find **"Hybrid site settings"** menu (only visible when hybrid mode is enabled)
3. Click on **"Organizations"** submenu
4. You'll see a table of all partner admin users
5. Enter organization IDs for each partner admin
6. Click **"Update Organization Assignments"**

## Visual Guide

```
WordPress Admin Sidebar:
├── Dashboard
├── Posts
├── Media
├── Pages
├── Comments
├── ...
├── Hybrid site settings  ← Click here (Hybrid Sites)
│   ├── Settings
│   ├── Documentation
│   └── Organizations  ← Then click here
├── Access code settings  ← Different menu! (Access Code Membership)
└── ...
```

## What You'll See

The Organizations page displays:

- **Table of Partner Admins**: All users with "Partner Admin" role
- **Organization ID Input**: Number field (1-999) for each admin
- **Current Stats**: Student count for each organization
- **Hybrid Mode Status**: Notice showing if filtering is active

## How to Assign Organization IDs

### Example: Two Companies

**Company A** (admins: John and Sarah):
1. Find John in the table
2. Enter `2` in his Organization ID field
3. Find Sarah in the table
4. Enter `2` in her Organization ID field

**Company B** (admin: Mike):
1. Find Mike in the table
2. Enter `3` in his Organization ID field

**Result:**
- John and Sarah see each other's students and codes (both in org 2)
- Mike sees only his own data (org 3)

## Organization ID Reference

| ID | Purpose |
|----|---------|
| 0  | Reserved for site administrators (see all data) |
| 1  | Default organization (partner admins without custom ID) |
| 2+ | Custom organizations for different companies |

## Common Questions

### Q: I don't see the Organizations menu
**A:** Make sure:
- You're logged in as a site administrator (not partner admin)
- **Hybrid mode is enabled** (the menu only appears for hybrid sites!)
- You're looking under "**Hybrid site settings**" NOT "Access code settings"
- Access Code Membership and Hybrid Sites are different systems

### Q: What's the difference between Hybrid Sites and Access Code sites?
**A:** 
- **Hybrid Sites:** Partners PURCHASE codes with Stripe/PayPal → Need organization filtering
- **Access Code Sites:** Partners CREATE codes for free → No organization filtering needed

### Q: Is hybrid mode required?
**A:** YES! Organization filtering ONLY works for hybrid sites. The Organizations menu only appears when hybrid mode is enabled.

### Q: What if I leave the organization ID empty?
**A:** Partner admins without a custom org ID default to organization 1. They'll see all data from org 1.

### Q: Can I change organization IDs later?
**A:** Yes! Just update the ID and save. The students and codes stay with their original organization.

## Enabling Hybrid Mode

If you see a warning that hybrid mode is not enabled:

1. Go to **IELTS Courses** → **Settings**
2. Find **"Enable Hybrid Site Mode"** option
3. Check the box
4. Click **Save Changes**
5. Return to **Access code settings** → **Organizations**

## Need Help?

See full documentation:
- `HYBRID_SITE_ORGANIZATION_MANAGEMENT.md` - Complete organization guide
- `IMPLEMENTATION_COMPLETE_HYBRID_ORG_ISOLATION.md` - Technical details

## Screenshots

### Finding the Organizations Menu

```
Admin Menu:
┌─────────────────────────────┐
│ 🏠 Dashboard                │
│ 📝 Posts                     │
│ 📷 Media                     │
│ 📄 Pages                     │
│ ...                         │
│ 🔧 Hybrid site settings  ◄──┤ 1. Click here
│   ├─ Settings               │
│   ├─ Documentation          │
│   └─ Organizations      ◄───┤ 2. Then here
│ 👥 Access code settings     │   (Different menu!)
│ ...                         │
└─────────────────────────────┘
```

### Organizations Page

```
┌────────────────────────────────────────────────────────────┐
│ Manage Partner Organizations                               │
├────────────────────────────────────────────────────────────┤
│ ℹ Hybrid Mode Enabled: Partners filtered by organization   │
│                                                            │
│ Assign partner admins to organizations. Partners in the    │
│ same organization will share access to codes and students. │
│                                                            │
│ Organization ID Guidelines:                                │
│  • 0: Reserved for site administrators                     │
│  • 1: Default organization                                 │
│  • 2+: Custom organizations for different companies        │
│                                                            │
│ ┌─────────────────────────────────────────────────────┐   │
│ │ Partner Admin   Email           Org ID   Stats      │   │
│ ├─────────────────────────────────────────────────────┤   │
│ │ John Smith     john@co.com      [2]      15 students│   │
│ │ Sarah Jones    sarah@co.com     [2]      15 students│   │
│ │ Mike Wilson    mike@other.com   [3]       8 students│   │
│ └─────────────────────────────────────────────────────┘   │
│                                                            │
│ [Update Organization Assignments]                          │
└────────────────────────────────────────────────────────────┘
```

## Quick Steps Summary

1. **Access:** WordPress Admin → **Hybrid site settings** → **Organizations**
2. **Assign:** Enter organization IDs for each partner admin
3. **Same company = Same org ID**
4. **Different companies = Different org IDs**
5. **Save:** Click "Update Organization Assignments"

**Remember:** This is ONLY for hybrid sites where partners purchase codes!

That's it! Your hybrid site now has organization-based isolation.
