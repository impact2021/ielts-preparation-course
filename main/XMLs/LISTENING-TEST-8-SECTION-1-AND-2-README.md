# Listening Test 8 - Section 1 and 2 Combined

## Overview
This file (`Listening-Test-8-Section-1-and-2.xml`) combines questions from Listening Test 8 Section 1 and Section 2 into a single exercise containing questions 1-20.

## Generation
The file was generated using the `generate-test-8-section-1-and-2.php` script, which:
1. Extracts questions from both individual section files
2. Combines them into a properly formatted WordPress RSS export
3. Ensures all metadata is correctly serialized

## Usage
To regenerate this file, run:
```bash
cd main/XMLs
php generate-test-8-section-1-and-2.php
```

## Contents
- **Questions**: 20 total (10 from Section 1 + 10 from Section 2)
- **Audio**: Uses the combined audio file from Section 1 (L0052-1-2.mp3)
- **Transcript**: Combined transcripts from both sections

## Validation
The file has been validated to ensure:
- XML is well-formed
- Questions are properly serialized
- Import will not fail with "Error no questions found"
- All required WordPress RSS metadata is present

## Source Files
- `Listening Test 8 Section 1.xml`
- `Listening Test 8 Section 2.xml`
