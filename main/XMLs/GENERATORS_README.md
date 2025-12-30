# Listening Test XML Generator

This directory contains ONE master script to generate properly formatted XML files for IELTS listening tests from TXT source files.

## ⭐ THE ONLY GENERATOR YOU NEED

### **`generate_listening_xml_master.py`** ✅ PRODUCTION READY

**Single, comprehensive generator that handles EVERYTHING:**
- ✅ ALL question types (Multiple choice, Multi-select, Summary completion, Short answer, Matching, Sentence completion)
- ✅ FULL feedback auto-generated (CORRECT, INCORRECT, NO ANSWER) - no manual input needed
- ✅ ALL listening tests (1-15+)
- ✅ Intelligent question type detection
- ✅ Proper PHP serialization
- ✅ Batch processing support

**Usage:**

```bash
cd main/XMLs

# Single section
python3 generate_listening_xml_master.py "Listening Test 6 Section 1.txt"

# All sections of one test (recommended)
python3 generate_listening_xml_master.py --test 6

# Range of tests
python3 generate_listening_xml_master.py --tests 6-10

# ALL tests in directory
python3 generate_listening_xml_master.py --all
```

**What You Get:**
- ✅ Full educational feedback automatically generated
- ✅ Correct question types detected from context
- ✅ WordPress-ready XML files
- ✅ No manual intervention required

## Utility Tool

### **`extract-annotated-transcript-from-xml.py`**

Extracts transcripts from existing XML files. Useful for generating yellow-highlighted transcripts from already-created XMLs.

```bash
python3 extract-annotated-transcript-from-xml.py "Listening Test 1 Section 1.xml"
```

## Output Format

Each XML file includes:
1. **Questions** with proper type detection
2. **Full feedback** for all three states (correct/incorrect/no answer)
3. **Audio URL** from source TXT file
4. **Transcript** with HTML formatting
5. **Metadata** for WordPress IELTS Course Manager plugin

## Troubleshooting

**Q: Generator shows "Only found X questions (expected 10)"**
A: The TXT file may have formatting issues. The generator will still create XML with the questions it found.

**Q: How do I verify the XML is correct?**
A: Check the console output. It shows question types detected and question count. Review the XML file to ensure answers are correct.

**Q: Can I use this for tests not yet in the repository?**
A: Yes! Just create a TXT file following the format of existing tests (with {ANSWER} markers), and run the generator.

## That's It!

**One generator. Full feedback. All question types. Zero manual work.**

No more confusion about which generator to use. Just use `generate_listening_xml_master.py` for everything.
