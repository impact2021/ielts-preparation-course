# IELTS Membership System

A complete membership and payment system for IELTS preparation courses with PayPal and Stripe integration.

## Features

- **Membership Management**
  - 90-day membership ($24.95 USD)
  - Membership extensions (1 week - $5, 1 month - $10, 3 months - $20)
  - Automatic expiration tracking
  - Full access to all IELTS courses when active

- **Payment Integration**
  - PayPal Standard integration
  - Stripe Checkout integration
  - Secure payment processing
  - Payment history tracking

- **Custom Login System**
  - Custom login/registration pages
  - Forgot password functionality
  - Replaces WordPress default login
  - Link to legacy course for pre-2026 enrollees

- **Account Management**
  - View membership status and expiration
  - Change email address
  - Change password
  - View payment history
  - Purchase/extend membership

## Installation

1. Upload the `ielts-membership-system` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Membership > Settings to configure payment gateways
4. The plugin will automatically create required pages:
   - Membership Login (`/membership-login/`)
   - Membership Registration (`/membership-register/`)
   - My Account (`/my-account/`)

## Configuration

### PayPal Setup

1. Go to **Membership > Settings**
2. Under PayPal Settings:
   - Enable PayPal
   - Enter your PayPal business email
   - Enable sandbox mode for testing (optional)
3. Save settings

### Stripe Setup

1. Go to **Membership > Settings**
2. Under Stripe Settings:
   - Enable Stripe
   - Enter your Stripe Publishable Key
   - Enter your Stripe Secret Key
   - (Optional) Configure webhook for advanced features
3. Save settings

**Stripe Webhook URL:** `https://yoursite.com/wp-admin/admin-ajax.php?action=ielts_ms_stripe_webhook`

### Login Settings

- Enable/disable custom login page replacement
- Default: WordPress login redirects to custom login page

## Usage

### Shortcodes

- `[ielts_membership_login]` - Display login form
- `[ielts_membership_register]` - Display registration form
- `[ielts_membership_account]` - Display account page

### For Users

1. **Register:** Visit the registration page to create an account
2. **Login:** Use the login page to access your account
3. **Purchase:** From the account page, select a membership plan
4. **Manage:** Change email/password, view membership status
5. **Extend:** Current or expired members can extend their membership

### For Administrators

- **Members:** View all members and their status
- **Payments:** Track all payment transactions
- **Settings:** Configure payment gateways and system options

## Integration with IELTS Course Manager

This plugin integrates with the IELTS Course Manager plugin to control access:

- Active members automatically get the "subscriber" role
- Subscribers have access to all IELTS courses
- Access is checked via the `ielts_cm_has_course_access` filter

## Pricing Plans

### New Membership
- **90 Days:** $24.95 USD
- Full access to all content

### Extensions (for existing/expired members)
- **1 Week:** $5.00 USD
- **1 Month:** $10.00 USD (Recommended)
- **3 Months:** $20.00 USD (Best Value)

## Database Tables

The plugin creates two custom tables:

1. `wp_ielts_ms_memberships` - Stores membership records
2. `wp_ielts_ms_payments` - Stores payment transactions

## Legacy Users

Users enrolled before January 1, 2026 can access the old course version via a link on the login page that redirects to `https://www.ieltstestonline.com/older-version/`

## Support

For support or questions, contact the plugin maintainer at IELTStestONLINE.

## Changelog

### Version 1.0.0
- Initial release
- PayPal and Stripe integration
- Custom login/registration system
- Account management
- Membership system with extensions
- Admin dashboard for members and payments

## License

GPL v2 or later
