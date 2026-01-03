# Listening Test JSONs

This folder contains complete IELTS Listening Tests in JSON format, combining all 4 sections of each test into a single file.

## Files

### Listening-Test-1-Complete.json
- **Questions**: 1-40 (all 4 sections)
- **Format**: JSON
- **Sections**: 4
- **Audio URLs**: 3 (Sections 2, 3, 4)
- **Transcripts**: All 4 sections included

### Listening-Test-2-Complete.json
- **Questions**: 1-40 (all 4 sections)
- **Format**: JSON
- **Sections**: 4
- **Audio URL**: https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0048.mp3
- **Transcripts**: All 4 sections included

### Listening-Test-4-Complete.json
- **Questions**: 1-40 (all 4 sections)
- **Format**: JSON
- **Sections**: 4
- **Audio URL**: https://www.ieltstestonline.com/wp-content/uploads/2018/12/listening-test-4.mp3
- **Transcripts**: All 4 sections included

## Structure

Each JSON file contains:
- Title and description
- All questions from sections 1-4
- Complete transcripts
- Audio URLs (where available)
- Settings (timer, pass percentage, etc.)

## Question Breakdown

Listening Test 1:
- Questions 1-10: Section 1 (Community Courses)
- Questions 11-20: Section 2 (Gorgona Island)
- Questions 21-30: Section 3 (Family Structures in NZ)
- Questions 31-40: Section 4 (Factors Affecting Elections)

Listening Test 2:
- Questions 1-10: Section 1 (Hunter Valley Holiday Park Booking)
- Questions 11-20: Section 2 (Swimming for Fitness)
- Questions 21-30: Section 3 (Student Academic Meeting)
- Questions 31-40: Section 4 (Remote Working)

Listening Test 4:
- Questions 1-10: Section 1 (Art Gallery Visit)
- Questions 11-20: Section 2 (Museum Information)
- Questions 21-30: Section 3 (Academic Discussion)
- Questions 31-40: Section 4 (Research Topic)

## Feedback Structure

This JSON follows the CRITICAL-FEEDBACK-RULES:
- ✅ Open questions have per-field feedback
- ✅ Closed questions have per-option feedback
- ✅ No generic feedback tables
- ✅ Multi-select questions properly configured

## Usage

Import these JSON files into WordPress using:
1. Go to Quizzes → Edit Quiz
2. Find "Import from JSON" section
3. Upload the JSON file
4. Click "Import JSON"

## Notes

- These files were converted from the original XML section files in `/main/XMLs/`
- All original feedback has been preserved
- Question numbering is continuous 1-40 across all sections
