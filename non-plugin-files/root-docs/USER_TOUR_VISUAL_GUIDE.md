# Visual Guide: How Element Highlighting Works

This guide shows you visually what happens when you highlight buttons and areas in your user tour.

## 🎨 The Highlighting Effect

### Before Tour Step (Normal Page)

```
┌─────────────────────────────────────────┐
│  IELTS Preparation Course               │
├─────────────────────────────────────────┤
│  [Menu] [Practice Tests] [Trophy Room]  │
├─────────────────────────────────────────┤
│                                          │
│  Quiz Question 1:                        │
│  What is the capital of France?          │
│                                          │
│  ( ) London                              │
│  ( ) Berlin                              │
│  ( ) Paris                               │
│  ( ) Madrid                              │
│                                          │
│  Timer: 10:00                            │
│                                          │
│  [Submit Quiz]                           │
│                                          │
└─────────────────────────────────────────┘
```
*Everything looks normal - no highlighting*

---

### During Tour Step (Highlighting Submit Button)

```
┌─────────────────────────────────────────┐
│  IELTS Preparation Course               │
├─────────────────────────────────────────┤
│  [Menu] [Practice Tests] [Trophy Room]  │  ← DIMMED (dark overlay)
├─────────────────────────────────────────┤
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │  ← DIMMED
│  ░ Quiz Question 1:              ░       │
│  ░ What is the capital of France?░       │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░ ( ) London                    ░       │
│  ░ ( ) Berlin                    ░       │
│  ░ ( ) Paris                     ░       │
│  ░ ( ) Madrid                    ░       │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░ Timer: 10:00                  ░       │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│              ┌──────────────────────┐    │
│              │ Click here to submit │    │  ← TOOLTIP
│              │ and see your results!│    │
│              └──────────┬───────────┘    │
│                        │                 │
│              ╔═════════════════════╗     │  ← HIGHLIGHTED!
│              ║  [Submit Quiz]  ✓   ║     │     (Glowing)
│              ╚═════════════════════╝     │
│                                          │
└─────────────────────────────────────────┘
```
*Submit button is GLOWING and HIGHLIGHTED*  
*Everything else is DIMMED*  
*Tooltip explains what to do*

---

## 🎯 What the User Sees

### Visual Effects:

1. **Dark Overlay** (rgba 0,0,0,0.5)
   - Covers entire page
   - Makes background less prominent
   - Focuses attention on highlighted element

2. **Highlighted Element** (the submit button)
   - Stands out bright and clear
   - Has a glowing border/shadow
   - Appears "on top" of the overlay
   - User can still click it (if allowed by tour)

3. **Tooltip/Popover**
   - Appears next to the element
   - Contains explanatory text
   - Has buttons (Next, Back, etc.)
   - Points to the element with an arrow

4. **Smooth Scroll**
   - Page automatically scrolls
   - Element comes into view
   - Smooth animation
   - Centers element on screen

---

## 📐 Positioning Examples

### Tooltip on Top

```
        ┌────────────────────┐
        │ "Click to submit!" │  ← Tooltip
        └─────────┬──────────┘
                  ↓
        ╔═════════════════╗
        ║  [Submit Quiz]  ║  ← Highlighted Button
        ╚═════════════════╝
```

### Tooltip on Bottom

```
        ╔═════════════════╗
        ║   [Menu Item]   ║  ← Highlighted
        ╚═════════════════╝
                  ↓
        ┌────────────────────┐
        │ "Find everything!" │  ← Tooltip
        └────────────────────┘
```

### Tooltip on Right

```
╔═════════════╗     ┌──────────────────┐
║ [Trophy 🏆] ║ ←── │ "View your wins!" │  ← Tooltip
╚═════════════╝     └──────────────────┘
     ↑
  Highlighted
```

### Tooltip on Left

```
   ┌──────────────┐     ╔═══════════╗
   │ "Track stats"│ ───→ ║ [Progress]║  ← Highlighted
   └──────────────┘     ╚═══════════╝
         ↑
      Tooltip
```

---

## 🌈 Color Variations

### Default (Blue Glow)

```
╔═══════════════════════╗
║  [Submit]  ✓          ║
╚═══════════════════════╝
   ↑ Blue glow (#3B82F6)
```

### Custom IELTS Blue

```css
.shepherd-target {
    box-shadow: 0 0 20px rgba(30, 64, 175, 0.8);  /* IELTS blue */
}
```

```
╔═══════════════════════╗
║  [Submit]  ✓          ║
╚═══════════════════════╝
   ↑ IELTS brand color
```

### Pulsing Animation

```
Frame 1:                Frame 2:                Frame 3:
╔══════════╗           ╔═══════════╗          ╔══════════╗
║ [Submit] ║     →     ║  [Submit]  ║    →    ║ [Submit] ║
╚══════════╝           ╚═══════════╝          ╚══════════╝
  (normal)              (bright glow)           (normal)
     ↑                       ↑                      ↑
  Cycles continuously creating a "breathing" effect
```

---

## 🎬 Step-by-Step Animation Sequence

### User Experience Timeline:

```
Time 0s:     Tour step triggered
             ↓

Time 0.2s:   Dark overlay fades in
             Page starts to dim
             ░░░░░░░░░░░░
             ↓

Time 0.4s:   Element starts glowing
             ╔══════════╗
             ║  Button  ║
             ↓

Time 0.6s:   Tooltip appears with slide-in effect
             ┌──────────┐
             │ Tooltip! │
             ↓

Time 0.8s:   Auto-scroll completes (if needed)
             Element centered on screen
             ↓

Time 1.0s:   Animation complete
             User sees fully highlighted element
             Ready to interact!
```

---

## 📱 Responsive Behavior

### Desktop View (Large Screen)

```
┌──────────────────────────────────────────────────────┐
│                                                       │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│  ░                                                ░   │
│  ░         ┌────────────────────────┐            ░   │
│  ░         │ Click Submit when done │            ░   │
│  ░         └───────────┬────────────┘            ░   │
│  ░                     ↓                         ░   │
│  ░         ╔════════════════════╗                ░   │
│  ░         ║  [Submit Quiz]  ✓  ║                ░   │
│  ░         ╚════════════════════╝                ░   │
│  ░                                                ░   │
│  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  │
│                                                       │
└──────────────────────────────────────────────────────┘
        Wide tooltip, element easily visible
```

### Mobile View (Small Screen)

```
┌────────────────────┐
│ ░░░░░░░░░░░░░░░░░ │
│ ░                ░ │
│ ░ ┌────────────┐ ░ │
│ ░ │Click Submit│ ░ │
│ ░ └──────┬─────┘ ░ │
│ ░        ↓       ░ │
│ ░ ╔════════════╗ ░ │
│ ░ ║  [Submit]  ║ ░ │
│ ░ ╚════════════╝ ░ │
│ ░                ░ │
│ ░░░░░░░░░░░░░░░░░ │
└────────────────────┘
   Compact tooltip
   Takes full width
```

---

## 🎨 Example: Complete Quiz Tour Visual Flow

### Step 1: Welcome (No Element Highlighted)

```
┌──────────────────────┐
│ Welcome to Quiz! 🎉 │  ← Centered modal
│                      │
│  [Skip] [Start Tour] │
└──────────────────────┘
```

### Step 2: Highlight Timer

```
    ┌─────────────────┐
    │ Watch the time! │
    └────────┬────────┘
             ↓
    ╔════════════════╗
    ║  Timer: 10:00  ║  ← Glowing
    ╚════════════════╝
    
    [Back] [Next]
```

### Step 3: Highlight Questions

```
╔══════════════════════════════╗  ← Glowing
║ Q1: What is the capital...?  ║
║                              ║
║ ( ) London                   ║
║ ( ) Paris                    ║
╚══════════════════════════════╝
        ↑
  ┌──────────────┐
  │ Read careful │
  │ [Back] [Next]│
  └──────────────┘
```

### Step 4: Highlight Submit Button

```
    ┌──────────────────────┐
    │ Submit when ready!   │
    └──────────┬───────────┘
               ↓
    ╔═════════════════════╗
    ║  [Submit Quiz]  ✓   ║  ← Glowing strongly
    ╚═════════════════════╝
    
       [Back] [Finish]
```

---

## 💡 Implementation Reality Check

### What You Write (Simple):

```javascript
tour.addStep({
    text: 'Submit your quiz here!',
    attachTo: { element: '#submit-btn', on: 'top' }
});
```

### What User Sees (Automatic):

- ✅ Dark overlay covering page
- ✅ Submit button glowing and highlighted
- ✅ Tooltip with your text
- ✅ Smooth scrolling to button
- ✅ Can't click anything except tour buttons
- ✅ Professional, polished appearance

**All this happens automatically!** You just point to the element.

---

## 🔍 Finding Elements to Highlight

### Using Browser Inspector:

```
1. Right-click element → "Inspect"

2. See in DevTools:
   <button type="submit" id="quiz-submit" class="btn-primary">
       Submit Quiz
   </button>

3. Choose selector:
   ✓ By ID:    '#quiz-submit'
   ✓ By class: '.btn-primary'
   ✓ By type:  'button[type="submit"]'
   
4. Use in tour:
   attachTo: { element: '#quiz-submit', on: 'top' }
```

---

## 🎯 Common Patterns

### Pattern 1: Button Highlighting

```javascript
// Any button can be highlighted
tour.addStep({
    text: 'Click this button!',
    attachTo: { element: 'button.action-btn', on: 'top' }
});
```

Visual:
```
    ┌──────────────┐
    │ Click this!  │
    └──────┬───────┘
           ↓
    ╔════════════╗
    ║  [Button]  ║  ← Glows
    ╚════════════╝
```

### Pattern 2: Form Field Highlighting

```javascript
// Input field gets highlighted
tour.addStep({
    text: 'Enter your answer here',
    attachTo: { element: 'input[name="answer"]', on: 'right' }
});
```

Visual:
```
╔═══════════════════╗     ┌────────────────┐
║ [____________]    ║ ←── │ Type here      │
╚═══════════════════╝     └────────────────┘
     ↑ Glowing input
```

### Pattern 3: Area/Section Highlighting

```javascript
// Entire section gets highlighted
tour.addStep({
    text: 'This is the quiz area',
    attachTo: { element: '.quiz-container', on: 'top' }
});
```

Visual:
```
        ┌──────────────────┐
        │ This is the quiz │
        └────────┬─────────┘
                 ↓
    ╔══════════════════════════╗
    ║ Quiz Title               ║
    ║                          ║
    ║ Questions go here...     ║
    ║                          ║
    ║ [Submit]                 ║
    ╚══════════════════════════╝
           ↑ Entire box glows
```

---

## 🚀 Quick Reference

### Element Types You Can Highlight:

| Element Type | Example Selector | Use Case |
|-------------|------------------|----------|
| Button | `button[type="submit"]` | Submit actions |
| Link | `a[href*="trophy"]` | Navigation items |
| Input | `input[name="answer"]` | Form fields |
| Div/Section | `.quiz-container` | Content areas |
| Timer | `#quiz-timer` | Status indicators |
| Score | `.score-display` | Results |
| Navigation | `.main-menu` | Site navigation |
| Icon | `.trophy-icon` | Visual elements |

### Tooltip Positions:

```
        top
         ↑
left ← [ELEMENT] → right
         ↓
       bottom
```

---

## ✅ Summary

**Highlighting is EASY and AUTOMATIC!**

1. **Find element** - Use browser inspector
2. **Point to it** - Use `attachTo: { element: '#id', on: 'top' }`
3. **Done!** - Element glows, page dims, tooltip shows

**No extra code needed for:**
- ✅ Glowing effect
- ✅ Dark overlay
- ✅ Scrolling into view
- ✅ Focus management
- ✅ Accessibility

Everything is built-in! Just point and it highlights! 🎉

---

## 📚 Related Guides

- **Implementation**: [USER_TOUR_QUICK_START.md](USER_TOUR_QUICK_START.md)
- **Detailed Examples**: [USER_TOUR_HIGHLIGHTING_EXAMPLES.md](USER_TOUR_HIGHLIGHTING_EXAMPLES.md)
- **Complete Guide**: [USER_TOUR_IMPLEMENTATION_GUIDE.md](USER_TOUR_IMPLEMENTATION_GUIDE.md)
