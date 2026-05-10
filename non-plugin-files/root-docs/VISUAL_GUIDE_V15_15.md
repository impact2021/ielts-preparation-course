# Visual Guide - Tours Admin Page (Version 15.15)

This guide shows what the new Tours admin page looks like and how to use it.

## Navigation Path

**WordPress Admin > IELTS Courses > Tours**

The "Tours" link appears in the IELTS Courses admin sidebar menu, between the existing menu items.

## Admin Sidebar Menu Structure

```
IELTS Courses
├── All Courses
├── Add New Course
├── Lessons
├── Lesson Pages
├── Sub Lesson Pages
├── Quizzes
├── Documentation
├── Settings
├── Awards
├── Payment Errors
├── Multi-Site Sync
├── Sync Status
└── Tours  ← NEW
```

## Tours Admin Page Layout

### Page Title
**"User Tours"**

### Section 1: Tour Settings (Card Layout)

**Heading:** "Tour Settings"

**Fields:**

1. **Enable Tours** (Checkbox)
   - Label: "Show guided tours to first-time users"
   - Description: "When enabled, users who haven't completed the tour will see it automatically."
   - Default: Checked

2. **Enable for Memberships** (Multiple Checkboxes)
   - Section heading: "Enable for Memberships"
   - Options (8 checkboxes):
     - [ ] Academic Module - Free Trial
     - [ ] General Training - Free Trial
     - [ ] IELTS Core (Academic Module)
     - [ ] IELTS Core (General Training Module)
     - [ ] IELTS Plus (Academic Module)
     - [ ] IELTS Plus (General Training Module)
     - [ ] English Only - Free Trial
     - [ ] English Only Full Membership
   - Description: "Select which membership types should see the tour. If no memberships are selected, the tour will be shown to all users when enabled."

3. **Save Button**
   - Text: "Save Settings"
   - Style: Primary button (blue)

### Section 2: Testing & Management (Card Layout)

**Heading:** "Testing & Management"

**Fields:**

1. **Test Tour**
   - Label: "Test Tour"
   - Button: "Run Tour as Admin"
   - Description: "Reset the tour completion flag for your account so you can test the tour. After clicking, refresh any page to see the tour."

2. **Reset All Tours**
   - Label: "Reset All Tours"
   - Button: "Reset for All Users" (secondary/gray button)
   - Confirmation: JavaScript confirm dialog
   - Description: "Reset tour completion for all users. Use this if you've updated the tour and want all users to see it again."

### Section 3: Tour Information (Card Layout)

**Heading:** "Tour Information"

**Content:**

Paragraph: "The user tour is a guided walkthrough that helps new users understand the platform. It highlights key features and functionality."

**Subheading:** "How it works:"

Bulleted list:
- First-time users automatically see the tour when they log in
- Users can skip the tour at any time
- Once completed or skipped, the tour won't show again unless reset
- Tours can be enabled/disabled globally or for specific membership types

**Link Button:**
- Text: "View Tour Documentation"
- Links to: IELTS Courses > Documentation (Tours tab)

## Documentation Page - Tours Tab

**Navigation:** IELTS Courses > Documentation > User Tours (tab)

### Tab Bar
```
| Getting Started | Creating Content | Question Types | Shortcodes | Enrollment & Progress | User Tours |
                                                                                              ^^^^^^^^^
                                                                                              NEW TAB
```

### Tours Tab Content Structure

1. **Main Heading:** "User Tours"

2. **Section: What is a User Tour?**
   - Explanation of user tours
   - Interactive guided experience description

3. **Section: Managing Tours**
   - Link to Tours admin page
   - List of capabilities:
     - Enable/disable globally
     - Enable for specific memberships
     - Test as administrator
     - Reset for all users

4. **Section: How to Modify the Tour** (Most Important)
   - 5-step process:
     1. Locate the tour file (assets/js/user-tour.js)
     2. Edit tour steps
     3. Example code with syntax highlighting
     4. Finding CSS selectors (with sub-steps)
     5. Testing changes
   
   - Code example in gray box:
     ```javascript
     tour.addStep({
         id: 'my-custom-step',
         text: '<h3>Step Title</h3><p>Step description...</p>',
         attachTo: {
             element: '.css-selector',
             on: 'bottom'
         },
         buttons: [
             { text: 'Back', action: tour.back },
             { text: 'Next', action: tour.next }
         ]
     });
     ```

5. **Section: Common Tour Modifications**
   - Change step text (with before/after example)
   - Remove a tour step
   - Change order of steps
   - Customize styling

6. **Section: Best Practices**
   - Bulleted list of 6 best practices
   - Keep tours short
   - Focus on essentials
   - Clear language
   - Always provide skip
   - Test on different sizes
   - Update when UI changes

7. **Section: Troubleshooting**
   - Three common issues with solutions:
     - Tour doesn't appear
     - Tour shows every time
     - Tour highlights wrong element

8. **Section: Additional Resources**
   - Link to Shepherd.js documentation
   - Link to Tours admin page

## Visual Styling

### Cards
- White background
- Padding: 20px
- Subtle border
- Max-width: 800px
- Margin between cards: 20px

### Forms
- Standard WordPress admin form styling
- Table layout for form fields
- Descriptions in gray text below fields

### Buttons
- Primary button: Blue background, white text
- Secondary button: Gray background, white text
- Hover effects: Darker shade

### Tab Navigation
- Horizontal tabs
- Active tab: White background, no bottom border
- Inactive tabs: Light gray background
- Border below tab bar

## Success/Error Messages

When forms are submitted, WordPress admin notices appear at the top:

**Success messages (green background):**
- "Tour settings updated successfully"
- "Tour reset for your account. Refresh any page to see the tour."
- "Tours reset for all users. All users will see the tour on their next page load."

**Error messages (red background):**
- "Security check failed"
- Any validation errors

## Responsive Design

The page uses WordPress admin styles which are responsive:
- Cards stack vertically on smaller screens
- Form fields adjust to available width
- Tab navigation may scroll horizontally on very small screens

## Accessibility

- All form fields have proper labels
- Buttons have descriptive text
- Links have clear purposes
- Keyboard navigation works throughout
- Screen reader friendly

## Database Impact

**Options Table:**
- `ielts_cm_tours_enabled` (boolean)
- `ielts_cm_tours_enabled_memberships` (serialized array)

**User Meta Table:**
- `ielts_tour_completed` (boolean, per user)

## Use Cases

### Use Case 1: Enable Tours for Trial Users Only
1. Go to Tours admin page
2. Check "Enable Tours"
3. Check only:
   - Academic Module - Free Trial
   - General Training - Free Trial
   - English Only - Free Trial
4. Click "Save Settings"
5. Now only trial users will see the tour

### Use Case 2: Test the Tour Before Launching
1. Make changes to assets/js/user-tour.js
2. Go to Tours admin page
3. Click "Run Tour as Admin"
4. Open any front-end page
5. See the tour and verify changes
6. Repeat as needed

### Use Case 3: Relaunch Tour After Major Updates
1. Update tour content in assets/js/user-tour.js
2. Go to Tours admin page
3. Click "Reset for All Users"
4. Confirm the action
5. All users will see the updated tour

## Security

- CSRF protection via WordPress nonces
- Capability checking (manage_options required)
- Input sanitization on all form fields
- SQL injection protection via wpdb methods
- XSS protection via WordPress escaping functions
