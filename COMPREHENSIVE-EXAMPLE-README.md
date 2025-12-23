# Comprehensive Example - All IELTS Question Types

## What is this?

`comprehensive-example.xml` is an IELTS reading exercise that demonstrates **all major question types** supported by the IELTS Course Manager plugin. It includes:

- **2 reading passages** (Urban Gardening & History of Coffee)
- **12 questions** covering 5 different question types:
  - 3 True/False/Not Given questions
  - 2 Multiple Choice (single answer) questions
  - 1 Multi-select (choose TWO) question
  - 3 Matching Headings questions
  - 3 Short Answer questions
- 20-minute timer
- Standard layout (not computer-based)
- 65% pass percentage

This comprehensive example is perfect for:
- Testing all question type functionality
- Training content creators on available question formats
- Demonstrating the full capabilities of the IELTS Course Manager
- Creating templates for full-length practice tests

## Question Types Included

### 1. True/False/Not Given
Test-takers decide if statements agree with, contradict, or aren't mentioned in the passage.

**Example:** "Urban gardens can reduce city temperatures by up to 5 degrees Celsius."

### 2. Multiple Choice (Single Answer)
Traditional multiple choice with one correct answer from 4 options (A, B, C, D).

**Example:** "According to the passage, urban gardening participants experienced..."

### 3. Multi-select (Multiple Answers)
Choose TWO (or more) correct answers from a list of options.

**Example:** "Which TWO benefits of urban gardens are mentioned in the passage?"

### 4. Matching Headings
Match paragraph headings to paragraphs labeled A-F.

**Example:** "Choose the most suitable heading for Paragraph A"

### 5. Short Answer
Complete sentences or answer questions using words from the passage (word limit specified).

**Example:** "In what city are there over 550 community gardens?"

## How to Upload to WordPress

Use the **standard WordPress importer**:

1. Log in to WordPress admin dashboard
2. Navigate to **Tools → Import**
3. Select **WordPress** importer (install if needed)
4. Click **Choose File** and select `comprehensive-example.xml`
5. Click **Upload file and import**
6. Assign to an author and click **Submit**
7. Find the imported exercise in **IELTS Courses → Exercises**

For detailed upload instructions, see `QUICK-EXAMPLE-README.md`.

## Regenerating the File

To regenerate or modify this example:

```bash
php create-comprehensive-example.php
```

Edit the `$test_data` array in `create-comprehensive-example.php` to:
- Change question content
- Add/remove questions
- Modify reading passages
- Adjust settings (timer, pass percentage, etc.)

## Comparing with Quick Example

| Feature | Quick Example | Comprehensive Example |
|---------|--------------|----------------------|
| Questions | 5 | 12 |
| Question Types | 2 (True/False, Multiple Choice) | 5 (all major types) |
| Reading Passages | 1 | 2 |
| Timer | 10 minutes | 20 minutes |
| Purpose | Quick testing & validation | Full feature demonstration |

## Technical Details

- **Format**: WordPress eXtended RSS (WXR) 1.2
- **Post Type**: `ielts_quiz`
- **Encoding**: UTF-8
- **Serialization**: PHP serialize() for questions and reading texts
- **Compatible with**: WordPress 5.0+ and IELTS Course Manager plugin
- **File Size**: ~18 KB

## Question Type Support

The IELTS Course Manager supports even more question types than shown in this example:

- ✅ Included in this example:
  - `true_false` - True/False/Not Given
  - `multiple_choice` - Single answer multiple choice
  - `multi_select` - Multiple answer selection
  - `headings` - Matching headings to paragraphs
  - `short_answer` - Fill-in-the-blank with word limit

- 📝 Not included (but supported):
  - `sentence_completion` - Complete sentences from passage
  - `summary_completion` - Fill in summary blanks
  - `matching` - Match items between lists
  - `matching_classifying` - Classify items into categories
  - `table_completion` - Complete table cells
  - `dropdown_paragraph` - Select correct words from dropdowns
  - `labelling` - Label diagrams
  - `locating_information` - Find paragraphs containing information

Use `create-test-xml.php` to create tests with these additional question types.

## Next Steps

After importing this example:

1. **Test all question types** - Complete the exercise to see how each question type behaves
2. **Customize for your needs** - Modify `create-comprehensive-example.php` with your own content
3. **Create full tests** - Use `create-test-xml.php` as a starting point for full 40-question practice tests
4. **Review documentation** - See `HOW-TO-CREATE-TESTS.md` for detailed test creation guidance

---

**Need help?** Check the troubleshooting section in `QUICK-EXAMPLE-README.md` for common import issues.
