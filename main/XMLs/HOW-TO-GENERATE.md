# HOW TO GENERATE LISTENING TEST XMLs

## ONE COMMAND. THAT'S IT.

### Generate All Sections of a Test

```bash
cd main/XMLs
python3 generate_listening_xml_master.py --test 6
```

This generates all 4 sections of Test 6 with:
- ✅ Automatic question type detection
- ✅ Full feedback (CORRECT, INCORRECT, NO ANSWER)
- ✅ All metadata for WordPress
- ✅ Zero manual work required

### Other Options

```bash
# Single section only
python3 generate_listening_xml_master.py "Listening Test 6 Section 1.txt"

# Range of tests (e.g., Tests 6-10)
python3 generate_listening_xml_master.py --tests 6-10

# ALL tests in the directory
python3 generate_listening_xml_master.py --all
```

## What You Get

Each XML file contains:
1. **10 questions** with proper types (multiple choice, summary completion, etc.)
2. **Full educational feedback** for each question
3. **Audio URL** from the TXT file
4. **Transcript** for students to review
5. **All WordPress metadata** ready for import

## Question Types Supported

The generator automatically detects and handles:
- Multiple Choice (A, B, C, D, E)
- Multi-Select (Choose TWO letters)
- Summary Completion (fill in the blanks)
- Sentence Completion
- Map/Diagram Labeling
- Short Answer

## Feedback Examples

**Correct Answer:**
> ✓ Excellent! The answer is "RICE". You listened carefully and identified the key information. Well done!

**Incorrect Answer:**
> ✗ Not quite. The answer is "RICE". Listen to the audio again and check the transcript. Pay attention to keywords and phrases that directly relate to the question.

**No Answer:**
> No answer provided. The answer is "RICE". In the IELTS Listening test, you should always attempt every question - there's no penalty for wrong answers.

## That's All You Need to Know

No confusion. No multiple scripts. Just one command.
