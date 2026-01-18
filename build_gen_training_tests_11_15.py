#!/usr/bin/env python3
"""
Generate General Training Reading Tests 11-15 using the EXACT process from Tests 3:
- Use Test 3 as template for Q1-26 and reading texts 0-3
- Extract passage 3 (text_id 2) from Academic test for section 3
- Renumber Academic questions to Q27+
- Set proper scoring type: ielts_general_training_reading
"""

import json
import re
from pathlib import Path

BASE_DIR = Path("main/General Training Reading Test JSONs")

def load_json(filename):
    with open(BASE_DIR / filename, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_json(data, filename):
    with open(BASE_DIR / filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

def renumber_questions(questions, start_num=27, end_num=40, new_text_id=4):
    """Renumber questions from Q27+ and update reading_text_id to 4
    
    Also updates all question number references in instructions
    """
    renumbered = []
    
    # Build mapping of old to new question numbers
    old_to_new = {}
    for i, q in enumerate(questions):
        old_num = None
        # Try to extract old question number from question text
        if 'question' in q:
            match = re.match(r'^(\d+)\.', q['question'])
            if match:
                old_num = int(match.group(1))
        
        new_num = start_num + i
        if old_num:
            old_to_new[old_num] = new_num
    
    for i, q in enumerate(questions):
        new_q = q.copy()
        new_num = start_num + i
        
        # Update question text
        if 'question' in new_q:
            # Replace question number at the start (e.g., "21." -> "27.")
            new_q['question'] = re.sub(r'^\d+\.', f'{new_num}.', new_q['question'])
        
        # Update instructions - more comprehensive approach
        if 'instructions' in new_q and new_q['instructions']:
            instr = new_q['instructions']
            
            # Replace all question number ranges and single numbers
            # Pattern: "Questions 21-25", "Question 21", "21-25", "21 – 25", etc.
            def replace_range(match):
                prefix = match.group(1) or ''  # "Questions " or "Question " or ""
                first = int(match.group(2))
                separator = match.group(3) or ''  # "-" or " – " or ""
                second_str = match.group(4) or ''  # second number or empty
                
                # Calculate new numbers
                if first in old_to_new:
                    new_first = old_to_new[first]
                else:
                    # Estimate offset
                    offset = start_num - (min(old_to_new.keys()) if old_to_new else first)
                    new_first = first + offset
                
                if second_str:
                    second = int(second_str)
                    if second in old_to_new:
                        new_second = old_to_new[second]
                    else:
                        offset = start_num - (min(old_to_new.keys()) if old_to_new else first)
                        new_second = second + offset
                    
                    # Ensure we don't go beyond end_num
                    new_second = min(new_second, end_num)
                    new_first = min(new_first, end_num)
                    
                    return f'{prefix}{new_first}{separator}{new_second}'
                else:
                    new_first = min(new_first, end_num)
                    return f'{prefix}{new_first}'
            
            # Match patterns like "Questions 21-25" or "Question 21" or just "21-25"
            instr = re.sub(
                r'(Questions?\s+)?(\d+)(\s*[-–]\s*)?(\d+)?',
                replace_range,
                instr
            )
            
            new_q['instructions'] = instr
        
        # Update reading_text_id to 4 (last passage)
        new_q['reading_text_id'] = new_text_id
        
        renumbered.append(new_q)
    
    return renumbered

def add_html_markers(passage_content, start_q, end_q):
    """Add HTML markers to passage for questions"""
    if not passage_content:
        return passage_content
    
    # Simple approach: add markers at paragraph boundaries
    # Find all <p> tags
    paragraphs = re.findall(r'<p[^>]*>.*?</p>', passage_content, re.DOTALL)
    
    if not paragraphs:
        return passage_content
    
    num_questions = end_q - start_q + 1
    markers_to_add = min(num_questions, len(paragraphs))
    
    # Distribute markers evenly
    marked_content = passage_content
    for i in range(markers_to_add):
        q_num = start_q + i
        marker = f'<span id="passage-q{q_num}" data-question="{q_num}"></span>'
        
        # Find a good insertion point (after every few paragraphs)
        para_index = int(i * len(paragraphs) / markers_to_add)
        if para_index < len(paragraphs):
            # Insert marker at the start of this paragraph
            old_para = paragraphs[para_index]
            new_para = old_para.replace('<p', f'{marker}<p', 1)
            marked_content = marked_content.replace(old_para, new_para, 1)
    
    return marked_content

def create_test(test_num):
    """Create General Training Reading Test X"""
    print(f"\n{'='*60}")
    print(f"Creating General Training Reading Test {test_num}")
    print(f"{'='*60}")
    
    # Load Test 3 as template
    print("Loading Test 3 template...")
    template = load_json("General Training Reading Test 3.json")
    
    # Extract template sections 1-2 (first 4 reading texts, first 26 questions)
    template_texts = template['reading_texts'][:4]  # texts 0-3
    template_questions = template['questions'][:26]  # Q1-26
    
    print(f"  Extracted {len(template_texts)} reading texts")
    print(f"  Extracted {len(template_questions)} questions")
    
    # Load corresponding Academic test
    print(f"Loading Academic Test {test_num:02d}...")
    academic = load_json(f"Academic-IELTS-Reading-Test-{test_num:02d}.json")
    
    # Get the last reading text (passage 3)
    academic_texts = academic['reading_texts']
    academic_passage = academic_texts[-1].copy()
    academic_passage_title = academic_passage.get('title', 'Reading Passage 3')
    
    print(f"  Found passage: {academic_passage_title}")
    
    # Get questions for the last passage
    last_text_ids = [q['reading_text_id'] for q in academic['questions']]
    max_text_id = max(last_text_ids)
    
    academic_questions = [q for q in academic['questions'] 
                         if q['reading_text_id'] == max_text_id]
    
    print(f"  Found {len(academic_questions)} questions for section 3")
    
    # Calculate question numbering
    start_q = 27
    end_q = start_q + len(academic_questions) - 1
    
    print(f"  Will renumber to Q{start_q}-Q{end_q}")
    
    # Add HTML markers to academic passage
    if 'content' in academic_passage:
        print("  Adding HTML markers...")
        academic_passage['content'] = add_html_markers(
            academic_passage['content'], start_q, end_q
        )
    
    # Renumber academic questions
    print("  Renumbering questions...")
    academic_questions = renumber_questions(academic_questions, start_q, end_q, new_text_id=4)
    
    # Create new test
    new_test = {
        "title": f"General Training Reading Test {test_num}",
        "content": "",
        "questions": template_questions + academic_questions,
        "reading_texts": template_texts + [academic_passage],
        "settings": template['settings'].copy()
    }
    
    # Ensure correct settings
    new_test['settings']['scoring_type'] = 'ielts_general_training_reading'
    new_test['settings']['cbt_test_type'] = 'general_training'
    
    # Save
    output_file = f"General Training Reading Test {test_num}.json"
    print(f"Saving {output_file}...")
    save_json(new_test, output_file)
    
    print(f"✓ Test {test_num} created successfully!")
    print(f"  - Reading texts: {len(new_test['reading_texts'])}")
    print(f"  - Questions: {len(new_test['questions'])} (Q1-Q{end_q})")
    print(f"  - Scoring type: {new_test['settings']['scoring_type']}")
    
    return True

def main():
    print("="*60)
    print("General Training Reading Tests 11-15 Generator")
    print("="*60)
    print("\nUsing Test 3 as template for sections 1-2")
    print("Extracting section 3 from Academic Tests 11-15")
    
    for test_num in range(11, 16):
        try:
            create_test(test_num)
        except Exception as e:
            print(f"\n❌ ERROR creating test {test_num}: {e}")
            import traceback
            traceback.print_exc()
            return False
    
    print("\n" + "="*60)
    print("✅ All tests created successfully!")
    print("="*60)
    return True

if __name__ == "__main__":
    import sys
    sys.exit(0 if main() else 1)
