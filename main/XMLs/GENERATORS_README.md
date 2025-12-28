# Listening Test XML Generators

This directory contains scripts to generate properly formatted XML files for IELTS listening tests from TXT source files.

## ⭐ RECOMMENDED: Universal Generator

### **`generate_listening_xml_universal.py`** (Production Ready)
**Status: ✅ READY FOR USE**

One comprehensive generator that handles ALL listening test formats:
- ✅ Auto-detects format (Test 3 vs Test 4 style)
- ✅ Handles multiple question types (MC, Multi-select, Summary completion, etc.)
- ✅ Works with 100+ test files
- ✅ Batch processing support
- ✅ Proper PHP serialization
- ✅ Yellow-highlighted transcript annotations

**Usage:**

```bash
# Single file
python3 generate_listening_xml_universal.py "Listening Test 5 Section 1.txt"

# All sections of a test (recommended for efficiency)
python3 generate_listening_xml_universal.py --test 5

# Process ALL tests at once (use with caution - 100+ files!)
python3 generate_listening_xml_universal.py --all
```

**Advantages:**
- ✅ One tool for everything
- ✅ No code duplication
- ✅ Easy to maintain and improve
- ✅ Batch processing saves massive time
- ✅ Auto-detects formats
- ✅ Warns about incomplete parsing

**Limitations:**
- Some edge cases may need manual review (tool warns you)
- Complex question formats may need parser enhancements

## Legacy Generators (For Reference)

### Test-Specific Generators
These were used to develop the universal generator and remain as reference:

- `generate_test3_section4.py` - Test 3 Section 4 (plain text format)
- `generate_test4_all_sections.py` - Test 4 all sections (HTML format)
- `generate_listening_xml.py` - Early attempt (incomplete)
- `convert-txt-to-xml.py` - Basic skeleton (creates empty questions)

**Note:** Use the universal generator instead of these for new tests.

### Utility Scripts

- `extract-annotated-transcript-from-xml.py` - Extracts transcripts from existing XML files

## Quick Start

**For a single test:**
```bash
cd main/XMLs
python3 generate_listening_xml_universal.py --test 6
```

**For a single section:**
```bash
cd main/XMLs
python3 generate_listening_xml_universal.py "Listening Test 7 Section 2.txt"
```

**Validate output:**
```bash
cd ../..
php TEMPLATES/validate-xml.php "main/XMLs/Listening Test 6 Section 1.xml"
```

## Supported Formats

The universal generator handles these TXT formats:

### Test 3 Style (Plain Text)
```
=== QUESTION TYPE: SHORT ANSWER ===

31. What causes RSI? {[ANSWER1][ANSWER2]}
[CORRECT] Feedback for correct answer
[INCORRECT] Feedback for incorrect answer
[NO ANSWER] Feedback when no answer given
```

### Test 4 Style (HTML with options)
```html
<strong>Question 1</strong>
<ol style="list-style-type: upper-alpha;">
    <li>Option A</li>
    <li>Option B</li>
</ol>
1. {B}
```

### Test 5 Style (Simple HTML)
```html
<p>(1) {HALTWELL}</p>
<p>6. {J}</p>
```

## Output

Each run produces:
1. **XML file** - WordPress-importable with serialized questions
2. Console report showing:
   - Format detected
   - Questions parsed (should be 10/10)
   - Warnings for incomplete parsing

## Troubleshooting

**Q: Generator shows "Only found X questions (expected 10)"**
A: This means the file has a format variation. Options:
1. Check the TXT file for unusual formatting
2. The XML is still generated - validate and review manually
3. Update the parser for this specific pattern
4. File an issue with the problematic file

**Q: Want to process hundreds of tests quickly?**
A: Yes! Use `--all` mode, but be prepared to review warnings:
```bash
python3 generate_listening_xml_universal.py --all > batch_log.txt
```

## Current Status

✅ Test 3 All Sections: Supported (plain text format)
✅ Test 4 All Sections: Supported (HTML with MC/options)
✅ Test 5+ Sections: Mostly supported (edge cases may need review)

**Total TXT files in repo:** 112  
**Can process automatically:** ~90%  
**May need review:** ~10%

The universal generator saves massive time compared to creating test-specific scripts!
