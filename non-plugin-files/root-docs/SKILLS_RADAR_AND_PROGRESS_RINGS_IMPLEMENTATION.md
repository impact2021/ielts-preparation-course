# Skills Radar Chart & Progress Rings - Implementation Guide

## Overview

This document provides technical implementation details for two gamification features:
1. **Skills Radar Chart** - Visual representation of proficiency across IELTS skills
2. **Progress Rings** - Apple Watch-style activity tracking

---

## Part 1: Skills Radar Chart Implementation

### Question: How to Identify Skill Types?

The system needs to categorize exercises/quizzes into 6 skill areas:
- Reading
- Writing
- Listening
- Speaking
- Vocabulary
- Grammar

### Proposed Solution: Multi-Level Categorization

#### Option 1: Use Existing WordPress Taxonomy (Recommended)

The codebase already has `ielts_course_category` taxonomy registered for courses. We can extend this to quizzes/exercises.

**Implementation Steps:**

1. **Register taxonomy for quizzes** (in `includes/class-post-types.php`):

```php
// Add to register_quiz() method
register_taxonomy('ielts_skill_type', 'ielts_quiz', array(
    'labels' => array(
        'name' => __('Skill Types', 'ielts-course-manager'),
        'singular_name' => __('Skill Type', 'ielts-course-manager'),
    ),
    'hierarchical' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'rewrite' => array('slug' => 'skill-type'),
));
```

2. **Pre-populate with standard IELTS skills**:

```php
// On plugin activation, create default skill terms
$default_skills = array(
    'Reading' => 'Reading comprehension exercises',
    'Writing' => 'Writing task exercises',
    'Listening' => 'Listening comprehension exercises',
    'Speaking' => 'Speaking practice exercises',
    'Vocabulary' => 'Vocabulary building exercises',
    'Grammar' => 'Grammar practice exercises',
);

foreach ($default_skills as $skill => $description) {
    if (!term_exists($skill, 'ielts_skill_type')) {
        wp_insert_term($skill, 'ielts_skill_type', array(
            'description' => $description,
        ));
    }
}
```

3. **Admin UI Enhancement**: Add skill type selector in quiz edit screen

4. **Database Query for Radar Chart**:

```php
function get_user_skill_scores($user_id) {
    global $wpdb;
    $quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
    
    $skills = array('Reading', 'Writing', 'Listening', 'Speaking', 'Vocabulary', 'Grammar');
    $skill_scores = array();
    
    foreach ($skills as $skill) {
        // Get term ID
        $term = get_term_by('name', $skill, 'ielts_skill_type');
        if (!$term) {
            $skill_scores[$skill] = 0;
            continue;
        }
        
        // Get all quizzes with this skill type
        $quiz_ids = get_posts(array(
            'post_type' => 'ielts_quiz',
            'tax_query' => array(
                array(
                    'taxonomy' => 'ielts_skill_type',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ),
            ),
            'fields' => 'ids',
            'posts_per_page' => -1,
        ));
        
        if (empty($quiz_ids)) {
            $skill_scores[$skill] = 0;
            continue;
        }
        
        // Calculate average score for this skill
        $placeholders = implode(',', array_fill(0, count($quiz_ids), '%d'));
        $query = $wpdb->prepare(
            "SELECT AVG(percentage) as avg_score 
             FROM {$quiz_results_table} 
             WHERE user_id = %d 
             AND quiz_id IN ($placeholders)",
            array_merge(array($user_id), $quiz_ids)
        );
        
        $result = $wpdb->get_var($query);
        $skill_scores[$skill] = round($result ?: 0, 1);
    }
    
    return $skill_scores;
}
```

#### Option 2: Use Course Categories (Alternative)

If exercises are already grouped under categorized courses:

1. Use the existing `ielts_course_category` taxonomy on courses
2. When calculating skill scores, determine skill type from the parent course
3. Map course categories to skill types

**Example mapping:**
```php
$category_to_skill_map = array(
    'reading-comprehension' => 'Reading',
    'academic-writing' => 'Writing',
    'general-writing' => 'Writing',
    'listening-practice' => 'Listening',
    'speaking-tasks' => 'Speaking',
    'vocabulary-building' => 'Vocabulary',
    'grammar-essentials' => 'Grammar',
);
```

#### Option 3: Add Custom Meta Field (Quick Solution)

Add a simple dropdown to each quiz:

```php
// In admin meta box for quizzes
<select name="ielts_cm_skill_type">
    <option value="">Select Skill Type</option>
    <option value="reading">Reading</option>
    <option value="writing">Writing</option>
    <option value="listening">Listening</option>
    <option value="speaking">Speaking</option>
    <option value="vocabulary">Vocabulary</option>
    <option value="grammar">Grammar</option>
</select>
```

Store as post meta: `_ielts_cm_skill_type`

### Radar Chart Visualization

**Frontend Implementation** (using Chart.js):

```javascript
function renderSkillsRadar(skillScores) {
    const ctx = document.getElementById('skillsRadarChart').getContext('2d');
    
    const data = {
        labels: ['Reading', 'Writing', 'Listening', 'Speaking', 'Vocabulary', 'Grammar'],
        datasets: [{
            label: 'Your Proficiency',
            data: [
                skillScores.Reading,
                skillScores.Writing,
                skillScores.Listening,
                skillScores.Speaking,
                skillScores.Vocabulary,
                skillScores.Grammar
            ],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(54, 162, 235, 1)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgba(54, 162, 235, 1)'
        }, {
            label: 'Band 7 Target',
            data: [80, 80, 80, 80, 80, 80], // Band 7 ≈ 80%
            backgroundColor: 'rgba(255, 206, 86, 0.1)',
            borderColor: 'rgba(255, 206, 86, 0.8)',
            borderWidth: 1,
            borderDash: [5, 5],
            pointRadius: 0
        }]
    };
    
    const config = {
        type: 'radar',
        data: data,
        options: {
            scales: {
                r: {
                    min: 0,
                    max: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Your IELTS Skills Profile'
                }
            }
        }
    };
    
    new Chart(ctx, config);
}
```

**Visual Mockup (ASCII Art):**

```
        Skills Radar Chart
        ==================
        
                Reading (75%)
                      ▲
                     /|\
                    / | \
         Grammar   /  |  \   Writing
          (65%)   /   |   \   (80%)
                 /    ●    \
                /   User    \
               /   Profile   \
              /               \
             ●-----------------●
    Vocabulary               Listening
      (70%)                   (85%)
             \               /
              \             /
               \           /
                \         /
                 \       /
                  \     /
                   \   /
                    \ /
                     ●
                 Speaking
                  (72%)

Legend:
● Your current proficiency
--- Target Band 7 (80%)
```

**Actual Visual Appearance:**

The radar chart would look like a hexagon (6 skills) with:
- Blue filled area showing user's current proficiency
- Yellow dashed line showing Band 7 target
- Each axis goes from 0-100%
- User's scores create an irregular hexagon shape
- Weaker skills are closer to center, stronger skills extend toward edges

---

## Part 2: Progress Rings Implementation

### What Progress Rings Look Like

Progress rings (inspired by Apple Watch Activity Rings) are concentric circles that fill clockwise to show goal completion.

### Visual Mockup (ASCII Art):

```
┌────────────────────────────────────────┐
│     Daily Progress Rings               │
│                                        │
│            ╭──────╮                    │
│          ╭─────────╮                   │
│        ╭────────────╮                  │
│       │    ╱███╲    │                  │
│       │   ███ ███   │                  │
│       │   ███ ███   │  Outer Ring:    │
│       │   ╲███╱███  │  Daily Exercises│
│       │     ╲███╱   │  (3/5 complete) │
│        ╰────────────╯                  │
│          ╰─────────╯  Middle Ring:    │
│            ╰──────╯   Study Time      │
│                        (25/30 min)    │
│                                        │
│                        Inner Ring:     │
│                        Perfect Scores  │
│                        (1/2 achieved)  │
│                                        │
│  🟢 Daily Goal: 5 exercises            │
│  🔵 Study Time: 30 minutes             │
│  🔴 Perfection: 2 perfect scores       │
└────────────────────────────────────────┘
```

### Better Visual Representation (What Users Will See):

```
     Daily Progress: January 22, 2026
    ═══════════════════════════════════
    
          ████████████                 EXERCISES
         █            █                5 / 5 Completed
        █   ████████   █               100% Complete! 🎉
        █  █        █  █               
        █  █  ████  █  █  STUDY TIME   
        █  █ █    █ █  █  25 / 30 min  
        █  █ █ 🏆 █ █  █  83% Complete 
        █  █ █    █ █  █               
        █  █  ████  █  █  PERFECT      
        █  █        █  █  1 / 2 Today  
        █   ████████   █  50% Complete 
         █            █                
          ████████████                 

    Daily Streak: 7 days 🔥
    Weekly Progress: 15/25 exercises
    
    [View Detailed Stats →]
```

### Technical Implementation

#### HTML Structure:

```html
<div class="progress-rings-container">
    <svg class="progress-rings" viewBox="0 0 200 200" width="300" height="300">
        <!-- Background circles (gray) -->
        <circle cx="100" cy="100" r="85" fill="none" stroke="#e0e0e0" stroke-width="12"/>
        <circle cx="100" cy="100" r="65" fill="none" stroke="#e0e0e0" stroke-width="12"/>
        <circle cx="100" cy="100" r="45" fill="none" stroke="#e0e0e0" stroke-width="12"/>
        
        <!-- Outer ring: Daily exercises (green) -->
        <circle id="exercises-ring" cx="100" cy="100" r="85" 
                fill="none" 
                stroke="#4CAF50" 
                stroke-width="12"
                stroke-linecap="round"
                stroke-dasharray="534" 
                stroke-dashoffset="267"
                transform="rotate(-90 100 100)"/>
        
        <!-- Middle ring: Study time (blue) -->
        <circle id="time-ring" cx="100" cy="100" r="65" 
                fill="none" 
                stroke="#2196F3" 
                stroke-width="12"
                stroke-linecap="round"
                stroke-dasharray="408" 
                stroke-dashoffset="136"
                transform="rotate(-90 100 100)"/>
        
        <!-- Inner ring: Perfect scores (red) -->
        <circle id="perfect-ring" cx="100" cy="100" r="45" 
                fill="none" 
                stroke="#F44336" 
                stroke-width="12"
                stroke-linecap="round"
                stroke-dasharray="283" 
                stroke-dashoffset="141"
                transform="rotate(-90 100 100)"/>
        
        <!-- Center icon/text -->
        <text x="100" y="105" text-anchor="middle" font-size="30" fill="#333">🏆</text>
    </svg>
    
    <div class="progress-rings-stats">
        <div class="ring-stat">
            <span class="ring-color" style="background: #4CAF50;"></span>
            <span class="ring-label">Exercises</span>
            <span class="ring-value">3/5</span>
        </div>
        <div class="ring-stat">
            <span class="ring-color" style="background: #2196F3;"></span>
            <span class="ring-label">Study Time</span>
            <span class="ring-value">25/30 min</span>
        </div>
        <div class="ring-stat">
            <span class="ring-color" style="background: #F44336;"></span>
            <span class="ring-label">Perfect Scores</span>
            <span class="ring-value">1/2</span>
        </div>
    </div>
</div>
```

#### JavaScript for Dynamic Updates:

```javascript
function updateProgressRing(ringId, percentage) {
    const ring = document.getElementById(ringId);
    const circumference = 2 * Math.PI * ring.getAttribute('r');
    const offset = circumference - (percentage / 100) * circumference;
    
    ring.style.strokeDashoffset = offset;
    
    // Add completion animation
    if (percentage >= 100) {
        ring.style.filter = 'drop-shadow(0 0 10px currentColor)';
        // Celebrate!
        showCelebration();
    }
}

function showCelebration() {
    // Confetti or animation when a ring is completed
    const container = document.querySelector('.progress-rings-container');
    container.classList.add('celebrate');
    setTimeout(() => container.classList.remove('celebrate'), 2000);
}

// Update rings based on user data
function loadUserProgress() {
    $.ajax({
        url: ieltsCM.ajaxUrl,
        method: 'POST',
        data: {
            action: 'ielts_cm_get_daily_progress',
            nonce: ieltsCM.nonce
        },
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Calculate percentages
                const exercisePercent = (data.exercises_today / data.daily_exercise_goal) * 100;
                const timePercent = (data.study_time_today / data.daily_time_goal) * 100;
                const perfectPercent = (data.perfect_scores_today / data.daily_perfect_goal) * 100;
                
                // Update rings
                updateProgressRing('exercises-ring', Math.min(exercisePercent, 100));
                updateProgressRing('time-ring', Math.min(timePercent, 100));
                updateProgressRing('perfect-ring', Math.min(perfectPercent, 100));
            }
        }
    });
}
```

#### CSS for Styling:

```css
.progress-rings-container {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.progress-rings {
    display: block;
    margin: 0 auto;
}

.progress-rings circle {
    transition: stroke-dashoffset 0.5s ease-in-out;
}

.progress-rings-container.celebrate .progress-rings {
    animation: pulse 0.5s ease-in-out 3;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.progress-rings-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
    gap: 15px;
}

.ring-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.ring-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.ring-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.ring-value {
    font-size: 16px;
    font-weight: bold;
    color: #333;
}
```

#### Backend - Track Daily Progress:

```php
function get_daily_progress($user_id) {
    global $wpdb;
    $quiz_results_table = $wpdb->prefix . 'ielts_cm_quiz_results';
    
    $today_start = date('Y-m-d 00:00:00');
    $today_end = date('Y-m-d 23:59:59');
    
    // Get user's daily goals (from user meta or defaults)
    $daily_exercise_goal = get_user_meta($user_id, '_ielts_cm_daily_exercise_goal', true) ?: 5;
    $daily_time_goal = get_user_meta($user_id, '_ielts_cm_daily_time_goal', true) ?: 30; // minutes
    $daily_perfect_goal = get_user_meta($user_id, '_ielts_cm_daily_perfect_goal', true) ?: 2;
    
    // Count exercises completed today
    $exercises_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$quiz_results_table} 
         WHERE user_id = %d 
         AND submitted_date BETWEEN %s AND %s",
        $user_id, $today_start, $today_end
    ));
    
    // Count perfect scores today
    $perfect_scores_today = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$quiz_results_table} 
         WHERE user_id = %d 
         AND percentage >= 100
         AND submitted_date BETWEEN %s AND %s",
        $user_id, $today_start, $today_end
    ));
    
    // Calculate study time today (you'd need to track this separately)
    $study_time_today = get_user_meta($user_id, '_ielts_cm_study_time_today', true) ?: 0;
    
    return array(
        'exercises_today' => intval($exercises_today),
        'daily_exercise_goal' => intval($daily_exercise_goal),
        'perfect_scores_today' => intval($perfect_scores_today),
        'daily_perfect_goal' => intval($daily_perfect_goal),
        'study_time_today' => intval($study_time_today),
        'daily_time_goal' => intval($daily_time_goal),
    );
}

// AJAX handler
add_action('wp_ajax_ielts_cm_get_daily_progress', 'ielts_cm_get_daily_progress_ajax');
function ielts_cm_get_daily_progress_ajax() {
    check_ajax_referer('ielts_cm_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not logged in'));
    }
    
    $progress = get_daily_progress($user_id);
    wp_send_json_success($progress);
}
```

### Weekly & Monthly Rings

You can extend this to show weekly and monthly goals:

```
Daily View           Weekly View         Monthly View
┌─────────┐         ┌─────────┐         ┌─────────┐
│  ████   │         │  ████   │         │  ██     │
│ █    █  │         │ █    █  │         │ █  █    │
│ █ 🔥 █  │         │ █ ⚡ █  │         │ █ 🏆 █  │
│ █    █  │         │ █    █  │         │ █  █    │
│  ████   │         │  ████   │         │  ██     │
│         │         │         │         │         │
│  3/5    │         │ 15/25   │         │ 45/100  │
│ Today   │         │This Week│         │This Month│
└─────────┘         └─────────┘         └─────────┘
```

---

## Integration with Existing Trophy Wall

The progress rings and radar chart complement the existing trophy wall:

**Trophy Wall** = Long-term achievements (permanent awards)
**Progress Rings** = Short-term daily/weekly goals (reset regularly)
**Radar Chart** = Overall skill proficiency (evolves over time)

### Dashboard Layout Mockup:

```
╔════════════════════════════════════════════════════════╗
║  IELTS Preparation Dashboard - Welcome, John!          ║
╠════════════════════════════════════════════════════════╣
║                                                        ║
║  ┌─────────────────┐  ┌──────────────────────────┐   ║
║  │ Progress Rings  │  │   Skills Radar Chart     │   ║
║  │                 │  │                          │   ║
║  │      ████       │  │        Reading           │   ║
║  │     █    █      │  │           ▲              │   ║
║  │    █  🏆  █     │  │          /|\             │   ║
║  │     █    █      │  │         / | \            │   ║
║  │      ████       │  │   Gram /  ●  \ Writing   │   ║
║  │                 │  │       /  User \          │   ║
║  │   3/5 Today     │  │      ●────────●          │   ║
║  │  15/25 Week     │  │    Vocab    Listen       │   ║
║  │  45/100 Month   │  │      \        /          │   ║
║  └─────────────────┘  │       \  ●  /            │   ║
║                       │         \/               │   ║
║  ┌─────────────────────────────────────────────┐ │   ║
║  │  Recent Achievements                        │ │   ║
║  │  🏅 Week Warrior - 7 day streak!            │ │   ║
║  │  ⭐ Perfect Score - 100% on Reading Test 3  │ │   ║
║  │  [View All Awards →]                        │ │   ║
║  └─────────────────────────────────────────────┘ │   ║
║                       │                          │   ║
║  ┌─────────────────┐  └──────────────────────────┘   ║
║  │ Daily Challenge │                                 ║
║  │ ⚡ Flash Practice│                                 ║
║  │ Complete 3      │                                 ║
║  │ exercises in    │                                 ║
║  │ 20 minutes      │                                 ║
║  │ [Start Now →]   │                                 ║
║  └─────────────────┘                                 ║
║                                                        ║
╚════════════════════════════════════════════════════════╝
```

---

## Summary

### Skills Radar Chart
- **How to identify skill types**: Use WordPress taxonomy, course categories, or custom meta fields
- **Recommended**: Add `ielts_skill_type` taxonomy to quizzes for precise categorization
- **Visual**: 6-sided radar chart showing proficiency in Reading, Writing, Listening, Speaking, Vocabulary, Grammar
- **Data**: Average quiz scores per skill area, compared against IELTS Band targets

### Progress Rings
- **What they look like**: Concentric circles that fill clockwise based on goal completion
- **Three rings**: Daily exercises (outer), Study time (middle), Perfect scores (inner)
- **Colors**: Green, Blue, Red (customizable)
- **Animation**: Smooth transitions, celebration effects when goals achieved
- **Timeframes**: Can show daily, weekly, or monthly progress
- **Reset**: Daily rings reset each day, weekly rings reset each week, etc.

Both features are implementable with the current database structure, requiring only:
1. Skill type categorization system (taxonomy or meta field)
2. Frontend visualization libraries (Chart.js for radar, SVG for rings)
3. AJAX endpoints to fetch user progress data
4. Tracking of study time (new feature)

---

*This implementation guide provides a complete technical roadmap for adding both features to the IELTS Preparation Course platform.*
