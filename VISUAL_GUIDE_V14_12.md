# Visual Guide - Version 14.12 Features

## Login Stats Shortcode Display

### Desktop View
```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐ │
│  │   🕒             │  │   📊             │  │   ⏱️              │ │
│  │                  │  │                  │  │                  │ │
│  │   LAST LOGIN     │  │   TOTAL LOGINS   │  │   TIME LOGGED IN │ │
│  │   2 hours ago    │  │   15             │  │   3 hours 24 min │ │
│  │                  │  │                  │  │                  │ │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘ │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Mobile View
```
┌────────────────────┐
│                    │
│  ┌──────────────┐  │
│  │   🕒         │  │
│  │              │  │
│  │  LAST LOGIN  │  │
│  │  2 hours ago │  │
│  │              │  │
│  └──────────────┘  │
│                    │
│  ┌──────────────┐  │
│  │   📊         │  │
│  │              │  │
│  │ TOTAL LOGINS │  │
│  │      15      │  │
│  │              │  │
│  └──────────────┘  │
│                    │
│  ┌──────────────┐  │
│  │   ⏱️          │  │
│  │              │  │
│  │ TIME LOGGED  │  │
│  │ 3 hours 24 m │  │
│  │              │  │
│  └──────────────┘  │
│                    │
└────────────────────┘
```

## Create Account Button Animation

### State 1: Normal (Before Click)
```
┌─────────────────────────────┐
│                             │
│      Create Account         │
│                             │
└─────────────────────────────┘
   Blue button, white text
```

### State 2: Loading Animation
```
┌─────────────────────────────┐
│                             │
│            .                │  ← Frame 1 (0.0s)
│                             │
└─────────────────────────────┘

┌─────────────────────────────┐
│                             │
│            ..               │  ← Frame 2 (0.5s)
│                             │
└─────────────────────────────┘

┌─────────────────────────────┐
│                             │
│            ...              │  ← Frame 3 (1.0s)
│                             │
└─────────────────────────────┘

┌─────────────────────────────┐
│                             │
│            .                │  ← Frame 4 (1.5s) - repeats
│                             │
└─────────────────────────────┘

   Blue button, button is disabled,
   original text is transparent,
   ellipsis animates continuously
```

## Registration Form with Animation

### Before Submission
```
┌──────────────────────────────────────┐
│  Create Your Account                 │
│                                      │
│  First Name: [John        ]          │
│  Last Name:  [Smith       ]          │
│  Email:      [john@email.com]        │
│  Password:   [••••••••    ]          │
│  Confirm:    [••••••••    ]          │
│  Membership: [Academic Trial ▼]      │
│                                      │
│  ┌────────────────────────────────┐  │
│  │     Create Account             │  │
│  └────────────────────────────────┘  │
│       ↑ Ready to click                │
└──────────────────────────────────────┘
```

### After Click (Loading)
```
┌──────────────────────────────────────┐
│  Create Your Account                 │
│                                      │
│  First Name: [John        ]          │
│  Last Name:  [Smith       ]          │
│  Email:      [john@email.com]        │
│  Password:   [••••••••    ]          │
│  Confirm:    [••••••••    ]          │
│  Membership: [Academic Trial ▼]      │
│                                      │
│  ┌────────────────────────────────┐  │
│  │            ...                 │  │
│  └────────────────────────────────┘  │
│       ↑ Button disabled, animating   │
│         User cannot click again      │
└──────────────────────────────────────┘
```

## Login Stats - Different States

### New User (No Data Yet)
```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│   🕒             │  │   📊             │  │   ⏱️              │
│                  │  │                  │  │                  │
│   LAST LOGIN     │  │   TOTAL LOGINS   │  │   TIME LOGGED IN │
│   Never          │  │   0              │  │   0 minutes      │
│                  │  │                  │  │                  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

### Active User
```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│   🕒             │  │   📊             │  │   ⏱️              │
│                  │  │                  │  │                  │
│   LAST LOGIN     │  │   TOTAL LOGINS   │  │   TIME LOGGED IN │
│   5 minutes ago  │  │   47             │  │   12 hours 18 m  │
│                  │  │                  │  │                  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

### Long-Time User
```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│   🕒             │  │   📊             │  │   ⏱️              │
│                  │  │                  │  │                  │
│   LAST LOGIN     │  │   TOTAL LOGINS   │  │   TIME LOGGED IN │
│   Just now       │  │   234            │  │   15 days 7 hrs  │
│                  │  │                  │  │                  │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

## Hover Effects

### Normal State
```
┌──────────────────┐
│   🕒             │  Border: Light gray (#e5e7eb)
│                  │  Shadow: Subtle
│   LAST LOGIN     │
│   2 hours ago    │
│                  │
└──────────────────┘
```

### Hover State
```
┌──────────────────┐
│   🕒             │  Border: Blue (#3b82f6)
│                  │  Shadow: Stronger, blue tint
│   LAST LOGIN     │  Effect: Smooth transition
│   2 hours ago    │
│                  │
└──────────────────┘
```

## Shortcode Usage Examples

### Homepage Welcome
```html
<h2>Welcome Back!</h2>
<p>Here's your activity at a glance:</p>
[ielts_login_stats]
```

### Dashboard
```html
<h1>My Dashboard</h1>
<h3>Your Statistics</h3>
[ielts_login_stats]
<h3>Your Courses</h3>
[ielts_courses]
```

### Customized Display
```html
<!-- Show only login count -->
[ielts_login_stats show_last_login="no" show_total_time="no"]

<!-- Show only time logged in -->
[ielts_login_stats show_last_login="no" show_login_count="no"]
```

## Color Scheme

### Default Colors
- **Card Background**: White (#fff)
- **Card Border**: Light Gray (#e5e7eb)
- **Hover Border**: Blue (#3b82f6)
- **Label Text**: Medium Gray (#6b7280)
- **Value Text**: Dark Gray (#1f2937)
- **Icon Size**: 32px
- **Animation Color**: White (#fff)

### Responsive Breakpoints
- **Desktop**: Cards in a row (min-width: 768px)
- **Mobile**: Cards stacked vertically (< 768px)
- **Gap**: 20px between cards
- **Padding**: 20px inside each card

## Implementation Code Snippets

### Add to Any Page
```
Simply paste: [ielts_login_stats]
```

### Custom CSS Override
```css
/* In your theme's Custom CSS */
.ielts-stat-item {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.ielts-stat-label {
    color: rgba(255, 255, 255, 0.8);
}
.ielts-stat-value {
    color: white;
}
```

### Change Animation Color
```css
:root {
    --ielts-button-loading-color: #10b981; /* Green */
}
```

## Technical Flow

### Login Tracking Flow
```
User Logs In
     ↓
WordPress fires 'wp_login' hook
     ↓
track_user_login() method called
     ↓
Updates:
  - _ielts_cm_last_login = current timestamp
  - _ielts_cm_login_count = count + 1
  - _ielts_cm_session_start = current timestamp
```

### Session Time Tracking Flow
```
User Loads Page
     ↓
WordPress fires 'wp_footer' hook
     ↓
track_session_time() method called
     ↓
Calculate: current_time - session_start
     ↓
If < 1 hour (active session):
  - Add elapsed time to _ielts_cm_total_time_logged_in
  - Reset _ielts_cm_session_start to current_time
     ↓
If ≥ 1 hour (inactive):
  - Don't count time
  - Reset _ielts_cm_session_start to current_time
```

### Button Animation Flow
```
User Clicks "Create Account"
     ↓
Form submit event triggered
     ↓
JavaScript checks if free/paid membership
     ↓
For Free Trial:
  - Add 'loading' class to button
  - CSS animation starts
  - Form submits normally
     ↓
For Paid Membership:
  - registration-payment.js handles it
  - setLoading(true) adds 'loading' class
  - Payment processing starts
  - CSS animation shows while processing
```
