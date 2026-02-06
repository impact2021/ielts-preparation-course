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

---

### `[ielts_last_page]`
Displays the last lesson/page the user was studying with a "Continue Learning" button.

**Usage:**
```
[ielts_last_page]
```

**Output:**
- Shows a formatted widget with the last accessed lesson
- Includes course name, lesson title, and time since last access
- Provides a "Continue Learning" button linking to the lesson
- Returns a message if user hasn't started any lessons yet
- Returns login prompt if user is not logged in

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
