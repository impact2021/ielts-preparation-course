# Listening Test Converter Scripts

This directory contains two Python scripts to help with creating and managing listening test XML files with annotated transcripts.

## Scripts

### 1. convert-txt-to-xml.py

Converts a listening test TXT file to XML format and generates an annotated transcript.

**Usage:**
```bash
python3 convert-txt-to-xml.py "Listening Test 3 Section 1.txt"
```

**Input:** A TXT file with questions and transcript (see existing .txt files for format)

**Output:**
- `Listening Test 3 Section 1.xml` - XML file for import
- `Listening Test 3 Section 1-transcript.txt` - Annotated HTML transcript with answer markers

### 2. extract-annotated-transcript-from-xml.py

Extracts the transcript from an existing XML file and generates an annotated version.

**Usage:**
```bash
python3 extract-annotated-transcript-from-xml.py "Listening Test 1 Section 1.xml"
```

**Input:** An existing XML file with questions and transcript

**Output:**
- `Listening Test 1 Section 1-transcript.txt` - Annotated HTML transcript with answer markers

## What is an Annotated Transcript?

An annotated transcript is an HTML version of the listening test transcript where each answer is marked with a **yellow-highlighted** tag like `<strong style="background-color: yellow;">[Q1: answer]</strong>`.

**Example:**
```html
<td>We'd like to stay for <strong style="background-color: yellow;">[Q1: 4]</strong> nights please.</td>
```

**Visual appearance:** The answer markers will appear with a yellow background, making them easy to spot at a glance.

This makes it easy to:
- Copy and paste into WordPress or other systems
- Review where answers appear in the transcript
- Create study materials for students
- Quickly identify answer locations with yellow highlighting

## Examples

### Converting from TXT (if you have source TXT files)
```bash
cd /home/runner/work/ielts-preparation-course/ielts-preparation-course/main/XMLs
python3 convert-txt-to-xml.py "Listening Test 3 Section 1.txt"
```

### Extracting from existing XML (for already converted tests)
```bash
cd /home/runner/work/ielts-preparation-course/ielts-preparation-course/main/XMLs
python3 extract-annotated-transcript-from-xml.py "Listening Test 1 Section 1.xml"
```

## Generated Files

The scripts have already been used to generate:
- `Listening Test 1 Section 1-transcript.txt` - Annotated transcript for Test 1 Section 1
- `Listening Test 3 Section 1-transcript.txt` - Annotated transcript for Test 3 Section 1

You can open these files to see the annotated HTML that can be copied into WordPress.

## Technical Details

### Answer Annotation
The scripts automatically:
1. Extract questions and answers from the source
2. Find where each answer appears in the transcript
3. Mark the first occurrence with `<strong>[Q#: answer]</strong>`
4. Handle multiple answer variants (e.g., "4|four" or "2 months|two months")
5. Case-insensitive matching

### Supported Question Types
- Summary completion (form filling, note completion)
- Short answer questions
- Multiple choice (basic support)

## Requirements

- Python 3.x (already available on this system)
- No additional packages required (uses only standard library)
