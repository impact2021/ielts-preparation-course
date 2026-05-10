# Version 15.15 Release Notes

## New Features: User Tours Admin Controls

This release adds comprehensive admin controls for managing user tours.

### What's New

#### 1. New Tours Admin Page
- **Location**: IELTS Courses > Tours
- **Purpose**: Central hub for managing user tour functionality

#### 2. Tour Control Features

**Global Settings:**
- Enable/disable tours for all users with a single checkbox
- Granular control over which membership types see tours
  - Academic Trial
  - General Training Trial
  - Academic Full (IELTS Core)
  - General Training Full (IELTS Core)
  - Academic Plus (IELTS Plus)
  - General Training Plus (IELTS Plus)
  - English Trial
  - English Full

**Testing & Management:**
- "Run Tour as Admin" - Test the tour at any time without affecting user data
- "Reset for All Users" - Make all users see the tour again (useful after tour updates)

#### 3. New Documentation Tab
- **Location**: IELTS Courses > Documentation > User Tours tab
- **Content**: Comprehensive guide on how to modify and customize tours
- Includes:
  - What user tours are
  - How to manage tours via the admin page
  - Step-by-step guide to modifying tour content
  - How to add/remove/reorder tour steps
  - Finding CSS selectors for highlighting elements
  - Best practices for effective tours
  - Troubleshooting common issues
  - Links to external resources (Shepherd.js documentation)

### Technical Details

**New Files:**
- `includes/admin/class-tours-page.php` - Tours admin page class

**Modified Files:**
- `ielts-course-manager.php` - Added tours page include and version bump
- `includes/class-ielts-course-manager.php` - Initialized tours page
- `includes/admin/class-admin.php` - Added tours tab to documentation

**Database Options:**
- `ielts_cm_tours_enabled` - Global enable/disable flag (boolean)
- `ielts_cm_tours_enabled_memberships` - Array of enabled membership types

**User Meta:**
- `ielts_tour_completed` - Tracks if user has completed/skipped the tour

### How to Use

#### For Admins:

1. **Enable/Disable Tours:**
   - Navigate to IELTS Courses > Tours
   - Check/uncheck "Enable Tours"
   - Select which membership types should see tours
   - Click "Save Settings"

2. **Test the Tour:**
   - Navigate to IELTS Courses > Tours
   - Click "Run Tour as Admin"
   - Refresh any page to see the tour

3. **Reset Tours After Updates:**
   - Navigate to IELTS Courses > Tours
   - Click "Reset for All Users" (confirm the action)
   - All users will see the tour on their next page load

#### For Developers:

1. **Modifying Tour Content:**
   - Edit `assets/js/user-tour.js`
   - Add/remove/modify tour steps
   - Update step text, selectors, or button actions
   - See the documentation tab for detailed examples

2. **Customizing Tour Styles:**
   - Edit `assets/css/user-tour.css`
   - Modify colors, fonts, spacing to match branding

### Benefits

- **Improved Onboarding**: New users get guided walkthroughs of key features
- **Flexible Control**: Admins can enable tours for specific user segments
- **Easy Testing**: Admins can test tours without affecting user data
- **Developer Friendly**: Clear documentation on how to customize tours
- **Membership Specific**: Different membership types can have different tour experiences

### Version Information

- **Version**: 15.15
- **Previous Version**: 15.14
- **Release Date**: 2026-02-06

### Upgrade Notes

This is a minor update that adds new functionality. No database migrations or breaking changes.

### Future Enhancements

Potential future improvements:
- Multiple tours (e.g., separate tours for different features)
- Tour analytics (completion rates, drop-off points)
- A/B testing for tour effectiveness
- Tour scheduling (show tours based on user tenure)
