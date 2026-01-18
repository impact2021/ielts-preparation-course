#!/usr/bin/env python3
"""
Fix General Training Reading Tests 4-15 by replacing incorrect questions
with correct ones parsed from TXT files.
"""

import json
import re
import os
from pathlib import Path

# Base directory for files
BASE_DIR = Path("/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs")

def parse_metadata_block(lines, start_idx):
    """Parse metadata block in square brackets to extract question type and instructions."""
    metadata = {}
    block_lines = []
    
    i = start_idx
    while i < len(lines):
        line = lines[i].strip()
        if line.startswith('['):
            # Start of metadata block
            i += 1
            continue
        elif line.endswith(']'):
            # End of metadata block
            block_lines.append(line[:-1])
            break
        elif not line or (i > start_idx and not line.startswith(('[', 'This is', 'Question type', 'There are', 'Answer', 'Each', 'All questions', 'Answers must'))):
            # End of metadata block
            i -= 1
            break
        else:
            block_lines.append(line)
        i += 1
    
    metadata_text = ' '.join(block_lines)
    
    # Extract question type
    if 'TRUE / FALSE / NOT GIVEN' in metadata_text or 'TRUE, FALSE, NOT GIVEN' in metadata_text:
        metadata['type'] = 'true_false_not_given'
    elif 'Multiple choice' in metadata_text or 'single answer' in metadata_text:
        metadata['type'] = 'multiple_choice'
    elif 'Short answer' in metadata_text:
        metadata['type'] = 'short_answer'
    elif 'Matching' in metadata_text or 'Classification' in metadata_text:
        metadata['type'] = 'matching'
    elif 'Summary completion' in metadata_text or 'Complete the' in metadata_text or 'completion' in metadata_text.lower():
        metadata['type'] = 'summary_completion'
    elif 'Sentence completion' in metadata_text:
        metadata['type'] = 'sentence_completion'
    else:
        metadata['type'] = 'unknown'
    
    # Extract word limit if present
    word_limit_match = re.search(r'NO MORE THAN ([A-Z\s\d/]+?)(?:\.|$)', metadata_text, re.IGNORECASE)
    if word_limit_match:
        metadata['word_limit'] = word_limit_match.group(1).strip()
    
    metadata['full_text'] = metadata_text
    
    return metadata, i

def parse_txt_file(filepath):
    """Parse a Gen Reading X.txt file to extract questions and metadata."""
    with open(filepath, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    questions = []
    current_section = None
    current_reading_text = None
    current_reading_text_id = -1
    i = 0
    
    while i < len(lines):
        line = lines[i].strip()
        
        # Track sections
        if line.startswith('SECTION 1'):
            current_section = 1
            current_reading_text_id = -1
        elif line.startswith('SECTION 2'):
            current_section = 2
            current_reading_text_id = -1
        elif line.startswith('SECTION 3'):
            current_section = 3
            current_reading_text_id = -1
        
        # Track reading texts
        if line.startswith('Reading Text') or line.startswith('Reading Passage'):
            # Extract number if present
            match = re.search(r'(\d+)', line)
            if match:
                text_num = int(match.group(1))
                current_reading_text_id = text_num - 1
            else:
                # Increment based on section
                if current_section == 1:
                    if current_reading_text_id < 0:
                        current_reading_text_id = 0
                    else:
                        current_reading_text_id += 1
                elif current_section == 2:
                    if current_reading_text_id < 1:
                        current_reading_text_id = 2
                    else:
                        current_reading_text_id += 1
                elif current_section == 3:
                    if current_reading_text_id < 3:
                        current_reading_text_id = 4
                    else:
                        current_reading_text_id += 1
        
        # Detect question blocks
        if line.startswith('Questions') and '–' in line:
            # Extract question range
            match = re.search(r'Questions?\s+(\d+)[-–](\d+)', line)
            if match:
                start_q = int(match.group(1))
                end_q = int(match.group(2))
                
                # Look for metadata block
                metadata = {}
                j = i + 1
                while j < len(lines) and lines[j].strip() == '':
                    j += 1
                
                if j < len(lines) and lines[j].strip().startswith('['):
                    metadata, j = parse_metadata_block(lines, j)
                
                # Parse questions in this block
                j += 1
                current_q_num = start_q
                
                while j < len(lines) and current_q_num <= end_q:
                    qline = lines[j].strip()
                    
                    # Skip empty lines
                    if not qline:
                        j += 1
                        continue
                    
                    # Check for next question block
                    if qline.startswith('Questions') or qline.startswith('SECTION') or qline.startswith('Reading'):
                        break
                    
                    # Check for answer line
                    if qline.startswith('Answer:'):
                        # This is an answer for open questions
                        answer = qline.replace('Answer:', '').strip()
                        if questions and questions[-1]['question_number'] == current_q_num - 1:
                            questions[-1]['answer'] = answer
                        j += 1
                        continue
                    
                    # Parse question
                    q_obj = {
                        'question_number': current_q_num,
                        'question_text': qline,
                        'metadata': metadata,
                        'reading_text_id': max(0, current_reading_text_id),
                        'section': current_section
                    }
                    
                    # Check if this is a multiple choice question
                    if metadata.get('type') == 'multiple_choice':
                        # Look for options A, B, C, D
                        options = []
                        k = j + 1
                        while k < len(lines):
                            opt_line = lines[k].strip()
                            if re.match(r'^[A-D]\.', opt_line):
                                options.append(opt_line)
                                k += 1
                            elif opt_line == '':
                                k += 1
                            else:
                                break
                        
                        if options:
                            q_obj['options'] = options
                            j = k - 1
                    
                    # Check if question text contains classification options
                    if metadata.get('type') == 'matching' and re.match(r'^[A-E]\.', qline):
                        # This is an option line, not a question
                        if 'classification_options' not in metadata:
                            metadata['classification_options'] = []
                        metadata['classification_options'].append(qline)
                        j += 1
                        continue
                    
                    questions.append(q_obj)
                    current_q_num += 1
                    j += 1
                
                i = j - 1
        
        i += 1
    
    return questions

def determine_ielts_category(metadata):
    """Determine IELTS question category from metadata."""
    q_type = metadata.get('type', 'unknown')
    
    if q_type == 'true_false_not_given':
        return 'true_false_not_given'
    elif q_type == 'multiple_choice':
        return 'multiple_choice'
    elif q_type == 'short_answer':
        return 'short_answer'
    elif q_type == 'matching':
        return 'matching_features'
    elif q_type == 'summary_completion':
        return 'summary_completion'
    elif q_type == 'sentence_completion':
        return 'sentence_completion'
    else:
        return 'short_answer'  # default

def build_instructions(metadata, question_range):
    """Build instructions text from metadata."""
    q_type = metadata.get('type', 'unknown')
    
    if q_type == 'true_false_not_given':
        return f"Questions {question_range}\n\nDo the following statements agree with the information given in the reading passage?\n\nWrite TRUE if the statement agrees with the information, FALSE if the statement contradicts the information, or NOT GIVEN if there is no information on this."
    elif q_type == 'multiple_choice':
        return f"Questions {question_range}\n\nChoose the correct letter, A, B, C, or D."
    elif q_type == 'short_answer':
        word_limit = metadata.get('word_limit', 'THREE WORDS AND/OR A NUMBER')
        return f"Questions {question_range}\n\nAnswer the questions below.\n\nWrite NO MORE THAN {word_limit} from the passage for each answer."
    elif q_type == 'matching':
        return f"Questions {question_range}\n\nMatch each statement with the correct option."
    elif q_type == 'summary_completion':
        word_limit = metadata.get('word_limit', 'ONE WORD OR A NUMBER')
        return f"Questions {question_range}\n\nComplete the summary below.\n\nWrite NO MORE THAN {word_limit} from the passage for each answer."
    else:
        return f"Questions {question_range}"

def create_question_object(q_data, prev_question=None):
    """Create a properly formatted question object for the JSON."""
    metadata = q_data['metadata']
    q_num = q_data['question_number']
    q_text = q_data['question_text']
    
    # Determine if this is open or closed question
    q_type_meta = metadata.get('type', 'unknown')
    is_closed = q_type_meta in ['true_false_not_given', 'multiple_choice', 'matching']
    
    q_obj = {
        'type': 'closed_question' if is_closed else 'open_question',
        'instructions': '',
        'question': q_text,
        'points': 1,
        'no_answer_feedback': '',
        'correct_feedback': '',
        'incorrect_feedback': '',
        'reading_text_id': q_data['reading_text_id'],
        'audio_section_id': None,
        'audio_start_time': None,
        'audio_end_time': None,
        'ielts_question_category': determine_ielts_category(metadata)
    }
    
    # Handle instructions (only set for first question in a block)
    if prev_question is None or prev_question.get('metadata', {}).get('full_text') != metadata.get('full_text'):
        # This is the first question in a new block
        # We'll set instructions here
        pass  # Instructions will be set later
    
    # Handle TRUE/FALSE/NOT GIVEN questions
    if q_type_meta == 'true_false_not_given':
        q_obj['mc_options'] = [
            {'text': 'TRUE', 'is_correct': False, 'feedback': ''},
            {'text': 'FALSE', 'is_correct': False, 'feedback': ''},
            {'text': 'NOT GIVEN', 'is_correct': False, 'feedback': ''}
        ]
        q_obj['options'] = 'TRUE\nFALSE\nNOT GIVEN'
        q_obj['correct_answer_count'] = 1
        q_obj['show_option_letters'] = False
    
    # Handle multiple choice questions
    elif q_type_meta == 'multiple_choice' and 'options' in q_data:
        mc_options = []
        options_text = []
        for opt in q_data['options']:
            mc_options.append({
                'text': opt,
                'is_correct': False,
                'feedback': ''
            })
            options_text.append(opt)
        
        q_obj['mc_options'] = mc_options
        q_obj['options'] = '\n'.join(options_text)
        q_obj['correct_answer_count'] = 1
        q_obj['show_option_letters'] = False
    
    # Handle matching questions
    elif q_type_meta == 'matching':
        # Extract classification options from metadata if available
        class_opts = metadata.get('classification_options', [])
        if class_opts:
            mc_options = []
            for opt in class_opts:
                mc_options.append({
                    'text': opt,
                    'is_correct': False,
                    'feedback': ''
                })
            
            q_obj['mc_options'] = mc_options
            q_obj['options'] = '\n'.join(class_opts)
            q_obj['correct_answer_count'] = 1
            q_obj['show_option_letters'] = False
    
    return q_obj

def fix_test(test_num):
    """Fix a single General Training Reading Test."""
    txt_file = BASE_DIR / f"Gen Reading {test_num}.txt"
    json_file = BASE_DIR / f"General Training Reading Test {test_num}.json"
    
    if not txt_file.exists():
        print(f"❌ TXT file not found: {txt_file}")
        return False
    
    if not json_file.exists():
        print(f"❌ JSON file not found: {json_file}")
        return False
    
    print(f"\n📝 Processing Test {test_num}...")
    
    # Parse TXT file
    print(f"  Parsing {txt_file.name}...")
    parsed_questions = parse_txt_file(txt_file)
    print(f"  Found {len(parsed_questions)} questions in TXT file")
    
    # Load existing JSON
    print(f"  Loading {json_file.name}...")
    with open(json_file, 'r', encoding='utf-8') as f:
        test_data = json.load(f)
    
    original_q_count = len(test_data['questions'])
    print(f"  Original JSON had {original_q_count} questions")
    
    # Build new questions
    new_questions = []
    prev_metadata = None
    question_blocks = []
    current_block = []
    
    # Group questions into blocks by metadata
    for q_data in parsed_questions:
        current_metadata = q_data['metadata'].get('full_text', '')
        
        if prev_metadata is None or current_metadata != prev_metadata:
            if current_block:
                question_blocks.append(current_block)
            current_block = [q_data]
            prev_metadata = current_metadata
        else:
            current_block.append(q_data)
    
    if current_block:
        question_blocks.append(current_block)
    
    # Build questions with proper instructions
    for block in question_blocks:
        if not block:
            continue
        
        first_q = block[0]
        last_q = block[-1]
        q_range = f"{first_q['question_number']}–{last_q['question_number']}"
        instructions = build_instructions(first_q['metadata'], q_range)
        
        for idx, q_data in enumerate(block):
            q_obj = create_question_object(q_data, block[idx-1] if idx > 0 else None)
            
            # Set instructions only for first question in block
            if idx == 0:
                q_obj['instructions'] = instructions
            
            new_questions.append(q_obj)
    
    # Replace questions in test data
    test_data['questions'] = new_questions
    
    print(f"  Built {len(new_questions)} new questions")
    
    # Save updated JSON
    backup_file = json_file.with_suffix('.json.backup')
    print(f"  Creating backup: {backup_file.name}")
    with open(backup_file, 'w', encoding='utf-8') as f:
        json.dump(test_data, f, indent=4, ensure_ascii=False)
    
    print(f"  Writing updated JSON...")
    with open(json_file, 'w', encoding='utf-8') as f:
        json.dump(test_data, f, indent=4, ensure_ascii=False)
    
    # Verify the update
    if new_questions and new_questions[0]['question'] != "1. Should only be packaged in boxes.":
        print(f"  ✅ Test {test_num} updated successfully!")
        print(f"     First question: {new_questions[0]['question'][:60]}...")
        return True
    else:
        print(f"  ⚠️  Warning: Test {test_num} may still have Test 3 questions")
        return False

def main():
    """Fix all General Training Reading Tests 4-15."""
    print("=" * 70)
    print("Fixing General Training Reading Tests 4-15")
    print("=" * 70)
    
    results = {}
    
    for test_num in range(4, 16):
        try:
            success = fix_test(test_num)
            results[test_num] = success
        except Exception as e:
            print(f"❌ Error processing Test {test_num}: {e}")
            import traceback
            traceback.print_exc()
            results[test_num] = False
    
    # Summary
    print("\n" + "=" * 70)
    print("SUMMARY")
    print("=" * 70)
    
    successful = [num for num, success in results.items() if success]
    failed = [num for num, success in results.items() if not success]
    
    print(f"\n✅ Successfully fixed: {len(successful)} tests")
    if successful:
        print(f"   Tests: {', '.join(map(str, successful))}")
    
    if failed:
        print(f"\n❌ Failed or skipped: {len(failed)} tests")
        print(f"   Tests: {', '.join(map(str, failed))}")
    
    print("\n" + "=" * 70)
    print(f"Complete! {len(successful)}/{len(results)} tests fixed successfully")
    print("=" * 70)

if __name__ == '__main__':
    main()
