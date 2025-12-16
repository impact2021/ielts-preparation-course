# Structure Rebuild - Interface Guide

## Interface Overview

The Structure Rebuild tool has two main screens:

1. **Input Form** - Where you enter the course structure
2. **Structure Editor** - Where you review and edit before creation

## Screen 1: Input Form

### Location
Navigate to: **IELTS Courses → Rebuild from LearnDash**

### Form Fields

```
┌─────────────────────────────────────────────────────────────┐
│ Rebuild Course Structure from LearnDash                      │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ ℹ️ About This Tool:                                          │
│ This tool helps you rebuild your course structure when the   │
│ XML export doesn't preserve relationships...                 │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ How to Use This Tool                                         │
│                                                               │
│ Step 1: Get LearnDash Course Structure                       │
│ • Option 1: Copy HTML (Recommended)                          │
│   - Open course page in LearnDash                            │
│   - Right-click on curriculum section                        │
│   - Select "Inspect" or "Inspect Element"                    │
│   - Copy outer HTML of curriculum container                  │
│                                                               │
│ • Option 2: Manual Entry                                     │
│   - List lessons and topics in structured format             │
│   - Use indentation to show hierarchy                        │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ Upload LearnDash XML Export                                  │
│                                                               │
│ Course Name:  [_____________________________]                │
│               Enter the name of the course                   │
│                                                               │
│ LearnDash HTML or Structure:                                 │
│ ┌───────────────────────────────────────────────────────┐   │
│ │                                                         │   │
│ │  [Paste HTML or text here]                             │   │
│ │                                                         │   │
│ │                                                         │   │
│ │                                                         │   │
│ │                                                         │   │
│ └───────────────────────────────────────────────────────┘   │
│ Paste the HTML from LearnDash or enter structured list      │
│                                                               │
│ Input Type:                                                   │
│ ⚪ HTML from LearnDash                                        │
│ ⚪ Plain text structure                                       │
│                                                               │
│                    [ Parse Structure ]                        │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ ⚠️ Example Plain Text Structure:                             │
│                                                               │
│   Introduction to IELTS                                      │
│   - Reading Skills                                           │
│     - Skimming and Scanning                                  │
│     - Understanding Main Ideas                               │
│   - Writing Task 1                                           │
│     - Describing Graphs                                      │
│                                                               │
│   Use this format for plain text input...                    │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## Screen 2: Structure Editor

After clicking "Parse Structure", you'll see:

```
┌─────────────────────────────────────────────────────────────┐
│ Review and Edit Course Structure                             │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ ℹ️ Structure Parsed Successfully!                            │
│ Review the course structure below. You can drag and drop     │
│ items to reorder them, or click "Edit" to modify names.      │
│ When ready, click "Create Course Structure"...               │
│                                                               │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│ IELTS Preparation Course                                     │
│                                                               │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │                                                           │ │
│ │ ⋮⋮ Introduction to IELTS                     Order: 0    │ │
│ │   ⋮⋮ What is IELTS?                          Order: 0    │ │
│ │   ⋮⋮ Test Format Overview                    Order: 1    │ │
│ │   ⋮⋮ Scoring System                          Order: 2    │ │
│ │                                                           │ │
│ │ ⋮⋮ Reading Skills                            Order: 1    │ │
│ │   ⋮⋮ Skimming and Scanning                   Order: 0    │ │
│ │   ⋮⋮ Understanding Main Ideas                Order: 1    │ │
│ │   ⋮⋮ Detail Questions                        Order: 2    │ │
│ │                                                           │ │
│ │ ⋮⋮ Writing Task 1                            Order: 2    │ │
│ │   ⋮⋮ Describing Graphs                       Order: 0    │ │
│ │   ⋮⋮ Comparing Data                          Order: 1    │ │
│ │                                                           │ │
│ │ ⋮⋮ Listening Practice                        Order: 3    │ │
│ │   ⋮⋮ Section 1: Conversations                Order: 0    │ │
│ │   ⋮⋮ Section 2: Monologues                   Order: 1    │ │
│ │                                                           │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                               │
│ [ ← Back to Input ]     [ Create Course Structure ]          │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

### Visual Elements

**Lessons** (Dark blue left border):
```
┌──────────────────────────────────────────┐
│⋮⋮ Lesson Name                  Order: 0  │
│  ⋮⋮ Topic Name 1               Order: 0  │
│  ⋮⋮ Topic Name 2               Order: 1  │
└──────────────────────────────────────────┘
```

**Topics** (Light blue left border, indented):
```
  ┌────────────────────────────────────────┐
  │⋮⋮ Topic Name              Order: 0     │
  └────────────────────────────────────────┘
```

**Drag Handles** (⋮⋮):
- Click and hold to drag
- Reorder within the same level
- Visual feedback during drag

## Screen 3: Success Message

After clicking "Create Course Structure":

```
┌─────────────────────────────────────────────────────────────┐
│ ✅ Course structure created successfully!                    │
│                                                               │
│ • Course: IELTS Preparation Course (ID: 123)                 │
│ • Lessons created: 4                                         │
│ • Lesson pages created: 11                                   │
│                                                               │
│                    [ Edit Course ]                            │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

## Interactive Features

### Drag and Drop

**To reorder lessons:**
1. Click and hold on the ⋮⋮ handle next to a lesson
2. Drag up or down
3. Drop in the desired position
4. Order updates automatically

**To reorder topics:**
1. Click and hold on the ⋮⋮ handle next to a topic
2. Drag up or down within the lesson
3. Drop in the desired position
4. Topics can only be reordered within their parent lesson

### Visual Feedback

**During Drag:**
- Item being dragged: Slightly transparent, elevated shadow
- Placeholder: Yellow dashed border where item will drop
- Other items: Shift smoothly to make room

**Hover States:**
- Lessons: Light blue background on hover
- Topics: Lighter blue background on hover
- Handles: Darker background on hover

## Color Coding

The interface uses color to distinguish different item types:

**Lessons:**
- Border: `#2271b1` (WordPress blue)
- Background: `#f9f9f9` (light gray)
- Hover: `#e8f4f8` (light blue)

**Topics:**
- Border: `#72aee6` (lighter blue)
- Background: `#f0f6fc` (very light blue)
- Hover: `#e8f4f8` (light blue)

**Drag Placeholder:**
- Background: `#fff3cd` (light yellow)
- Border: `#ffc107` (yellow, dashed)

## Keyboard Navigation

While the interface is primarily mouse-based, keyboard users can:
- Tab between form fields
- Use Enter to submit forms
- Use Esc to dismiss notices

## Responsive Design

The interface adapts to different screen sizes:

**Desktop (>900px):**
- Full-width forms with max-width: 900px
- All features visible
- Optimal drag-and-drop experience

**Tablet (768px-900px):**
- Slightly narrower forms
- Drag-and-drop still works well
- Text wraps appropriately

**Mobile (<768px):**
- Forms take full width
- Drag-and-drop may be challenging
- Consider using desktop for complex structures

## Accessibility

**Screen Readers:**
- All form fields have labels
- Structure tree has semantic HTML
- ARIA labels on drag handles

**Keyboard Users:**
- All interactive elements are focusable
- Visible focus indicators
- Logical tab order

**Color Blindness:**
- Not relying solely on color
- Text labels and order numbers
- High contrast ratios

## Tips for Best Experience

1. **Use a modern browser** (Chrome, Firefox, Safari, Edge)
2. **Desktop recommended** for drag-and-drop editing
3. **Clear form** uses system monospace font for code/HTML
4. **Structure preview** uses WordPress admin font
5. **Success messages** auto-dismiss after you navigate away

## Common UI Patterns

### Loading States
- Form submission shows WordPress loading spinner
- Page redirects after processing
- Transient data clears after display

### Error States
- Red notice boxes for errors
- Clear error messages
- Ability to go back and retry

### Success States
- Green notice boxes for success
- Summary of what was created
- Direct action button (Edit Course)

## Browser Compatibility

**Fully Supported:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

**Basic Support (no drag-and-drop):**
- Older browsers without jQuery UI Sortable
- Can still create structure, just can't reorder

**Not Supported:**
- Internet Explorer (Microsoft recommends Edge)

## Performance

**Parse Time:**
- Small structures (<10 lessons): Instant
- Medium structures (10-30 lessons): 1-2 seconds
- Large structures (30+ lessons): 2-5 seconds

**Render Time:**
- Structure editor renders instantly
- Drag-and-drop responsive even with 50+ items

**Creation Time:**
- WordPress post creation: ~100ms per post
- 20 lessons + 50 topics = ~7 seconds total

## Tips for Large Structures

If you have a very large course (50+ lessons):

1. **Consider splitting** into multiple courses
2. **Use text mode** for faster parsing
3. **Review carefully** before creating
4. **Be patient** during creation (may take 10-20 seconds)
5. **Check creation success** message for counts

## Troubleshooting UI Issues

**Structure not parsing:**
- Check input format (HTML vs. text)
- Verify indentation in text mode
- Try simpler structure first

**Drag-and-drop not working:**
- Check browser compatibility
- Disable browser extensions
- Try refreshing the page

**Items out of order:**
- Use drag-and-drop to fix
- Or delete and recreate structure

**Creation failed:**
- Check error message
- Verify you have admin permissions
- Try with smaller structure

## Next Steps After Creation

Once you see the success message:

1. **Click "Edit Course"** to add course description
2. **Edit each lesson** to add lesson content
3. **Edit each lesson page** to add page content
4. **Create quizzes** separately and assign to course
5. **Preview course** on frontend to verify structure

The created structure is live immediately, but posts are empty until you add content.
