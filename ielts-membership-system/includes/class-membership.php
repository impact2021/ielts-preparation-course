<?php
/**
 * Membership management
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_MS_Membership {
    
    private $db;
    
    public function __construct() {
        $this->db = new IELTS_MS_Database();
        
        // Hook into IELTS Course Manager enrollment check
        add_filter('ielts_cm_has_course_access', array($this, 'check_course_access'), 10, 2);
    }
    
    /**
     * Create or update membership
     */
    public function create_membership($user_id, $duration_days, $payment_id = null) {
        global $wpdb;
        $table = IELTS_MS_Database::get_memberships_table();
        
        // Check for existing active membership
        $existing = $this->get_user_membership($user_id);
        
        $start_date = current_time('mysql');
        $base_date = $start_date; // Base date for calculating end date
        
        if ($existing && $existing->status === 'active' && strtotime($existing->end_date) > time()) {
            // Extend existing membership from current end date
            $base_date = $existing->end_date;
        }
        
        $end_date = date('Y-m-d H:i:s', strtotime($base_date . ' +' . $duration_days . ' days'));
        
        if ($existing) {
            // Update existing membership - keep original start_date
            $result = $wpdb->update(
                $table,
                array(
                    'status' => 'active',
                    'end_date' => $end_date,
                    'updated_date' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            $membership_id = $existing->id;
        } else {
            // Create new membership
            $result = $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'status' => 'active',
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ),
                array('%d', '%s', '%s', '%s')
            );
            $membership_id = $wpdb->insert_id;
        }
        
        // Update payment with membership ID
        if ($payment_id && $membership_id) {
            $payments_table = IELTS_MS_Database::get_payments_table();
            $wpdb->update(
                $payments_table,
                array('membership_id' => $membership_id),
                array('id' => $payment_id),
                array('%d'),
                array('%d')
            );
        }
        
        // Grant subscriber role if user doesn't have it
        $user = get_userdata($user_id);
        if ($user && !in_array('subscriber', $user->roles)) {
            $user->add_role('subscriber');
        }
        
        return $membership_id;
    }
    
    /**
     * Get user's membership
     */
    public function get_user_membership($user_id) {
        global $wpdb;
        $table = IELTS_MS_Database::get_memberships_table();
        
        $membership = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY end_date DESC LIMIT 1",
            $user_id
        ));
        
        return $membership;
    }
    
    /**
     * Check if user has active membership
     */
    public function has_active_membership($user_id) {
        $membership = $this->get_user_membership($user_id);
        
        if (!$membership) {
            return false;
        }
        
        // Check if membership is active and not expired
        return $membership->status === 'active' && strtotime($membership->end_date) > time();
    }
    
    /**
     * Check if membership is expired
     */
    public function is_expired($user_id) {
        $membership = $this->get_user_membership($user_id);
        
        if (!$membership) {
            return false;
        }
        
        return strtotime($membership->end_date) < time();
    }
    
    /**
     * Expire membership
     */
    public function expire_membership($user_id) {
        global $wpdb;
        $table = IELTS_MS_Database::get_memberships_table();
        
        return $wpdb->update(
            $table,
            array(
                'status' => 'expired',
                'updated_date' => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%s', '%s'),
            array('%d')
        );
    }
    
    /**
     * Get days remaining in membership
     */
    public function get_days_remaining($user_id) {
        $membership = $this->get_user_membership($user_id);
        
        if (!$membership || $membership->status !== 'active') {
            return 0;
        }
        
        $now = time();
        $end = strtotime($membership->end_date);
        
        if ($end < $now) {
            return 0;
        }
        
        return ceil(($end - $now) / (60 * 60 * 24));
    }
    
    /**
     * Hook into IELTS Course Manager to check access
     */
    public function check_course_access($has_access, $user_id) {
        // If already has access (admin, etc), don't override
        if ($has_access) {
            return $has_access;
        }
        
        // Check if user has active membership
        return $this->has_active_membership($user_id);
    }
    
    /**
     * Get all memberships (for admin)
     */
    public function get_all_memberships($status = null, $limit = 100, $offset = 0) {
        global $wpdb;
        $table = IELTS_MS_Database::get_memberships_table();
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $memberships = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY created_date DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ));
        
        return $memberships;
    }
}
