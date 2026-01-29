# IELTS Price Shortcode Documentation

## Overview
The `[ielts_price]` shortcode displays the price for IELTS Course Manager membership types on the frontend of your WordPress site.

## Basic Usage

### Display Academic Module Full Course Price
```
[ielts_price type="academic_full"]
```

This will display the price in the default format: `$10.00` (or whatever price is configured in the admin settings).

## Shortcode Attributes

### `type` (required)
The membership type to display the price for. Available options:

**Core Memberships (30 days):**
- `academic_full` - IELTS Core (Academic Module) (default)
- `general_full` - IELTS Core (General Training Module)

**Plus Memberships (90 days + 2 live speaking assessments):**
- `academic_plus` - IELTS Plus (Academic Module)
- `general_plus` - IELTS Plus (General Training Module)

**Other Memberships:**
- `english_full` - English Only Full Membership
- `academic_trial` - Academic Module - Free Trial
- `general_trial` - General Training - Free Trial
- `english_trial` - English Only - Free Trial

**Example:**
```
[ielts_price type="academic_plus"]
```

### `format` (optional)
Custom price format using sprintf syntax. Default: `$%.2f`

**Examples:**
```
[ielts_price type="academic_full" format="$%.2f"]  <!-- Displays: $10.00 -->
[ielts_price type="academic_full" format="%.2f USD"]  <!-- Displays: 10.00 USD -->
[ielts_price type="academic_full" format="£%.2f"]  <!-- Displays: £10.00 -->
[ielts_price type="academic_full" format="%.0f"]  <!-- Displays: 10 -->
```

### `class` (optional)
Add a custom CSS class to the output span element for styling.

**Example:**
```
[ielts_price type="academic_full" class="price-highlight"]
```

This will output:
```html
<span class="price-highlight">$10.00</span>
```

## Complete Examples

### Example 1: Simple Academic Price Display
```
The Academic Module Full Course costs [ielts_price type="academic_full"].
```

Output: "The Academic Module Full Course costs $10.00."

### Example 2: Custom Formatting
```
<div class="pricing-box">
    <h3>Academic Module</h3>
    <p class="price">[ielts_price type="academic_full" format="$%.0f" class="big-price"]</p>
    <a href="/register">Sign Up Now</a>
</div>
```

### Example 3: Multiple Prices
```
<table>
    <tr>
        <td>IELTS Core (Academic):</td>
        <td>[ielts_price type="academic_full"]</td>
        <td>30 days access</td>
    </tr>
    <tr>
        <td>IELTS Plus (Academic):</td>
        <td>[ielts_price type="academic_plus"]</td>
        <td>90 days + 2 live speaking assessments</td>
    </tr>
    <tr>
        <td>IELTS Core (General Training):</td>
        <td>[ielts_price type="general_full"]</td>
        <td>30 days access</td>
    </tr>
    <tr>
        <td>IELTS Plus (General Training):</td>
        <td>[ielts_price type="general_plus"]</td>
        <td>90 days + 2 live speaking assessments</td>
    </tr>
</table>
```

## Setting Prices

Prices are configured in the WordPress admin:
1. Go to **Courses > Membership Settings > Payment Settings**
2. Set the price for each membership type
3. Click **Save Changes**

The shortcode will automatically display the configured price.

## Notes

- Prices are stored in USD in the database
- The default format displays prices with 2 decimal places (e.g., $10.00)
- If a membership type has no configured price, it will display $0.00
- Trial memberships typically have a price of $0.00
- The shortcode is safe to use with caching plugins as it outputs static content based on admin settings

## Troubleshooting

**Shortcode displays $0.00:**
- Check that you've configured the price in **Courses > Membership Settings > Payment Settings**
- Verify you're using the correct membership type name

**Invalid membership type:**
- The shortcode will default to `academic_full` if an invalid type is provided
- Valid types are listed above in the `type` attribute section

## Support

For additional help or to report issues, please contact the plugin developer.
