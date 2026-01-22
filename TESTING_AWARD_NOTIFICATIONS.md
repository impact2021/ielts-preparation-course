# Testing Award Notifications on Quiz Completion

## Overview
Awards (badges, shields, trophies) now appear on the page where they're earned, not just on the trophy room page.

## How It Works

### Before
- Awards were only visible on the Trophy Room/Awards Wall page
- Users had to navigate to a separate page to see their achievements
- No immediate feedback when earning an award

### After
- Awards appear as a notification on the quiz completion page
- Notifications slide in from the right side of the screen
- Multiple awards appear sequentially with 4-second delays
- Awards are still visible on the Trophy Room page

## Testing Steps

### Test 1: First Quiz Completion (Badges)

1. **Setup**: Create a new test user who has never completed a quiz
2. **Action**: Complete any quiz
3. **Expected Result**: 
   - Quiz results modal appears
   - After 500ms, award notification slides in from the right
   - Should see "Getting Started" badge (for completing first page)
   - Should see "First Test" badge (for completing first exercise)
   - Notifications auto-hide after 3 seconds each
4. **Verify**: Navigate to Trophy Room page and confirm badges are displayed there too

### Test 2: Perfect Score (Shield)

1. **Setup**: Use a user who has completed at least one quiz
2. **Action**: Complete a quiz with 100% score
3. **Expected Result**:
   - Quiz results show 100% score
   - "100%!" shield notification appears
   - If this is the first perfect score, also see "Perfectionist" badge
4. **Verify**: Check Trophy Room for both awards

### Test 3: Multiple Awards at Once

1. **Setup**: Create conditions where multiple awards can be earned
   - New user completing their 5th quiz with 100% score
2. **Action**: Complete the quiz
3. **Expected Result**:
   - Quiz results modal appears
   - Multiple award notifications appear sequentially
   - Each notification has a 4-second delay
   - Notifications slide in/out smoothly
4. **Verify**: All earned awards appear in Trophy Room

### Test 4: Time-Based Awards

**Early Bird Test:**
1. **Setup**: User has not earned "Early Bird" badge
2. **Action**: Complete a quiz before 9 AM
3. **Expected Result**: "Early Bird" badge notification appears

**Night Owl Test:**
1. **Setup**: User has not earned "Night Owl" badge
2. **Action**: Complete a quiz after 9 PM
3. **Expected Result**: "Night Owl" badge notification appears

### Test 5: No Duplicate Notifications

1. **Setup**: User who has already earned some awards
2. **Action**: Complete a quiz that doesn't trigger new awards
3. **Expected Result**: 
   - Quiz results appear normally
   - No award notifications appear
   - No JavaScript errors in console

### Test 6: Awards Wall Page Still Works

1. **Action**: Navigate to Trophy Room/Awards Wall page
2. **Expected Result**:
   - All earned awards display correctly
   - Tab filtering works (All, Badges, Shields, Trophies)
   - Locked awards appear grayed out
   - Earned awards show earn date
   - No duplicate notification elements in DOM

## Technical Verification

### Browser Console Checks

1. **Before Quiz Submission**:
   ```javascript
   // Check that IELTSAwards is available
   console.log(window.IELTSAwards);
   // Should show: {showAwardNotifications: ƒ}
   ```

2. **After Quiz Submission**:
   ```javascript
   // In Network tab, check AJAX response includes new_awards
   // Response should have: { success: true, data: { ..., new_awards: [...] } }
   ```

3. **Check Notification Element**:
   ```javascript
   // Verify notification element exists in DOM
   console.log($('#ielts-award-notification').length);
   // Should be: 1
   ```

### DOM Structure Check

**Award Notification Element:**
```html
<div id="ielts-award-notification" class="ielts-award-notification" style="display: none;">
    <div class="award-notification-content">
        <div class="award-notification-icon"></div>
        <div class="award-notification-text">
            <h3>Award Earned!</h3>
            <p class="award-notification-name"></p>
            <p class="award-notification-description"></p>
        </div>
    </div>
</div>
```

This element should appear exactly once in the page footer.

## Award Types and Triggers

### Badges (15 total)
- **Getting Started**: Complete first page
- **First Test**: Complete first exercise
- **Five Strong**: Complete 5 exercises
- **Perfect Ten**: Complete 10 exercises
- **Twenty Champion**: Complete 20 exercises
- **Half Century**: Complete 50 exercises
- **Perfectionist**: First 100% score
- **Perfect Streak**: 5 perfect scores
- **Early Bird**: Exercise before 9 AM
- **Night Owl**: Exercise after 9 PM
- **Week Warrior**: 7 day streak
- **Monthly Master**: 30 day streak
- **Speed Demon**: Exercise in under 5 minutes
- **Word Wizard**: 10 vocabulary exercises
- **Grammar Guru**: 10 grammar exercises

### Shields (20 total)
- **100%!**: Get 100% on any test
- **First Lesson Done**: First lesson completion
- **Lesson Leader**: 5 lessons
- **Lesson Legend**: 10 lessons
- And more...

### Trophies (15 total)
- **Course Complete**: First course completion
- **Triple Threat**: 3 courses
- **Master Student**: All courses
- **Perfect Course**: 100% on all exercises in a course
- And more...

## Known Behaviors

1. **Timing**: Notifications appear 500ms after the quiz results modal to ensure the modal is fully visible
2. **Stacking**: Multiple awards appear sequentially with 4-second delays, not all at once
3. **Persistence**: Awards are only shown once per earning - refreshing the page won't show them again
4. **Mobile**: Notifications are responsive and adjust for mobile screens

## Troubleshooting

### Notifications Not Appearing

1. **Check User Login**: Notifications only appear for logged-in users
2. **Check Console**: Look for JavaScript errors
3. **Check Network Tab**: Verify `new_awards` array in AJAX response
4. **Check Scripts**: Verify awards.js and awards.css are loaded
5. **Check Element**: Verify `#ielts-award-notification` exists in DOM

### Duplicate Notifications

1. **Clear Cache**: Browser cache might have old versions
2. **Check Template**: Ensure awards-wall.php doesn't have duplicate elements
3. **Check Footer**: Ensure only one notification element in page

### Awards Not Triggering

This is a separate issue from notification display. Check:
1. Award logic in `class-awards.php`
2. Hook triggers in `class-quiz-handler.php`
3. Database tables and user meta

## Files Modified

1. **includes/class-ielts-course-manager.php**: Global script enqueuing and notification element
2. **includes/class-quiz-handler.php**: Return awards in AJAX response
3. **assets/js/awards.js**: Expose showAwardNotifications function
4. **assets/js/frontend.js**: Trigger notifications on quiz submission
5. **templates/awards-wall.php**: Remove duplicate elements
6. **includes/class-shortcodes.php**: Remove duplicate script enqueuing
