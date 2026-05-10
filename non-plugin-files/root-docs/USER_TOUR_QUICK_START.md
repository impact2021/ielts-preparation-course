# User Tour Quick Start Guide

## TL;DR - How to Add a User Tour in 30 Minutes

This is a condensed version of the full [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md). For detailed explanations, see the complete guide.

---

## What You'll Get

A guided tour that automatically shows first-time users:
- ✅ Where the main navigation menu is
- ✅ How to access practice tests  
- ✅ Where to find the trophy room
- ✅ How to track their progress
- ✅ **Highlights specific buttons and areas** (e.g., "Submit Quiz" button)
- ✅ Other key features

**Key Features:**
- 🎯 **Automatically highlights** buttons, forms, and interactive elements
- 🌟 **Spotlights** important areas while dimming the rest of the page
- 📍 **Points to specific elements** like submit buttons, timers, score displays
- **No shortcodes needed!** Everything is JavaScript-based.

---

## 3-Step Setup

### Step 1: Add Shepherd.js Library (2 minutes)

Edit `includes/frontend/class-frontend.php`, find the `enqueue_scripts()` method, and add:

```php
// Add after existing wp_enqueue_script calls

// User tour for first-time users
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $tour_completed = get_user_meta($user_id, 'ielts_tour_completed', true);
    
    if (!$tour_completed) {
        wp_enqueue_style('shepherd-theme', 
            'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/css/shepherd.css');
        
        wp_enqueue_script('shepherd-js', 
            'https://cdn.jsdelivr.net/npm/shepherd.js@11.2.0/dist/js/shepherd.min.js', 
            array(), '11.2.0', true);
        
        wp_enqueue_script('ielts-user-tour', 
            IELTS_CM_PLUGIN_URL . 'assets/js/user-tour.js', 
            array('jquery', 'shepherd-js'), IELTS_CM_VERSION, true);
        
        wp_localize_script('ielts-user-tour', 'ieltsTourData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ielts_tour_complete'),
            'userId' => $user_id
        ));
    }
}
```

---

### Step 2: Add AJAX Handler (2 minutes)

In the same file (`includes/frontend/class-frontend.php`), add to the `init()` method:

```php
public function init() {
    // ... existing code ...
    
    // User tour completion handler
    add_action('wp_ajax_ielts_complete_tour', array($this, 'handle_tour_completion'));
}
```

Then add this new method anywhere in the class:

```php
/**
 * Mark user tour as completed
 */
public function handle_tour_completion() {
    check_ajax_referer('ielts_tour_complete', 'nonce');
    
    $user_id = get_current_user_id();
    if ($user_id) {
        update_user_meta($user_id, 'ielts_tour_completed', true);
        wp_send_json_success(array('message' => 'Tour completed'));
    } else {
        wp_send_json_error(array('message' => 'User not logged in'));
    }
}
```

---

### Step 3: Create Tour Steps (20 minutes)

Create new file: `assets/js/user-tour.js`

```javascript
/**
 * User Tour for IELTS Course Manager
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        if (typeof Shepherd === 'undefined') return;
        
        const tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: { enabled: true },
                classes: 'ielts-tour-step',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        });
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome! 🎉</h3><p>Let\'s take a quick tour. It only takes 2 minutes.</p>',
            buttons: [
                { text: 'Skip', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // Main Menu - CUSTOMIZE THIS SELECTOR
        tour.addStep({
            id: 'menu',
            text: '<h3>Main Menu 📚</h3><p>Find courses, lessons, and your dashboard here.</p>',
            attachTo: { element: '.main-navigation', on: 'bottom' }, // ← Change .main-navigation to your menu's CSS class
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Practice Tests - CUSTOMIZE THIS SELECTOR
        tour.addStep({
            id: 'tests',
            text: '<h3>Practice Tests 📝</h3><p>Take full-length IELTS practice tests.</p>',
            attachTo: { element: 'a[href*="practice"]', on: 'bottom' }, // ← Change to your practice tests link
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Trophy Room - CUSTOMIZE THIS SELECTOR
        tour.addStep({
            id: 'trophies',
            text: '<h3>Trophy Room 🏆</h3><p>Earn badges and trophies as you complete courses!</p>',
            attachTo: { element: 'a[href*="trophy"], a[href*="award"]', on: 'bottom' }, // ← Change to your trophy link
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // Progress - CUSTOMIZE THIS SELECTOR
        tour.addStep({
            id: 'progress',
            text: '<h3>Your Progress 📊</h3><p>Track your scores and study statistics.</p>',
            attachTo: { element: 'a[href*="progress"], a[href*="account"]', on: 'bottom' }, // ← Change to your progress link
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Next', action: tour.next }
            ]
        });
        
        // EXAMPLE: Highlighting a Submit Quiz Button (if on quiz page)
        // Uncomment and customize this if you want to highlight the submit button
        /*
        tour.addStep({
            id: 'submit-quiz',
            text: '<h3>Submit Your Quiz ✓</h3><p>When you finish answering, click this button to see your results!</p>',
            attachTo: { 
                element: 'button[type="submit"]',  // Highlights the submit button!
                on: 'top'  // Tooltip appears above the button
            },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Got it!', action: tour.next }
            ]
        });
        */
        
        // Finish
        tour.addStep({
            id: 'finish',
            text: '<h3>You\'re Ready! ✅</h3><p>Start learning by browsing courses or taking a practice test.</p>',
            buttons: [
                { text: 'Finish', action: tour.complete }
            ]
        });
        
        // Save completion to database
        tour.on('complete', function() {
            $.ajax({
                url: ieltsTourData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_complete_tour',
                    nonce: ieltsTourData.nonce,
                    user_id: ieltsTourData.userId
                }
            });
            localStorage.setItem('ielts_tour_completed', 'true');
        });
        
        // Auto-start for first-time users
        if (!localStorage.getItem('ielts_tour_completed')) {
            setTimeout(() => tour.start(), 1000);
        }
    });
})(jQuery);
```

---

## Customizing for Your Site

### Finding the Right CSS Selectors

The tour won't work properly until you update the CSS selectors to match YOUR site's navigation.

**How to find them:**

1. Open your IELTS site in Chrome/Firefox
2. Right-click on your main menu → "Inspect"
3. Look for `class="..."` or `id="..."` 
4. Copy the class name (e.g., `class="primary-nav"`)
5. Update the tour step:
   ```javascript
   attachTo: { element: '.primary-nav', on: 'bottom' }
   ```

**Common WordPress menu classes:**
- `.main-navigation`
- `.primary-menu`
- `.site-navigation`
- `.nav-primary`
- `#site-navigation`

Do this for each tour step (menu, practice tests, trophy room, progress).

---

## 🎯 Highlighting Specific Elements

**Important:** When you use `attachTo` in a tour step, the element is **automatically highlighted**!

```javascript
tour.addStep({
    text: 'Click this button to submit!',
    attachTo: { 
        element: 'button[type="submit"]',  // ← Element gets highlighted!
        on: 'top'  // Tooltip position
    }
});
```

**What happens:**
- ✅ The button **glows** and stands out
- ✅ The rest of the page **dims** (dark overlay)
- ✅ Page **scrolls** the button into view automatically
- ✅ User can't miss it!

### Common Elements to Highlight:

```javascript
// Submit button
attachTo: { element: 'button[type="submit"]', on: 'top' }

// Quiz timer
attachTo: { element: '#quiz-timer', on: 'bottom' }

// Score display
attachTo: { element: '.quiz-score', on: 'left' }

// Answer area
attachTo: { element: '.quiz-question:first', on: 'right' }
```

**See full examples:** [USER_TOUR_HIGHLIGHTING_EXAMPLES.md](USER_TOUR_HIGHLIGHTING_EXAMPLES.md)

---

## Testing

### Clear Tour Status (to test again)

**Option 1: Browser Console**
```javascript
localStorage.removeItem('ielts_tour_completed');
location.reload();
```

**Option 2: WordPress**
```php
// Add to wp-config.php temporarily, then visit site
delete_user_meta(get_current_user_id(), 'ielts_tour_completed');
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Tour doesn't appear | Check browser console for errors. Verify Shepherd.js loaded. |
| Tour shows every time | Check AJAX handler is running. Clear browser cache. |
| Wrong elements highlighted | Update CSS selectors to match your theme. |
| Tour appears for logged-out users | Move enqueue code inside `if (is_user_logged_in())` |

---

## Adding More Steps

Copy and paste this template before the "Finish" step:

```javascript
tour.addStep({
    id: 'my-new-step',
    text: '<h3>Step Title</h3><p>Step description.</p>',
    attachTo: { element: '.css-selector', on: 'bottom' },
    buttons: [
        { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
        { text: 'Next', action: tour.next }
    ]
});
```

---

## What's Next?

After basic setup works:

1. ✅ **Customize styling** - Match your brand colors
2. ✅ **Add more steps** - Cover more features
3. ✅ **Mobile optimization** - Test on phones/tablets
4. ✅ **Allow restart** - Add "Restart Tour" button
5. ✅ **Track analytics** - See completion rates

See [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md) for advanced features.

---

## Resources

- **Full Guide**: [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md)
- **Shepherd.js Docs**: https://shepherdjs.dev/
- **Live Demo**: https://shepherdjs.dev/demo/

---

## Summary

✅ **3 files to edit** (2 PHP, 1 new JS)  
✅ **30 minutes** total time  
✅ **No shortcodes** needed  
✅ **Works automatically** for first-time users  
✅ **Easy to customize** - just edit one JavaScript file  

Happy touring! 🎉
