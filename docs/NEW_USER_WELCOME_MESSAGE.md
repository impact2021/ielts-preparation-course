# New User Welcome Message Implementation Guide

## Problem

The standard welcome message doesn't make sense for brand new students on their first login:

**Before (for new users):**
```
Hi John,

Welcome back! The last lesson you were studying was your first lesson.
Your current predicted band score is N/A based on your scores and performance so far.
```

This is confusing because:
- "Welcome back!" doesn't make sense for first-time users
- "your first lesson" when they haven't started yet is unclear
- "N/A" for band score isn't helpful

## Solution

Use the new conditional shortcodes `[ielts_is_new_user]` and `[ielts_is_returning_user]` to display different content based on whether the user is logging in for the first time or returning.

## Implementation

### Basic Example

Replace your current welcome message with:

```
Hi [ielts_user_firstname],

[ielts_is_new_user]
Welcome to the IELTS Preparation Course! We're excited to help you achieve your target band score. 
Let's get started with your first lesson.
[/ielts_is_new_user]

[ielts_is_returning_user]
Welcome back! The last lesson you were studying was [ielts_last_page]. 
Your current predicted band score is [ielts_predicted_band_score] based on 
your scores and performance so far.
[/ielts_is_returning_user]
```

### Advanced Example

You can provide more detailed guidance for new users:

```
Hi [ielts_user_firstname],

[ielts_is_new_user]
## Welcome to Your IELTS Journey! 🎓

Thank you for joining the IELTS Preparation Course. Here's how to get started:

1. **Complete your profile** - Make sure your personal information is up to date
2. **Take the placement test** - This helps us understand your current level
3. **Choose your learning path** - Academic or General Training module
4. **Start learning** - Begin with Module 1: Introduction to IELTS

We're here to support you every step of the way. Good luck!
[/ielts_is_new_user]

[ielts_is_returning_user]
## Welcome Back! 👋

Great to see you again! Here's your progress update:

- **Last lesson:** [ielts_last_page]
- **Current predicted band score:** [ielts_predicted_band_score]
- **Overall progress:** [ielts_progress]

Keep up the excellent work! Remember, consistency is key to achieving your target band score.
[/ielts_is_returning_user]
```

## How It Works

The shortcodes check the user's login count stored in the WordPress user meta:

- **New user** (`login_count <= 1`): Shows content inside `[ielts_is_new_user]` tags
- **Returning user** (`login_count > 1`): Shows content inside `[ielts_is_returning_user]` tags

The login count is automatically tracked by the plugin every time a user logs in.

## Available Shortcodes

### Conditional Shortcodes

- `[ielts_is_new_user]content[/ielts_is_new_user]` - Shows content only for first-time users
- `[ielts_is_returning_user]content[/ielts_is_returning_user]` - Shows content only for returning users

### User Information Shortcodes

- `[ielts_user_firstname]` - User's first name
- `[ielts_last_page]` - Last lesson studied (with hyperlink)
- `[ielts_predicted_band_score]` - Overall predicted band score (0-9)
- `[ielts_progress]` - User's course progress
- `[ielts_band_scores]` - Detailed band scores for all skills

## Best Practices

1. **Be encouraging for new users** - Welcome them warmly and provide clear next steps
2. **Show progress for returning users** - Motivate them by showing their achievements
3. **Keep it concise** - Don't overwhelm users with too much text
4. **Use clear CTAs** - Guide users on what to do next
5. **Test both scenarios** - Make sure the message makes sense for both new and returning users

## Testing

To test your implementation:

1. **Test as new user:**
   - Create a test user account
   - Log in for the first time
   - Verify you see the new user message

2. **Test as returning user:**
   - Use an existing account that has logged in before
   - Complete some lessons and quizzes
   - Verify you see the returning user message with correct data

## Additional Resources

- Full shortcodes reference: [SHORTCODES.md](SHORTCODES.md)
- Main documentation: [README.md](README.md)
