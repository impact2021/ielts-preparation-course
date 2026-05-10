# User Tour - Highlighting Buttons and Interactive Elements

This guide shows you how to highlight specific buttons, areas, and interactive elements during your user tour (like "Submit Quiz" buttons, forms, input fields, etc.).

## 🎯 Key Feature: Element Highlighting

**Good news!** The recommended Shepherd.js library **automatically highlights** any element you attach a tour step to. You don't need any extra code - just point to the element and it gets highlighted!

### How It Works

When you use `attachTo` in a tour step, Shepherd.js automatically:
- ✅ **Highlights the element** with a subtle glow/outline
- ✅ **Dims the rest of the page** with a modal overlay
- ✅ **Scrolls the element into view** smoothly
- ✅ **Shows a tooltip/popover** next to the element

---

## 📝 Example: Highlighting a "Submit Quiz" Button

Here's a complete example showing how to highlight the quiz submit button:

```javascript
// In your user-tour.js file

tour.addStep({
    id: 'submit-quiz',
    text: '<h3>Submit Your Answers ✓</h3><p>When you\'re done, click this button to submit your quiz and see your results!</p>',
    attachTo: {
        element: '#ielts-quiz-form button[type="submit"]',  // CSS selector for submit button
        on: 'top'  // Show tooltip above the button
    },
    buttons: [
        { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
        { text: 'Got it!', action: tour.next }
    ]
});
```

### What This Does:

1. **Finds** the submit button using the CSS selector
2. **Highlights** the button with a glowing effect
3. **Darkens** everything else on the page
4. **Shows** a tooltip above the button explaining what it does
5. **Scrolls** the button into view if it's off-screen

---

## 🎨 Visual Highlighting Styles

Shepherd.js provides different highlight styles out of the box:

### Default Highlight
```javascript
// Subtle white glow around the element (default)
tour.addStep({
    id: 'example',
    text: 'This element is highlighted!',
    attachTo: { element: '.my-button', on: 'bottom' }
});
```

### Custom Highlight Color
```css
/* Add to your assets/css/user-tour.css */

.shepherd-target {
    /* The highlighted element gets this class */
    box-shadow: 0 0 0 99999px rgba(0, 0, 0, 0.5),  /* Dark overlay */
                0 0 20px rgba(59, 130, 246, 0.8);    /* Blue glow - customize color! */
    position: relative;
    z-index: 9999;
}

/* For IELTS brand colors */
.shepherd-target {
    box-shadow: 0 0 0 99999px rgba(0, 0, 0, 0.5),
                0 0 20px rgba(30, 64, 175, 0.8);  /* IELTS blue */
}
```

---

## 🔘 Common Elements to Highlight

### 1. Submit Quiz Button

```javascript
tour.addStep({
    id: 'quiz-submit',
    text: '<h3>Submit Quiz ✓</h3><p>Click here when you\'re ready to see your score!</p>',
    attachTo: {
        element: 'button[type="submit"]',  // or '#submit-quiz-btn'
        on: 'top'
    }
});
```

### 2. Quiz Questions Area

```javascript
tour.addStep({
    id: 'quiz-questions',
    text: '<h3>Answer the Questions 📝</h3><p>Read each question carefully and select your answer.</p>',
    attachTo: {
        element: '.ielts-single-quiz',  // Highlight entire quiz container
        on: 'top'
    }
});
```

### 3. Timer Display

```javascript
tour.addStep({
    id: 'timer',
    text: '<h3>Watch the Timer ⏱️</h3><p>Complete the quiz before time runs out!</p>',
    attachTo: {
        element: '#quiz-timer',  // Your timer element
        on: 'bottom'
    }
});
```

### 4. Answer Input Field

```javascript
tour.addStep({
    id: 'answer-input',
    text: '<h3>Type Your Answer ✏️</h3><p>Enter your response in this field.</p>',
    attachTo: {
        element: 'input[name="answer_1"]',  // Specific input field
        on: 'right'
    }
});
```

### 5. Navigation Buttons

```javascript
tour.addStep({
    id: 'next-question',
    text: '<h3>Move Between Questions →</h3><p>Use these buttons to navigate through the quiz.</p>',
    attachTo: {
        element: '.quiz-navigation .next-button',
        on: 'bottom'
    }
});
```

### 6. Score Display

```javascript
tour.addStep({
    id: 'score',
    text: '<h3>Your Score 🎯</h3><p>See your results here after submitting!</p>',
    attachTo: {
        element: '.quiz-score',
        on: 'left'
    }
});
```

### 7. Feedback Section

```javascript
tour.addStep({
    id: 'feedback',
    text: '<h3>Review Feedback 📊</h3><p>Check detailed explanations for each question.</p>',
    attachTo: {
        element: '.quiz-feedback-section',
        on: 'top'
    }
});
```

---

## 🎯 Complete Quiz Tour Example

Here's a full tour specifically for quiz-taking, highlighting all important buttons and areas:

```javascript
/**
 * Quiz-specific tour for first-time quiz takers
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Only run on quiz pages
        if (!$('.ielts-single-quiz').length) return;
        
        // Check if user has seen quiz tour
        if (localStorage.getItem('ielts_quiz_tour_completed')) return;
        
        if (typeof Shepherd === 'undefined') return;
        
        const quizTour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: { enabled: true },
                classes: 'ielts-quiz-tour-step',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        });
        
        // Step 1: Welcome to Quiz
        quizTour.addStep({
            id: 'quiz-welcome',
            text: '<h3>Your First Quiz! 📝</h3><p>Let me show you around before you start.</p>',
            buttons: [
                { text: 'Skip', classes: 'shepherd-button-secondary', action: quizTour.complete },
                { text: 'Show me around', action: quizTour.next }
            ]
        });
        
        // Step 2: Highlight Timer
        quizTour.addStep({
            id: 'quiz-timer',
            text: '<h3>Timer ⏱️</h3><p>Keep an eye on this! You have limited time to complete the quiz.</p>',
            attachTo: {
                element: '#quiz-timer',
                on: 'bottom'
            },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: quizTour.back },
                { text: 'Next', action: quizTour.next }
            ]
        });
        
        // Step 3: Highlight Question Area
        quizTour.addStep({
            id: 'quiz-questions',
            text: '<h3>Questions 📋</h3><p>Read each question carefully. Take your time to understand what\'s being asked.</p>',
            attachTo: {
                element: '.quiz-question:first',  // First question
                on: 'right'
            },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: quizTour.back },
                { text: 'Next', action: quizTour.next }
            ]
        });
        
        // Step 4: Highlight Answer Area
        quizTour.addStep({
            id: 'quiz-answers',
            text: '<h3>Select Your Answer ✓</h3><p>Click on your chosen answer. You can change it before submitting.</p>',
            attachTo: {
                element: '.quiz-question:first .quiz-options',  // Answer options
                on: 'left'
            },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: quizTour.back },
                { text: 'Next', action: quizTour.next }
            ]
        });
        
        // Step 5: Highlight Submit Button (IMPORTANT!)
        quizTour.addStep({
            id: 'quiz-submit',
            text: '<h3>Submit Quiz ✅</h3><p><strong>Important:</strong> When you\'ve answered all questions, click this button to submit and see your results!</p>',
            attachTo: {
                element: '#ielts-quiz-form button[type="submit"]',
                on: 'top'
            },
            buttons: [
                { text: 'Back', classes: 'shepherd-button-secondary', action: quizTour.back },
                { text: 'Got it!', action: quizTour.next }
            ]
        });
        
        // Step 6: Final Tips
        quizTour.addStep({
            id: 'quiz-tips',
            text: '<h3>You\'re Ready! 🎯</h3><p><strong>Tips:</strong><br/>• Answer all questions<br/>• Watch the timer<br/>• Review before submitting<br/><br/>Good luck!</p>',
            buttons: [
                { text: 'Start Quiz!', action: quizTour.complete }
            ]
        });
        
        // Save completion
        quizTour.on('complete', function() {
            localStorage.setItem('ielts_quiz_tour_completed', 'true');
        });
        
        // Auto-start after short delay
        setTimeout(() => quizTour.start(), 1500);
    });
    
})(jQuery);
```

---

## 📍 Tooltip Positioning

Control where the tooltip appears relative to the highlighted element:

```javascript
attachTo: {
    element: '.my-button',
    on: 'top'     // Options: 'top', 'bottom', 'left', 'right'
}
```

### Positioning Options:

| Position | Best For |
|----------|----------|
| `'top'` | Buttons at bottom of screen |
| `'bottom'` | Header elements, navigation |
| `'left'` | Right-side buttons/panels |
| `'right'` | Left-side menus |
| `'auto'` | Let Shepherd decide (default) |

### Advanced Positioning:

```javascript
attachTo: {
    element: '.submit-button',
    on: 'bottom-start'  // Bottom-left corner of element
}
```

Options: `top-start`, `top-end`, `bottom-start`, `bottom-end`, `left-start`, `left-end`, `right-start`, `right-end`

---

## 🎨 Customizing the Highlight Effect

### Stronger Highlight

```css
/* In assets/css/user-tour.css */

.shepherd-target {
    /* Stronger glow */
    box-shadow: 0 0 0 99999px rgba(0, 0, 0, 0.7),  /* Darker overlay */
                0 0 30px 5px rgba(59, 130, 246, 1);  /* Stronger blue glow */
    border-radius: 4px;  /* Rounded corners */
}
```

### Pulsing Animation

```css
@keyframes pulse-highlight {
    0%, 100% {
        box-shadow: 0 0 0 99999px rgba(0, 0, 0, 0.5),
                    0 0 20px rgba(59, 130, 246, 0.8);
    }
    50% {
        box-shadow: 0 0 0 99999px rgba(0, 0, 0, 0.5),
                    0 0 30px rgba(59, 130, 246, 1);
    }
}

.shepherd-target {
    animation: pulse-highlight 2s infinite;
}
```

### No Overlay (Just Highlight Element)

```javascript
// Remove dark overlay, just highlight the element
const tour = new Shepherd.Tour({
    useModalOverlay: false,  // Turn off overlay
    // ... rest of config
});

// Then add custom highlight via CSS
```

```css
.shepherd-target {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.5);  /* Just a border glow */
    position: relative;
    z-index: 1000;
}
```

---

## 🔍 Finding Element Selectors

### How to Find the Right Selector for Your Buttons

1. **Open your quiz page** in Chrome/Firefox
2. **Right-click** on the submit button (or any element you want to highlight)
3. **Select "Inspect"** from the menu
4. **Look for**:
   - `id="..."` → Use `#submit-button`
   - `class="..."` → Use `.submit-button`
   - `name="..."` → Use `[name="submit"]`
   - `type="..."` → Use `button[type="submit"]`

### Common IELTS Course Selectors

Based on your codebase:

```javascript
// Quiz submit button
'#ielts-quiz-form button[type="submit"]'

// Quiz container
'.ielts-single-quiz'

// Timer
'#quiz-timer'

// Question elements
'.quiz-question'

// Answer options
'.quiz-options'

// Score display
'.quiz-score'

// Feedback section
'.quiz-feedback-section'
```

---

## 💡 Pro Tips for Highlighting

### 1. Test Element Exists First

```javascript
// Only show step if element exists on page
if ($('#submit-button').length) {
    tour.addStep({
        id: 'submit',
        attachTo: { element: '#submit-button', on: 'top' }
    });
}
```

### 2. Highlight Multiple Elements

```javascript
// Show submit AND cancel buttons
tour.addStep({
    id: 'quiz-actions',
    text: '<h3>Quiz Actions</h3><p>Submit your answers or cancel to exit.</p>',
    attachTo: {
        element: '.quiz-actions',  // Parent container of both buttons
        on: 'top'
    }
});
```

### 3. Dynamic Elements

```javascript
// Wait for element to load
setTimeout(() => {
    tour.addStep({
        id: 'dynamic-element',
        attachTo: { element: '.loaded-later', on: 'bottom' }
    });
    tour.show('dynamic-element');
}, 1000);
```

### 4. Scroll Into View

```javascript
tour.addStep({
    id: 'bottom-button',
    attachTo: { element: '.footer-submit', on: 'top' },
    scrollTo: { 
        behavior: 'smooth',  // Smooth scrolling
        block: 'center'      // Center in viewport
    }
});
```

---

## 🎬 Context-Specific Tours

### Tour for Quiz Page Only

```javascript
// Only show on quiz pages
if ($('.ielts-single-quiz').length && !localStorage.getItem('quiz_tour_done')) {
    startQuizTour();
}
```

### Tour for Practice Test Page

```javascript
// Only show on practice test pages
if (window.location.pathname.includes('/practice-test/')) {
    startPracticeTestTour();
}
```

### Tour for Trophy Room

```javascript
// Only show on trophy/awards page
if ($('.awards-wall').length && !localStorage.getItem('trophy_tour_done')) {
    startTrophyTour();
}
```

---

## 📋 Complete Implementation Checklist

To highlight buttons and areas in your tour:

- [x] Choose elements to highlight (submit button, quiz area, etc.)
- [x] Find CSS selectors for each element
- [x] Add tour steps with `attachTo` pointing to each element
- [x] Choose tooltip position (`on: 'top'`, etc.)
- [x] Test on actual quiz page
- [x] Customize highlight styling (optional)
- [x] Add animations (optional)
- [x] Save tour completion to localStorage

---

## 🚀 Quick Start: Add Submit Button Highlight

**Simplest possible example** to highlight the submit button:

```javascript
// Add this step to your existing tour in user-tour.js

tour.addStep({
    id: 'submit-quiz',
    text: '<h3>Submit Your Quiz ✓</h3><p>Click here when done!</p>',
    attachTo: {
        element: 'button[type="submit"]',  // Your submit button
        on: 'top'                          // Tooltip above button
    },
    buttons: [
        { text: 'Got it!', action: tour.next }
    ]
});
```

**That's it!** The submit button will automatically be highlighted with a glowing effect when this step shows.

---

## 🎨 Visual Examples

### What Users Will See:

1. **Page darkens** (modal overlay)
2. **Submit button glows** (highlighted in the spotlight)
3. **Tooltip appears** above/below the button
4. **User can't click** anywhere except the tooltip buttons
5. **Button stands out** clearly from the rest of the page

### Example Flow:

```
Step 1: "Welcome to your first quiz!"
  → No element highlighted, just welcome message

Step 2: "This is the timer" 
  → Timer element highlighted, page darkened

Step 3: "Answer the questions here"
  → Question area highlighted, shows glow

Step 4: "Submit when ready" ← YOUR SUBMIT BUTTON
  → Submit button HIGHLIGHTED and GLOWING
  → User can't miss it!
```

---

## 🔧 Troubleshooting Highlights

### Highlight Not Showing

**Problem**: Element not getting highlighted

**Solutions**:
1. Check selector is correct: `console.log($('.my-button').length)`
2. Ensure `useModalOverlay: true` in tour config
3. Verify element exists when tour runs
4. Check element isn't hidden (`display: none`)

### Wrong Element Highlighted

**Problem**: Highlighting wrong button

**Solutions**:
1. Use more specific selector: `#quiz-form button[type="submit"]` instead of `button`
2. Use browser inspector to verify selector
3. Add ID to your button if needed

### Highlight Behind Other Elements

**Problem**: Element highlighted but covered by other content

**Solutions**:
```css
.shepherd-target {
    z-index: 10000 !important;  /* Bring to front */
}
```

---

## 📚 Additional Resources

- **Live Demo**: https://shepherdjs.dev/demo/ (See highlighting in action)
- **Full Guide**: [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md)
- **Quick Start**: [USER_TOUR_QUICK_START.md](USER_TOUR_QUICK_START.md)

---

## ✅ Summary

**Highlighting buttons and areas is EASY!** Just add `attachTo` to your tour step:

```javascript
tour.addStep({
    text: 'This is important!',
    attachTo: { 
        element: '#your-button',  // CSS selector
        on: 'top'                 // Position
    }
});
```

Shepherd.js automatically:
- ✅ Highlights the element
- ✅ Darkens the rest of the page
- ✅ Scrolls element into view
- ✅ Shows tooltip next to element

**No extra code needed for basic highlighting!** It just works. 🎉
