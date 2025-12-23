# IELTS Test Creation - Quick Reference

## One-Page Cheat Sheet

### Basic Workflow
```bash
1. Edit create-test-xml.php
2. php create-test-xml.php
3. Upload XML to WordPress
```

### Question Type Templates

#### True/False/Not Given
```php
[
    'type' => 'true_false',
    'instructions' => 'First question only: "Do the following..."',
    'question' => 'Statement to evaluate',
    'points' => 1,
    'no_answer_feedback' => 'Optional hint',
    'correct_feedback' => 'Optional explanation',
    'incorrect_feedback' => 'Optional explanation',
    'reading_text_id' => 0,  // 0=passage1, 1=passage2, 2=passage3
    'options' => '',
    'correct_answer' => 'true'  // or 'false' or 'not_given'
],
```

#### Multiple Choice
```php
[
    'type' => 'multiple_choice',
    'instructions' => 'Choose the appropriate letter...',
    'question' => 'Question text',
    'points' => 1,
    'reading_text_id' => 0,
    'mc_options' => [
        ['text' => 'A: option', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'B: option', 'is_correct' => true, 'feedback' => ''],
        ['text' => 'C: option', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'D: option', 'is_correct' => false, 'feedback' => '']
    ],
    'options' => 'A: option\nB: option\nC: option\nD: option',
    'correct_answer' => '1',  // 0-based index
    'option_feedback' => ['', '', '', '']
],
```

#### Short Answer
```php
[
    'type' => 'short_answer',
    'instructions' => 'Choose NO MORE THAN THREE WORDS...',
    'question' => 'The first windmill was built in ___.',
    'points' => 1,
    'reading_text_id' => null,
    'correct_answer' => 'SCOTLAND|SCOTLAND 1887'  // Multiple with |
],
```

#### Headings
```php
[
    'type' => 'headings',
    'instructions' => 'Choose the most suitable heading...\n\nList of Headings:\nI. First\nII. Second...',
    'question' => 'Paragraph A',
    'points' => 1,
    'reading_text_id' => 1,
    'mc_options' => [
        ['text' => 'I. Heading text', 'is_correct' => true, 'feedback' => ''],
        ['text' => 'II. Heading text', 'is_correct' => false, 'feedback' => ''],
        // Add all 9 headings
    ],
    'options' => 'I. Heading\nII. Heading...',
    'correct_answer' => '0',
    'option_feedback' => ['', '', ...]
],
```

### Common Settings

```php
$test_data = [
    'title' => 'Academic IELTS Reading Test 06',
    'slug' => 'academic-ielts-reading-test-06',
    'pass_percentage' => 70,
    'layout_type' => 'computer_based',  // or 'standard'
    'exercise_label' => 'practice_test',
    'open_as_popup' => 1,
    'scoring_type' => 'ielts_academic_reading',
    'timer_minutes' => 60,
];
```

### Reading Passage Structure

```php
'reading_texts' => [
    [
        'title' => 'Reading Passage 1 - Title',
        'content' => 'Full text here...'
    ],
    [
        'title' => 'Reading Passage 2 - Title',
        'content' => 'Full text here...'
    ],
    [
        'title' => 'Reading Passage 3 - Title',
        'content' => 'Full text here...'
    ]
],
```

### Remember

- **Instructions**: Only on first question of each section
- **Reading Text ID**: 0, 1, 2 for passages 1, 2, 3 (or null)
- **Correct Answer Format**:
  - True/False: `'true'`, `'false'`, `'not_given'` (lowercase)
  - Multiple Choice: `'0'`, `'1'`, `'2'` (string, 0-based)
  - Short Answer: `'EXACT TEXT'` or `'ANSWER1|ANSWER2'`
- **Feedback**: Can be empty `''`
- **Points**: Usually `1`

### Quick Test

```bash
# Check syntax
php -l create-test-xml.php

# Generate XML
php create-test-xml.php

# Check output
ls -lh exercise-*.xml
```

### Minimal Question (Optional fields empty)

```php
[
    'type' => 'true_false',
    'instructions' => '',
    'question' => 'Your question here',
    'points' => 1,
    'no_answer_feedback' => '',
    'correct_feedback' => '',
    'incorrect_feedback' => '',
    'reading_text_id' => 0,
    'options' => '',
    'correct_answer' => 'true'
],
```

### Common Mistakes

❌ `'correct_answer' => 'True'` → ✅ `'correct_answer' => 'true'`
❌ `'correct_answer' => 1` → ✅ `'correct_answer' => '1'`
❌ Missing comma after array item → ✅ Add comma
❌ Unescaped quote in string → ✅ Use `\'` or switch to `"`

### File Naming

Generated files: `exercise-{slug}-{YYYY-MM-DD}.xml`

Example: `exercise-academic-ielts-reading-test-06-2025-12-23.xml`

---

**That's all you need to know! 🚀**

Edit → Run → Upload → Done!
