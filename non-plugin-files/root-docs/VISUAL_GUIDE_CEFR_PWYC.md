# Visual Guide: CEFR Scoring & Pay What You Can (v16.18)

This guide shows what users and admins will see after the v16.18 update.

---

## Part 1 — CEFR Grading (all sites)

### 1a. Admin: Set CEFR scoring on an exercise

In the exercise edit screen, the **Scoring Type** dropdown now includes a **CEFR Level** option:

```
┌─────────────────────────────────────────────────────────────┐
│  Exercise Settings                                          │
├─────────────────────────────────────────────────────────────┤
│  Scoring Type:                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ CEFR Level (A1–C2)                              ▼  │   │
│  └─────────────────────────────────────────────────────┘   │
│  Choose how results are displayed. For CEFR, results will  │
│  show as A1–C2 levels.                                      │
└─────────────────────────────────────────────────────────────┘
```

Other scoring options remain unchanged:
- Percentage (default)
- IELTS General Training Reading (Band Score)
- IELTS Academic Reading (Band Score)
- IELTS Listening (Band Score)
- **CEFR Level (A1–C2)** ← new

---

### 1b. Student: Exercise result panel (after submission)

**Before (percentage — unchanged)**
```
┌─────────────────────────────────────────────────────┐
│             Exercise completed                      │
│                                                     │
│     ┌─────────────────────────────────────────┐    │
│     │  18 / 25  (72%)                         │    │
│     └─────────────────────────────────────────┘    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**After — CEFR scoring enabled on this exercise**
```
┌─────────────────────────────────────────────────────┐
│             Exercise completed                      │
│                                                     │
│     ┌─────────────────────────────────────────┐    │
│     │  Your Score:  18 / 25 correct           │    │
│     │  CEFR Level:  Level B2                  │    │
│     └─────────────────────────────────────────┘    │
│                                                     │
└─────────────────────────────────────────────────────┘
```

CEFR level thresholds:

| Percentage | CEFR Level |
|------------|------------|
| ≥ 85 %     | C2         |
| 70 – 84 %  | C1         |
| 55 – 69 %  | B2         |
| 40 – 54 %  | B1         |
| 25 – 39 %  | A2         |
| < 25 %     | A1         |

---

### 1c. Student: Timer bar (CBT / Listening exercises)

**Before (percentage)**
```
┌──────────────────────────────────────────────────────────┐
│  ← Prev     NEXT →   │  Score: 72%   │  [Review answers] │
└──────────────────────────────────────────────────────────┘
```

**After — CEFR scoring enabled**
```
┌──────────────────────────────────────────────────────────┐
│  ← Prev     NEXT →   │  CEFR Level: Level B2  │  [Review] │
└──────────────────────────────────────────────────────────┘
```

---

### 1d. Band Scores table — `display_type="cefr"` shortcode

Use the shortcode on any page to show the band scores table in CEFR mode:

```
[ielts_band_scores display_type="cefr" title="Your CEFR Levels"]
```

**Standard (band score) view**
```
┌────────────────────────────────────────────────────────┐
│               Your Band Scores                         │
├───────────┬───────────┬───────────┬───────────────────┤
│ Reading   │ Listening │  Writing  │  Speaking         │
│   6.0     │   7.0     │   6.5     │   6.0             │
│   Band    │   Band    │   Band    │   Band            │
├───────────┴───────────┴───────────┴───────────────────┤
│ Official skills total:  6.4    Band                   │
│ Overall (all skills):   6.4    Band                   │
├────────────────────────────────────────────────────────┤
│ Band scores are estimates based on your test           │
│ performance. Complete more tests for accurate results. │
└────────────────────────────────────────────────────────┘
```

**CEFR view (`display_type="cefr"`)**
```
┌────────────────────────────────────────────────────────┐
│               Your CEFR Levels                         │
├───────────┬───────────┬───────────┬───────────────────┤
│ Reading   │ Listening │  Writing  │  Speaking         │
│ LEVEL B2  │ LEVEL C1  │ LEVEL B2  │ LEVEL B2          │
│           │           │           │                   │
├───────────┴───────────┴───────────┴───────────────────┤
│ Official skills total:  LEVEL B2                      │
│ Overall (all skills):   LEVEL B2                      │
├────────────────────────────────────────────────────────┤
│ CEFR levels are estimates based on your test           │
│ performance. Complete more tests for accurate results. │
└────────────────────────────────────────────────────────┘
```

---

## Part 2 — Pay What You Can (primary site only)

### 2a. Admin: Enable PWYC in Membership Settings

The settings are visible **only on the primary site** (standalone or primary role).

Navigate to **IELTS Course Manager → Membership Settings**:

```
┌───────────────────────────────────────────────────────────┐
│  Membership Settings                                      │
├───────────────────────────────────────────────────────────┤
│  Enable Free Trial          [✓] Allow free trial sign-up  │
│                                                           │
│  Pay What You Can           [✓] Allow trial members to    │
│  (Full Membership)              choose their own price    │
│                                 when upgrading to full    │
│                                 membership                │
│                             Only available on the primary │
│                             site.                         │
│                                                           │
│  Minimum PWYC Amount        [ 10.00 ] USD                 │
│                             Minimum amount a user must    │
│                             pay (must be at least $5.00). │
│                                                           │
│                             [ Save Settings ]             │
└───────────────────────────────────────────────────────────┘
```

> **Note**: These settings only appear on the **primary site**. On sub-sites the rows are hidden.

---

### 2b. Student: Registration / upgrade form with PWYC active

When a trial member selects a **paid full membership** type (e.g. Academic Full Membership),
a "Choose your price" field appears **above** the Stripe card element:

```
┌──────────────────────────────────────────────────────────┐
│               Upgrade to Full Membership                 │
├──────────────────────────────────────────────────────────┤
│  Membership Type                                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │ Academic Full Membership                     ▼  │   │
│  └──────────────────────────────────────────────────┘   │
│                                                          │
│  ── Payment Details ───────────────────────────────────  │
│                                                          │
│  Choose your price (USD)                                 │
│  $ [ 10.00 ]                                             │
│  Minimum: $10.00 USD. Pay what you can — every           │
│  contribution helps!                                     │
│                                                          │
│  Payment Method                                          │
│  ┌──────────────────────┐  ┌──────────────────────────┐ │
│  │ 💳 Credit/Debit Card │  │  PayPal (if enabled)     │ │
│  └──────────────────────┘  └──────────────────────────┘ │
│                                                          │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Card Number                                     │   │
│  ├───────────────────────┬──────────────────────────┤   │
│  │  MM / YY              │  CVC                     │   │
│  └───────────────────────┴──────────────────────────┘   │
│                                                          │
│  [ Pay Now ]                                             │
└──────────────────────────────────────────────────────────┘
```

The price field is **hidden** when:
- The user selects a trial membership
- The user selects an extension
- PWYC is not enabled in admin settings

It is **shown** when:
- PWYC is enabled in admin settings
- The user selects a non-trial, non-extension full membership

---

### 2c. Validation

If the user enters an amount below the configured minimum, submission is blocked:

```
┌──────────────────────────────────────────────────────────┐
│  ⚠️  Please enter an amount of at least $10.00 USD.      │
└──────────────────────────────────────────────────────────┘
```

The Stripe card element automatically reinitialises when the amount field loses focus
(`blur`) or changes (`change`), so the payment intent reflects the entered amount.

---

## Quick Setup Checklist

### Enable CEFR scoring on an exercise
1. Edit the exercise in WordPress admin
2. Scroll to **Exercise Settings**
3. Set **Scoring Type** → **CEFR Level (A1–C2)**
4. Save/Publish the exercise

### Show CEFR levels in the Band Scores table
Add this shortcode to your progress page:
```
[ielts_band_scores display_type="cefr" title="Your CEFR Level"]
```

### Enable Pay What You Can upgrades
1. Make sure you are on the **primary site** dashboard
2. Go to **IELTS Course Manager → Membership Settings**
3. Check **Pay What You Can (Full Membership)**
4. Set the **Minimum PWYC Amount** (minimum $5.00)
5. Click **Save Settings**
