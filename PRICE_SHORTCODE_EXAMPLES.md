# IELTS Price Shortcode - Visual Examples

## ✅ Implementation Complete

The `[ielts_price]` shortcode has been successfully added to display membership prices on the frontend.

---

## 📋 Quick Start

### Basic Usage for Academic Module Full Course:
```
[ielts_price type="academic_full"]
```

**Output:** `$49.99`

---

## 🎨 Visual Examples

### Example 1: Simple Price Display
```
The Academic Module Full Course costs [ielts_price type="academic_full"].
```
**Displays:** The Academic Module Full Course costs **$49.99**.

---

### Example 2: Pricing Page with Multiple Options

```html
<div class="pricing-section">
    <h2>Academic Module</h2>
    <p class="price">[ielts_price type="academic_full"]</p>
    <a href="/register">Sign Up</a>
</div>

<div class="pricing-section">
    <h2>General Training</h2>
    <p class="price">[ielts_price type="general_full"]</p>
    <a href="/register">Sign Up</a>
</div>

<div class="pricing-section">
    <h2>English Only</h2>
    <p class="price">[ielts_price type="english_full"]</p>
    <a href="/register">Sign Up</a>
</div>
```

**Displays:**
- Academic Module: **$49.99**
- General Training: **$39.99**  
- English Only: **$29.99**

---

### Example 3: Pricing Table

```html
<table>
    <tr>
        <td>Academic Module Full:</td>
        <td>[ielts_price type="academic_full"]</td>
    </tr>
    <tr>
        <td>General Training Full:</td>
        <td>[ielts_price type="general_full"]</td>
    </tr>
    <tr>
        <td>English Only Full:</td>
        <td>[ielts_price type="english_full"]</td>
    </tr>
</table>
```

---

### Example 4: Custom Formatting

**Without Dollar Sign:**
```
[ielts_price type="academic_full" format="%.2f USD"]
```
**Output:** `49.99 USD`

**With GBP Symbol:**
```
[ielts_price type="academic_full" format="£%.2f"]
```
**Output:** `£49.99`

**Rounded (No Decimals):**
```
[ielts_price type="academic_full" format="$%.0f"]
```
**Output:** `$50`

---

### Example 5: With Custom CSS Class

```
<span class="special-price">[ielts_price type="academic_full" class="highlight"]</span>
```

This allows you to apply custom styling via CSS to make the price stand out.

---

## 🔧 All Membership Types

| Type Code | Description | Example Usage |
|-----------|-------------|---------------|
| `academic_full` | Academic Module Full Membership | `[ielts_price type="academic_full"]` |
| `general_full` | General Training Full Membership | `[ielts_price type="general_full"]` |
| `english_full` | English Only Full Membership | `[ielts_price type="english_full"]` |
| `academic_trial` | Academic Module - Free Trial | `[ielts_price type="academic_trial"]` → $0.00 |
| `general_trial` | General Training - Free Trial | `[ielts_price type="general_trial"]` → $0.00 |
| `english_trial` | English Only - Free Trial | `[ielts_price type="english_trial"]` → $0.00 |

---

## 📝 Shortcode Attributes

| Attribute | Description | Default | Example |
|-----------|-------------|---------|---------|
| `type` | Membership type to display | `academic_full` | `type="general_full"` |
| `format` | Price format string | `$%.2f` | `format="%.2f USD"` |
| `class` | CSS class for styling | _(empty)_ | `class="price-large"` |

---

## ⚙️ Admin Configuration

Prices are configured in the WordPress admin panel:

**Navigation:** Courses → Membership Settings → Payment Settings

There you can set the price for each membership type. The shortcode automatically displays the configured price.

---

## ✅ Testing Results

All 7 automated tests passed:

1. ✓ Default usage displays correct price
2. ✓ Different membership types work correctly  
3. ✓ Custom formatting works
4. ✓ CSS classes apply properly
5. ✓ Trial memberships show $0.00
6. ✓ Invalid types fall back to academic_full
7. ✓ Integer formatting works

---

## 📦 Files Modified

1. **includes/class-shortcodes.php** - Added shortcode registration and `display_price()` method
2. **PRICE_SHORTCODE_DOCUMENTATION.md** - Complete usage documentation

---

## 🚀 Ready to Use

The shortcode is now available for use on any WordPress page, post, or widget area. Simply add it to your content and it will display the configured price.

**Most Common Use Case:**
```
[ielts_price type="academic_full"]
```

This displays the price for the Academic Module Full Course as requested.
