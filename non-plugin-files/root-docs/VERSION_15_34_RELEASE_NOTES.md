# Version 15.34 Release Notes

## Membership Tier Simplification

### Changes Made

This version simplifies the membership structure by:

1. **Removing Plus Membership Tiers**
   - Removed `academic_plus` (IELTS Plus - Academic Module)
   - Removed `general_plus` (IELTS Plus - General Module)

2. **Renaming Core Memberships**
   - Changed `academic_full` from "IELTS Core (Academic Module)" to **"Academic Module IELTS"**
   - Changed `general_full` from "IELTS Core (General Training Module)" to **"General Module IELTS"**

### Current Membership Structure

After these changes, the membership levels are:

#### Trial Memberships (Free)
- `academic_trial` - Academic Module - Free Trial
- `general_trial` - General Training - Free Trial
- `english_trial` - English Only - Free Trial

#### Paid Memberships
- `academic_full` - **Academic Module IELTS** (30 days)
- `general_full` - **General Module IELTS** (30 days)
- `english_full` - English Only Full Membership (30 days)

### Files Modified

1. **includes/class-membership.php**
   - Updated `MEMBERSHIP_LEVELS` constant to remove Plus tiers and rename Core tiers
   - Updated `MEMBERSHIP_BENEFITS` constant to remove Plus tier benefits
   - Removed Plus tier default durations from `$default_durations` array

2. **includes/admin/class-tours-page.php**
   - Updated membership level arrays to remove Plus tiers and use new names
   - Ensures user tours are properly configured for current membership levels

3. **includes/class-access-codes.php**
   - Updated documentation to reflect current WordPress roles (removed Plus tier references)

4. **includes/class-shortcodes.php**
   - Removed special handling for Plus tier descriptions in registration forms
   - Simplified membership option display logic

5. **ielts-course-manager.php**
   - Updated version from 15.33 to 15.34

### Impact on Existing Data

**No data migration required.** The changes are:

- **Backward Compatible:** Existing users with `academic_full` or `general_full` memberships are unaffected
- **Display Only:** Only the display names have changed; database keys remain the same
- **Removal of Future Options:** Plus tiers are removed from new registrations but don't affect any existing data

### Migration Notes

#### For Administrators
- Plus tier pricing settings will still appear in the admin but won't be used
- Any existing users with Plus memberships (if any) will need manual intervention
- The system will continue to recognize old membership keys for backward compatibility

#### For Users
- No changes to existing memberships
- New registrations will see simplified options
- Clearer, more concise membership names

### User-Facing Changes

#### Before
Registration form showed:
- IELTS Core (Academic Module)
- IELTS Plus (Academic Module) - Includes 2 x 30-minute live speaking assessments
- IELTS Core (General Training Module)
- IELTS Plus (General Training Module) - Includes 2 x 30-minute live speaking assessments

#### After
Registration form shows:
- Academic Module IELTS
- General Module IELTS

### Technical Details

#### Constants Updated
```php
// Before
const MEMBERSHIP_LEVELS = array(
    'academic_full' => 'IELTS Core (Academic Module)',
    'general_full' => 'IELTS Core (General Training Module)',
    'academic_plus' => 'IELTS Plus (Academic Module)',
    'general_plus' => 'IELTS Plus (General Training Module)',
    // ...
);

// After
const MEMBERSHIP_LEVELS = array(
    'academic_full' => 'Academic Module IELTS',
    'general_full' => 'General Module IELTS',
    // Plus tiers removed
    // ...
);
```

#### Arrays Cleaned
Removed Plus tier entries from:
- `MEMBERSHIP_BENEFITS` constant
- Default durations configuration
- Tour page membership arrays
- Access code documentation

### Testing Recommendations

1. **Admin Interface**
   - Verify membership settings page displays correctly
   - Check that pricing settings still work
   - Ensure duration settings are properly configured

2. **Registration Forms**
   - Test registration shortcode `[ielts_registration]`
   - Verify membership options display correctly
   - Confirm no Plus tier options appear

3. **User Tours**
   - Check user tour settings page
   - Verify tours are enabled for correct membership levels

4. **Existing Memberships**
   - Verify existing users with `academic_full` see "Academic Module IELTS"
   - Verify existing users with `general_full` see "General Module IELTS"
   - Confirm all existing user data is intact

### Rollback Information

If rollback is needed:
1. Revert changes to the 5 files listed above
2. No database changes are required
3. System will immediately recognize Plus tiers again

### Related Documentation

This change supersedes:
- `CORE_AND_PLUS_TIERS.md` - Now outdated, Plus tiers removed
- Previous membership documentation mentioning Core/Plus distinction

### Future Considerations

With Plus tiers removed:
- Simpler pricing structure for users
- Easier to understand membership options
- Reduced maintenance overhead
- Can be re-added in future if needed by restoring removed code

## Summary

Version 15.34 simplifies the membership structure by removing the Plus tier and giving the main paid memberships clearer, more concise names. This change improves user experience and simplifies the codebase without affecting existing user data.
