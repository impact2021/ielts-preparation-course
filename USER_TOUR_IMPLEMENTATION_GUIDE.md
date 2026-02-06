# User Tour Implementation Guide for IELTS Course Manager

## Overview

This guide provides recommendations for implementing a user tour/onboarding experience for first-time users of the IELTS Preparation Course. The tour would guide users through key features like the menu navigation, practice tests, trophy room, and other important areas.

## Executive Summary

**Difficulty Level**: Easy to Moderate  
**Estimated Setup Time**: 1-2 hours (using recommended library)  
**Maintenance**: Minimal (library-maintained)  
**Best Approach**: Use a dedicated JavaScript tour library (Shepherd.js or Intro.js)

---

## Recommended Solution: Shepherd.js

### Why Shepherd.js?

**Shepherd.js** is the recommended solution because:

✅ **Easy Setup** - No shortcodes needed for each stage  
✅ **Free & Open Source** - MIT licensed  
✅ **Modern & Maintained** - Active development, good documentation  
✅ **Lightweight** - ~20KB minified  
✅ **Flexible** - Works with any website structure  
✅ **Mobile Friendly** - Responsive design  
✅ **Accessible** - WCAG compliant  
✅ **Customizable** - Full CSS control for branding  

**Alternative**: Intro.js (also excellent, similar features)

### Key Features
- Step-by-step guided tours
- Highlight specific elements
- Modal overlays with tooltips
- Progress indicators
- Customizable styling
- Multi-tour support (different tours for different user types)
- LocalStorage integration (remember completed tours)
- Callback hooks for analytics

---

## Implementation Overview

### How It Works (High-Level)

1. **Include Library Files**: Add Shepherd.js JavaScript and CSS to your WordPress site
2. **Define Tour Steps**: Create a JavaScript configuration defining each tour step
3. **Trigger Tour**: Show tour for first-time users (using localStorage or WordPress user meta)
4. **No Shortcodes Needed**: Tour is controlled entirely through JavaScript

### File Changes Required

You'll only need to modify **2 files**:

1. **`includes/frontend/class-frontend.php`** - Enqueue the tour script
2. **`assets/js/user-tour.js`** - Define your tour steps (new file)

---

## Detailed Implementation Steps

### Step 1: Add Shepherd.js Library

**Option A: CDN (Easiest)**

Add to `includes/frontend/class-frontend.php` in the `enqueue_scripts()` method:

```php
public function enqueue_scripts() {
    // Existing enqueues...
    
    // Only load tour for logged-in users who haven't completed it
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $tour_completed = get_user_meta($user_id, 'ielts_tour_completed', true);
        
        if (!$tour_completed) {
            // Shepherd.js library
            wp_enqueue_style(
                'shepherd-theme',
                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css',
                array(),
                '11.2.0'
            );
            
            wp_enqueue_script(
                'shepherd-js',
                'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js',
                array(),
                '11.2.0',
                true
            );
            
            // Your custom tour configuration
            wp_enqueue_script(
                'ielts-user-tour',
                IELTS_CM_PLUGIN_URL . 'assets/js/user-tour.js',
                array('jquery', 'shepherd-js'),
                IELTS_CM_VERSION,
                true
            );
            
            // Pass data to JavaScript
            wp_localize_script('ielts-user-tour', 'ieltsTourData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ielts_tour_complete'),
                'userId' => $user_id
            ));
        }
    }
}
```

**Option B: Local Files (More Control)**

Download Shepherd.js and place in `assets/js/vendor/` and `assets/css/vendor/`, then reference locally instead of CDN.

---

### Step 2: Create Tour Configuration

Create new file: `assets/js/user-tour.js`

```javascript
/**
 * User Tour for First-Time IELTS Course Users
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Only run tour if Shepherd is loaded
        if (typeof Shepherd === 'undefined') {
            return;
        }
        
        // Initialize the tour
        const tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: {
                    enabled: true
                },
                classes: 'ielts-tour-step',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        });
        
        // Step 1: Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to IELTS Preparation Course! 🎉</h3><p>Let\'s take a quick tour of the platform. This will only take 2 minutes.</p>',
            buttons: [
                {
                    text: 'Skip Tour',
                    classes: 'shepherd-button-secondary',
                    action: tour.complete
                },
                {
                    text: 'Start Tour',
                    action: tour.next
                }
            ]
        });
        
        // Step 2: Navigation Menu
        tour.addStep({
            id: 'navigation',
            text: '<h3>Main Navigation 📚</h3><p>Use this menu to access courses, lessons, and your dashboard.</p>',
            attachTo: {
                element: '.main-navigation', // Adjust selector to match your menu
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    classes: 'shepherd-button-secondary',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });
        
        // Step 3: Practice Tests
        tour.addStep({
            id: 'practice-tests',
            text: '<h3>Practice Tests 📝</h3><p>Access full-length IELTS practice tests here to prepare for your exam.</p>',
            attachTo: {
                element: 'a[href*="practice-test"]', // Adjust to your practice test link
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    classes: 'shepherd-button-secondary',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });
        
        // Step 4: Trophy Room
        tour.addStep({
            id: 'trophy-room',
            text: '<h3>Trophy Room 🏆</h3><p>Track your achievements! Earn badges, shields, and trophies as you complete courses and exercises.</p>',
            attachTo: {
                element: 'a[href*="trophy"], a[href*="awards"]', // Adjust to your trophy room link
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    classes: 'shepherd-button-secondary',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });
        
        // Step 5: Progress Dashboard
        tour.addStep({
            id: 'progress',
            text: '<h3>Your Progress 📊</h3><p>View your learning progress, scores, and study statistics here.</p>',
            attachTo: {
                element: 'a[href*="progress"], a[href*="my-account"]',
                on: 'bottom'
            },
            buttons: [
                {
                    text: 'Back',
                    classes: 'shepherd-button-secondary',
                    action: tour.back
                },
                {
                    text: 'Next',
                    action: tour.next
                }
            ]
        });
        
        // Step 6: Get Started
        tour.addStep({
            id: 'get-started',
            text: '<h3>You\'re All Set! ✅</h3><p>Start your IELTS preparation journey by browsing courses or taking a practice test.</p>',
            buttons: [
                {
                    text: 'Finish Tour',
                    action: tour.complete
                }
            ]
        });
        
        // When tour is completed, save to database
        tour.on('complete', function() {
            // Mark tour as completed in WordPress
            $.ajax({
                url: ieltsTourData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_complete_tour',
                    nonce: ieltsTourData.nonce,
                    user_id: ieltsTourData.userId
                }
            });
            
            // Also save to localStorage as backup
            localStorage.setItem('ielts_tour_completed', 'true');
        });
        
        // Start the tour automatically for first-time users
        // Check localStorage first (faster than server)
        if (!localStorage.getItem('ielts_tour_completed')) {
            // Small delay to ensure page is fully loaded
            setTimeout(function() {
                tour.start();
            }, 1000);
        }
        
    });
    
})(jQuery);
```

---

### Step 3: Add AJAX Handler for Tour Completion

Add to `includes/frontend/class-frontend.php`:

```php
public function init() {
    // Existing init code...
    
    // AJAX handler for tour completion
    add_action('wp_ajax_ielts_complete_tour', array($this, 'handle_tour_completion'));
}

/**
 * Handle tour completion AJAX request
 */
public function handle_tour_completion() {
    check_ajax_referer('ielts_tour_complete', 'nonce');
    
    $user_id = get_current_user_id();
    if ($user_id) {
        update_user_meta($user_id, 'ielts_tour_completed', true);
        wp_send_json_success(array('message' => 'Tour completed successfully'));
    } else {
        wp_send_json_error(array('message' => 'User not logged in'));
    }
}
```

---

### Step 4: Add Custom Styling (Optional)

Create `assets/css/user-tour.css`:

```css
/* Custom tour styling to match IELTS branding */

.shepherd-modal-overlay-container {
    z-index: 9998;
}

.shepherd-element {
    z-index: 9999;
}

.ielts-tour-step {
    max-width: 400px;
}

.ielts-tour-step .shepherd-header {
    background-color: #1e40af; /* IELTS blue - adjust to your brand */
    color: white;
    padding: 1rem;
}

.ielts-tour-step .shepherd-text {
    padding: 1.5rem;
    font-size: 16px;
    line-height: 1.6;
}

.ielts-tour-step .shepherd-text h3 {
    margin-top: 0;
    color: #1e40af;
    font-size: 1.25rem;
}

.ielts-tour-step .shepherd-footer {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
}

.shepherd-button {
    background-color: #1e40af;
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s;
}

.shepherd-button:hover {
    background-color: #1e3a8a;
}

.shepherd-button-secondary {
    background-color: #6b7280;
}

.shepherd-button-secondary:hover {
    background-color: #4b5563;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .ielts-tour-step {
        max-width: 90vw;
    }
    
    .ielts-tour-step .shepherd-text {
        font-size: 14px;
    }
}
```

Enqueue this CSS file along with the JavaScript in Step 1.

---

## Customization Guide

### Adjusting Tour Steps

You can easily add, remove, or modify tour steps by editing `user-tour.js`:

```javascript
// Add a new step
tour.addStep({
    id: 'unique-step-id',
    text: '<h3>Step Title</h3><p>Step description...</p>',
    attachTo: {
        element: '.css-selector',  // Element to highlight
        on: 'bottom'                // Tooltip position: top, bottom, left, right
    },
    buttons: [
        { text: 'Back', action: tour.back },
        { text: 'Next', action: tour.next }
    ]
});
```

### Targeting Different User Types

You can create different tours for different user types:

```javascript
// Check user role from WordPress
const userRole = document.body.classList; // WordPress adds user role to body class

if (userRole.contains('role-student')) {
    // Show student tour
    startStudentTour();
} else if (userRole.contains('role-teacher')) {
    // Show teacher tour
    startTeacherTour();
}
```

### Allowing Users to Restart Tour

Add a "Restart Tour" link in your user dashboard:

```php
// In your template file
<a href="#" id="restart-tour-btn">Take the Tour Again</a>

<script>
jQuery('#restart-tour-btn').on('click', function(e) {
    e.preventDefault();
    localStorage.removeItem('ielts_tour_completed');
    location.reload();
});
</script>
```

---

## Finding CSS Selectors for Your Site

To find the correct CSS selectors for your menu items and features:

1. **Open your WordPress site** in a web browser
2. **Right-click** on the menu item or element you want to highlight
3. **Select "Inspect" or "Inspect Element"**
4. Look for the element's `class` or `id` attributes
5. Use these in your tour configuration (e.g., `.main-navigation`, `#trophy-link`)

**Common selectors for WordPress sites:**
- Main menu: `.main-navigation`, `.primary-menu`, `.site-navigation`
- User account: `.my-account-link`, `.user-menu`
- Practice tests: `a[href*="practice-test"]` (matches any link containing "practice-test")
- Trophy room: `a[href*="trophy"]`, `a[href*="awards"]`

---

## Testing Your Tour

### Quick Testing Checklist

1. ✅ **Clear user meta** to reset tour status:
   ```php
   // Add this temporarily to wp-config.php or run in WP Console
   delete_user_meta(get_current_user_id(), 'ielts_tour_completed');
   ```

2. ✅ **Clear localStorage** in browser console:
   ```javascript
   localStorage.removeItem('ielts_tour_completed');
   ```

3. ✅ **Test all steps**: Click through entire tour
4. ✅ **Test skip button**: Ensure it properly ends tour
5. ✅ **Test back button**: Navigate backwards through steps
6. ✅ **Test mobile**: Responsive design on phone/tablet
7. ✅ **Test after completion**: Tour shouldn't show again

---

## Advanced Features

### 1. Conditional Steps

Show different steps based on user behavior:

```javascript
// Only show practice test step if user hasn't taken one
if (!userHasTakenPracticeTest) {
    tour.addStep({
        id: 'first-practice-test',
        text: 'Try your first practice test!'
    });
}
```

### 2. Progress Indicator

Add a progress counter to each step:

```javascript
tour.addStep({
    id: 'step-1',
    text: '<div class="tour-progress">Step 1 of 6</div><h3>Welcome!</h3>',
    // ... rest of step config
});
```

### 3. Video in Tour Steps

Embed video tutorials in tour steps:

```javascript
tour.addStep({
    id: 'video-step',
    text: `
        <h3>How to Take a Test</h3>
        <iframe width="100%" height="200" 
                src="https://www.youtube.com/embed/YOUR_VIDEO_ID" 
                frameborder="0" allowfullscreen>
        </iframe>
    `
});
```

### 4. Analytics Integration

Track tour completion and step engagement:

```javascript
tour.on('show', function(event) {
    // Track when each step is shown
    gtag('event', 'tour_step_view', {
        'step_id': event.step.id
    });
});

tour.on('complete', function() {
    gtag('event', 'tour_completed');
});

tour.on('cancel', function() {
    gtag('event', 'tour_cancelled');
});
```

---

## Alternative: Intro.js

If you prefer Intro.js instead of Shepherd.js:

### Pros of Intro.js
- Simpler API
- Slightly smaller file size
- Data attribute-based (can add tour steps directly in HTML)

### Cons
- Less modern UI
- Fewer customization options
- Commercial license required for some features

### Quick Intro.js Example

```html
<!-- Add data attributes to elements in your theme -->
<a href="/practice-tests" 
   data-intro="Access full-length IELTS practice tests here" 
   data-step="1">
   Practice Tests
</a>

<script>
// Start tour
introJs().start();
</script>
```

---

## Maintenance & Updates

### Keeping the Library Updated

**CDN Approach**: Updates automatically (just change version number)
**Local Files**: Check for updates quarterly

### When to Update Tour Content

- After adding new features
- After redesigning navigation
- Based on user feedback
- When you notice users getting stuck

### Monitoring Tour Effectiveness

Add these metrics to your analytics:

1. **Completion Rate**: % of users who complete the tour
2. **Skip Rate**: % of users who skip the tour
3. **Drop-off Points**: Which steps users abandon at
4. **Feature Usage**: Do tour users engage more with features?

```javascript
// Track completion rate
tour.on('complete', function() {
    // Send to analytics
    logTourMetric('completed');
});

tour.on('cancel', function() {
    logTourMetric('skipped');
});
```

---

## Troubleshooting

### Tour Doesn't Appear

**Check:**
1. Is JavaScript loaded? (Check browser console for errors)
2. Is user logged in? (Tour only shows for logged-in users)
3. Is tour already completed? (Check user meta and localStorage)
4. Are selectors correct? (Elements must exist on page)

### Tour Shows Every Time

**Fix:**
- Verify AJAX handler is saving user meta correctly
- Check localStorage is being set
- Ensure nonce is valid

### Tour Highlights Wrong Element

**Fix:**
- Update CSS selector in tour configuration
- Use more specific selector
- Check element exists when tour starts

### Tour Looks Bad on Mobile

**Fix:**
- Add responsive CSS
- Use shorter text for mobile
- Test on actual devices
- Consider skipping tour on mobile (optional)

---

## Cost Analysis

### Recommended Approach (Shepherd.js via CDN)
- **Cost**: $0 (free, open source)
- **Time to implement**: 1-2 hours
- **Maintenance**: Minimal (library maintained by community)

### Alternative Approaches

| Approach | Cost | Time | Maintenance | Pros | Cons |
|----------|------|------|-------------|------|------|
| **Shepherd.js** | Free | 1-2h | Low | Modern, flexible | Requires JS knowledge |
| **Intro.js** | Free* | 1-2h | Low | Simple | Less customizable |
| **Custom build** | Free | 8-12h | High | Full control | Time-consuming |
| **WordPress Plugin** | $0-60 | 30min | Medium | Quick setup | Less flexible |

*Intro.js free version has some limitations

---

## FAQ

### Do I need to add a shortcode for each step?
**No!** The tour is entirely JavaScript-based. You define all steps in one JavaScript file.

### Will this slow down my site?
**Minimal impact.** Shepherd.js is only ~20KB minified and gzipped, and only loads for first-time users.

### Can I show the tour again to existing users?
**Yes!** Just delete their user meta: `delete_user_meta($user_id, 'ielts_tour_completed');`

### Can I have different tours for different pages?
**Yes!** Check the current page in JavaScript and show different tours:
```javascript
if (window.location.pathname.includes('/dashboard/')) {
    startDashboardTour();
} else {
    startMainTour();
}
```

### Can users skip the tour?
**Yes!** Each step has a skip button, and there's a close icon in the corner.

### Will the tour work on mobile?
**Yes!** Shepherd.js is fully responsive. You may want to adjust text length for mobile.

### Can I A/B test different tour versions?
**Yes!** Create two tour configurations and randomly assign users to each version.

---

## Next Steps

### Immediate Actions (1-2 hours)

1. ✅ **Decide on library**: Choose Shepherd.js (recommended) or Intro.js
2. ✅ **Identify elements**: Find CSS selectors for menu, practice tests, trophy room
3. ✅ **Add code**: Follow steps 1-3 above
4. ✅ **Test**: Clear user meta and localStorage, test tour flow
5. ✅ **Refine**: Adjust text, timing, and styling

### Optional Enhancements (Later)

- Add progress indicators
- Create mobile-specific tour
- Add video tutorials to steps
- Implement analytics tracking
- Create separate tours for different user roles
- Add "Help" button to restart tour anytime
- Translate tour for multiple languages

---

## Resources

### Official Documentation
- [Shepherd.js Docs](https://shepherdjs.dev/)
- [Intro.js Docs](https://introjs.com/)
- [WordPress AJAX Guide](https://developer.wordpress.org/plugins/javascript/ajax/)

### Tutorials
- [Shepherd.js Getting Started](https://shepherdjs.dev/docs/tutorial-01-basic.html)
- [User Onboarding Best Practices](https://www.appcues.com/blog/user-onboarding-best-practices)

### Alternative Libraries (For Reference)
- Driver.js - Lightweight alternative
- Hopscotch - Older but stable
- Bootstrap Tour - If already using Bootstrap

---

## Summary

**Bottom Line**: Adding a user tour is **easy** with modern JavaScript libraries like Shepherd.js. You only need to:

1. Add the library (via CDN - just 2 lines of code)
2. Create a JavaScript file with tour steps (100-200 lines)
3. Add a simple AJAX handler in PHP (10-15 lines)

**No shortcodes needed!** Everything is controlled through JavaScript configuration.

**Total time**: 1-2 hours for basic implementation  
**Difficulty**: Easy to Moderate (requires basic JavaScript knowledge)  
**Maintenance**: Minimal (update tour text as needed)

This approach gives you maximum flexibility with minimum effort. You can easily add, remove, or modify tour steps by editing a single JavaScript file.
