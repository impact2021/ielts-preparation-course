<?php
/**
 * Awards and gamification functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Awards {
    
    private $db;
    private $awards_table;
    
    public function __construct() {
        global $wpdb;
        $this->db = new IELTS_CM_Database();
        $this->awards_table = $wpdb->prefix . 'ielts_cm_user_awards';
        
        // Hook into various actions to track award progress
        add_action('ielts_cm_page_completed', array($this, 'check_first_page_completion'), 10, 2);
        add_action('ielts_cm_quiz_submitted', array($this, 'check_quiz_awards'), 10, 4);
        add_action('ielts_cm_lesson_completed', array($this, 'check_lesson_completion'), 10, 2);
        add_action('ielts_cm_course_completed', array($this, 'check_course_completion'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_ielts_cm_get_user_awards', array($this, 'get_user_awards_ajax'));
    }
    
    /**
     * Get all available awards
     */
    public function get_all_awards() {
        return array(
            // Badges (15 total)
            array('id' => 'getting_started', 'name' => 'Getting Started', 'type' => 'badge', 'description' => 'Complete your first page'),
            array('id' => 'first_test', 'name' => 'First Test', 'type' => 'badge', 'description' => 'Complete your first exercise'),
            array('id' => 'five_exercises', 'name' => 'Five Strong', 'type' => 'badge', 'description' => 'Complete 5 exercises'),
            array('id' => 'ten_exercises', 'name' => 'Perfect Ten', 'type' => 'badge', 'description' => 'Complete 10 exercises'),
            array('id' => 'twenty_exercises', 'name' => 'Twenty Champion', 'type' => 'badge', 'description' => 'Complete 20 exercises'),
            array('id' => 'fifty_exercises', 'name' => 'Half Century', 'type' => 'badge', 'description' => 'Complete 50 exercises'),
            array('id' => 'first_perfect', 'name' => 'Perfectionist', 'type' => 'badge', 'description' => 'Get your first 100% score'),
            array('id' => 'five_perfect', 'name' => 'Perfect Streak', 'type' => 'badge', 'description' => 'Get 100% on 5 exercises'),
            array('id' => 'early_bird', 'name' => 'Early Bird', 'type' => 'badge', 'description' => 'Complete an exercise before 9 AM'),
            array('id' => 'night_owl', 'name' => 'Night Owl', 'type' => 'badge', 'description' => 'Complete an exercise after 9 PM'),
            array('id' => 'week_streak', 'name' => 'Week Warrior', 'type' => 'badge', 'description' => 'Study for 7 days in a row'),
            array('id' => 'month_streak', 'name' => 'Monthly Master', 'type' => 'badge', 'description' => 'Study for 30 days in a row'),
            array('id' => 'speed_demon', 'name' => 'Speed Demon', 'type' => 'badge', 'description' => 'Complete an exercise in under 5 minutes'),
            array('id' => 'vocabulary_master', 'name' => 'Word Wizard', 'type' => 'badge', 'description' => 'Complete 10 vocabulary exercises'),
            array('id' => 'grammar_guru', 'name' => 'Grammar Guru', 'type' => 'badge', 'description' => 'Complete 10 grammar exercises'),
            
            // Shields (20 total)
            array('id' => 'hundred_percent', 'name' => '100%!', 'type' => 'shield', 'description' => 'Get 100% on any test'),
            array('id' => 'first_lesson_done', 'name' => 'First Lesson Done', 'type' => 'shield', 'description' => 'Finish your first lesson'),
            array('id' => 'five_lessons', 'name' => 'Lesson Leader', 'type' => 'shield', 'description' => 'Complete 5 lessons'),
            array('id' => 'ten_lessons', 'name' => 'Lesson Legend', 'type' => 'shield', 'description' => 'Complete 10 lessons'),
            array('id' => 'reading_master', 'name' => 'Reading Master', 'type' => 'shield', 'description' => 'Complete 5 reading exercises'),
            array('id' => 'writing_master', 'name' => 'Writing Master', 'type' => 'shield', 'description' => 'Complete 5 writing exercises'),
            array('id' => 'listening_master', 'name' => 'Listening Master', 'type' => 'shield', 'description' => 'Complete 5 listening exercises'),
            array('id' => 'speaking_master', 'name' => 'Speaking Master', 'type' => 'shield', 'description' => 'Complete 5 speaking exercises'),
            array('id' => 'high_scorer', 'name' => 'High Scorer', 'type' => 'shield', 'description' => 'Get over 90% on 10 exercises'),
            array('id' => 'consistent_performer', 'name' => 'Consistent Performer', 'type' => 'shield', 'description' => 'Get over 80% on 20 exercises'),
            array('id' => 'reading_champion', 'name' => 'Reading Champion', 'type' => 'shield', 'description' => 'Get 100% on 5 reading exercises'),
            array('id' => 'listening_champion', 'name' => 'Listening Champion', 'type' => 'shield', 'description' => 'Get 100% on 5 listening exercises'),
            array('id' => 'quick_learner', 'name' => 'Quick Learner', 'type' => 'shield', 'description' => 'Complete a lesson on first try with 100%'),
            array('id' => 'perseverance', 'name' => 'Perseverance', 'type' => 'shield', 'description' => 'Retry an exercise 3 times to improve score'),
            array('id' => 'improving', 'name' => 'Always Improving', 'type' => 'shield', 'description' => 'Improve your score by 20% on retry'),
            array('id' => 'three_lessons_day', 'name' => 'Daily Dedication', 'type' => 'shield', 'description' => 'Complete 3 lessons in one day'),
            array('id' => 'five_exercises_day', 'name' => 'Exercise Enthusiast', 'type' => 'shield', 'description' => 'Complete 5 exercises in one day'),
            array('id' => 'full_marks_reading', 'name' => 'Reading Ace', 'type' => 'shield', 'description' => 'Get 100% on a full reading test'),
            array('id' => 'full_marks_listening', 'name' => 'Listening Ace', 'type' => 'shield', 'description' => 'Get 100% on a full listening test'),
            array('id' => 'band_7_reading', 'name' => 'Band 7 Reading', 'type' => 'shield', 'description' => 'Achieve Band 7 equivalent in reading'),
            
            // Trophies (15 total)
            array('id' => 'course_complete', 'name' => 'Course Complete', 'type' => 'trophy', 'description' => 'Finish your first course'),
            array('id' => 'three_courses', 'name' => 'Triple Threat', 'type' => 'trophy', 'description' => 'Complete 3 courses'),
            array('id' => 'all_courses', 'name' => 'Master Student', 'type' => 'trophy', 'description' => 'Complete all available courses'),
            array('id' => 'perfect_course', 'name' => 'Perfect Course', 'type' => 'trophy', 'description' => 'Complete a course with 100% on all exercises'),
            array('id' => 'reading_expert', 'name' => 'Reading Expert', 'type' => 'trophy', 'description' => 'Complete a full reading course'),
            array('id' => 'listening_expert', 'name' => 'Listening Expert', 'type' => 'trophy', 'description' => 'Complete a full listening course'),
            array('id' => 'writing_expert', 'name' => 'Writing Expert', 'type' => 'trophy', 'description' => 'Complete a full writing course'),
            array('id' => 'speaking_expert', 'name' => 'Speaking Expert', 'type' => 'trophy', 'description' => 'Complete a full speaking course'),
            array('id' => 'hundred_exercises', 'name' => 'Century Maker', 'type' => 'trophy', 'description' => 'Complete 100 exercises'),
            array('id' => 'dedicated_learner', 'name' => 'Dedicated Learner', 'type' => 'trophy', 'description' => 'Study for 60 days in a row'),
            array('id' => 'ielts_ready', 'name' => 'IELTS Ready', 'type' => 'trophy', 'description' => 'Complete all practice tests with Band 7+'),
            array('id' => 'overachiever', 'name' => 'Overachiever', 'type' => 'trophy', 'description' => 'Get 100% on 20 exercises'),
            array('id' => 'all_skills_master', 'name' => 'All Skills Master', 'type' => 'trophy', 'description' => 'Complete courses in all 4 skills'),
            array('id' => 'speed_master', 'name' => 'Speed Master', 'type' => 'trophy', 'description' => 'Complete 10 exercises in under 5 minutes each'),
            array('id' => 'ultimate_champion', 'name' => 'Ultimate Champion', 'type' => 'trophy', 'description' => 'Earn all other awards'),
        );
    }
    
    /**
     * Check if user has earned an award
     */
    public function has_award($user_id, $award_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->awards_table} WHERE user_id = %d AND award_id = %s",
            $user_id, $award_id
        ));
        
        return !empty($result);
    }
    
    /**
     * Grant an award to a user
     */
    public function grant_award($user_id, $award_id) {
        global $wpdb;
        
        // Check if already earned
        if ($this->has_award($user_id, $award_id)) {
            return false;
        }
        
        // Insert award
        $result = $wpdb->insert(
            $this->awards_table,
            array(
                'user_id' => $user_id,
                'award_id' => $award_id,
                'earned_date' => current_time('mysql')
            ),
            array('%d', '%s', '%s')
        );
        
        if ($result) {
            // Store for notification
            $user_new_awards = get_user_meta($user_id, '_ielts_cm_new_awards', true);
            if (!is_array($user_new_awards)) {
                $user_new_awards = array();
            }
            $user_new_awards[] = $award_id;
            update_user_meta($user_id, '_ielts_cm_new_awards', $user_new_awards);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get user's earned awards
     */
    public function get_user_awards($user_id) {
        global $wpdb;
        
        $awards = $wpdb->get_results($wpdb->prepare(
            "SELECT award_id, earned_date FROM {$this->awards_table} WHERE user_id = %d ORDER BY earned_date DESC",
            $user_id
        ), ARRAY_A);
        
        return $awards;
    }
    
    /**
     * Get user award stats
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        // Get counts of various completions
        $progress_table = $this->db->get_progress_table();
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        // Count completed pages
        $pages_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT resource_id) FROM $progress_table WHERE user_id = %d AND completed = 1 AND resource_id IS NOT NULL",
            $user_id
        ));
        
        // Count completed exercises
        $exercises_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT quiz_id) FROM $quiz_results_table WHERE user_id = %d",
            $user_id
        ));
        
        // Count perfect scores
        $perfect_scores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $quiz_results_table WHERE user_id = %d AND percentage >= 100",
            $user_id
        ));
        
        // Count completed lessons
        $lessons_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT lesson_id) FROM $progress_table WHERE user_id = %d AND completed = 1 AND resource_id IS NULL",
            $user_id
        ));
        
        // Count completed courses
        $courses_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT course_id) FROM $progress_table WHERE user_id = %d AND completed = 1",
            $user_id
        ));
        
        // Count high scores (90%+)
        $high_scores = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $quiz_results_table WHERE user_id = %d AND percentage >= 90",
            $user_id
        ));
        
        return array(
            'pages_completed' => intval($pages_completed),
            'exercises_completed' => intval($exercises_completed),
            'perfect_scores' => intval($perfect_scores),
            'lessons_completed' => intval($lessons_completed),
            'courses_completed' => intval($courses_completed),
            'high_scores' => intval($high_scores)
        );
    }
    
    /**
     * Check for first page completion
     */
    public function check_first_page_completion($user_id, $resource_id) {
        $stats = $this->get_user_stats($user_id);
        
        if ($stats['pages_completed'] == 1) {
            $this->grant_award($user_id, 'getting_started');
        }
    }
    
    /**
     * Check for quiz-related awards
     */
    public function check_quiz_awards($user_id, $quiz_id, $percentage, $submitted_time = null) {
        $stats = $this->get_user_stats($user_id);
        
        // First test
        if ($stats['exercises_completed'] == 1) {
            $this->grant_award($user_id, 'first_test');
        }
        
        // Exercise count milestones
        if ($stats['exercises_completed'] >= 5) {
            $this->grant_award($user_id, 'five_exercises');
        }
        if ($stats['exercises_completed'] >= 10) {
            $this->grant_award($user_id, 'ten_exercises');
        }
        if ($stats['exercises_completed'] >= 20) {
            $this->grant_award($user_id, 'twenty_exercises');
        }
        if ($stats['exercises_completed'] >= 50) {
            $this->grant_award($user_id, 'fifty_exercises');
        }
        if ($stats['exercises_completed'] >= 100) {
            $this->grant_award($user_id, 'hundred_exercises');
        }
        
        // Perfect score awards
        if ($percentage >= 100) {
            $this->grant_award($user_id, 'hundred_percent');
            
            if ($stats['perfect_scores'] == 1) {
                $this->grant_award($user_id, 'first_perfect');
            }
            if ($stats['perfect_scores'] >= 5) {
                $this->grant_award($user_id, 'five_perfect');
            }
            if ($stats['perfect_scores'] >= 20) {
                $this->grant_award($user_id, 'overachiever');
            }
        }
        
        // High scorer
        if ($stats['high_scores'] >= 10) {
            $this->grant_award($user_id, 'high_scorer');
        }
        
        // Time-based awards
        if ($submitted_time) {
            $hour = date('G', $submitted_time);
            if ($hour < 9) {
                $this->grant_award($user_id, 'early_bird');
            } elseif ($hour >= 21) {
                $this->grant_award($user_id, 'night_owl');
            }
        }
    }
    
    /**
     * Check for lesson completion awards
     */
    public function check_lesson_completion($user_id, $lesson_id) {
        $stats = $this->get_user_stats($user_id);
        
        // First lesson
        if ($stats['lessons_completed'] == 1) {
            $this->grant_award($user_id, 'first_lesson_done');
        }
        
        // Lesson milestones
        if ($stats['lessons_completed'] >= 5) {
            $this->grant_award($user_id, 'five_lessons');
        }
        if ($stats['lessons_completed'] >= 10) {
            $this->grant_award($user_id, 'ten_lessons');
        }
    }
    
    /**
     * Check for course completion awards
     */
    public function check_course_completion($user_id, $course_id) {
        $stats = $this->get_user_stats($user_id);
        
        // First course
        if ($stats['courses_completed'] == 1) {
            $this->grant_award($user_id, 'course_complete');
        }
        
        // Multiple courses
        if ($stats['courses_completed'] >= 3) {
            $this->grant_award($user_id, 'three_courses');
        }
    }
    
    /**
     * AJAX handler to get user awards
     */
    public function get_user_awards_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $earned_awards = $this->get_user_awards($user_id);
        $all_awards = $this->get_all_awards();
        $new_awards = get_user_meta($user_id, '_ielts_cm_new_awards', true);
        
        // Clear new awards after fetching
        if (!empty($new_awards)) {
            delete_user_meta($user_id, '_ielts_cm_new_awards');
        }
        
        wp_send_json_success(array(
            'earned' => $earned_awards,
            'all' => $all_awards,
            'new' => is_array($new_awards) ? $new_awards : array()
        ));
    }
}
