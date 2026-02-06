# Tour Replay Shortcode Documentation

## Overview

The `[ielts_replay_tour]` shortcode allows users to replay the welcome tour at any time. This is useful for users who want to review the site features or skipped the tour initially.

## Features

1. **Replay Tour Button**: Creates a clickable button that restarts the welcome tour
2. **Customizable Text**: Button text can be customized via shortcode attributes
3. **Custom CSS Class**: Apply custom styling by specifying a CSS class
4. **Membership-Aware**: Automatically shows the correct tour based on user's membership type (Academic, General Training, or English Only)
5. **Extended Tour Steps**: Tour now includes additional sections:
   - Courses section (Unit 1 guidance)
   - Practice tests
   - General English (with importance message)
   - Vocabulary course

## Usage

### Basic Usage

```
[ielts_replay_tour]
```

This creates a button with default text: "Play the welcome tour again"

### Custom Button Text

```
[ielts_replay_tour text="Take the tour again"]
```

or

```
[ielts_replay_tour text="🎓 Show me around"]
```

### Custom CSS Class

```
[ielts_replay_tour class="my-custom-button-class"]
```

### Combined Custom Text and Class

```
[ielts_replay_tour text="Replay Welcome Tour" class="button button-primary"]
```

## Shortcode Attributes

| Attribute | Default Value | Description |
|-----------|---------------|-------------|
| `text` | "Play the welcome tour again" | The text displayed on the button |
| `class` | "ielts-replay-tour-button" | CSS class(es) applied to the button |

## Behavior

1. **Logged-in Users**: Button is visible and functional
2. **Logged-out Users**: Button is not displayed
3. **Tour Completion**: Replaying the tour does NOT mark it as completed again (users can replay unlimited times)
4. **Page Requirements**: Tour scripts are automatically loaded for all logged-in users with a valid membership type

## Extended Tour Steps

The tour now includes these additional steps after the Band Scores section:

### 1. Courses Section
- **Target**: Divi block with ID `#courses`
- **Message**: "After the tour, click Unit 1 below to start your course"
- **Purpose**: Guide users to their first lesson

### 2. Practice Tests Section
- **Target**: Divi block with ID `#practice-tests`
- **Message**: Information about full-length practice exams
- **Purpose**: Highlight testing resources

### 3. General English Section
- **Target**: Divi block with ID `#general-english`
- **Message**: "A good result in IELTS requires a good level of English, so don't ignore this section"
- **Purpose**: Emphasize the importance of English fundamentals

### 4. Vocabulary Course Section
- **Target**: Link containing "vocabulary-for-ielts" in `.ielts-course-item`
- **Message**: Information about vocabulary course importance
- **Purpose**: Promote vocabulary building

## Technical Details

### JavaScript Function

The shortcode button calls the global JavaScript function:

```javascript
ieltsStartTour(true)
```

The `true` parameter indicates this is a forced replay, which:
- Bypasses localStorage checks
- Bypasses user_meta completion checks
- Does NOT save tour completion when finished

### Browser Compatibility

Works with all modern browsers that support:
- ES6 JavaScript
- CSS3
- Shepherd.js library (v11.2.0)

## Styling the Button

The default class `ielts-replay-tour-button` can be styled in your theme's CSS:

```css
.ielts-replay-tour-button {
    background-color: #667eea;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
}

.ielts-replay-tour-button:hover {
    background-color: #5568d3;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
```

Or use your theme's existing button classes:

```
[ielts_replay_tour class="button button-primary" text="View Tour"]
```

## Example Implementations

### In a Sidebar Widget

Place this in a text widget in your sidebar:

```html
<div class="tour-replay-widget">
    <h4>Need Help?</h4>
    <p>Not sure where to start? Take our guided tour!</p>
    [ielts_replay_tour text="Start Tour" class="button button-secondary"]
</div>
```

### In Page Content

Add to any page or post:

```
Welcome back! If you'd like a refresher on how to use the site, you can [ielts_replay_tour text="replay the welcome tour"].
```

### In User Dashboard

Add to the user account/dashboard page:

```html
<div class="dashboard-help">
    [ielts_replay_tour text="🎓 Take the Welcome Tour" class="dashboard-tour-button"]
</div>
```

## Troubleshooting

### Button appears but tour doesn't start

**Cause**: Tour scripts are not loaded on the current page
**Solution**: Ensure the page is being viewed by a logged-in user with a valid membership type

### Button doesn't appear

**Possible causes**:
1. User is not logged in (expected behavior)
2. Shortcode is misspelled
3. Tours are disabled in admin settings

**Check**: Go to admin settings and verify tours are enabled for the user's membership type

### Tour shows wrong steps

**Cause**: User's membership type determines which tour variant they see
**Solution**: This is expected - Academic users see Academic tour, General Training users see General Training tour, etc.

## Related Documentation

- [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md)
- [USER_TOUR_QUICK_START.md](USER_TOUR_QUICK_START.md)
- [USER_TOUR_ADMIN_CONTROLS.md](USER_TOUR_ADMIN_CONTROLS.md)

## Support

For issues or questions about the tour replay shortcode, please check:
1. WordPress admin dashboard > Tours settings
2. Browser console for JavaScript errors
3. User's membership type is set correctly
