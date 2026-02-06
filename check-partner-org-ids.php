<?php
/**
 * Diagnostic script to check partner admin organization IDs
 * Run this from WordPress admin or via WP-CLI to diagnose partner admin visibility issues
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('You must be an administrator to run this script.');
}

echo "<h2>Partner Admin Organization ID Diagnostic</h2>\n";

// Get all partner admins
$partner_admins = get_users(array(
    'role' => 'partner_admin'
));

echo "<h3>Partner Admins Found: " . count($partner_admins) . "</h3>\n";

if (empty($partner_admins)) {
    echo "<p>No partner admins found.</p>\n";
} else {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Custom Org ID</th><th>Effective Org ID</th></tr>\n";
    
    foreach ($partner_admins as $admin) {
        $custom_org_id = get_user_meta($admin->ID, 'iw_partner_organization_id', true);
        $effective_org_id = empty($custom_org_id) ? '1 (SITE_PARTNER_ORG_ID - default)' : $custom_org_id . ' (custom)';
        
        echo "<tr>";
        echo "<td>" . esc_html($admin->ID) . "</td>";
        echo "<td>" . esc_html($admin->user_login) . "</td>";
        echo "<td>" . esc_html($admin->user_email) . "</td>";
        echo "<td>" . (empty($custom_org_id) ? '<em>none</em>' : esc_html($custom_org_id)) . "</td>";
        echo "<td>" . esc_html($effective_org_id) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

// Check migration status
echo "<h3>Migration Status</h3>\n";
$migration_v1 = get_option('iw_partner_site_org_migration_done', false);
$migration_v2 = get_option('iw_partner_site_org_migration_v2_done', false);

echo "<p>V1 Migration (old): " . ($migration_v1 ? 'Complete' : 'Not run') . "</p>\n";
echo "<p>V2 Migration (new): " . ($migration_v2 ? 'Complete' : 'Not run') . "</p>\n";

// Check for users with partner org meta
global $wpdb;
$users_with_org_meta = $wpdb->get_results("
    SELECT meta_value as org_id, COUNT(*) as user_count
    FROM {$wpdb->usermeta} 
    WHERE meta_key = 'iw_created_by_partner'
    GROUP BY meta_value
");

echo "<h3>Users Created By Partner Org (grouped by org_id)</h3>\n";
if (empty($users_with_org_meta)) {
    echo "<p>No users found with iw_created_by_partner meta.</p>\n";
} else {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Org ID</th><th>User Count</th></tr>\n";
    
    foreach ($users_with_org_meta as $row) {
        $org_id = $row->org_id;
        $org_display = $org_id;
        if ($org_id == '0') {
            $org_display .= ' (ADMIN_ORG_ID)';
        } elseif ($org_id == '1') {
            $org_display .= ' (SITE_PARTNER_ORG_ID - correct)';
        } else {
            $org_display .= ' (custom/user ID - needs migration)';
        }
        
        echo "<tr>";
        echo "<td>" . esc_html($org_display) . "</td>";
        echo "<td>" . esc_html($row->user_count) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

// Check for codes
$codes_by_org = $wpdb->get_results("
    SELECT created_by, COUNT(*) as code_count
    FROM {$wpdb->prefix}ielts_cm_access_codes
    GROUP BY created_by
");

echo "<h3>Access Codes By Creator Org ID</h3>\n";
if (empty($codes_by_org)) {
    echo "<p>No access codes found.</p>\n";
} else {
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Org ID</th><th>Code Count</th></tr>\n";
    
    foreach ($codes_by_org as $row) {
        $org_id = $row->created_by;
        $org_display = $org_id;
        if ($org_id == '0') {
            $org_display .= ' (ADMIN_ORG_ID)';
        } elseif ($org_id == '1') {
            $org_display .= ' (SITE_PARTNER_ORG_ID - correct)';
        } else {
            $org_display .= ' (custom/user ID - needs migration)';
        }
        
        echo "<tr>";
        echo "<td>" . esc_html($org_display) . "</td>";
        echo "<td>" . esc_html($row->code_count) . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

echo "<h3>Recommendation</h3>\n";
if (!$migration_v2) {
    echo "<p><strong>The V2 migration has NOT run yet.</strong> To trigger it, visit any WordPress admin page as a site administrator.</p>\n";
} else {
    echo "<p>The V2 migration has completed. If partner admins still can't see each other's data, check if they have custom org IDs set above.</p>\n";
}
