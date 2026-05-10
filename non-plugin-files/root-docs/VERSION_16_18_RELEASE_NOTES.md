# Version 16.18 Release Notes

**Release Date**: March 2026
**Type**: Feature Release

## Overview

This release adds three user-facing improvements:

1. **CEFR grading mode** for exercises and the band scores table (all sites)
2. **Free trial re-enabled** by default
3. **Pay What You Can (PWYC)** upgrade path for trial members on the primary site

---

## Feature 1 — CEFR Grading (all sites)

### What is CEFR?

The Common European Framework of Reference (CEFR) uses six levels—A1, A2, B1, B2, C1, C2—to
describe language proficiency. The mapping used in this plugin is:

| Percentage | CEFR Level |
|------------|------------|
| ≥ 85 %     | C2         |
| 70 – 84 %  | C1         |
| 55 – 69 %  | B2         |
| 40 – 54 %  | B1         |
| 25 – 39 %  | A2         |
| < 25 %     | A1         |

### Changes

| File | Change |
|---|---|
| `includes/admin/class-admin.php` | Added **CEFR Level (A1–C2)** option to the Scoring Type dropdown in the exercise meta box; `cefr` added to the valid-types whitelist so the meta value persists on save. |
| `includes/class-quiz-handler.php` | `get_display_score()` now returns `display_type = 'cefr'` and `display = 'Level B2'` (etc.) when the exercise is set to CEFR scoring; new `convert_percentage_to_cefr()` helper. |
| `assets/js/frontend.js` | Quiz result panel and CBT/Listening timer bar both handle `display_type === 'cefr'`, rendering the level string instead of a raw percentage. |
| `includes/class-shortcodes.php` | `[ielts_band_scores]` shortcode accepts `display_type="cefr"`. When set, cells show `LEVEL A2` etc., totals are back-converted through the same thresholds, and the footer note reads "CEFR levels are estimates…". |

### How to use

**Per-exercise CEFR scoring**

1. Edit the exercise in WordPress admin.
2. In **Exercise Settings**, set **Scoring Type** → **CEFR Level (A1–C2)**.
3. Save. Students will now see their CEFR level in the result panel after submitting.

**Band scores table in CEFR mode**

Add `display_type="cefr"` to the shortcode on any page:

```
[ielts_band_scores display_type="cefr" title="Your CEFR Level"]
```

See `VISUAL_GUIDE_CEFR_PWYC.md` for rendered examples.

---

## Feature 2 — Free Trial Re-enabled

The default value of `ielts_cm_free_trial_enabled` has been changed from `false` to `true`
in both `class-membership.php` and `class-shortcodes.php`. Sites that previously set this
option explicitly are unaffected; the change only matters for fresh installations or sites
where the option has never been saved.

---

## Feature 3 — Pay What You Can (primary site only)

### Overview

Trial members on the **primary site** can now choose their own price when upgrading to a
paid full membership. A minimum is enforced both client-side and server-side.

### Changes

| File | Change |
|---|---|
| `includes/class-membership.php` | Two new admin settings (`ielts_cm_pwyw_enabled`, `ielts_cm_pwyw_minimum`) registered and rendered inside **Membership Settings**, visible only on the primary site. `is_pwyw_active()` helper added. |
| `includes/class-shortcodes.php` | Registration form renders a `#ielts-pwyw-container` div with a numeric price input when PWYC is active; `pwywEnabled` and `pwywMinimum` are passed to `wp_localize_script`. |
| `assets/js/registration-payment.js` | New helpers `isPwywMembership()`, `getEffectivePrice()`, `togglePwywField()`. The PWYC field is shown/hidden on membership-type change; Stripe Elements re-initialises on amount change/blur; form submit validates the entered amount ≥ minimum before proceeding. |
| `includes/class-stripe-payment.php` | `create_payment_intent()` accepts an optional `pwyw_amount` POST field. When present: validates primary-site-only, validates PWYC enabled, validates amount ≥ `max(5.00, ielts_cm_pwyw_minimum)`, then uses the user-supplied amount as the charge amount. |

### How to enable

1. Log in to the **primary site** dashboard.
2. Go to **IELTS Course Manager → Membership Settings**.
3. Check **Pay What You Can (Full Membership)**.
4. Set the **Minimum PWYC Amount** (floor: $5.00 USD).
5. Click **Save Settings**.

Students selecting a paid full membership on the registration/upgrade form will then see a
price input field above the Stripe card element.

See `VISUAL_GUIDE_CEFR_PWYC.md` for a full rendered example of the form.

---

## Files Changed

- `assets/js/registration-payment.js`
- `ielts-course-manager.php` (version bump 16.17 → 16.18)
- `includes/admin/class-admin.php`
- `includes/class-membership.php`
- `includes/class-quiz-handler.php`
- `includes/class-shortcodes.php`
- `includes/class-stripe-payment.php`

## Security

- PWYC amount bypass is prevented server-side: the endpoint rejects PWYC requests on non-primary
  sites, when PWYC is disabled, and when the supplied amount is below the configured minimum.
- The PWYC code path in `create_payment_intent()` is explicitly separated from the standard
  exact-price-match check so neither path can be used to bypass the other.
