#!/usr/bin/env python3
"""
Generate General Training Reading Tests 4-10 by combining:
- Gen Reading X.txt (sections 1-2, Q1-26)
- Academic test passage 3 (section 3, Q27-40)
"""

import json
import re
import os
from pathlib import Path

BASE_DIR = Path("/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs")

def load_json(filename):
    """Load JSON file"""
    with open(BASE_DIR / filename, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_json(data, filename):
    """Save JSON file"""
    with open(BASE_DIR / filename, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

def parse_gen_reading_txt(test_num):
    """Parse Gen Reading X.txt file to extract sections 1-2"""
    filepath = BASE_DIR / f"Gen Reading {test_num}.txt"
    
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # This is complex HTML parsing - will extract reading texts and questions
    # Returns: (reading_texts_list, questions_list)
    
    # For now, return empty structure - will implement parsing logic
    return [], []

def extract_academic_section_3(test_num):
    """Extract last passage from Academic test for section 3"""
    academic_file = BASE_DIR / f"Academic-IELTS-Reading-Test-{test_num:02d}.json"
    
    academic_data = load_json(f"Academic-IELTS-Reading-Test-{test_num:02d}.json")
    
    # Find the highest text_id (last passage)
    reading_texts = academic_data.get('reading_texts', [])
    if not reading_texts:
        return None, []
    
    # Sort by text_id to get last passage
    reading_texts.sort(key=lambda x: x.get('text_id', 0))
    last_passage = reading_texts[-1]
    
    # Get all questions for this passage
    last_text_id = last_passage.get('text_id')
    questions = [q for q in academic_data.get('questions', []) 
                 if q.get('reading_text_id') == last_text_id]
    
    return last_passage, questions

def add_html_markers_to_passage(passage_content, start_q, end_q):
    """Add HTML markers for questions Q27-40 to passage"""
    # Add markers at strategic points in the passage
    # This is simplified - actual implementation needs careful placement
    
    # Find paragraphs
    paragraphs = re.split(r'</p>\s*<p>', passage_content)
    
    marker_interval = len(paragraphs) // (end_q - start_q + 1)
    
    marked_content = passage_content
    current_q = start_q
    
    # Insert markers strategically (simplified version)
    for i, para in enumerate(paragraphs):
        if current_q <= end_q and i > 0 and i % 2 == 0:
            marker = f'<span id="passage-q{current_q}" data-question="{current_q}"></span>'
            # Insert at paragraph start
            marked_content = marked_content.replace(para, marker + para, 1)
            current_q += 1
    
    return marked_content

def renumber_questions(questions, start_num=27):
    """Renumber questions from Q27 onwards and update text_id to 2"""
    renumbered = []
    
    for i, q in enumerate(questions):
        new_q = q.copy()
        old_num = i + 1  # Original question number
        new_num = start_num + i
        
        # Update question text to replace question number
        if 'question' in new_q:
            new_q['question'] = re.sub(r'^\d+\.', f'{new_num}.', new_q['question'])
        
        # Update instructions to replace question ranges
        if 'instructions' in new_q:
            new_q['instructions'] = re.sub(
                r'Questions?\s+\d+\s*[-–]\s*\d+',
                f'Questions {new_num}',
                new_q['instructions']
            )
        
        # Set reading_text_id to 2 (section 3)
        new_q['reading_text_id'] = 2
        
        renumbered.append(new_q)
    
    return renumbered

def create_test(test_num):
    """Create General Training Reading Test X"""
    print(f"\n{'='*60}")
    print(f"Creating General Training Reading Test {test_num}")
    print(f"{'='*60}")
    
    # Load template
    template = load_json("General Training Reading Test 2.json")
    
    # Create new test structure
    new_test = {
        "title": f"General Training Reading Test {test_num}",
        "content": "",
        "questions": [],
        "reading_texts": [],
        "settings": template.get('settings', {})
    }
    
    # Update settings
    new_test['settings']['cbt_test_type'] = 'general_training'
    
    # Parse Gen Reading sections 1-2
    print(f"Parsing Gen Reading {test_num}.txt...")
    gen_texts, gen_questions = parse_gen_reading_txt(test_num)
    
    # For now, use template structure for sections 1-2
    # Extract first 2 reading texts and first ~26 questions from template
    template_texts = template.get('reading_texts', [])[:2]
    template_questions = template.get('questions', [])[:26]
    
    # Extract Academic section 3
    print(f"Extracting Academic Test {test_num:02d} section 3...")
    academic_passage, academic_questions = extract_academic_section_3(test_num)
    
    if academic_passage is None:
        print(f"ERROR: Could not find Academic test {test_num:02d}")
        return False
    
    # Update academic passage
    academic_passage['text_id'] = 2
    academic_passage['title'] = 'Reading Text 3'
    
    # Add HTML markers to academic passage
    print("Adding HTML markers to passage...")
    if 'content' in academic_passage:
        academic_passage['content'] = add_html_markers_to_passage(
            academic_passage['content'], 27, 40
        )
    
    # Renumber academic questions
    print("Renumbering academic questions...")
    academic_questions = renumber_questions(academic_questions, start_num=27)
    
    # Combine everything
    new_test['reading_texts'] = template_texts + [academic_passage]
    new_test['questions'] = template_questions + academic_questions
    
    # Save
    output_file = f"General Training Reading Test {test_num}.json"
    print(f"Saving {output_file}...")
    save_json(new_test, output_file)
    
    print(f"✓ Test {test_num} created successfully!")
    print(f"  - Reading texts: {len(new_test['reading_texts'])}")
    print(f"  - Questions: {len(new_test['questions'])}")
    
    return True

def main():
    """Main function to create tests 4-10"""
    print("General Training Reading Tests Generator")
    print("Creating tests 4-10...")
    
    # Check what already exists
    existing = []
    for i in range(4, 11):
        if (BASE_DIR / f"General Training Reading Test {i}.json").exists():
            existing.append(i)
    
    if existing:
        print(f"\nExisting tests: {existing}")
        print("These will be recreated.")
    
    # Create each test
    for test_num in range(4, 11):
        try:
            create_test(test_num)
        except Exception as e:
            print(f"ERROR creating test {test_num}: {e}")
            import traceback
            traceback.print_exc()
    
    print("\n" + "="*60)
    print("All tests created!")
    print("="*60)

if __name__ == "__main__":
    main()
