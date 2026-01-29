# IELTS Core and Plus Membership Tiers

## Overview
The IELTS Course Manager now offers two tiers of paid memberships: **Core** and **Plus**, each available for both Academic and General Training modules.

## Membership Tiers

### 🎯 IELTS Core Memberships

#### IELTS Core (Academic Module)
- **Duration:** 30 days
- **Access:** Full access to all Academic Module content
- **Price:** Set in admin (default configuration)
- **Membership Key:** `academic_full`

#### IELTS Core (General Training Module)
- **Duration:** 30 days
- **Access:** Full access to all General Training content
- **Price:** Set in admin (default configuration)
- **Membership Key:** `general_full`

---

### ⭐ IELTS Plus Memberships (NEW)

#### IELTS Plus (Academic Module)
- **Duration:** 90 days (3 months)
- **Access:** Full access to all Academic Module content
- **PLUS:** 2 live speaking assessments with certified instructors
- **Price:** $59.95 (default)
- **Membership Key:** `academic_plus`

#### IELTS Plus (General Training Module)
- **Duration:** 90 days (3 months)
- **Access:** Full access to all General Training content
- **PLUS:** 2 live speaking assessments with certified instructors
- **Price:** $59.95 (default)
- **Membership Key:** `general_plus`

---

## Key Differences: Core vs Plus

| Feature | Core | Plus |
|---------|------|------|
| **Duration** | 30 days | 90 days |
| **Course Access** | ✓ Full access | ✓ Full access |
| **Live Speaking Assessments** | ✗ Not included | ✓ 2 assessments |
| **Default Price** | Configure in admin | $59.95 |
| **Value** | Great for quick prep | Best for comprehensive preparation |

---

## Speaking Assessments (Plus Tier Only)

### What's Included
- **2 Live Sessions:** One-on-one speaking practice with certified IELTS instructors
- **Real IELTS Format:** Authentic exam simulation
- **Personalized Feedback:** Detailed assessment of your performance
- **Band Score Estimate:** Understanding your current level
- **Improvement Tips:** Targeted advice for better scores

### Scheduling
- Book assessments at your convenience during your 90-day access period
- Sessions typically 15-20 minutes each
- Available via video conferencing

---

## Admin Configuration

### Setting Prices

**Location:** Courses → Membership Settings → Payment Settings

You'll find fields for all membership types:
- Academic Module - Free Trial: $0.00
- General Training - Free Trial: $0.00
- **IELTS Core (Academic Module):** $__.__ (configure)
- **IELTS Core (General Training Module):** $__.__ (configure)
- **IELTS Plus (Academic Module):** $59.95 (default)
- **IELTS Plus (General Training Module):** $59.95 (default)
- English Only - Free Trial: $0.00
- English Only Full Membership: $__.__ (configure)

### Setting Durations

Durations are pre-configured with the following defaults:
- **Trial memberships:** 6 hours
- **Core memberships:** 30 days
- **Plus memberships:** 90 days
- **English Only:** 30 days

These can be customized in the Membership Settings page if needed.

---

## Using the Price Shortcode

### Display Core Membership Prices
```
[ielts_price type="academic_full"]  <!-- IELTS Core (Academic) -->
[ielts_price type="general_full"]   <!-- IELTS Core (General Training) -->
```

### Display Plus Membership Prices
```
[ielts_price type="academic_plus"]  <!-- IELTS Plus (Academic) -->
[ielts_price type="general_plus"]   <!-- IELTS Plus (General Training) -->
```

### Example: Pricing Comparison Table
```html
<h2>Choose Your Plan</h2>
<table>
    <thead>
        <tr>
            <th>Plan</th>
            <th>Duration</th>
            <th>Speaking Assessments</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>IELTS Core (Academic)</td>
            <td>30 days</td>
            <td>—</td>
            <td>[ielts_price type="academic_full"]</td>
        </tr>
        <tr>
            <td><strong>IELTS Plus (Academic)</strong></td>
            <td>90 days</td>
            <td><strong>2 live sessions</strong></td>
            <td>[ielts_price type="academic_plus"]</td>
        </tr>
        <tr>
            <td>IELTS Core (General Training)</td>
            <td>30 days</td>
            <td>—</td>
            <td>[ielts_price type="general_full"]</td>
        </tr>
        <tr>
            <td><strong>IELTS Plus (General Training)</strong></td>
            <td>90 days</td>
            <td><strong>2 live sessions</strong></td>
            <td>[ielts_price type="general_plus"]</td>
        </tr>
    </tbody>
</table>
```

---

## Benefits Display

The system includes a MEMBERSHIP_BENEFITS constant that provides descriptions:

```php
IELTS_CM_Membership::MEMBERSHIP_BENEFITS['academic_plus']
// Returns: "90 days full access + 2 live speaking assessments"
```

This can be used in templates to display what's included with each membership.

---

## Example Pricing Page Layout

```html
<div class="pricing-grid">
    <div class="pricing-card">
        <h3>IELTS Core</h3>
        <p class="subtitle">Academic Module</p>
        <div class="price">[ielts_price type="academic_full"]</div>
        <ul class="features">
            <li>✓ 30 days full access</li>
            <li>✓ All Academic materials</li>
            <li>✓ Practice tests</li>
        </ul>
        <a href="/register?type=academic_full" class="btn">Get Started</a>
    </div>
    
    <div class="pricing-card featured">
        <div class="badge">BEST VALUE</div>
        <h3>IELTS Plus</h3>
        <p class="subtitle">Academic Module</p>
        <div class="price">[ielts_price type="academic_plus"]</div>
        <ul class="features">
            <li>✓ 90 days full access</li>
            <li>✓ All Academic materials</li>
            <li>✓ Practice tests</li>
            <li><strong>✓ 2 live speaking assessments</strong></li>
        </ul>
        <a href="/register?type=academic_plus" class="btn btn-primary">Get Started</a>
    </div>
</div>
```

---

## Migration Notes

### Existing Memberships
- Users with "Academic Module Full Membership" are now labeled as "IELTS Core (Academic Module)"
- Users with "General Training Full Membership" are now labeled as "IELTS Core (General Training Module)"
- No data migration needed - the membership keys (`academic_full`, `general_full`) remain the same
- Existing memberships continue to work with 30-day durations as before

### Database Keys
- **Core memberships:** `academic_full`, `general_full` (unchanged)
- **New Plus memberships:** `academic_plus`, `general_plus`

---

## Troubleshooting

### Plus Memberships Not Showing in Admin
- Clear WordPress cache
- Check that the latest code has been deployed
- Verify `includes/class-membership.php` has the updated MEMBERSHIP_LEVELS constant

### Pricing Not Displaying Correctly
- Ensure prices are set in **Courses → Membership Settings → Payment Settings**
- Default Plus tier price is $59.95
- Use shortcode: `[ielts_price type="academic_plus"]` or `[ielts_price type="general_plus"]`

### Speaking Assessments Not Available
- Plus tier feature is configured in the membership description
- Actual scheduling/delivery of speaking assessments should be handled separately
- Consider integrating with booking system or calendar for assessment scheduling

---

## Summary

✅ **Core Memberships:** 30 days of full access to course content
✅ **Plus Memberships:** 90 days of full access + 2 live speaking assessments
✅ **Clear Differentiation:** Plus tier benefits clearly communicated
✅ **Flexible Pricing:** All prices configurable in WordPress admin
✅ **Shortcode Support:** Easy to display prices anywhere on your site

The new tier structure provides better value options for students while increasing revenue potential through the premium Plus offering.
