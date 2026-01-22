# Progress Rings and Skills Radar - User Guide

## Version 13.0 Features

This guide covers the two new gamification shortcodes added in version 13.0.

---

## Progress Rings Shortcode

### Basic Usage

```
[ielts_progress_rings]
```

### Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
| `view` | `daily`, `weekly`, `monthly` | `daily` | Time period to display |

### Examples

**Daily progress (default):**
```
[ielts_progress_rings]
```

**Weekly progress:**
```
[ielts_progress_rings view="weekly"]
```

**Monthly progress:**
```
[ielts_progress_rings view="monthly"]
```

### What It Shows

The progress rings display three concentric circles:

1. **Outer Ring (Green)** - Exercises completed vs. goal
2. **Middle Ring (Blue)** - Study time vs. goal  
3. **Inner Ring (Orange)** - Perfect scores (100%) vs. goal

**Daily View:**
- Exercises: Default goal is 5 per day
- Study Time: Default goal is 30 minutes
- Perfect Scores: Default goal is 2 per day
- Also shows current study streak

**Weekly View:**
- Exercises: Default goal is 25 per week
- Study Time: Default goal is 180 minutes (3 hours)
- Perfect Scores: Default goal is 10 per week

**Monthly View:**
- Exercises: Default goal is 100 per month
- Study Time: Default goal is 720 minutes (12 hours)
- Perfect Scores: Default goal is 40 per month

### Customizing Goals

Users can customize their goals using WordPress user meta fields:

**Daily goals:**
- `_ielts_cm_daily_exercise_goal`
- `_ielts_cm_daily_time_goal`
- `_ielts_cm_daily_perfect_goal`

**Weekly goals:**
- `_ielts_cm_weekly_exercise_goal`
- `_ielts_cm_weekly_time_goal`
- `_ielts_cm_weekly_perfect_goal`

**Monthly goals:**
- `_ielts_cm_monthly_exercise_goal`
- `_ielts_cm_monthly_time_goal`
- `_ielts_cm_monthly_perfect_goal`

---

## Skills Radar Chart Shortcode

### Basic Usage

```
[ielts_skills_radar]
```

### Parameters

| Parameter | Options | Default | Description |
|-----------|---------|---------|-------------|
| `show_target` | `yes`, `no` | `yes` | Show Band 7 target line |
| `height` | `200`-`800` | `400` | Chart height in pixels |

### Examples

**Standard radar chart:**
```
[ielts_skills_radar]
```

**Larger chart without target:**
```
[ielts_skills_radar show_target="no" height="500"]
```

**Compact chart:**
```
[ielts_skills_radar height="300"]
```

### What It Shows

The skills radar chart displays a hexagonal graph showing the user's proficiency across 6 IELTS skill areas:

1. Reading
2. Writing  
3. Listening
4. Speaking
5. Vocabulary
6. Grammar

**How Scores Are Calculated:**

For each skill, the chart shows the average percentage score across all exercises tagged with that skill type. For example:
- If a user has completed 5 reading exercises with scores of 70%, 80%, 75%, 85%, 90%
- Their Reading score on the radar chart will be 80% (the average)

**Band 7 Target Line:**

When `show_target="yes"` (default), a yellow dashed line shows 80% proficiency (approximately Band 7 level) for all skills.

---

## Setting Up Skill Types for Exercises

For the Skills Radar Chart to work properly, exercises must be tagged with skill types.

### In WordPress Admin

1. Edit any Exercise (Quiz)
2. Find the "Skill Type" dropdown in the Exercise Settings section
3. Select the primary skill: Reading, Writing, Listening, Speaking, Vocabulary, or Grammar
4. Save the exercise

**Auto-Detection:**
- When you check "This is for a listening exercise", the Skill Type automatically selects "Listening"
- You can override this if needed (e.g., for a listening exercise that focuses on vocabulary)

### Best Practices

- **Be Specific:** Choose the primary skill the exercise targets
- **Reading Exercises:** Tag as "Reading" (even if they test vocabulary/grammar through reading)
- **Listening Exercises:** Tag as "Listening" (even if they test comprehension through listening)
- **Vocabulary/Grammar:** Use these tags for exercises specifically teaching word usage or grammar rules
- **Writing/Speaking:** Use for composition and speaking practice exercises

---

## Example Page Layout

Here's an example of how to combine both shortcodes on a "My Progress" page:

```html
<h1>My IELTS Progress Dashboard</h1>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
  <div>
    [ielts_progress_rings]
  </div>
  <div>
    [ielts_skills_radar]
  </div>
</div>

<h2>Recent Achievements</h2>
[ielts_awards]

<h2>Course Progress</h2>
[ielts_my_progress]
```

This creates a comprehensive dashboard showing:
- Daily progress rings (left)
- Skills radar chart (right)
- Trophy wall below
- Detailed course progress at the bottom

---

## Technical Notes

### Requirements

- **User Must Be Logged In:** Both shortcodes require user authentication
- **JavaScript:** Chart.js is loaded from CDN for the radar chart
- **Browser Compatibility:** Modern browsers (Chrome, Firefox, Safari, Edge)

### Performance

- Progress data is loaded via AJAX
- Queries are optimized with proper indexes
- Study time is estimated (10 minutes per exercise)

### Data Privacy

- All data is user-specific
- No data is shared between users
- Progress is calculated from the quiz results table

---

## Troubleshooting

### "Please log in to view your progress"

Make sure the user is logged in. These features are only available to authenticated users.

### Skills Radar shows 0% for all skills

This means no exercises have been tagged with skill types. Tag your exercises:
1. Go to Exercises in WordPress admin
2. Edit each exercise
3. Select a Skill Type from the dropdown
4. Save

### Progress rings not updating

The rings update based on quiz completion. Make sure:
1. Exercises are being submitted through the quiz system
2. Results are being saved to the database
3. The quiz results table exists

### Chart not displaying

If the radar chart doesn't appear:
1. Check browser console for JavaScript errors
2. Ensure Chart.js is loading (check Network tab)
3. Verify user has completed at least one exercise with a skill type

---

## Migration from Previous Versions

### Coming from Version 12.x

No database changes are required. Simply:
1. Update to version 13.0
2. Tag your existing exercises with skill types
3. Add shortcodes to your pages

### Bulk Tagging Exercises

You can bulk update exercises using WordPress's Quick Edit:
1. Go to Exercises list
2. Hover over an exercise
3. Click "Quick Edit"
4. (Note: Currently skill type must be set individually - bulk edit support coming in future version)

For now, exercises must be tagged individually through the edit screen.

---

## Support

For issues or questions:
1. Check this documentation
2. Review the implementation guide in `SKILLS_RADAR_AND_PROGRESS_RINGS_IMPLEMENTATION.md`
3. Check the general gamification recommendations in `GAMIFICATION_RECOMMENDATIONS.md`
4. Open an issue on GitHub

---

## Changelog

### Version 13.0 (2026-01-22)

**Added:**
- Progress Rings shortcode with daily/weekly/monthly views
- Skills Radar Chart shortcode with customizable display
- Skill Type field for exercises
- Gamification class with AJAX handlers
- Automatic streak calculation
- Progress tracking for exercises, study time, and perfect scores

**Technical:**
- New class: `IELTS_CM_Gamification`
- New templates: `progress-rings.php`, `skills-radar.php`
- Integration with Chart.js for radar visualization
- SVG-based ring animations
- User meta fields for custom goals
