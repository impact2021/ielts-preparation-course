<?php
/**
 * Gamification features - Progress Rings and Skills Radar
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_Gamification {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_CM_Database();
        
        // AJAX handlers
        add_action('wp_ajax_ielts_cm_get_progress_rings_data', array($this, 'get_progress_rings_data_ajax'));
        add_action('wp_ajax_ielts_cm_get_skills_radar_data', array($this, 'get_skills_radar_data_ajax'));
        add_action('wp_ajax_ielts_cm_update_target_band', array($this, 'update_target_band_ajax'));
    }
    
    /**
     * Get daily progress data for progress rings
     */
    public function get_daily_progress($user_id) {
        global $wpdb;
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');
        
        // Get user's daily goals (from user meta or defaults)
        $daily_exercise_goal = get_user_meta($user_id, '_ielts_cm_daily_exercise_goal', true);
        if (!$daily_exercise_goal) $daily_exercise_goal = 5;
        
        $daily_time_goal = get_user_meta($user_id, '_ielts_cm_daily_time_goal', true);
        if (!$daily_time_goal) $daily_time_goal = 30; // minutes
        
        $daily_perfect_goal = get_user_meta($user_id, '_ielts_cm_daily_perfect_goal', true);
        if (!$daily_perfect_goal) $daily_perfect_goal = 2;
        
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
        
        // Calculate study time today (approximate based on quiz submissions)
        // Estimate 10 minutes per exercise
        $study_time_today = intval($exercises_today) * 10;
        
        // Get streak count
        $streak_days = $this->get_streak_days($user_id);
        
        return array(
            'exercises_today' => intval($exercises_today),
            'daily_exercise_goal' => intval($daily_exercise_goal),
            'perfect_scores_today' => intval($perfect_scores_today),
            'daily_perfect_goal' => intval($daily_perfect_goal),
            'study_time_today' => intval($study_time_today),
            'daily_time_goal' => intval($daily_time_goal),
            'streak_days' => intval($streak_days),
        );
    }
    
    /**
     * Get weekly progress data
     */
    public function get_weekly_progress($user_id) {
        global $wpdb;
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        $week_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $week_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        
        // Get user's weekly goals
        $weekly_exercise_goal = get_user_meta($user_id, '_ielts_cm_weekly_exercise_goal', true);
        if (!$weekly_exercise_goal) $weekly_exercise_goal = 25;
        
        $weekly_time_goal = get_user_meta($user_id, '_ielts_cm_weekly_time_goal', true);
        if (!$weekly_time_goal) $weekly_time_goal = 180; // 3 hours
        
        $weekly_perfect_goal = get_user_meta($user_id, '_ielts_cm_weekly_perfect_goal', true);
        if (!$weekly_perfect_goal) $weekly_perfect_goal = 10;
        
        $exercises_week = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$quiz_results_table} 
             WHERE user_id = %d 
             AND submitted_date BETWEEN %s AND %s",
            $user_id, $week_start, $week_end
        ));
        
        $perfect_scores_week = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$quiz_results_table} 
             WHERE user_id = %d 
             AND percentage >= 100
             AND submitted_date BETWEEN %s AND %s",
            $user_id, $week_start, $week_end
        ));
        
        $study_time_week = intval($exercises_week) * 10;
        
        return array(
            'exercises_week' => intval($exercises_week),
            'weekly_exercise_goal' => intval($weekly_exercise_goal),
            'perfect_scores_week' => intval($perfect_scores_week),
            'weekly_perfect_goal' => intval($weekly_perfect_goal),
            'study_time_week' => intval($study_time_week),
            'weekly_time_goal' => intval($weekly_time_goal),
        );
    }
    
    /**
     * Get monthly progress data
     */
    public function get_monthly_progress($user_id) {
        global $wpdb;
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        $month_start = date('Y-m-01 00:00:00');
        $month_end = date('Y-m-t 23:59:59');
        
        $monthly_exercise_goal = get_user_meta($user_id, '_ielts_cm_monthly_exercise_goal', true);
        if (!$monthly_exercise_goal) $monthly_exercise_goal = 100;
        
        $monthly_time_goal = get_user_meta($user_id, '_ielts_cm_monthly_time_goal', true);
        if (!$monthly_time_goal) $monthly_time_goal = 720; // 12 hours
        
        $monthly_perfect_goal = get_user_meta($user_id, '_ielts_cm_monthly_perfect_goal', true);
        if (!$monthly_perfect_goal) $monthly_perfect_goal = 40;
        
        $exercises_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$quiz_results_table} 
             WHERE user_id = %d 
             AND submitted_date BETWEEN %s AND %s",
            $user_id, $month_start, $month_end
        ));
        
        $perfect_scores_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$quiz_results_table} 
             WHERE user_id = %d 
             AND percentage >= 100
             AND submitted_date BETWEEN %s AND %s",
            $user_id, $month_start, $month_end
        ));
        
        $study_time_month = intval($exercises_month) * 10;
        
        return array(
            'exercises_month' => intval($exercises_month),
            'monthly_exercise_goal' => intval($monthly_exercise_goal),
            'perfect_scores_month' => intval($perfect_scores_month),
            'monthly_perfect_goal' => intval($monthly_perfect_goal),
            'study_time_month' => intval($study_time_month),
            'monthly_time_goal' => intval($monthly_time_goal),
        );
    }
    
    /**
     * Get user's skills scores for radar chart
     */
    public function get_user_skill_scores($user_id) {
        global $wpdb;
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        $skills = array('reading', 'writing', 'listening', 'speaking', 'vocabulary', 'grammar');
        $skill_scores = array();
        
        foreach ($skills as $skill) {
            // Get all quizzes with this skill type
            $quiz_ids = get_posts(array(
                'post_type' => 'ielts_quiz',
                'meta_query' => array(
                    array(
                        'key' => '_ielts_cm_skill_type',
                        'value' => $skill,
                        'compare' => '='
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
    
    /**
     * Calculate streak days
     */
    private function get_streak_days($user_id) {
        global $wpdb;
        $quiz_results_table = $this->db->get_quiz_results_table();
        
        // Get distinct days with activity
        $query = $wpdb->prepare(
            "SELECT DISTINCT DATE(submitted_date) as activity_date 
             FROM {$quiz_results_table} 
             WHERE user_id = %d 
             ORDER BY activity_date DESC 
             LIMIT 365",
            $user_id
        );
        
        $activity_dates = $wpdb->get_col($query);
        
        if (empty($activity_dates)) {
            return 0;
        }
        
        $streak = 0;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Check if there's activity today or yesterday to start counting
        if ($activity_dates[0] !== $today && $activity_dates[0] !== $yesterday) {
            return 0;
        }
        
        $expected_date = $today;
        foreach ($activity_dates as $date) {
            if ($date === $expected_date) {
                $streak++;
                $expected_date = date('Y-m-d', strtotime($expected_date . ' -1 day'));
            } else if ($date === date('Y-m-d', strtotime($expected_date . ' -1 day'))) {
                // Allow for same day if we started from yesterday
                $expected_date = $date;
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    /**
     * AJAX handler for progress rings data
     */
    public function get_progress_rings_data_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $view = isset($_POST['view']) ? sanitize_text_field($_POST['view']) : 'daily';
        
        if ($view === 'daily') {
            $data = $this->get_daily_progress($user_id);
        } elseif ($view === 'weekly') {
            $data = $this->get_weekly_progress($user_id);
        } elseif ($view === 'monthly') {
            $data = $this->get_monthly_progress($user_id);
        } else {
            $data = $this->get_daily_progress($user_id);
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for skills radar data
     */
    public function get_skills_radar_data_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $skill_scores = $this->get_user_skill_scores($user_id);
        
        // Get user's target band (default to 7.0 if not set)
        $target_band = get_user_meta($user_id, '_ielts_cm_target_band', true);
        if (!$target_band) {
            $target_band = 7.0;
        }
        
        wp_send_json_success(array(
            'skill_scores' => $skill_scores,
            'target_band' => floatval($target_band)
        ));
    }
    
    /**
     * AJAX handler for updating target band
     */
    public function update_target_band_ajax() {
        check_ajax_referer('ielts_cm_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not logged in'));
        }
        
        $target_band = isset($_POST['target_band']) ? floatval($_POST['target_band']) : 7.0;
        
        // Validate target band (must be between 5.5 and 9.0 in 0.5 increments)
        if ($target_band < 5.5 || $target_band > 9.0) {
            wp_send_json_error(array('message' => 'Target band must be between 5.5 and 9.0'));
        }
        
        // Check if it's a valid half-band increment
        $decimal_part = ($target_band * 10) % 10;
        if ($decimal_part != 0 && $decimal_part != 5) {
            wp_send_json_error(array('message' => 'Target band must be in 0.5 increments'));
        }
        
        update_user_meta($user_id, '_ielts_cm_target_band', $target_band);
        
        wp_send_json_success(array(
            'target_band' => $target_band,
            'message' => 'Target band updated successfully'
        ));
    }
}
