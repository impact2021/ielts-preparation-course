# User Tour Admin Controls - Enable/Disable Tours

This guide shows how to add admin controls to turn user tours on/off globally or per membership type.

## 🎯 Overview

You can add admin settings to:
- ✅ **Turn tours completely on/off** globally
- ✅ **Disable tours for specific membership types** (Academic, General, English)
- ✅ **Temporarily disable during site maintenance**
- ✅ **Re-enable tours for all users** (force everyone to see it again)

---

## 🚀 Quick Implementation (3 Options)

### Option 1: Simple Global On/Off Switch (Easiest - 10 minutes)

Add a single checkbox in WordPress admin to enable/disable all tours.

### Option 2: Per-Membership Control (Moderate - 20 minutes)

Add separate controls for Academic, General Training, and English tours.

### Option 3: Advanced Controls (30 minutes)

Full admin panel with reset options, analytics, and per-membership settings.

---

## 📝 Option 1: Simple Global On/Off Switch

### Step 1: Add Admin Setting

Add to `includes/frontend/class-frontend.php`:

```php
/**
 * Register tour settings
 */
public function register_tour_settings() {
    // Register setting
    register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled', array(
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));
    
    // Add settings section
    add_settings_section(
        'ielts_cm_tour_section',
        __('User Tour Settings', 'ielts-course-manager'),
        array($this, 'tour_settings_section_callback'),
        'ielts_cm_tour_settings'
    );
    
    // Add enable/disable field
    add_settings_field(
        'ielts_cm_tour_enabled',
        __('Enable User Tours', 'ielts-course-manager'),
        array($this, 'tour_enabled_field_callback'),
        'ielts_cm_tour_settings',
        'ielts_cm_tour_section'
    );
}

/**
 * Settings section description
 */
public function tour_settings_section_callback() {
    echo '<p>' . __('Control whether first-time user tours are shown to new members.', 'ielts-course-manager') . '</p>';
}

/**
 * Enable/disable field
 */
public function tour_enabled_field_callback() {
    $enabled = get_option('ielts_cm_tour_enabled', true);
    ?>
    <label>
        <input type="checkbox" 
               name="ielts_cm_tour_enabled" 
               value="1" 
               <?php checked($enabled, true); ?> />
        <?php _e('Show guided tours to first-time users', 'ielts-course-manager'); ?>
    </label>
    <p class="description">
        <?php _e('Uncheck to disable all user tours. Users will not see the tour even if they haven\'t completed it.', 'ielts-course-manager'); ?>
    </p>
    <?php
}

/**
 * Add admin menu page
 */
public function add_tour_admin_menu() {
    add_options_page(
        __('User Tour Settings', 'ielts-course-manager'),
        __('User Tours', 'ielts-course-manager'),
        'manage_options',
        'ielts-tour-settings',
        array($this, 'render_tour_settings_page')
    );
}

/**
 * Render settings page
 */
public function render_tour_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ielts_cm_tour_settings');
            do_settings_sections('ielts_cm_tour_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Initialize tour admin
 */
public function init() {
    // ... existing code ...
    
    // Register tour settings
    add_action('admin_init', array($this, 'register_tour_settings'));
    add_action('admin_menu', array($this, 'add_tour_admin_menu'));
}
```

### Step 2: Check Setting Before Loading Tour

Update the `enqueue_scripts()` method:

```php
public function enqueue_scripts() {
    // ... existing code ...
    
    // User tour for first-time users
    if (is_user_logged_in()) {
        // CHECK IF TOURS ARE ENABLED (NEW!)
        $tours_enabled = get_option('ielts_cm_tour_enabled', true);
        
        // Don't load tour if disabled by admin
        if (!$tours_enabled) {
            return;  // Exit early - no tour
        }
        
        // ... rest of tour loading code ...
        $user_id = get_current_user_id();
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        // ... etc
    }
}
```

### Step 3: Access Admin Settings

**Navigate to**: WordPress Admin → Settings → User Tours

**You'll see**: Checkbox to enable/disable tours globally

**Effect**: When unchecked, NO tours will load for ANY users, even if they haven't seen it.

---

## 📊 Option 2: Per-Membership Type Controls

### Add Settings for Each Membership

```php
public function register_tour_settings() {
    // Register settings
    register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_academic', array(
        'type' => 'boolean',
        'default' => true
    ));
    register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_general', array(
        'type' => 'boolean',
        'default' => true
    ));
    register_setting('ielts_cm_tour_settings', 'ielts_cm_tour_enabled_english', array(
        'type' => 'boolean',
        'default' => true
    ));
    
    // Add settings section
    add_settings_section(
        'ielts_cm_tour_section',
        __('User Tour Settings', 'ielts-course-manager'),
        array($this, 'tour_settings_section_callback'),
        'ielts_cm_tour_settings'
    );
    
    // Academic tour field
    add_settings_field(
        'ielts_cm_tour_enabled_academic',
        __('Academic Module Tour', 'ielts-course-manager'),
        array($this, 'tour_enabled_academic_callback'),
        'ielts_cm_tour_settings',
        'ielts_cm_tour_section'
    );
    
    // General Training tour field
    add_settings_field(
        'ielts_cm_tour_enabled_general',
        __('General Training Tour', 'ielts-course-manager'),
        array($this, 'tour_enabled_general_callback'),
        'ielts_cm_tour_settings',
        'ielts_cm_tour_section'
    );
    
    // English tour field
    add_settings_field(
        'ielts_cm_tour_enabled_english',
        __('English Only Tour', 'ielts-course-manager'),
        array($this, 'tour_enabled_english_callback'),
        'ielts_cm_tour_settings',
        'ielts_cm_tour_section'
    );
}

public function tour_enabled_academic_callback() {
    $enabled = get_option('ielts_cm_tour_enabled_academic', true);
    ?>
    <label>
        <input type="checkbox" name="ielts_cm_tour_enabled_academic" value="1" <?php checked($enabled, true); ?> />
        <?php _e('Show tour for Academic module members', 'ielts-course-manager'); ?>
    </label>
    <?php
}

public function tour_enabled_general_callback() {
    $enabled = get_option('ielts_cm_tour_enabled_general', true);
    ?>
    <label>
        <input type="checkbox" name="ielts_cm_tour_enabled_general" value="1" <?php checked($enabled, true); ?> />
        <?php _e('Show tour for General Training members', 'ielts-course-manager'); ?>
    </label>
    <?php
}

public function tour_enabled_english_callback() {
    $enabled = get_option('ielts_cm_tour_enabled_english', true);
    ?>
    <label>
        <input type="checkbox" name="ielts_cm_tour_enabled_english" value="1" <?php checked($enabled, true); ?> />
        <?php _e('Show tour for English-only members', 'ielts-course-manager'); ?>
    </label>
    <?php
}
```

### Check Per-Membership Setting

```php
public function enqueue_scripts() {
    // ... existing code ...
    
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        
        // Determine tour type
        $tour_type = '';
        if (strpos($membership_type, 'academic') !== false) {
            $tour_type = 'academic';
        } elseif (strpos($membership_type, 'general') !== false) {
            $tour_type = 'general';
        } elseif (strpos($membership_type, 'english') !== false) {
            $tour_type = 'english';
        }
        
        // CHECK IF THIS SPECIFIC TOUR TYPE IS ENABLED (NEW!)
        $tour_enabled = get_option('ielts_cm_tour_enabled_' . $tour_type, true);
        
        if (!$tour_enabled) {
            return;  // Exit - this tour type is disabled
        }
        
        // ... rest of tour loading code ...
    }
}
```

---

## 🎛️ Option 3: Advanced Admin Panel with Reset

### Complete Admin Interface

```php
/**
 * Render advanced settings page
 */
public function render_tour_settings_page() {
    // Handle reset actions
    if (isset($_POST['reset_all_tours']) && check_admin_referer('reset_tours_nonce')) {
        $this->reset_all_user_tours();
        echo '<div class="notice notice-success"><p>' . __('All user tours have been reset!', 'ielts-course-manager') . '</p></div>';
    }
    
    if (isset($_POST['reset_academic_tours']) && check_admin_referer('reset_tours_nonce')) {
        $this->reset_tours_by_type('academic');
        echo '<div class="notice notice-success"><p>' . __('Academic tours reset!', 'ielts-course-manager') . '</p></div>';
    }
    
    // Get tour statistics
    $stats = $this->get_tour_statistics();
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <!-- Enable/Disable Settings -->
        <div class="card">
            <h2><?php _e('Tour Controls', 'ielts-course-manager'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('ielts_cm_tour_settings');
                do_settings_sections('ielts_cm_tour_settings');
                submit_button(__('Save Settings', 'ielts-course-manager'));
                ?>
            </form>
        </div>
        
        <!-- Tour Statistics -->
        <div class="card">
            <h2><?php _e('Tour Statistics', 'ielts-course-manager'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Tour Type', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Completed', 'ielts-course-manager'); ?></th>
                        <th><?php _e('Status', 'ielts-course-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('Academic', 'ielts-course-manager'); ?></td>
                        <td><?php echo esc_html($stats['academic']); ?></td>
                        <td>
                            <?php echo get_option('ielts_cm_tour_enabled_academic', true) 
                                ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('General Training', 'ielts-course-manager'); ?></td>
                        <td><?php echo esc_html($stats['general']); ?></td>
                        <td>
                            <?php echo get_option('ielts_cm_tour_enabled_general', true) 
                                ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('English Only', 'ielts-course-manager'); ?></td>
                        <td><?php echo esc_html($stats['english']); ?></td>
                        <td>
                            <?php echo get_option('ielts_cm_tour_enabled_english', true) 
                                ? '<span style="color: green;">●</span> ' . __('Enabled', 'ielts-course-manager')
                                : '<span style="color: red;">●</span> ' . __('Disabled', 'ielts-course-manager'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Reset Options -->
        <div class="card">
            <h2><?php _e('Reset Tours', 'ielts-course-manager'); ?></h2>
            <p><?php _e('Force users to see tours again by resetting completion status.', 'ielts-course-manager'); ?></p>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('reset_tours_nonce'); ?>
                <button type="submit" 
                        name="reset_all_tours" 
                        class="button button-secondary"
                        onclick="return confirm('<?php _e('Reset ALL user tours? All users will see tours again.', 'ielts-course-manager'); ?>');">
                    <?php _e('Reset All Tours', 'ielts-course-manager'); ?>
                </button>
            </form>
            
            <form method="post" style="display: inline; margin-left: 10px;">
                <?php wp_nonce_field('reset_tours_nonce'); ?>
                <button type="submit" 
                        name="reset_academic_tours" 
                        class="button button-secondary"
                        onclick="return confirm('<?php _e('Reset Academic tours only?', 'ielts-course-manager'); ?>');">
                    <?php _e('Reset Academic Tours', 'ielts-course-manager'); ?>
                </button>
            </form>
            
            <form method="post" style="display: inline; margin-left: 10px;">
                <?php wp_nonce_field('reset_tours_nonce'); ?>
                <button type="submit" 
                        name="reset_general_tours" 
                        class="button button-secondary"
                        onclick="return confirm('<?php _e('Reset General Training tours?', 'ielts-course-manager'); ?>');">
                    <?php _e('Reset General Tours', 'ielts-course-manager'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Get tour completion statistics
 */
private function get_tour_statistics() {
    global $wpdb;
    
    $stats = array(
        'academic' => 0,
        'general' => 0,
        'english' => 0
    );
    
    // Count users who completed each tour type
    $stats['academic'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
         WHERE meta_key = 'ielts_tour_completed_academic' AND meta_value = '1'"
    );
    
    $stats['general'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
         WHERE meta_key = 'ielts_tour_completed_general' AND meta_value = '1'"
    );
    
    $stats['english'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
         WHERE meta_key = 'ielts_tour_completed_english' AND meta_value = '1'"
    );
    
    return $stats;
}

/**
 * Reset all user tours
 */
private function reset_all_user_tours() {
    global $wpdb;
    
    // Delete all tour completion meta
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");
}

/**
 * Reset tours by type
 */
private function reset_tours_by_type($type) {
    global $wpdb;
    
    $meta_key = 'ielts_tour_completed_' . sanitize_key($type);
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $meta_key
    ));
}
```

---

## 🔧 Quick Disable Methods

### Method 1: Via WordPress Admin (Recommended)

1. Navigate to **Settings → User Tours**
2. Uncheck **"Show guided tours to first-time users"**
3. Click **Save Changes**
4. Tours are now disabled for all users

### Method 2: Via wp-config.php (Emergency)

Add to `wp-config.php`:

```php
// Disable user tours globally
define('IELTS_TOURS_DISABLED', true);
```

Then update enqueue logic:

```php
public function enqueue_scripts() {
    // Emergency disable via constant
    if (defined('IELTS_TOURS_DISABLED') && IELTS_TOURS_DISABLED) {
        return;
    }
    
    // ... rest of tour code ...
}
```

### Method 3: Via Database (Direct)

Run in phpMyAdmin or WP-CLI:

```sql
-- Disable tours via options table
UPDATE wp_options 
SET option_value = '0' 
WHERE option_name = 'ielts_cm_tour_enabled';
```

Or via WP-CLI:

```bash
wp option update ielts_cm_tour_enabled 0
```

### Method 4: Temporarily via Code

```php
// In class-frontend.php
public function enqueue_scripts() {
    // Temporary disable during testing/maintenance
    if (true) {  // Change to false to re-enable
        return;
    }
    
    // ... tour code ...
}
```

---

## 📋 Use Cases

### Use Case 1: Disable During Site Maintenance

```php
// Disable tours during major updates
update_option('ielts_cm_tour_enabled', false);

// Do maintenance...

// Re-enable after maintenance
update_option('ielts_cm_tour_enabled', true);
```

### Use Case 2: Disable Specific Membership Tour

```php
// Academic tour getting too many complaints
update_option('ielts_cm_tour_enabled_academic', false);

// Fix tour content...

// Re-enable when fixed
update_option('ielts_cm_tour_enabled_academic', true);
```

### Use Case 3: Force All Users to See Updated Tour

```php
// Delete all completion records
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");

// All users will see tour again on next login
```

### Use Case 4: A/B Testing Tours

```php
// Show tour to 50% of users
public function enqueue_scripts() {
    // ... existing checks ...
    
    // A/B test - only show to 50% of users
    if (rand(1, 100) > 50) {
        return;
    }
    
    // Load tour for the lucky 50%
}
```

---

## 🎯 Admin Interface Screenshot (What You'll See)

```
┌────────────────────────────────────────────┐
│ User Tour Settings                         │
├────────────────────────────────────────────┤
│                                            │
│ Tour Controls                              │
│ ──────────────────────────────────────    │
│                                            │
│ ☑ Academic Module Tour                    │
│   Show tour for Academic module members    │
│                                            │
│ ☑ General Training Tour                   │
│   Show tour for General Training members   │
│                                            │
│ ☑ English Only Tour                        │
│   Show tour for English-only members       │
│                                            │
│ [Save Settings]                            │
│                                            │
│ ────────────────────────────────────────  │
│                                            │
│ Tour Statistics                            │
│ ──────────────────────────────────────    │
│                                            │
│ Tour Type         │ Completed │ Status    │
│ ─────────────────│───────────│──────────  │
│ Academic          │    45     │ ● Enabled │
│ General Training  │    32     │ ● Enabled │
│ English Only      │    18     │ ● Enabled │
│                                            │
│ ────────────────────────────────────────  │
│                                            │
│ Reset Tours                                │
│ ──────────────────────────────────────    │
│ Force users to see tours again             │
│                                            │
│ [Reset All Tours]  [Reset Academic]        │
│                    [Reset General]         │
│                                            │
└────────────────────────────────────────────┘
```

---

## 🔒 Security Considerations

### Capability Checks

Always verify admin permissions:

```php
public function render_tour_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // ... rest of code ...
}
```

### Nonce Verification

For reset actions:

```php
if (isset($_POST['reset_all_tours'])) {
    // Verify nonce
    if (!check_admin_referer('reset_tours_nonce')) {
        wp_die(__('Security check failed'));
    }
    
    $this->reset_all_user_tours();
}
```

---

## 💡 Best Practices

### 1. Don't Delete - Disable Instead

```php
// ✅ Good - Preserve data
update_option('ielts_cm_tour_enabled', false);

// ❌ Bad - Lose analytics
delete_option('ielts_cm_tour_enabled');
```

### 2. Log Admin Actions

```php
private function reset_all_user_tours() {
    global $wpdb;
    
    // Count before reset
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");
    
    // Reset
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");
    
    // Log action
    error_log("IELTS Tours: Admin reset {$count} tour completions");
}
```

### 3. Provide User Feedback

```php
// Show success message
add_settings_error(
    'ielts_cm_tour_settings',
    'tours_disabled',
    __('Tours have been disabled successfully.', 'ielts-course-manager'),
    'success'
);
```

---

## 🚀 Quick Implementation Checklist

### For Simple Global On/Off:

- [ ] Add `register_tour_settings()` method
- [ ] Add `render_tour_settings_page()` method
- [ ] Add `add_tour_admin_menu()` method
- [ ] Hook into `admin_init` and `admin_menu`
- [ ] Update `enqueue_scripts()` to check setting
- [ ] Test: Disable tours via admin
- [ ] Test: Re-enable tours via admin

### For Per-Membership Controls:

- [ ] Add settings for academic, general, english
- [ ] Create callback for each tour type
- [ ] Update enqueue logic to check specific tour
- [ ] Test each tour type independently

### For Advanced Panel:

- [ ] Add statistics display
- [ ] Add reset functionality
- [ ] Add nonce verification
- [ ] Style admin interface
- [ ] Test all reset options

---

## 📊 Monitoring Tour Usage

### Track Disabled State

```php
// When tours are disabled, log it
if (!$tours_enabled) {
    error_log('IELTS Tours: Tours disabled by admin, not loading for user ' . $user_id);
    return;
}
```

### Monitor Re-Enable Impact

```php
// After re-enabling, track new completions
add_action('wp_ajax_ielts_complete_tour', function() {
    // ... existing code ...
    
    // Log completion
    error_log("IELTS Tours: User {$user_id} completed {$tour_type} tour");
});
```

---

## ✅ Testing Checklist

### Test Disable Functionality:

- [ ] Disable tours in admin
- [ ] Login as new user → No tour shows
- [ ] Check no JavaScript errors in console
- [ ] Verify Shepherd.js not loaded (Network tab)

### Test Re-Enable:

- [ ] Re-enable tours in admin
- [ ] Clear user tour completion meta
- [ ] Login → Tour shows correctly
- [ ] Complete tour → Saves properly

### Test Per-Membership:

- [ ] Disable Academic tour only
- [ ] Login as Academic user → No tour
- [ ] Login as General user → Tour shows
- [ ] Verify correct tour content

### Test Reset:

- [ ] Complete a tour
- [ ] Click "Reset All Tours"
- [ ] Reload page → Tour shows again
- [ ] Verify database meta deleted

---

## 🎓 Example Admin Workflow

### Scenario: Updating Tour Content

```
1. Admin decides to update Academic tour
   ↓
2. Navigate to Settings → User Tours
   ↓
3. Uncheck "Academic Module Tour"
   ↓
4. Click "Save Settings"
   ↓
5. Update tour content in user-tour.js
   ↓
6. Test updated tour on staging
   ↓
7. Deploy to production
   ↓
8. Click "Reset Academic Tours" button
   ↓
9. Re-check "Academic Module Tour"
   ↓
10. Click "Save Settings"
    ↓
11. All Academic users see updated tour! ✅
```

---

## 🔍 Troubleshooting

### Tours Not Disabling

**Check:**
1. Option value in database: `SELECT * FROM wp_options WHERE option_name = 'ielts_cm_tour_enabled'`
2. Enqueue logic is checking the option
3. No caching plugins interfering
4. Clear browser localStorage

**Fix:**
```php
// Force disable via code
return; // Add at top of enqueue_scripts()
```

### Can't Access Admin Page

**Check:**
1. User has `manage_options` capability
2. Menu hook registered correctly
3. WordPress admin menu refreshed

**Fix:**
```php
// Check current user capabilities
if (current_user_can('manage_options')) {
    echo 'You have access!';
}
```

### Reset Not Working

**Check:**
1. Nonce verification passing
2. Database permissions
3. Meta key names match

**Fix:**
```php
// Manual database reset
global $wpdb;
$deleted = $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ielts_tour_completed_%'");
error_log("Deleted {$deleted} tour completion records");
```

---

## 📚 Summary

### Quick Answers:

**Q: Can I turn tours off?**  
A: Yes! Add admin settings to enable/disable globally or per membership.

**Q: How to disable temporarily?**  
A: Uncheck option in Settings → User Tours, or add constant to wp-config.php

**Q: How to re-enable for all users?**  
A: Use the "Reset All Tours" button in admin panel

**Q: Per-membership control?**  
A: Yes! Separate settings for Academic, General, English

**Q: Emergency disable?**  
A: Add `define('IELTS_TOURS_DISABLED', true);` to wp-config.php

---

## 🔗 Related Documentation

- [USER_TOUR_MEMBERSHIP_SPECIFIC.md](USER_TOUR_MEMBERSHIP_SPECIFIC.md) - Membership-specific tours
- [USER_TOUR_QUICK_START.md](USER_TOUR_QUICK_START.md) - Basic implementation
- [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md) - Complete reference

---

## ✨ Final Implementation Code

### Minimal Code (Option 1 - Global On/Off):

**File: `includes/frontend/class-frontend.php`**

```php
// Add to init() method:
add_action('admin_init', array($this, 'register_tour_settings'));
add_action('admin_menu', array($this, 'add_tour_admin_menu'));

// Add new methods:
public function register_tour_settings() { /* code above */ }
public function add_tour_admin_menu() { /* code above */ }
public function render_tour_settings_page() { /* code above */ }

// Update enqueue_scripts():
if (is_user_logged_in()) {
    if (!get_option('ielts_cm_tour_enabled', true)) {
        return;  // Tours disabled!
    }
    // ... rest of tour code
}
```

**Time to implement**: 10-15 minutes  
**Lines of code**: ~50 lines  
**Complexity**: Easy

---

**Ready to implement?** Start with Option 1 for quick global control! 🎯
