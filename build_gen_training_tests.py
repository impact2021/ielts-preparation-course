#!/usr/bin/env python3
"""
Build General Training Reading Tests 4-10 by using Test 2 as template
and replacing section 3 (Q27-40) with Academic test passage 3.
"""

import json
import re
import os
import sys
from copy import deepcopy

def load_json(filepath):
    """Load JSON file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_json(data, filepath):
    """Save JSON file."""
    with open(filepath, 'w', encoding='utf-8') as f:
        json.dump(data, f, indent=4, ensure_ascii=False)

def add_html_markers(content, question_numbers):
    """Add HTML markers to passage content for specified question numbers."""
    if not content or not question_numbers:
        return content
    
    # Find all paragraph markers (A:, B:, C:, etc.) or paragraph boundaries
    # Strategy: Insert markers at the start of each labeled paragraph
    
    marked_content = content
    
    # Pattern 1: Look for paragraph labels like "A:", "B:", "C:" etc.
    para_labels = re.findall(r'\b([A-Z]):\s', content)
    
    if len(para_labels) >= len(question_numbers):
        # Use paragraph labels for marker placement
        for i, q_num in enumerate(question_numbers):
            if i < len(para_labels):
                label = para_labels[i]
                # Find the position of this paragraph label
                pattern = f'\\b{label}:\\s'
                match = re.search(pattern, marked_content)
                if match:
                    marker = f'<span id="passage-q{q_num}" data-question="{q_num}"></span>'
                    pos = match.start()
                    marked_content = marked_content[:pos] + marker + marked_content[pos:]
    else:
        # Fallback: distribute markers evenly through the content
        content_length = len(content)
        step = content_length // (len(question_numbers) + 1)
        
        # Insert markers at strategic positions
        offset = 0
        for i, q_num in enumerate(question_numbers):
            target_pos = step * (i + 1)
            # Find nearest paragraph break
            search_start = max(0, target_pos - 100)
            search_end = min(len(marked_content), target_pos + 100)
            search_area = marked_content[search_start:search_end]
            
            # Look for paragraph break
            para_break = re.search(r'\r?\n\r?\n', search_area)
            if para_break:
                insert_pos = search_start + para_break.end() + offset
                marker = f'<span id="passage-q{q_num}" data-question="{q_num}"></span>'
                marked_content = marked_content[:insert_pos] + marker + marked_content[insert_pos:]
                offset += len(marker)
            else:
                # Just insert at target position
                marker = f'<span id="passage-q{q_num}" data-question="{q_num}"></span>'
                insert_pos = min(target_pos + offset, len(marked_content))
                marked_content = marked_content[:insert_pos] + marker + marked_content[insert_pos:]
                offset += len(marker)
    
    return marked_content

def renumber_question(question, new_num):
    """Renumber a question object and add feedback."""
    q = deepcopy(question)
    
    # Update question text
    if 'question' in q and q['question']:
        # Replace leading number
        q['question'] = re.sub(r'^\d+\.?\s*', f'{new_num}. ', q['question'])
    
    # Update instructions if they contain question numbers
    if 'instructions' in q and q['instructions']:
        # Try to update "Questions X-Y" or "Question X"
        q['instructions'] = re.sub(
            r'Questions?\s+\d+\s*[-–]\s*\d+',
            f'Question {new_num}',
            q['instructions']
        )
        q['instructions'] = re.sub(
            r'Question\s+\d+',
            f'Question {new_num}',
            q['instructions']
        )
    
    # Add feedback fields if missing
    if 'no_answer_feedback' not in q or not q.get('no_answer_feedback'):
        q['no_answer_feedback'] = f"The correct answer is provided in the feedback. In the IELTS test, you should always attempt an answer as there's no penalty for incorrect responses."
    
    if 'correct_feedback' not in q:
        q['correct_feedback'] = ""
    
    if 'incorrect_feedback' not in q:
        q['incorrect_feedback'] = ""
    
    return q

def build_general_training_test(test_num, template_path, academic_json_path, output_path):
    """Build a General Training test using Test 2 template + Academic section 3."""
    
    print(f"\n{'='*60}")
    print(f"Building General Training Reading Test {test_num}")
    print(f"{'='*60}")
    
    # Load template (Test 2)
    print(f"Loading template: {os.path.basename(template_path)}")
    template = load_json(template_path)
    
    # Load Academic test
    print(f"Loading Academic test: {os.path.basename(academic_json_path)}")
    academic_test = load_json(academic_json_path)
    
    # Create new test from template
    new_test = deepcopy(template)
    new_test['title'] = f"General Training Reading Test {test_num}"
    
    # Extract Academic passage 3 (text_id = 2 in Academic tests)
    academic_passages = academic_test.get('reading_texts', [])
    academic_questions = academic_test.get('questions', [])
    
    # Find passage 3 (last passage)
    passage3 = None
    passage3_questions = []
    
    # Get passage with text_id = 2
    for passage in academic_passages:
        # Academic tests have 3 passages indexed 0,1,2
        if academic_passages.index(passage) == 2:
            passage3 = deepcopy(passage)
            break
    
    if not passage3 and len(academic_passages) >= 3:
        passage3 = deepcopy(academic_passages[2])
    
    if not passage3:
        print(f"  ERROR: Could not find passage 3 in Academic test")
        return None
    
    # Get questions for passage 3 (text_id = 2)
    passage3_questions = [q for q in academic_questions if q.get('reading_text_id') == 2]
    
    if not passage3_questions:
        print(f"  ERROR: Could not find questions for passage 3")
        return None
    
    print(f"  Found passage 3 with {len(passage3_questions)} questions")
    
    # Update passage 3 for General Training
    passage3['text_id'] = None  # Match template style
    passage3['title'] = 'Reading Text 6'  # Section 3 is the 6th reading text
    
    # Add HTML markers for Q27-40
    question_numbers = list(range(27, 27 + len(passage3_questions)))
    if 'content' in passage3:
        print(f"  Adding HTML markers for Q{question_numbers[0]}-Q{question_numbers[-1]}")
        passage3['content'] = add_html_markers(passage3['content'], question_numbers)
    
    # Replace reading text 6 (index 5) with new passage
    if len(new_test['reading_texts']) > 5:
        new_test['reading_texts'][5] = passage3
    else:
        new_test['reading_texts'].append(passage3)
    
    # Renumber and update section 3 questions (Q27-40)
    # Remove old section 3 questions
    new_test['questions'] = [q for q in new_test['questions'] if q.get('reading_text_id', 0) < 5]
    
    # Add new section 3 questions
    for i, academic_q in enumerate(passage3_questions):
        new_q_num = 27 + i
        new_q = renumber_question(academic_q, new_q_num)
        new_q['reading_text_id'] = 5  # Text ID for passage 6 in GT tests
        new_test['questions'].append(new_q)
    
    print(f"\n  Built test with:")
    print(f"    Reading texts: {len(new_test['reading_texts'])}")
    print(f"    Questions: {len(new_test['questions'])}")
    print(f"    Section 1-2 questions: 1-26")
    print(f"    Section 3 questions: 27-{26 + len(passage3_questions)}")
    
    # Save
    print(f"  Saving to: {os.path.basename(output_path)}")
    save_json(new_test, output_path)
    
    print(f"✓ Test {test_num} created successfully!")
    
    return new_test

# Main execution
if __name__ == "__main__":
    base_dir = "/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs"
    
    template_path = os.path.join(base_dir, "General Training Reading Test 2.json")
    
    # Test mapping
    tests_to_build = [4, 5, 6, 7, 8, 9, 10]
    
    success_count = 0
    
    for test_num in tests_to_build:
        academic_json_path = os.path.join(base_dir, f"Academic-IELTS-Reading-Test-{test_num:02d}.json")
        output_path = os.path.join(base_dir, f"General Training Reading Test {test_num}.json")
        
        if not os.path.exists(academic_json_path):
            print(f"✗ Missing: {academic_json_path}")
            continue
        
        try:
            result = build_general_training_test(test_num, template_path, academic_json_path, output_path)
            if result:
                success_count += 1
        except Exception as e:
            print(f"✗ Error building test {test_num}: {e}")
            import traceback
            traceback.print_exc()
    
    print(f"\n{'='*60}")
    print(f"Build process complete!")
    print(f"Successfully created {success_count}/{len(tests_to_build)} tests")
    print(f"{'='*60}")
