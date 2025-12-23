# How to Create IELTS Reading Tests - The Easy Way

This guide shows you how to create new IELTS reading tests and convert existing ones **without using the buggy text import interface**.

## The Problem

The text import feature in WordPress is buggy and time-consuming. You need a foolproof way to:
- Create new practice tests
- Convert existing tests you already have
- Avoid spending hours fighting with import bugs

## The Solution

Use the `create-test-xml.php` script to generate WordPress XML files that can be imported directly.

## Quick Start

### Creating Your First Test

1. **Edit the PHP script** (`create-test-xml.php`)
2. **Modify the `$test_data` array** with your content
3. **Run the script**: `php create-test-xml.php`
4. **Upload the generated XML** to WordPress

That's it! The XML file will have all fields properly formatted and serialized.

## Detailed Instructions

### Step 1: Understanding the Test Structure

The `$test_data` array in `create-test-xml.php` contains:

```php
$test_data = [
    // Basic settings
    'title' => 'Academic IELTS Reading Test 06',
    'slug' => 'academic-ielts-reading-test-06',
    'pass_percentage' => 70,
    'layout_type' => 'computer_based',  // or 'standard'
    'exercise_label' => 'practice_test',  // options below
    'open_as_popup' => 1,  // 1 = yes, 0 = no
    'scoring_type' => 'ielts_academic_reading',  // options below
    'timer_minutes' => 60,
    
    // Reading passages
    'reading_texts' => [...],
    
    // Questions
    'questions' => [...]
];
```

### Step 2: Add Your Reading Passages

```php
'reading_texts' => [
    [
        'title' => 'Reading Passage 1 - Your Title Here',
        'content' => 'Your passage content here...'
    ],
    [
        'title' => 'Reading Passage 2 - Another Title',
        'content' => 'More content...'
    ],
    [
        'title' => 'Reading Passage 3 - Final Title',
        'content' => 'Final passage content...'
    ]
],
```

**Tips:**
- Use HTML `<strong>A:</strong>` for paragraph markers if needed
- Include `<br />` for line breaks
- Keep formatting simple for best results

### Step 3: Add Your Questions

The script supports all IELTS question types:

#### 1. True/False/Not Given Questions

```php
[
    'type' => 'true_false',
    'instructions' => 'Do the following statements agree with the information...',
    'question' => 'The statement to evaluate',
    'points' => 1,
    'no_answer_feedback' => 'Hint for students who skip',
    'correct_feedback' => 'Explanation when correct',
    'incorrect_feedback' => 'Explanation when wrong',
    'reading_text_id' => 0,  // 0 for passage 1, 1 for passage 2, etc.
    'options' => '',
    'correct_answer' => 'true'  // or 'false' or 'not_given'
],
```

#### 2. Multiple Choice Questions

```php
[
    'type' => 'multiple_choice',
    'instructions' => 'Choose the appropriate letter A – D...',
    'question' => 'The question text',
    'points' => 1,
    'no_answer_feedback' => '',
    'correct_feedback' => '',
    'incorrect_feedback' => 'Explanation of correct answer',
    'reading_text_id' => 0,
    'mc_options' => [
        ['text' => 'A: First option', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'B: Second option', 'is_correct' => true, 'feedback' => ''],
        ['text' => 'C: Third option', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'D: Fourth option', 'is_correct' => false, 'feedback' => '']
    ],
    'options' => 'A: First option\nB: Second option\nC: Third option\nD: Fourth option',
    'correct_answer' => '1',  // Index of correct option (0-based)
    'option_feedback' => ['', '', '', '']
],
```

#### 3. Short Answer Questions

```php
[
    'type' => 'short_answer',
    'instructions' => 'Complete the sentences below. Choose NO MORE THAN THREE WORDS...',
    'question' => 'The earliest windmills appeared in Persia around ___________.',
    'points' => 1,
    'no_answer_feedback' => '',
    'correct_feedback' => '',
    'incorrect_feedback' => '',
    'reading_text_id' => null,
    'options' => '',
    'correct_answer' => '500-900 AD'  // Accepts multiple answers with |
],
```

**For multiple acceptable answers:**
```php
'correct_answer' => 'LITHIUM-ION|LITHIUM ION|LI-ION'
```

#### 4. Heading Matching Questions

```php
[
    'type' => 'headings',
    'instructions' => 'Reading Passage 2 has six paragraphs A – F.
Choose the most suitable heading from the list below...',
    'question' => 'Paragraph A',
    'points' => 1,
    'no_answer_feedback' => '',
    'correct_feedback' => '',
    'incorrect_feedback' => 'Paragraph A discusses...',
    'reading_text_id' => 1,  // passage number
    'mc_options' => [
        ['text' => 'I. First heading', 'is_correct' => true, 'feedback' => ''],
        ['text' => 'II. Second heading', 'is_correct' => false, 'feedback' => ''],
        // ... more headings
    ],
    'options' => 'I. First heading\nII. Second heading\nIII. Third heading...',
    'correct_answer' => '0',  // Index of correct heading
    'option_feedback' => ['', '', '', ...]
],
```

### Step 4: Configuration Options

#### Exercise Label Options:
- `'exercise'` - Regular exercise
- `'end_of_lesson_test'` - End of lesson test
- `'practice_test'` - Practice test (recommended for full tests)

#### Layout Type Options:
- `'computer_based'` - Computer-based IELTS layout
- `'standard'` - Standard paper-based layout

#### Scoring Type Options:
- `'ielts_academic_reading'` - Academic Reading band score
- `'ielts_general_reading'` - General Training Reading band score
- `'ielts_listening'` - Listening band score
- `'percentage'` - Simple percentage score

### Step 5: Run the Script

```bash
php create-test-xml.php
```

Output:
```
✓ Successfully generated: exercise-academic-ielts-reading-test-06-2025-12-23.xml
✓ Reading passages: 3
✓ Questions: 26

You can now upload this XML file to WordPress!
```

### Step 6: Upload to WordPress

1. Go to WordPress Admin → Tools → Import
2. Choose "WordPress" importer (install if needed)
3. Upload your generated XML file
4. Map the author (select existing user)
5. Click "Import"
6. Done!

## Converting Existing Tests

If you have tests in text files (like `academic-reading-test-03.txt`), you can:

1. **Copy the text content** from your file
2. **Paste it into the `'content'` field** of a reading passage
3. **Extract the questions** and add them to the questions array
4. **Run the script**

### Example: Converting a Text File

Your text file has:
```
[READING PASSAGE] Reading Passage 1 - Climate Change

Climate change is a serious issue...

[END READING PASSAGE]

Questions:
1. Climate change is serious. {TRUE}
2. The passage mentions solutions. {NOT GIVEN}
```

Convert to:
```php
'reading_texts' => [
    [
        'title' => 'Reading Passage 1 - Climate Change',
        'content' => 'Climate change is a serious issue...'
    ]
],
'questions' => [
    [
        'type' => 'true_false',
        'question' => 'Climate change is serious.',
        'correct_answer' => 'true',
        // ... other fields
    ],
    [
        'type' => 'true_false',
        'question' => 'The passage mentions solutions.',
        'correct_answer' => 'not_given',
        // ... other fields
    ]
]
```

## Tips for Success

### 1. Start with the Example
The script includes a complete example test. Use it as a template.

### 2. Test Question Numbering
Questions are automatically numbered based on their position in the array. Question 1 is index 0, question 2 is index 1, etc.

### 3. Instructions Field
Only add instructions to the **first question** of each section. Leave empty for subsequent questions in the same section.

### 4. Reading Text IDs
- Passage 1 = `'reading_text_id' => 0`
- Passage 2 = `'reading_text_id' => 1`
- Passage 3 = `'reading_text_id' => 2`
- For questions not tied to a specific passage, use `null`

### 5. Feedback Fields
You can leave feedback fields empty (`''`) if you don't need them. The test will still work fine.

### 6. Validate Your PHP
Before running, check for syntax errors:
```bash
php -l create-test-xml.php
```

## Creating Multiple Tests

### Method 1: Duplicate and Modify
1. Copy `create-test-xml.php` to `create-test-07.php`
2. Edit the new file with different content
3. Run both scripts to create multiple tests

### Method 2: Use Variables
```php
// At the top of the script
$test_number = 6;
$test_slug = "academic-ielts-reading-test-0{$test_number}";

$test_data = [
    'title' => "Academic IELTS Reading Test 0{$test_number}",
    'slug' => $test_slug,
    // ... rest of config
];
```

## Troubleshooting

### Problem: "Parse error" when running script
**Solution:** Check PHP syntax. Common issues:
- Missing comma after array item
- Unclosed quote or bracket
- Special characters not escaped

### Problem: WordPress won't import the file
**Solution:** Check the XML file was created successfully and is not empty

### Problem: Questions appear in wrong order
**Solution:** Questions are numbered based on their position in the array. Check the array order.

### Problem: Answers not marked correctly
**Solution:** 
- For true/false: use lowercase `'true'`, `'false'`, or `'not_given'`
- For multiple choice: use the index number as a string `'0'`, `'1'`, `'2'`, etc.
- For short answer: use exact text (uppercase recommended)

## Advanced Usage

### Adding Course/Lesson Associations

After importing, you can manually assign courses/lessons in WordPress, OR modify the script:

```php
// In the $test_data array
'course_ids' => [71975390],  // Your course ID
'lesson_ids' => [71976084],  // Your lesson ID
```

Then in the script, update:
```php
$course_ids_serialized = serialize($test_data['course_ids'] ?? []);
$lesson_ids_serialized = serialize($test_data['lesson_ids'] ?? []);
```

### Custom Post IDs

The script auto-generates post IDs. If you need specific IDs:
```php
$post_id = 12345;  // Your desired ID
```

### Changing Defaults

Edit these lines in `$test_data`:
- `'pass_percentage' => 70` - Change passing grade
- `'timer_minutes' => 60` - Change test duration
- `'open_as_popup' => 1` - Change popup behavior

## Summary

With this script, you can:
- ✅ Create unlimited new tests
- ✅ Convert existing text files
- ✅ Avoid the buggy import interface
- ✅ Have complete control over all fields
- ✅ Generate perfect XML every time

**Time saved:** Hours per test! 🎉

## Need Help?

- Check the example test in the script
- Look at the existing XML file: `exercise-academic-ielts-reading-test-05-2025-12-23.xml`
- Compare your data structure to the working example

## Next Steps

1. Edit `create-test-xml.php` with your first test
2. Run `php create-test-xml.php`
3. Upload to WordPress
4. Repeat for all your tests!

You now have a foolproof, reliable way to create IELTS tests. No more fighting with buggy import tools! 🚀
