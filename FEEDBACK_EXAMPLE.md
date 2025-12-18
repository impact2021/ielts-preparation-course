# Feedback System Example

## Example Quiz: Reading Test Facts

This example demonstrates the feedback system working with the exact scenario from the problem statement.

### Question 1: True/False Question

**Question Text:**
```
You have to answer 40 questions
```

**Type:** True/False/Not Given

**Options:**
- True
- False  
- Not Given

**Correct Answer:** `true`

**Correct Answer Feedback:**
```
Correct answer
```

**Incorrect Answer Feedback:**
```
There are 40 questions in the reading test.
```

**Student Experience:**
- Student selects "True" → Sees: ✓ **Question 1: Correct** with message "Correct answer"
- Student selects "False" or "Not Given" → Sees: ✗ **Question 1: Incorrect** with message "There are 40 questions in the reading test."

---

### Question 2: True/False with Detailed Explanation

**Question Text:**
```
There are always 5 different parts to the reading test.
```

**Type:** True/False/Not Given

**Correct Answer:** `false`

**Correct Answer Feedback:**
```
Incorrect
```

**Incorrect Answer Feedback:**
```
It's FALSE because although there are commonly 5 parts (2 parts to Section 1, 2 parts in Section 2 and 1 part in Section 3), this is not ALWAYS the case – it is possible to have 6 different sections, with 3 sections in Section 1.
```

**Student Experience:**
- Student selects "False" → Sees: ✓ **Question 2: Correct** with message "Incorrect"
- Student selects "True" or "Not Given" → Sees: ✗ **Question 2: Incorrect** with detailed explanation

---

### Question 3: HTML Formatted Question

**Question Text (with HTML):**
```html
<strong><span style="color: #3366ff">In the previous presentation, you saw how to prepare a plan for the first paragraph. Now we will look at planning the second paragraph.</span></strong>

<strong><span style="color: #3366ff">Are the ideas below suitable and accurate to include in paragraph 2?</span></strong>

<img class="aligncenter size-full wp-image-23839" src="https://www.ilsnz.com/wp-content/uploads/2018/11/barcharts.png" alt="" width="1379" height="1132" />

<strong>Europe and Australasia equal in 46-60 group</strong>
```

**Type:** True/False/Not Given

**Correct Answer:** `true`

**Correct Answer Feedback:**
```html
<strong style="color: green;">Correct!</strong> The chart shows that Europe and Australasia have equal values in the 46-60 age group.
```

**Incorrect Answer Feedback:**
```html
<strong style="color: red;">Look again at the chart.</strong> Check the values for Europe and Australasia in the 46-60 age group carefully.
```

**Student Experience:**
- **Question Display**: Properly formatted with blue bold text, image displayed, and bold statement
- Student selects "True" → Sees green "Correct!" message with explanation
- Student selects "False" → Sees red "Look again" message with hint

---

### Question 4: Multiple Choice with Per-Option Feedback

**Question Text:**
```
In the General Training module, all three sections are long, formal texts.
```

**Type:** Multiple Choice

**Options:**
```
True
False
```

**Correct Answer:** `1` (False)

**Correct Answer Feedback:**
```
Incorrect
```

**Incorrect Answer Feedback:**
```
Remember to consider the different types of texts in each section.
```

**Per-Option Feedback:**
```
In Sections 1 and 2, the texts can be quite short – sometimes just a timetable or short advert.

```

(First line is for option 0 "True", second line for option 1 "False" - left blank since it's the correct answer)

**Student Experience:**
- Student selects "False" (option 1) → Sees: ✓ **Question 4: Correct** with message "Incorrect"
- Student selects "True" (option 0) → Sees: ✗ **Question 4: Incorrect** with specific feedback: "In Sections 1 and 2, the texts can be quite short – sometimes just a timetable or short advert."

---

## Admin Interface

### Adding Feedback to a Question

When editing a quiz in WordPress admin, for each question you'll see:

```
┌─────────────────────────────────────────────────────────┐
│ Question 1                                               │
├─────────────────────────────────────────────────────────┤
│ Question Type: [True/False/Not Given ▼]                │
│                                                          │
│ Question Text:                                          │
│ ┌─────────────────────────────────────────────────────┐│
│ │ [Rich text editor with formatting toolbar]          ││
│ │ You have to answer 40 questions                     ││
│ └─────────────────────────────────────────────────────┘│
│                                                          │
│ Correct Answer: [true              ]                    │
│                                                          │
│ Points: [1]                                             │
│                                                          │
│ ╔═══════════════════════════════════════════════════╗ │
│ ║ Feedback Messages                                  ║ │
│ ╠═══════════════════════════════════════════════════╣ │
│ ║ Correct Answer Feedback                           ║ │
│ ║ ┌───────────────────────────────────────────────┐ ║ │
│ ║ │ Correct answer                                │ ║ │
│ ║ │                                               │ ║ │
│ ║ └───────────────────────────────────────────────┘ ║ │
│ ║ Shown when the student answers correctly.        ║ │
│ ║ HTML is supported.                               ║ │
│ ║                                                   ║ │
│ ║ Incorrect Answer Feedback                        ║ │
│ ║ ┌───────────────────────────────────────────────┐ ║ │
│ ║ │ There are 40 questions in the reading test.  │ ║ │
│ ║ │                                               │ ║ │
│ ║ └───────────────────────────────────────────────┘ ║ │
│ ║ Shown when the student answers incorrectly.      ║ │
│ ║ HTML is supported.                               ║ │
│ ╚═══════════════════════════════════════════════════╝ │
│                                                          │
│ [Remove Question]                                        │
└─────────────────────────────────────────────────────────┘
```

For Multiple Choice questions, an additional field appears:

```
╔═══════════════════════════════════════════════════╗
║ Per-Option Feedback (Multiple Choice)             ║
╠═══════════════════════════════════════════════════╣
║ Optional: Provide specific feedback for each     ║
║ wrong answer option. Enter one feedback per line, ║
║ matching the order of options above.              ║
║ ┌───────────────────────────────────────────────┐ ║
║ │ In Sections 1 and 2, texts can be quite short│ ║
║ │                                               │ ║
║ │                                               │ ║
║ └───────────────────────────────────────────────┘ ║
╚═══════════════════════════════════════════════════╝
```

---

## Student Quiz Results View

After submitting, students see:

```
┌────────────────────────────────────────────────────┐
│ ✓ Congratulations! You Passed!                     │
│                                                     │
│ Your Score: 15 / 20 (75%)                          │
│                                                     │
│ Great job! You have passed this quiz.              │
│                                                     │
│ ┌─────────────────────────────────────────────────┐│
│ │ Question Feedback                               ││
│ ├─────────────────────────────────────────────────┤│
│ │ ✓ Question 1: Correct                          ││
│ │   Correct answer                               ││
│ │                                                 ││
│ │ ✗ Question 2: Incorrect                        ││
│ │   It's FALSE because although there are        ││
│ │   commonly 5 parts (2 parts to Section 1,      ││
│ │   2 parts in Section 2 and 1 part in Section 3)││
│ │   this is not ALWAYS the case – it is possible ││
│ │   to have 6 different sections, with 3 sections││
│ │   in Section 1.                                ││
│ │                                                 ││
│ │ ✓ Question 3: Correct                          ││
│ │   Correct!                                     ││
│ │                                                 ││
│ │ ✗ Question 4: Incorrect                        ││
│ │   In Sections 1 and 2, the texts can be quite ││
│ │   short – sometimes just a timetable or short  ││
│ │   advert.                                      ││
│ └─────────────────────────────────────────────────┘│
│                                                     │
│ [Take Quiz Again]                                   │
└────────────────────────────────────────────────────┘
```

---

## Summary of Changes

### What Was Fixed:
1. **HTML Rendering**: Changed from `esc_html()` to `wp_kses_post()` so HTML tags render properly instead of showing as text
2. **Feedback Fields Added**: Three new optional fields per question
3. **Per-Option Feedback**: Multiple choice questions can have unique feedback for each wrong answer
4. **Safe HTML**: All HTML is sanitized to prevent security issues while allowing formatting

### What Was Not Changed:
- Existing quiz functionality remains the same
- Backward compatible - old quizzes without feedback work fine
- Quiz scoring and pass/fail logic unchanged
- Database structure uses existing meta fields

### Files Modified:
1. `templates/single-quiz.php` - Changed question display from `esc_html()` to `wp_kses_post()`
2. `includes/class-quiz-handler.php` - Added feedback logic with per-option support
3. `includes/admin/class-admin.php` - Added feedback input fields to quiz editor
4. `includes/admin/class-xml-exercises-creator.php` - Added feedback placeholders for imported exercises
5. `assets/js/frontend.js` - Already supported HTML feedback display (no changes needed)
