# User Tour Implementation Resources

This directory contains comprehensive guides for implementing a user tour/onboarding experience for the IELTS Preparation Course.

## 📚 Available Guides

### 1. [Quick Start Guide](USER_TOUR_QUICK_START.md) ⚡
**Start here if you want to implement quickly (30 minutes)**

- 3-step setup process
- Copy-paste code examples
- Minimal explanation, maximum efficiency
- Perfect for getting something working fast

### 2. [Complete Implementation Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md) 📖
**Read this for detailed explanations and advanced features**

- Full explanation of how everything works
- Customization options and examples
- Advanced features (analytics, multi-tour, video steps)
- Troubleshooting guide
- FAQs and best practices

### 3. [Highlighting Buttons & Areas Guide](USER_TOUR_HIGHLIGHTING_EXAMPLES.md) 🎯
**Read this to learn how to highlight specific elements**

- How to highlight submit buttons, forms, and interactive elements
- Complete quiz tour example
- Custom styling for highlighted elements
- Tips for finding CSS selectors
- Visual examples and best practices

### 4. [Membership-Specific Tours Guide](USER_TOUR_MEMBERSHIP_SPECIFIC.md) 👥 NEW!
**Read this for different tours based on membership type**

- Create separate tours for Academic, General Training, and English memberships
- Database persistence for cross-device support
- How to detect and use membership type
- Complete code examples for each membership
- Testing different membership scenarios

### 5. [Visual Guide](USER_TOUR_VISUAL_GUIDE.md) 🎨
**See visual examples of how highlighting works**

- Visual diagrams showing highlighting effects
- Tooltip positioning examples
- Step-by-step animation sequences
- Mobile vs desktop comparison

### 6. [Library Comparison](USER_TOUR_LIBRARY_COMPARISON.md) 🔍
**Read this if you're deciding which approach to use**

- Comparison of Shepherd.js, Intro.js, Driver.js, and WordPress plugins
- Pros/cons of each approach
- Feature comparison table
- Performance analysis
- Recommendation for IELTS Course (Shepherd.js)

---

## 🎯 Quick Answer to Your Questions

> "How difficult would it be to create a user tour for first time users? Would I have to add a shortcode for each stage or how would it work?"

> "I would also want it to do things like highlight a button or an area (e.g. Submit a quiz)"

> "There would also need to be a different tour for different memberships - people on a general training membership need to see the general training pages. So that's a tour for general training, academic and English. It would have to save to the user's account to say they've seen it or skipped it in case they login from a different device."

### Answer:

**Difficulty**: Easy to Moderate (1-2 hours)

**No shortcodes needed!** The tour is JavaScript-based. You define all tour steps in one JavaScript file.

**Highlighting is automatic!** When you point to an element (like a submit button), it automatically gets highlighted with a glow effect while the rest of the page dims. No extra code needed!

**Different tours for each membership!** The system automatically detects if a user has Academic, General Training, or English membership and shows the appropriate tour with relevant content.

**Cross-device persistence!** Tour completion is saved to the WordPress database (user meta), so when users login from a different device (phone, tablet, etc.), they won't see the tour again.

**What you need:**
1. Add Shepherd.js library (via CDN) - 2 minutes
2. Create tour configuration file with 3 membership-specific tours - 30 minutes  
3. Update PHP to pass membership type to JavaScript - 5 minutes
4. Update AJAX handler to save with membership type - 5 minutes
5. Test each membership type - 20 minutes

**Total time**: ~1 hour for complete implementation with all 3 membership types

**Example structure:**
```javascript
// Detects membership automatically
if (tourType === 'academic') {
    // Show Academic-specific tour (Academic tests, Academic reading, etc.)
} else if (tourType === 'general') {
    // Show General Training tour (General tests, General reading, etc.)
} else if (tourType === 'english') {
    // Show English-only tour
}

// Saves to database (works across devices)
update_user_meta($user_id, 'ielts_tour_completed_academic', true);
```

**Result**: Each membership type gets a customized tour, saves to database, works on all devices! ✨

---

## 🚀 Recommended Approach

We recommend using **Shepherd.js** because:

✅ **Free** - MIT licensed, no costs  
✅ **Easy** - No shortcodes, just JavaScript configuration  
✅ **Modern** - Beautiful, professional UI  
✅ **Flexible** - Easy to customize for IELTS branding  
✅ **Accessible** - Works for international students  
✅ **Maintained** - Active development, good support  

---

## 📋 What You'll Build

A guided tour that automatically shows first-time users:

1. **Welcome Message** - Friendly introduction
2. **Main Navigation** - Where the menu is located (highlighted)
3. **Practice Tests** - How to access test materials (highlighted)
4. **Trophy Room** - Where to view achievements (highlighted)
5. **Progress Dashboard** - How to track learning (highlighted)
6. **Submit Quiz Button** - How to complete quizzes (highlighted with glow!)
7. **Getting Started** - Encouragement to begin

**Each highlighted element:**
- 🌟 Glows with a spotlight effect
- 🎯 Stands out from dimmed background
- 📍 Shows helpful tooltip/explanation
- ↕️ Scrolls into view automatically

All with **no shortcodes** - just JavaScript!

---

## 🎬 Implementation Path

### Option 1: Quick Implementation (30 mins)
→ Follow [Quick Start Guide](USER_TOUR_QUICK_START.md)

### Option 2: Full Implementation (2 hours)
→ Follow [Complete Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md)

### Option 3: Research First
→ Read [Library Comparison](USER_TOUR_LIBRARY_COMPARISON.md)  
→ Then follow Quick Start Guide

---

## 💡 Key Points

### No Shortcodes Required
Unlike other WordPress features, the tour doesn't need shortcodes. You define all steps in JavaScript:

```javascript
tour.addStep({
    id: 'welcome',
    text: 'Welcome to IELTS Course!',
    buttons: [
        { text: 'Next', action: tour.next }
    ]
});
```

### Automatic Element Highlighting
When you want to highlight a button or area (like "Submit Quiz"), just point to it:

```javascript
tour.addStep({
    id: 'submit-button',
    text: 'Click here when you finish!',
    attachTo: { 
        element: 'button[type="submit"]',  // ← This button gets highlighted!
        on: 'top'  // Tooltip appears above
    }
});
```

**What happens automatically:**
- ✅ The element **glows** with a spotlight effect
- ✅ Rest of page **dims** (dark overlay)
- ✅ Element **scrolls into view** smoothly
- ✅ **Tooltip appears** next to the element
- ✅ User **can't miss it**!

**No extra code needed** - highlighting is built into Shepherd.js!

### Easy Setup for You
As requested, the setup is as easy as possible:

1. **Copy 3 code blocks** into existing files
2. **Create 1 new JavaScript file** with your tour steps
3. **Update CSS selectors** to match your site (5 minutes)
4. **Test** - Clear cache and reload

Done! 🎉

### Adding More Steps Later
Just edit the JavaScript file and add new steps:

```javascript
tour.addStep({
    id: 'new-feature',
    text: 'Check out this new feature!',
    attachTo: { element: '.new-feature-selector', on: 'bottom' }
});
```

No database changes, no shortcodes, no complexity.

---

## 🔧 Tech Stack

**What we're using:**
- **Shepherd.js** - User tour library (free, open source)
- **WordPress** - Your existing platform
- **JavaScript/jQuery** - Already in your plugin
- **AJAX** - To save tour completion status

**What you're NOT adding:**
- ❌ New database tables
- ❌ Complex dependencies
- ❌ Paid services
- ❌ Shortcodes for each step

---

## 📱 Mobile Support

The recommended solution (Shepherd.js) is fully responsive and works on:
- ✅ Desktop browsers
- ✅ Tablets  
- ✅ Mobile phones
- ✅ All modern browsers (Chrome, Firefox, Safari, Edge)

---

## 🎨 Customization

Easy to customize for IELTS branding:
- Change colors in CSS
- Modify step text in JavaScript
- Add your logo or images
- Adjust timing and animations
- Add/remove steps as needed

All customization happens in **one file** (`user-tour.js`).

---

## 🧪 Testing

Test your tour by:

1. **Clear tour status**:
   ```javascript
   localStorage.removeItem('ielts_tour_completed');
   ```

2. **Reload page** - Tour starts automatically

3. **Test all buttons** - Next, Back, Skip

4. **Test on mobile** - Ensure responsive design

---

## 📊 Analytics (Optional)

Track tour effectiveness:
- Completion rate
- Skip rate  
- Which steps users skip
- Feature usage after tour

See [Complete Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md#analytics-integration) for setup.

---

## 🆘 Support

### Getting Help

1. **Check the guides** - Most questions answered in Complete Guide
2. **Troubleshooting section** - Common issues and fixes
3. **Shepherd.js docs** - https://shepherdjs.dev/
4. **Open an issue** - On this repository

### Common Issues

**Tour doesn't appear**
→ Check browser console for errors
→ Verify Shepherd.js loaded (look for 404s)

**Wrong elements highlighted**
→ Update CSS selectors to match your theme
→ Use browser inspector to find correct selectors

**Tour shows every time**
→ Verify AJAX handler is saving completion status
→ Check user meta is being updated

All issues covered in detail in [Troubleshooting Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md#troubleshooting).

---

## 🎓 Learning Resources

### For Beginners
1. Start with [Quick Start Guide](USER_TOUR_QUICK_START.md)
2. Copy the example code
3. Test and refine

### For Advanced Users
1. Read [Complete Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md)  
2. Explore advanced features
3. Customize extensively

### For Decision Makers
1. Read [Library Comparison](USER_TOUR_LIBRARY_COMPARISON.md)
2. Understand trade-offs
3. Make informed choice

---

## ✅ Summary

**Your Question**: How difficult to create user tour? Do I need shortcodes?

**Our Answer**: 
- **Difficulty**: Easy (1-2 hours setup)
- **Shortcodes**: No! JavaScript-based, no shortcodes needed
- **Ease of Setup**: Very easy - follow Quick Start Guide
- **Maintenance**: Minimal - edit one JavaScript file as needed

**Next Step**: Start with [Quick Start Guide](USER_TOUR_QUICK_START.md) → Get tour running in 30 minutes

---

## 📄 Document Overview

| Document | Best For | Time Required |
|----------|----------|---------------|
| Quick Start | Fast implementation | 30 mins |
| Complete Guide | Understanding details | 1-2 hours |
| **Highlighting Guide** | **Learning to highlight buttons/areas** | **15 mins** |
| **Membership Tours** | **Different tours per membership type** | **20 mins** |
| Visual Guide | See how highlighting looks | 10 mins |
| Library Comparison | Making decisions | 20 mins |

---

## 🎯 Recommendation

For your IELTS Preparation Course:

1. **Read** [Quick Start Guide](USER_TOUR_QUICK_START.md) (5 mins)
2. **Implement** the 3-step setup (25 mins)  
3. **Customize** CSS selectors for your site (10 mins)
4. **Test** with cleared cache (10 mins)
5. **Refine** based on testing (10 mins)

**Total**: ~1 hour to working user tour

Then optionally:
- Add more steps
- Customize styling
- Add analytics
- Optimize for mobile

All very straightforward!

---

## 🚀 Ready to Start?

→ Go to [Quick Start Guide](USER_TOUR_QUICK_START.md)

Have questions? Check the [Complete Guide](USER_TOUR_IMPLEMENTATION_GUIDE.md) FAQ section.

Good luck with your user tour implementation! 🎉
