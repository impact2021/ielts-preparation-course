# Listening Test Converter Scripts

This directory contains the master XML generator and a utility script for IELTS listening tests.

## Master Generator

### `generate_listening_xml_master.py` ✅ USE THIS

**The ONLY generator you need.**

Converts listening test TXT files to XML format with:
- ✅ Automatic question type detection (all types supported)
- ✅ FULL feedback auto-generated (CORRECT, INCORRECT, NO ANSWER)
- ✅ No manual intervention required

**Usage:**
```bash
# Single section
python3 generate_listening_xml_master.py "Listening Test 6 Section 1.txt"

# All sections of a test
python3 generate_listening_xml_master.py --test 6

# Range of tests
python3 generate_listening_xml_master.py --tests 6-10

# All tests
python3 generate_listening_xml_master.py --all
```

**Input:** A TXT file with questions and transcript (see existing .txt files for format)

**Output:**
- `Listening Test X Section Y.xml` - WordPress-ready XML file with full feedback

## Utility Script

### `extract-annotated-transcript-from-xml.py`

Extracts the transcript from an existing XML file.

**Usage:**
```bash
python3 extract-annotated-transcript-from-xml.py "Listening Test 1 Section 1.xml"
```

**Output:**
- `Listening Test 1 Section 1-transcript.txt` - Annotated HTML transcript with answer markers

## What is an Annotated Transcript?

An annotated transcript is an HTML version of the listening test transcript where each answer is marked with a **yellow-highlighted** tag like `<strong style="background-color: yellow;">[Q1: answer]</strong>`.

**Example:**
```html
<td>We'd like to stay for <strong style="background-color: yellow;">[Q1: 4]</strong> nights please.</td>
```

This makes it easy to:
- Review where answers appear in the transcript
- Create study materials for students
- Copy into WordPress or other systems

## Generated Files

The master generator creates XML files with:
- Proper question type detection
- Full educational feedback (automatically generated)
- Audio URLs
- Transcripts
- All WordPress metadata

## Requirements

- Python 3.x (already available)
- No additional packages required (uses only standard library)

## That's It!

No confusion. One generator for everything. Full automation.
