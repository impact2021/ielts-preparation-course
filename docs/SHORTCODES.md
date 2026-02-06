# IELTS Course Manager Shortcodes

This document describes the available shortcodes in the IELTS Course Manager plugin.

## User Information Shortcodes

### `[ielts_user_firstname]`
Displays the logged-in user's first name.

**Usage:**
```
Hi [ielts_user_firstname], welcome back!
```

**Output:**
- Shows the user's first name from their WordPress profile
- Falls back to display name if first name is not set
- Falls back to username if neither first name nor display name is set
- Returns empty string if user is not logged in

---

### `[ielts_predicted_band_score]`
Displays the user's predicted overall IELTS band score based on their performance across all skills.

**Usage:**
```
Your current predicted band score is [ielts_predicted_band_score]
```

**Output:**
- Calculates the average band score from Reading, Listening, Writing, and Speaking skills
- Returns score rounded to nearest 0.5 (e.g., 6.5, 7.0, 7.5)
- Shows "N/A" if the user has no quiz scores yet
- Returns empty string if user is not logged in

**Example Usage Together:**
```
Hi [ielts_user_firstname],

Welcome back! The last lesson you were studying was [ielts_last_page]. 
Your current predicted band score is [ielts_predicted_band_score] based on 
your scores and performance so far.
```

**Better Example for New vs Returning Users:**
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

---

### `[ielts_is_new_user]`
Conditional shortcode that displays content only for new users (first-time login).

**Usage:**
```
[ielts_is_new_user]
This content only appears for new users on their first login.
[/ielts_is_new_user]
```

**Output:**
- Shows the enclosed content if the user is logging in for the first time (login count <= 1)
- Returns empty string for returning users or if user is not logged in
- Can be nested with other shortcodes

---

### `[ielts_is_returning_user]`
Conditional shortcode that displays content only for returning users.

**Usage:**
```
[ielts_is_returning_user]
This content only appears for users who have logged in before.
[/ielts_is_returning_user]
```

**Output:**
- Shows the enclosed content if the user has logged in more than once (login count > 1)
- Returns empty string for new users or if user is not logged in
- Can be nested with other shortcodes

---

### `[ielts_last_page]`
Displays the last lesson/page the user was studying as a hyperlinked lesson title.

**Usage:**
```
Welcome back! The last lesson you were studying was [ielts_last_page].
```

**Output:**
- Returns the lesson title as a hyperlink to the lesson
- Returns "your first lesson" if user hasn't started any lessons yet
- Returns "your last lesson" if user is not logged in or lesson data is unavailable

---

## Progress and Scores Shortcodes

### `[ielts_band_scores]`
Displays detailed band scores for all IELTS skills.

**Usage:**
```
[ielts_band_scores]
```

**Optional Parameters:**
- `skills` - Which skills to show (default: "reading,listening,writing,speaking")
- `title` - Custom title for the section

**Example:**
```
[ielts_band_scores skills="reading,listening" title="Your Reading and Listening Scores"]
```

---

### `[ielts_progress]`
Displays the user's course progress.

**Usage:**
```
[ielts_progress]
```

---

### `[ielts_my_progress]`
Displays comprehensive progress information for the logged-in user.

**Usage:**
```
[ielts_my_progress]
```

---

## Other Available Shortcodes

- `[ielts_courses]` - Display list of available courses
- `[ielts_course]` - Display a single course
- `[ielts_my_account]` - Display user account page
- `[ielts_awards]` - Display user awards
- `[ielts_progress_rings]` - Display visual progress rings
- `[ielts_skills_radar]` - Display skills radar chart
- `[ielts_login]` - Display login form
- `[ielts_registration]` - Display registration form
- `[ielts_login_stats]` - Display login statistics
- `[ielts_price]` - Display pricing information
- `[ielts_access_code_registration]` - Display access code registration form

For more information about other shortcodes, please refer to the plugin documentation or contact support.
