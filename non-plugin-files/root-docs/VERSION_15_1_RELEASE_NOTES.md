# Version 15.1 Release Notes

**Release Date:** January 27, 2026

## Overview
This release focuses on UI improvements, payment options, bug fixes, accessibility enhancements, and file organization to improve the user registration experience and fix issues with the daily streak counter.

## New Features

### PayPal Payment Option
- Added PayPal as an alternative payment method alongside Stripe
- Payment method selector with visual toggle between Credit/Debit Card and PayPal
- Prepared UI for PayPal integration (backend integration to be completed separately)
- Enhanced accessibility with ARIA attributes (aria-pressed, aria-hidden)
- Added proper null checks to prevent JavaScript errors
- Note: Full PayPal integration requires additional backend configuration

## Improvements

### Registration Form Spacing Optimization
- Reduced empty space throughout the registration form to fit above the fold
- Reduced grid gap from 30px to 20px on desktop layouts
- Reduced field spacing from 15px to 10px between form fields
- Reduced form field margin-bottom from 12px to 8px
- Reduced label margin-bottom from 6px to 4px
- Reduced container padding from 30px to 20px
- Reduced form margin-top from 20px to 15px
- Overall more compact and efficient use of screen space

### Band Scores Table Styling
- Removed gradient backgrounds from the `[ielts_band_scores]` shortcode table
- Updated header row to use solid color background (#E46B0A) with white text
- Simplified cell backgrounds (removed gradient from data cells)
- Cleaner, more professional appearance

### File Organization
- Removed 21 Academic Reading test JSON files from the General Training Reading Test folder
- Cleaned up misplaced test files (Academic-IELTS-Reading-Test-01.json through 21.json)
- General Training folder now only contains General Training tests (15 JSON + 15 TXT files)
- Better file organization for easier content management

## Bug Fixes

### Daily Streak Counter Fix
- Fixed streak calculation logic that was incorrectly counting consecutive days
- Improved algorithm to properly start from the most recent activity date
- Fixed edge case handling in date comparison
- Note: Streaks are calculated based on days with quiz/exercise activity (not just logins)
- Users must complete at least one exercise per day for it to count toward their streak

## Technical Changes

### Code Quality
- Added documentation to `get_streak_days()` method explaining streak calculation logic
- Simplified streak counting algorithm for better maintainability
- Improved code clarity by removing confusing conditional logic

### Accessibility Improvements
- Added ARIA pressed states to payment method toggle buttons
- Added ARIA hidden attributes to inactive payment containers
- Improved screen reader compatibility for payment method selection
- Added conditional checks to prevent errors when payment section is disabled

### CSS/Styling
- Added payment method selector styles with hover and active states
- Improved payment section layout with better visual hierarchy
- Added responsive payment method button grid

### JavaScript
- Added payment method switcher functionality with proper null checks
- Enhanced accessibility with ARIA attribute management
- Placeholder messaging for PayPal integration (coming soon)
- Maintained existing Stripe payment flow
- Conditional initialization to prevent errors when elements don't exist

## Known Limitations

1. **Streak Counter:** Currently only tracks days with quiz/exercise activity, not login-only days
2. **PayPal Integration:** UI is ready but backend integration needs to be completed
3. **Registration Form:** Further optimization may be needed for very small mobile screens

## Upgrade Notes

- No database changes required
- No breaking changes to existing functionality
- Version constant updated from 15.0 to 15.1
- Compatible with WordPress 5.8+ and PHP 7.2+
- 21 Academic Reading test files removed from General Training folder (moved to proper location)

## Files Modified

1. `ielts-course-manager.php` - Version number updates
2. `includes/class-shortcodes.php` - Registration form spacing, band scores styling, PayPal UI, accessibility improvements
3. `includes/class-gamification.php` - Streak counter bug fix

## Files Removed

- Removed 21 Academic Reading test JSON files from `main/General Training Reading Test JSONs/` folder
  - Academic-IELTS-Reading-Test-01.json through Academic-IELTS-Reading-Test-21.json

## Next Steps

For site administrators:
1. Test the registration form on various screen sizes to ensure it fits above the fold
2. Review the new band scores table styling
3. Monitor streak counter accuracy with user feedback
4. If PayPal integration is needed, contact development team for backend setup
5. Verify General Training Reading tests display correctly without Academic tests

## Support

For issues or questions about this release, please contact the development team or open an issue in the repository.
