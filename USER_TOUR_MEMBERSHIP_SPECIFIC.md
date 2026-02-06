# Membership-Specific User Tours

This guide shows how to create different tours for different membership types (General Training, Academic, English) and save completion to the user's database account for cross-device persistence.

## 🎯 Overview

**Goal**: Show different tour content based on user's membership type
- Academic members see Academic-specific tour
- General Training members see General Training tour  
- English-only members see English-only tour

**Persistence**: Tour completion saved to WordPress database, so users don't see the tour again even when logging in from different devices.

---

## 🔑 Understanding Membership Types

Based on your IELTS Course Manager, these are the membership types:

### Academic Module
- `academic_trial` - Academic Module - Free Trial
- `academic_full` - IELTS Core (Academic Module)
- `academic_plus` - IELTS Plus (Academic Module)

### General Training Module
- `general_trial` - General Training - Free Trial
- `general_full` - IELTS Core (General Training Module)
- `general_plus` - IELTS Plus (General Training Module)

### English Only
- `english_trial` - English Only - Free Trial
- `english_full` - English Only Full Membership

**Stored in database as:** `_ielts_cm_membership_type` user meta

---

## 📊 Database Persistence Architecture

### Current Setup (localStorage only - NOT cross-device)
```javascript
// ❌ Only saves to browser - lost on different device
localStorage.setItem('ielts_tour_completed', 'true');
```

### Updated Setup (Database - WORKS across devices)
```javascript
// ✅ Saves to WordPress database via AJAX
$.ajax({
    url: ieltsTourData.ajaxUrl,
    type: 'POST',
    data: {
        action: 'ielts_complete_tour',
        tour_type: 'academic',  // or 'general' or 'english'
        nonce: ieltsTourData.nonce
    }
});
```

**Saved in database as:** `ielts_tour_completed_academic`, `ielts_tour_completed_general`, `ielts_tour_completed_english`

---

## 🚀 Step-by-Step Implementation

### Step 1: Update PHP Enqueue Script (Pass Membership Data)

Edit `includes/frontend/class-frontend.php`:

```php
public function enqueue_scripts() {
    // ... existing code ...
    
    // User tour for first-time users
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        
        // Get user's membership type
        $membership_type = get_user_meta($user_id, '_ielts_cm_membership_type', true);
        
        // Determine tour type based on membership
        $tour_type = '';
        if (strpos($membership_type, 'academic') !== false) {
            $tour_type = 'academic';
        } elseif (strpos($membership_type, 'general') !== false) {
            $tour_type = 'general';
        } elseif (strpos($membership_type, 'english') !== false) {
            $tour_type = 'english';
        }
        
        // Check if user has completed tour for their membership type
        $tour_completed = get_user_meta($user_id, 'ielts_tour_completed_' . $tour_type, true);
        
        // Only load tour if not completed
        if (!$tour_completed && !empty($tour_type)) {
            // Shepherd.js library
            wp_enqueue_style('shepherd-theme', 
                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css');
            
            wp_enqueue_script('shepherd-js', 
                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js', 
                array(), '11.2.0', true);
            
            // Your custom tour configuration
            wp_enqueue_script('ielts-user-tour', 
                IELTS_CM_PLUGIN_URL . 'assets/js/user-tour.js', 
                array('jquery', 'shepherd-js'), IELTS_CM_VERSION, true);
            
            // Pass membership data to JavaScript
            wp_localize_script('ielts-user-tour', 'ieltsTourData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ielts_tour_complete'),
                'userId' => $user_id,
                'membershipType' => $membership_type,
                'tourType' => $tour_type  // 'academic', 'general', or 'english'
            ));
        }
    }
}
```

---

### Step 2: Update AJAX Handler (Save with Tour Type)

Add to `includes/frontend/class-frontend.php`:

```php
public function init() {
    // ... existing code ...
    
    // User tour completion handler
    add_action('wp_ajax_ielts_complete_tour', array($this, 'handle_tour_completion'));
}

/**
 * Mark user tour as completed for specific membership type
 */
public function handle_tour_completion() {
    check_ajax_referer('ielts_tour_complete', 'nonce');
    
    $user_id = get_current_user_id();
    $tour_type = isset($_POST['tour_type']) ? sanitize_text_field($_POST['tour_type']) : '';
    
    if ($user_id && in_array($tour_type, array('academic', 'general', 'english'))) {
        // Save completion with tour type suffix for cross-device persistence
        update_user_meta($user_id, 'ielts_tour_completed_' . $tour_type, true);
        
        // Also save timestamp
        update_user_meta($user_id, 'ielts_tour_completed_' . $tour_type . '_date', current_time('mysql'));
        
        wp_send_json_success(array(
            'message' => 'Tour completed successfully',
            'tour_type' => $tour_type
        ));
    } else {
        wp_send_json_error(array('message' => 'Invalid request'));
    }
}
```

---

### Step 3: Create Membership-Specific Tour JavaScript

Create `assets/js/user-tour.js`:

```javascript
/**
 * Membership-Specific User Tours for IELTS Course Manager
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Only run if Shepherd is loaded
        if (typeof Shepherd === 'undefined') {
            return;
        }
        
        // Check if tour data is available
        if (typeof ieltsTourData === 'undefined' || !ieltsTourData.tourType) {
            return;
        }
        
        // Get membership/tour type from PHP
        const tourType = ieltsTourData.tourType; // 'academic', 'general', or 'english'
        
        // Check localStorage as quick cache (optional, but faster)
        const localStorageKey = 'ielts_tour_completed_' + tourType;
        if (localStorage.getItem(localStorageKey)) {
            return; // Tour already completed
        }
        
        // Initialize tour
        const tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: { enabled: true },
                classes: 'ielts-tour-step',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        });
        
        // Load appropriate tour based on membership type
        if (tourType === 'academic') {
            loadAcademicTour(tour);
        } else if (tourType === 'general') {
            loadGeneralTrainingTour(tour);
        } else if (tourType === 'english') {
            loadEnglishOnlyTour(tour);
        }
        
        // When tour is completed, save to database
        tour.on('complete', function() {
            // Save to WordPress database (cross-device)
            $.ajax({
                url: ieltsTourData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_complete_tour',
                    tour_type: tourType,
                    nonce: ieltsTourData.nonce
                },
                success: function(response) {
                    console.log('Tour completion saved:', response);
                }
            });
            
            // Also save to localStorage for quick access
            localStorage.setItem(localStorageKey, 'true');
        });
        
        // When tour is cancelled/skipped
        tour.on('cancel', function() {
            // Still mark as completed so it doesn't show again
            $.ajax({
                url: ieltsTourData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_complete_tour',
                    tour_type: tourType,
                    nonce: ieltsTourData.nonce
                }
            });
            localStorage.setItem(localStorageKey, 'true');
        });
        
        // Start tour after short delay
        setTimeout(function() {
            tour.start();
        }, 1000);
    });
    
    /**
     * Academic Module Tour
     */
    function loadAcademicTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to IELTS Academic! 🎓</h3><p>Let\'s show you around the Academic Module. This will take 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // Main Navigation
        tour.addStep({
            id: 'navigation',
            text: '<h3>Main Menu 📚</h3><p>Access all your Academic courses and lessons here.</p>',
            attachTo: { element: '.main-navigation', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Academic Practice Tests
        tour.addStep({
            id: 'academic-tests',
            text: '<h3>Academic Practice Tests 📝</h3><p>Take full-length IELTS Academic practice tests here.</p>',
            attachTo: { element: 'a[href*="academic"][href*="practice"], a[href*="practice"][href*="academic"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Academic Reading Section
        tour.addStep({
            id: 'academic-reading',
            text: '<h3>Academic Reading 📖</h3><p>Practice Academic reading with passages from journals and textbooks.</p>',
            attachTo: { element: 'a[href*="academic"][href*="reading"], a[href*="reading"][href*="academic"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Trophy Room
        tour.addStep({
            id: 'trophies',
            text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you complete Academic modules!</p>',
            attachTo: { element: 'a[href*="trophy"], a[href*="award"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Progress
        tour.addStep({
            id: 'progress',
            text: '<h3>Your Progress 📊</h3><p>View your scores and study statistics for the Academic module.</p>',
            attachTo: { element: 'a[href*="progress"], a[href*="account"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Finish Tour', action: tour.complete }
            ]
        });
    }
    
    /**
     * General Training Module Tour
     */
    function loadGeneralTrainingTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to IELTS General Training! 🌍</h3><p>Let\'s show you around the General Training Module. This will take 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // Main Navigation
        tour.addStep({
            id: 'navigation',
            text: '<h3>Main Menu 📚</h3><p>Access all your General Training courses and lessons here.</p>',
            attachTo: { element: '.main-navigation', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // General Training Practice Tests
        tour.addStep({
            id: 'general-tests',
            text: '<h3>General Training Practice Tests 📝</h3><p>Take full-length IELTS General Training practice tests here.</p>',
            attachTo: { element: 'a[href*="general"][href*="practice"], a[href*="practice"][href*="general"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // General Training Reading Section
        tour.addStep({
            id: 'general-reading',
            text: '<h3>General Training Reading 📰</h3><p>Practice General Training reading with everyday materials like notices and advertisements.</p>',
            attachTo: { element: 'a[href*="general"][href*="reading"], a[href*="reading"][href*="general"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Trophy Room
        tour.addStep({
            id: 'trophies',
            text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you complete General Training modules!</p>',
            attachTo: { element: 'a[href*="trophy"], a[href*="award"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Progress
        tour.addStep({
            id: 'progress',
            text: '<h3>Your Progress 📊</h3><p>View your scores and study statistics for the General Training module.</p>',
            attachTo: { element: 'a[href*="progress"], a[href*="account"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Finish Tour', action: tour.complete }
            ]
        });
    }
    
    /**
     * English Only Module Tour
     */
    function loadEnglishOnlyTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to English Learning! 🌟</h3><p>Let\'s show you around the English-only content. This will take 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // Main Navigation
        tour.addStep({
            id: 'navigation',
            text: '<h3>Main Menu 📚</h3><p>Access all your English courses and lessons here.</p>',
            attachTo: { element: '.main-navigation', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // English Lessons
        tour.addStep({
            id: 'english-lessons',
            text: '<h3>English Lessons 📖</h3><p>Improve your English with comprehensive lessons.</p>',
            attachTo: { element: 'a[href*="english"][href*="lesson"], a[href*="lesson"][href*="english"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Trophy Room
        tour.addStep({
            id: 'trophies',
            text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you improve your English!</p>',
            attachTo: { element: 'a[href*="trophy"], a[href*="award"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Progress
        tour.addStep({
            id: 'progress',
            text: '<h3>Your Progress 📊</h3><p>View your learning progress and statistics.</p>',
            attachTo: { element: 'a[href*="progress"], a[href*="account"]', on: 'bottom' },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Finish Tour', action: tour.complete }
            ]
        });
    }
    
})(jQuery);
```

---

## 🔄 How Cross-Device Persistence Works

### Scenario 1: User on Desktop

```
1. User logs in to desktop
2. Tours JavaScript loads
3. PHP checks: get_user_meta($user_id, 'ielts_tour_completed_academic')
4. Returns false (not completed)
5. Tour shows
6. User completes tour
7. AJAX saves: update_user_meta($user_id, 'ielts_tour_completed_academic', true)
8. ✅ Saved in WordPress database
```

### Scenario 2: Same User on Mobile (Different Device)

```
1. User logs in to mobile phone
2. Tours JavaScript loads
3. PHP checks: get_user_meta($user_id, 'ielts_tour_completed_academic')
4. Returns true (completed on desktop!)
5. ✅ Tour does NOT show
6. User continues without interruption
```

### Why This Works:

- ❌ **localStorage**: Browser-specific (lost on different device)
- ✅ **WordPress user_meta**: Database-stored (available on all devices)

---

## 📊 Database Structure

### User Meta Keys:

```sql
-- Academic tour completion
ielts_tour_completed_academic = '1'
ielts_tour_completed_academic_date = '2026-02-06 12:30:45'

-- General Training tour completion
ielts_tour_completed_general = '1'
ielts_tour_completed_general_date = '2026-02-06 13:15:22'

-- English Only tour completion
ielts_tour_completed_english = '1'
ielts_tour_completed_english_date = '2026-02-06 14:00:10'

-- Original membership type
_ielts_cm_membership_type = 'academic_full'
```

---

## 🎯 Testing Different Membership Tours

### Test as Academic Member

```php
// In WordPress admin or WP Console
$user_id = get_current_user_id();

// Set as academic member
update_user_meta($user_id, '_ielts_cm_membership_type', 'academic_full');

// Clear tour completion
delete_user_meta($user_id, 'ielts_tour_completed_academic');

// Clear localStorage
// In browser console: localStorage.removeItem('ielts_tour_completed_academic');

// Reload page → See Academic tour
```

### Test as General Training Member

```php
// Set as general training member
update_user_meta($user_id, '_ielts_cm_membership_type', 'general_full');

// Clear tour completion
delete_user_meta($user_id, 'ielts_tour_completed_general');

// Clear localStorage
// In browser console: localStorage.removeItem('ielts_tour_completed_general');

// Reload page → See General Training tour
```

### Test as English Member

```php
// Set as english member
update_user_meta($user_id, '_ielts_cm_membership_type', 'english_full');

// Clear tour completion
delete_user_meta($user_id, 'ielts_tour_completed_english');

// Clear localStorage
// In browser console: localStorage.removeItem('ielts_tour_completed_english');

// Reload page → See English tour
```

---

## 🔧 Advanced: User Switches Membership

### Scenario: User Upgrades from General to Academic

```php
// User originally had general_trial
// Completed general training tour
// Now upgrades to academic_full

// They get a NEW tour for academic!
// Because academic tour is tracked separately
```

**Database state:**
```
_ielts_cm_membership_type = 'academic_full' (updated)
ielts_tour_completed_general = '1' (old tour, still there)
ielts_tour_completed_academic = '' (empty - will show new tour!)
```

This is **perfect behavior** - users see relevant tour when they change modules!

---

## 💡 Pro Tips

### 1. Dual Persistence Strategy

Use BOTH localStorage (fast) AND database (persistent):

```javascript
// Check localStorage first (instant)
if (localStorage.getItem(localStorageKey)) {
    return; // Fast exit
}

// Then load from database via PHP
// PHP already checked user_meta before enqueuing script
```

**Benefits:**
- ✅ Fast for repeat page loads (localStorage)
- ✅ Works across devices (database)
- ✅ Best of both worlds

### 2. Reset Tour for Testing

Add a URL parameter to allow resetting:

```javascript
// Check for reset parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('reset_tour') === '1' && urlParams.get('user_can_reset')) {
    localStorage.removeItem(localStorageKey);
    // Optionally call AJAX to clear database too
}
```

### 3. Tour Analytics

Track which tours users complete:

```php
public function handle_tour_completion() {
    // ... existing code ...
    
    // Log analytics
    $tour_completions = get_option('ielts_tour_analytics', array());
    $tour_completions[$tour_type] = isset($tour_completions[$tour_type]) 
        ? $tour_completions[$tour_type] + 1 
        : 1;
    update_option('ielts_tour_analytics', $tour_completions);
}
```

### 4. Admin Reset Option

Let admins reset a user's tour:

```php
// In user edit screen
<button onclick="resetUserTour(<?php echo $user_id; ?>, 'academic')">
    Reset Academic Tour
</button>

function resetUserTour(userId, tourType) {
    // AJAX call to delete user meta
    delete_user_meta(userId, 'ielts_tour_completed_' + tourType);
}
```

---

## 📋 Implementation Checklist

- [ ] Update `class-frontend.php` to pass membership type to JavaScript
- [ ] Update AJAX handler to save tour completion with tour type
- [ ] Create `user-tour.js` with membership-specific tours
- [ ] Test Academic tour
- [ ] Test General Training tour
- [ ] Test English tour
- [ ] Verify database persistence across devices
- [ ] Clear localStorage for testing
- [ ] Test membership switching scenario

---

## 🎓 Example: Complete User Journey

### New Academic Member Signs Up

```
Day 1, Device 1 (Desktop):
1. Sign up → Gets academic_trial membership
2. Login → PHP detects academic membership
3. Tour loads → Academic-specific content
4. Complete tour → Saved to database
   ✅ ielts_tour_completed_academic = 1

Day 2, Device 2 (Mobile):
1. Login from phone
2. PHP checks database → Tour already completed
3. ✅ No tour shown (good!)
4. User starts learning immediately

Day 30, Upgrade:
1. Upgrade to academic_full
2. Still academic membership type
3. Tour still marked complete
4. ✅ No tour shown (correct!)

Day 60, Switch to General:
1. Change to general_plus
2. PHP detects general membership
3. Check ielts_tour_completed_general → false!
4. ✅ General Training tour shows (new content!)
5. Complete → Saved to database
```

---

## ✅ Summary

**Three Key Points:**

1. **Different Tours**: Each membership type gets customized tour content
   - Academic → Academic resources/tests
   - General → General Training resources/tests
   - English → English learning content

2. **Database Persistence**: Completion saved to WordPress user meta
   - Works across all devices
   - Persists when user logs out/in
   - Survives browser cache clears

3. **Easy Implementation**: Just three changes
   - PHP: Pass membership type to JS
   - PHP: Save completion to user meta
   - JS: Load different tour based on type

**Result**: Professional, membership-aware onboarding that works everywhere! 🎉

---

## 📚 Related Guides

- **Basic Tour Setup**: [USER_TOUR_QUICK_START.md](USER_TOUR_QUICK_START.md)
- **Highlighting Elements**: [USER_TOUR_HIGHLIGHTING_EXAMPLES.md](USER_TOUR_HIGHLIGHTING_EXAMPLES.md)
- **Complete Guide**: [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md)
