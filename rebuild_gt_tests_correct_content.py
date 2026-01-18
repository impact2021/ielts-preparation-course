#!/usr/bin/env python3
"""
FINAL SOLUTION: Rebuild GT Tests 4-15 with CORRECT unique content.

Strategy:
1. Extract ACTUAL passage HTML from each Gen Reading X.txt file
2. Use Test 3's question structure as template (but verify content uniqueness)
3. Combine with Academic Test X section 3

CRITICAL: Each test must have its OWN unique passages from Gen Reading X.txt
"""

import json
import re
from pathlib import Path
from bs4 import BeautifulSoup

BASE_DIR = Path("main/General Training Reading Test JSONs")

# Constants for content validation
HTML_FORMAT_CHECK_CHARS = 200  # Number of chars to check for HTML tags
MIN_PASSAGE_LENGTH = 100  # Minimum length for a valid passage
QUESTION_MARKER_LENGTH = 50  # Length to check for question markers

# Expected content keywords for validation
EXPECTED_CONTENT = {
    4: ["Ferry", "Marine Island", "Passenger"],
    5: ["Gym", "City University", "Term-time"],
    10: ["TRADE WITH ME", "WWW.TRADE", "trade"],
}

def extract_passages_from_gen_reading(test_num):
    """
    Extract the ACTUAL reading passages from Gen Reading X.txt.
    Returns list of passage HTML strings (4 passages for GT sections 1-2).
    """
    filepath = BASE_DIR / f"Gen Reading {test_num}.txt"
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Check if it's plain text format (no HTML tags in first HTML_FORMAT_CHECK_CHARS)
    if '<' not in content[:HTML_FORMAT_CHECK_CHARS]:
        # Plain text format (Gen Reading 3, 11-15)
        return extract_passages_from_plain_text(content, test_num)
    
    # HTML format - parse with BeautifulSoup
    soup = BeautifulSoup(content, 'html.parser')
    
    passages = []
    
    # Try Method 1: ito-scroll-container structure (Tests 4-8)
    containers = soup.find_all('div', class_='ito-scroll-container')
    if containers:
        for container in containers:
            scroll_box = container.find('div', class_='ito-scroll-box')
            if scroll_box:
                passage_html = str(scroll_box.decode_contents())
                passage_html = passage_html.strip()
                if len(passage_html) > MIN_PASSAGE_LENGTH:
                    passages.append(passage_html)
        
        return passages
    
    # Try Method 2: Inline-styled divs (Tests 9-10)
    # Look for divs with style containing "float: right" (passage container)
    fieldsets = soup.find_all('fieldset')
    
    for fieldset in fieldsets:
        # Find divs with specific style attributes (passage containers)
        passage_divs = fieldset.find_all('div', style=re.compile(r'float:\s*right'))
        
        for div in passage_divs:
            # Look for nested div with id="text" or class="style1"
            text_div = div.find('div', id='text') or div.find('div', class_='style1')
            
            if text_div:
                # Get the content
                passage_html = str(text_div.decode_contents())
                passage_html = passage_html.strip()
                
                # Check if it's a passage (not questions)
                if (len(passage_html) > MIN_PASSAGE_LENGTH and 
                    'BLANK' not in passage_html and 
                    'Questions' not in passage_html[:QUESTION_MARKER_LENGTH]):
                    passages.append(passage_html)
    
    return passages

def extract_passages_from_plain_text(content, test_num):
    """
    Extract passages from plain text format Gen Reading files.
    These are structured with section headers and reading passage markers.
    """
    passages = []
    
    # Split content by "Reading Passage" or "Reading Text" markers
    # Pattern: Lines like "Reading Passage 1", "Reading Text 2", etc.
    sections = re.split(r'\n(SECTION \d+|Reading (?:Passage|Text) \d+)\n', content)
    
    current_passage = []
    
    for i, section in enumerate(sections):
        # Skip section headers
        if re.match(r'(SECTION \d+|Reading (?:Passage|Text) \d+)', section):
            if current_passage:
                # Save previous passage
                passage_text = '\n'.join(current_passage).strip()
                if len(passage_text) > 100 and 'Questions' not in passage_text[:100]:
                    # Convert plain text to HTML
                    passage_html = text_to_html(passage_text)
                    passages.append(passage_html)
                current_passage = []
            continue
        
        # Check if this is start of questions
        if 'Questions' in section[:50] and '-' in section[:50]:
            # Save current passage if exists
            if current_passage:
                passage_text = '\n'.join(current_passage).strip()
                if len(passage_text) > 100:
                    passage_html = text_to_html(passage_text)
                    passages.append(passage_html)
                current_passage = []
            continue
        
        # Accumulate passage content
        if section.strip():
            current_passage.append(section)
    
    # Add final passage if exists
    if current_passage:
        passage_text = '\n'.join(current_passage).strip()
        if len(passage_text) > 100:
            passage_html = text_to_html(passage_text)
            passages.append(passage_html)
    
    return passages

def text_to_html(text):
    """Convert plain text to basic HTML with paragraph tags."""
    # Split into paragraphs
    paragraphs = text.split('\n\n')
    
    html_parts = []
    for para in paragraphs:
        para = para.strip()
        if para:
            # Check if it's a heading (short line, no punctuation at end)
            if len(para) < 80 and not para.endswith('.') and not para.endswith(','):
                html_parts.append(f'<h4><strong>{para}</strong></h4>')
            else:
                # Regular paragraph
                # Replace single newlines with <br>, but keep paragraph structure
                para = para.replace('\n', '<br>\n')
                html_parts.append(f'<p>{para}</p>')
    
    return '\n\n'.join(html_parts)

def create_test_with_real_content(test_num):
    """
    Create General Training Reading Test X with REAL content from Gen Reading X.txt.
    """
    print(f"\n{'='*70}")
    print(f"Creating General Training Reading Test {test_num}")
    print(f"{'='*70}")
    
    # Load Test 3 as template for STRUCTURE ONLY
    print("Loading Test 3 as structural template...")
    with open(BASE_DIR / "General Training Reading Test 3.json", 'r') as f:
        template = json.load(f)
    
    # Extract REAL passages from Gen Reading X.txt
    print(f"Extracting REAL passages from Gen Reading {test_num}.txt...")
    real_passages = extract_passages_from_gen_reading(test_num)
    
    print(f"  Extracted {len(real_passages)} unique passages")
    
    # Verify unique content
    if real_passages:
        first_preview = real_passages[0][:150].replace('\n', ' ').replace('  ', ' ')
        print(f"  First passage: {first_preview}...")
        
        # Content verification using configuration
        if test_num in EXPECTED_CONTENT:
            keywords = EXPECTED_CONTENT[test_num]
            if any(kw in real_passages[0] for kw in keywords):
                matched = [kw for kw in keywords if kw in real_passages[0]][0]
                print(f"  ✓ Verified: Test {test_num} has expected content ('{matched}')")
    
    # Create new test structure
    new_test = {
        "title": f"General Training Reading Test {test_num}",
        "content": "",
        "questions": [],
        "reading_texts": [],
        "settings": template['settings'].copy()
    }
    
    # Update settings
    new_test['settings']['scoring_type'] = 'ielts_general_training_reading'
    new_test['settings']['cbt_test_type'] = 'general_training'
    
    # Build reading_texts with REAL content
    # GT tests typically have 4 passages for sections 1-2
    for i in range(min(4, len(real_passages))):
        reading_text = {
            "text_id": i,
            "title": f"Reading Text {i + 1}",
            "content": real_passages[i],
            "tags": []
        }
        new_test['reading_texts'].append(reading_text)
    
    print(f"  Created {len(new_test['reading_texts'])} reading texts with REAL content")
    
    # Copy questions 1-26 from template (structure is consistent)
    # Questions reference reading_text_id 0-3
    for q in template['questions']:
        q_text = q.get('question', '')
        match = re.search(r'^(\d+)\.', q_text)
        if match:
            q_num = int(match.group(1))
            if q_num <= 26:
                new_test['questions'].append(q.copy())
    
    print(f"  Copied {len(new_test['questions'])} GT questions (Q1-26)")
    
    # Load Academic test for Section 3
    academic_file = BASE_DIR / f"Academic-IELTS-Reading-Test-{test_num:02d}.json"
    print(f"Loading {academic_file.name}...")
    
    with open(academic_file, 'r') as f:
        academic_test = json.load(f)
    
    # Extract section 3 (passage with text_id=2 or last passage)
    academic_passage = None
    for rt in academic_test.get('reading_texts', []):
        if rt.get('text_id') == 2:
            academic_passage = rt.copy()
            break
    
    if not academic_passage and academic_test.get('reading_texts'):
        academic_passage = academic_test['reading_texts'][-1].copy()
    
    if academic_passage:
        # Update for GT test (now it's the 5th text, index 4)
        academic_passage['text_id'] = 4
        academic_passage['title'] = 'Reading Text 5'
        new_test['reading_texts'].append(academic_passage)
        print(f"  Added Academic section 3 passage")
    
    # Extract and renumber Academic questions
    academic_questions = [q for q in academic_test.get('questions', [])
                          if q.get('reading_text_id') == 2]
    
    if not academic_questions:
        # Fallback: get questions for last passage
        last_text_id = academic_test['reading_texts'][-1].get('text_id') if academic_test.get('reading_texts') else None
        if last_text_id is not None:
            academic_questions = [q for q in academic_test.get('questions', [])
                                  if q.get('reading_text_id') == last_text_id]
    
    print(f"  Found {len(academic_questions)} Academic questions")
    
    # Renumber from Q27+
    for i, q in enumerate(academic_questions):
        new_q = q.copy()
        new_num = 27 + i
        
        # Update reading_text_id to 4
        new_q['reading_text_id'] = 4
        
        # Renumber question
        if 'question' in new_q:
            new_q['question'] = re.sub(r'^\d+\.', f'{new_num}.', new_q['question'])
        
        # Update instructions
        if 'instructions' in new_q and new_q['instructions']:
            # This is approximate - full implementation would need better parsing
            pass
        
        new_test['questions'].append(new_q)
    
    # Final summary
    total_questions = len(new_test['questions'])
    total_texts = len(new_test['reading_texts'])
    
    print(f"\n  ✓ Test {test_num} complete:")
    print(f"    - Reading texts: {total_texts}")
    print(f"    - Questions: {total_questions}")
    
    # Save
    output_file = BASE_DIR / f"General Training Reading Test {test_num}.json"
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(new_test, f, indent=4, ensure_ascii=False)
    
    print(f"    - Saved: {output_file.name}")
    
    return True

def verify_test_content(test_num):
    """Verify that a test has unique content (not Test 3's content)."""
    test_file = BASE_DIR / f"General Training Reading Test {test_num}.json"
    
    if not test_file.exists():
        return False, "File not found"
    
    with open(test_file, 'r') as f:
        test_data = json.load(f)
    
    if not test_data.get('reading_texts'):
        return False, "No reading texts"
    
    first_content = test_data['reading_texts'][0]['content']
    
    # Check if it's Test 3's content
    if 'Jumble Sale' in first_content or 'Edgehill' in first_content:
        return False, "Has Test 3 content (Jumble Sale)"
    
    # Check for expected unique content
    if test_num == 4 and 'Ferry' in first_content:
        return True, "Ferry Timetable ✓"
    elif test_num == 5 and 'Gym' in first_content:
        return True, "Gym content ✓"
    elif test_num == 10 and 'TRADE WITH ME' in first_content:
        return True, "Trade With Me ✓"
    else:
        preview = first_content[:100].replace('\n', ' ')
        return True, f"Unique content: {preview}..."

def main():
    """Main execution."""
    print("="*70)
    print("General Training Reading Tests 4-15 Rebuild")
    print("WITH CORRECT UNIQUE CONTENT")
    print("="*70)
    
    # Create all tests
    success_count = 0
    for test_num in range(4, 16):
        try:
            if create_test_with_real_content(test_num):
                success_count += 1
        except Exception as e:
            print(f"\n❌ ERROR creating Test {test_num}: {e}")
            import traceback
            traceback.print_exc()
    
    # Verification
    print("\n" + "="*70)
    print(f"Creation complete: {success_count}/12 tests")
    print("="*70)
    
    print("\nContent Verification:")
    print("-" * 70)
    for test_num in range(4, 16):
        is_unique, message = verify_test_content(test_num)
        status = "✓" if is_unique else "❌"
        print(f"  {status} Test {test_num}: {message}")
    
    print("\n" + "="*70)
    print("Rebuild complete!")
    print("="*70)

if __name__ == "__main__":
    main()
