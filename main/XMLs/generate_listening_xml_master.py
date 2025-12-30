#!/usr/bin/env python3
"""
MASTER IELTS Listening Test XML Generator
==========================================

Single, comprehensive generator that handles:
✓ ALL question types (multiple choice, multi-select, summary completion, short answer, matching, sentence completion)
✓ FULL feedback generation (CORRECT, INCORRECT, NO ANSWER) - automatically generated
✓ ALL listening tests (1-15+)
✓ Intelligent question type detection
✓ Proper PHP serialization

Usage:
    # Single section:
    python3 generate_listening_xml_master.py "Listening Test 6 Section 1.txt"
    
    # All sections of a test:
    python3 generate_listening_xml_master.py --test 6
    
    # Batch process multiple tests:
    python3 generate_listening_xml_master.py --tests 6-10
    
    # Process ALL tests in directory:
    python3 generate_listening_xml_master.py --all

Features:
- Auto-detects question types from context
- Generates comprehensive, educational feedback
- No manual intervention required
- Handles all IELTS listening question formats

Author: IELTS Course Manager
Date: December 30, 2025
"""

import re, os, sys, argparse
from datetime import datetime
from html import escape

# ============================================================================
# CONSTANTS
# ============================================================================

# Valid letters for multi-choice and multi-select answers
VALID_MC_LETTERS = 'ABCDEFGHIJ'

# Maximum distance in characters between </ol> tag and answer for multi-select detection
MULTI_SELECT_PROXIMITY_CHARS = 40

# ============================================================================
# REGEX PATTERNS FOR QUESTION TYPE DETECTION
# ============================================================================

PATTERN_STRONG_NUMBER = r'<strong>\s*\d+\s*</strong>\s*$'
PATTERN_SENTENCE_BEFORE = r'[a-z]\s*\d*\.?\s*$'
PATTERN_SENTENCE_AFTER = r'^\s*\.?\s*[A-Z]?[a-z]'
PATTERN_ORDERED_LIST = r'<ol[^>]*>(.*?)</ol>'
PATTERN_LIST_ITEMS = r'<li>([^<]+)</li>'
PATTERN_LABEL_INSTRUCTION = r'(Label\s+the\s+[^<\n]+)'
PATTERN_COMPLETE_INSTRUCTION = r'(Complete\s+the\s+[^<\n]+)'

# ============================================================================
# PHP SERIALIZATION
# ============================================================================

def serialize_php(data):
    """Convert Python data to PHP serialized format with correct byte counts."""
    if data is None:
        return 'N;'
    elif isinstance(data, bool):
        return 'b:1;' if data else 'b:0;'
    elif isinstance(data, int):
        return f'i:{data};'
    elif isinstance(data, float):
        return f'd:{data};'
    elif isinstance(data, str):
        byte_length = len(data.encode('utf-8'))
        return f's:{byte_length}:"{data}";'
    elif isinstance(data, dict):
        items = []
        for key, value in data.items():
            items.append(serialize_php(key) + serialize_php(value))
        items_str = ''.join(items)
        return f'a:{len(data)}:{{{items_str}}}'
    elif isinstance(data, list):
        items = []
        for i, value in enumerate(data):
            items.append(serialize_php(i) + serialize_php(value))
        items_str = ''.join(items)
        return f'a:{len(data)}:{{{items_str}}}'
    else:
        return 'N;'

# ============================================================================
# CONTENT EXTRACTION
# ============================================================================

def parse_answer(answer_text):
    """Parse {[ans1][ans2]|ans3} format and return pipe-separated variants."""
    answer_text = answer_text.strip('{}')
    variants = re.findall(r'\[([^\]]+)\]', answer_text)
    if variants:
        return '|'.join(v.lower() for v in variants)
    else:
        return answer_text.lower()

def extract_audio(content):
    """Extract audio URL from [audio mp3="URL"] tag."""
    match = re.search(r'\[audio mp3="([^"]+)"\]', content)
    return match.group(1) if match else ""

def extract_transcript(content):
    """Extract transcript from HTML div or table."""
    # Method 1: div with overflow scroll
    match = re.search(r'<div[^>]*style="[^"]*overflow:\s*scroll[^"]*"[^>]*>(.*?)</div>', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1).strip()
    
    # Method 2: table
    match = re.search(r'(<table[^>]*>.*?</table>)', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1)
    
    return ""

# ============================================================================
# QUESTION TYPE DETECTION HELPERS
# ============================================================================

def extract_ordered_list_options(html_content):
    """Extract options from an ordered list in HTML - returns the LAST/closest <ol> to avoid picking up earlier lists."""
    # Find ALL <ol> sections and use the LAST one (closest to the answer)
    ol_matches = list(re.finditer(PATTERN_ORDERED_LIST, html_content, re.DOTALL))
    if ol_matches:
        # Use the last match (closest to the answer position)
        last_ol = ol_matches[-1]
        options_html = last_ol.group(1)
        items = re.findall(PATTERN_LIST_ITEMS, options_html)
        # Filter out items that contain answer placeholders {}, as those are not option lists
        clean_items = [item.strip() for item in items if '{' not in item]
        return clean_items if clean_items else items  # Fallback to original if all filtered
    return []

def is_summary_completion_context(before_answer, after_answer):
    """Check if the answer is in a summary completion context."""
    # Check for <strong>NUM</strong> format
    if re.search(PATTERN_STRONG_NUMBER, before_answer):
        return True
    # Check if surrounded by sentence text
    if (re.search(PATTERN_SENTENCE_BEFORE, before_answer) and 
        re.search(PATTERN_SENTENCE_AFTER, after_answer)):
        return True
    return False

def extract_summary_text(content, answer_start, answer_end, answer_index):
    """Extract summary completion text from paragraph."""
    para_start = max(0, answer_start - 500)
    para_end = min(len(content), answer_end + 500)
    para = content[para_start:para_end]
    
    # Clean and find the sentence
    para_clean = re.sub(r'<[^>]+>', '', para)
    para_clean = re.sub(r'\{[^}]+\}', '___', para_clean)
    
    # Get the sentence with this blank
    sentences = re.split(r'[.!]\s+', para_clean)
    for sent in sentences:
        if str(answer_index + 1) in sent or '___' in sent:
            return sent.strip()
    
    return para_clean[:200].strip() + '...'

def count_multi_select_answers(instruction_text):
    """Determine how many answer slots a multi-select question needs based on instruction."""
    instruction_lower = instruction_text.lower()
    if 'two' in instruction_lower or '2' in instruction_lower:
        return 2
    elif 'three' in instruction_lower or '3' in instruction_lower:
        return 3
    elif 'four' in instruction_lower or '4' in instruction_lower:
        return 4
    return 2  # Default to 2

# ============================================================================
# QUESTION TYPE DETECTION
# ============================================================================

def detect_question_type(content, answer_index, answer_text):
    """
    Detect the question type based on context and answer format.
    Returns: ('type_name', question_text, options_if_any)
    """
    pattern = r'\{[^}]+\}'
    matches = list(re.finditer(pattern, content))
    
    if answer_index >= len(matches):
        return ('short_answer', f"Question {answer_index + 1}", None)
    
    match = matches[answer_index]
    answer_start = match.start()
    answer_end = match.end()
    
    # Parse the answer to get variants
    parsed_answer = parse_answer(match.group())
    
    # Get context around the answer
    line_start = content.rfind('\n', 0, answer_start) + 1
    same_line_text = content[line_start:answer_start].strip()
    
    # Look back for question type indicators
    lookback = content[max(0, answer_start - 1000):answer_start]
    before_answer = content[max(0, answer_start - 200):answer_start]
    after_answer = content[answer_end:min(len(content), answer_end + 200)]
    
    # PRIORITY 1: Summary/form completion (answer embedded in paragraph)
    # This must come BEFORE checking for MC/multi-select to avoid false positives
    if is_summary_completion_context(before_answer, after_answer):
        summary_text = extract_summary_text(content, answer_start, answer_end, answer_index)
        return ('summary_completion', summary_text, None)
    
    # PRIORITY 2: Multi-select ("Choose TWO/THREE letters", "Select TWO options", etc.)
    # Check if answer contains multiple single letters (e.g., "b|d" or just "b")
    answer_parts = parsed_answer.upper().split('|')
    all_single_letters = all(len(part) == 1 and part in VALID_MC_LETTERS for part in answer_parts)
    
    multi_select_patterns = ['choose two', 'choose 2', 'choose three', 'choose 3', 
                             'select two', 'select 2', 'select three', 'select 3']
    has_multi_select_instruction = any(pattern in lookback.lower() for pattern in multi_select_patterns)
    
    # Treat as multi-select if: answer is letter(s) AND multi-select instruction is present
    if all_single_letters and has_multi_select_instruction:
        options_list = extract_ordered_list_options(lookback)
        # Only treat as multi-select if we found options that don't contain answer placeholders
        if options_list and len(options_list) >= 2:
            # Check that the options list is VERY CLOSE to the answer
            # This prevents false positives when "choose two" appears earlier in the section
            # Multi-select answers typically appear immediately after </ol>, often on the next line
            recent_context = content[max(0, answer_start - MULTI_SELECT_PROXIMITY_CHARS):answer_start]
            if '</ol>' in recent_context:
                # Try to find the instruction with variations - use full lookback
                instr_match = re.search(r'((?:Choose|Select)\s+(?:TWO|THREE|2|3)\s+(?:letters?|options?)[^<\n]*)', lookback, re.IGNORECASE)
                if instr_match:
                    q_text = instr_match.group(1).strip()
                    return ('multi_select', q_text, options_list)
                # Fallback: just use a generic multi-select question text
                return ('multi_select', f'Choose multiple answers', options_list)
    
    # PRIORITY 3: Multiple choice (has options list with <ol> and answer is single letter)
    if '<ol' in lookback and '</ol>' in lookback:
        answer_clean = answer_text.strip('{}[]').strip().upper()
        if len(answer_clean) == 1 and answer_clean in 'ABCDE':
            recent_lookback = content[max(0, answer_start - 300):answer_start]
            if '<ol' in recent_lookback:
                options_list = extract_ordered_list_options(lookback)
                if options_list:
                    question_match = re.search(r'(\d+)\.\s+(.+?)<ol', lookback, re.DOTALL)
                    if question_match:
                        q_text = re.sub(r'<[^>]+>', '', question_match.group(2)).strip()
                        return ('multiple_choice', q_text, options_list)
    
    # PRIORITY 4: Map/diagram labeling
    if 'label' in lookback.lower() and ('map' in lookback.lower() or 'diagram' in lookback.lower()):
        answer_clean = answer_text.strip('{}[]').strip().upper()
        if len(answer_clean) <= 2 and answer_clean.isalpha():
            label_match = re.search(PATTERN_LABEL_INSTRUCTION, lookback, re.IGNORECASE)
            if label_match:
                instr = label_match.group(1).strip()
                return ('matching', instr, None)
    
    # PRIORITY 5: Short answer with question mark
    same_line_clean = re.sub(r'<[^>]+>', '', same_line_text)
    if '?' in same_line_clean:
        q_text = re.sub(r'^\d+\.\s*', '', same_line_clean).strip()
        return ('short_answer', q_text, None)
    
    # PRIORITY 6: Sentence/table completion
    if 'complete the' in lookback.lower():
        complete_match = re.search(PATTERN_COMPLETE_INSTRUCTION, lookback, re.IGNORECASE)
        if complete_match:
            q_text = same_line_clean.strip()
            if q_text:
                return ('sentence_completion', q_text, None)
            else:
                return ('sentence_completion', complete_match.group(1), None)
    
    # DEFAULT: Short answer
    q_text = re.sub(r'<[^>]+>', '', same_line_text)
    q_text = re.sub(r'^\d+\.\s*', '', q_text).strip()
    if not q_text:
        q_text = f"Question {answer_index + 1}"
    
    return ('short_answer', q_text, None)

# ============================================================================
# FULL FEEDBACK GENERATION
# ============================================================================

def create_question_object(q_type, question_text, answer, display_answer, options=None):
    """
    Create a question object with FULL feedback for all states.
    Feedback is educational and specific to IELTS Listening.
    """
    
    base_question = {
        'type': q_type,
        'instructions': '',
        'question': question_text,
        'points': 1.0,
        'reading_text_id': None
    }
    
    if q_type == 'multiple_choice' and options:
        # Multiple choice with options
        answer_clean = answer.split('|')[0].strip().upper()
        if len(answer_clean) == 1 and answer_clean in VALID_MC_LETTERS:
            correct_idx = ord(answer_clean) - ord('A')
        else:
            # Fallback: try to find the answer in options
            correct_idx = 0
            for i, opt in enumerate(options):
                if answer_clean.lower() in opt.lower():
                    correct_idx = i
                    break
        
        mc_options = []
        for i, opt in enumerate(options):
            is_correct = i == correct_idx
            mc_options.append({
                'text': f"{chr(65+i)}. {opt.strip()}",
                'is_correct': is_correct,
                'feedback': (
                    f"✓ Correct! Option {chr(65+i)} is the right answer. You identified the key information in the listening passage."
                    if is_correct else
                    f"✗ Not quite. Option {chr(65+i)} is not correct. The correct answer is {answer_clean}. Listen again and focus on the specific details mentioned in the audio."
                )
            })
        
        base_question.update({
            'mc_options': mc_options,
            'options': '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options)),
            'correct_answer': str(correct_idx),
            'option_feedback': [opt['feedback'] for opt in mc_options],
            'no_answer_feedback': f'No answer provided. The correct answer is {answer_clean}. In the IELTS Listening test, you should always attempt every question even if you\'re unsure - there\'s no penalty for wrong answers. Review the audio and transcript to understand why this is the correct option.',
            'correct_feedback': f"✓ Excellent! The answer is {answer_clean}. You listened carefully and selected the correct option.",
            'incorrect_feedback': f"✗ Not quite. The correct answer is {answer_clean}. Listen to the audio again and review the transcript. Pay attention to specific words and phrases that indicate which option is correct."
        })
    
    elif q_type == 'multi_select' and options:
        # Multi-select (e.g., "Choose TWO letters")
        answer_letters = answer.upper().split('|')
        correct_indices = []
        for a in answer_letters:
            a_clean = a.strip()
            if len(a_clean) == 1 and a_clean in VALID_MC_LETTERS:
                correct_indices.append(ord(a_clean) - ord('A'))
        
        # Ensure we have at least one correct answer
        if not correct_indices:
            # Fallback: mark first option as correct to avoid empty answer
            correct_indices = [0]
        
        correct_letters = [chr(65+i) for i in correct_indices]
        correct_letters_str = ' and '.join(correct_letters)
        
        mc_options = []
        for i, opt in enumerate(options):
            is_correct = i in correct_indices
            mc_options.append({
                'text': f"{chr(65+i)}. {opt.strip()}",
                'is_correct': is_correct,
                'feedback': (
                    f"✓ Correct! Option {chr(65+i)} is one of the right answers. This information was mentioned in the listening passage."
                    if is_correct else
                    f"This is not one of the correct answers. The correct options are {correct_letters_str}. Review the audio to understand why these options are correct."
                )
            })
        
        base_question.update({
            'mc_options': mc_options,
            'options': '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options)),
            'correct_answer': '|'.join(str(i) for i in sorted(correct_indices)),
            'option_feedback': [opt['feedback'] for opt in mc_options],
            'no_answer_feedback': f'No answer provided. The correct answers are {correct_letters_str}. In the IELTS test, you should always attempt every question. Listen to the audio and identify the TWO correct pieces of information mentioned.',
            'correct_feedback': f'✓ Excellent! You selected both correct answers ({correct_letters_str}). You listened carefully and identified all the key information.',
            'incorrect_feedback': f'✗ Not quite. The correct answers are {correct_letters_str}. Make sure you select BOTH correct options. Listen again and check which TWO pieces of information are mentioned in the audio.'
        })
    
    elif q_type in ['summary_completion', 'sentence_completion', 'matching', 'short_answer']:
        # Text-based questions with variants
        # Format the display answer nicely
        variants = answer.split('|')
        if len(variants) > 1:
            variants_display = ' or '.join([f'"{v.upper()}"' for v in variants])
            answer_explanation = f"Acceptable answers: {variants_display}"
        else:
            answer_explanation = f'The answer is "{display_answer}"'
        
        base_question.update({
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'✓ Excellent! {answer_explanation}. You listened carefully and identified the key information. Well done!',
                    'incorrect_feedback': f'✗ Not quite. {answer_explanation}. Listen to the audio again and check the transcript. Pay attention to keywords and phrases that directly relate to the question. Try to identify signal words that indicate important information is coming. In IELTS Listening, answers appear in the same order as the questions.',
                    'no_answer_feedback': f'No answer provided. {answer_explanation}. In the IELTS Listening test, you should always attempt every question - there\'s no penalty for wrong answers. Listen to the audio and review the transcript to understand where this information appears and how it\'s presented. Take notes while listening to help you remember key details.'
                }
            },
            'options': '',
            'correct_answer': answer,
            'no_answer_feedback': f'No answer provided. {answer_explanation}. In the IELTS Listening test, you should always attempt every question - there\'s no penalty for wrong answers. Listen to the audio and review the transcript to understand where this information appears and how it\'s presented.',
            'correct_feedback': f'✓ Excellent! {answer_explanation}. You listened carefully and identified the key information.',
            'incorrect_feedback': f'✗ Not quite. {answer_explanation}. Listen to the audio again and check the transcript. Pay attention to keywords and signal words that indicate important information.'
        })
    
    return base_question

# ============================================================================
# XML GENERATION
# ============================================================================

def generate_xml(test_num, section_num, questions, audio_url, transcript):
    """Generate complete WordPress-compatible XML with proper serialization."""
    now = datetime.now()
    
    questions_serialized = serialize_php(questions)
    starting_q_num = (section_num - 1) * 10 + 1
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 Generated by generate_listening_xml_master.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
 Master generator with full feedback and intelligent question type detection
-->
<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">
<channel>
<title>IELTStestONLINE</title>
<link>https://www.ieltstestonline.com/2026</link>
<description>Online IELTS preparation</description>
<pubDate>{now.strftime("%a, %d %b %Y %H:%M:%S +0000")}</pubDate>
<language>en-NZ</language>
<wp:wxr_version>1.2</wp:wxr_version>
<wp:base_site_url>https://www.ieltstestonline.com/2026</wp:base_site_url>
<wp:base_blog_url>https://www.ieltstestonline.com/2026</wp:base_blog_url>
<wp:author>
<wp:author_id>1</wp:author_id>
<wp:author_login><![CDATA[impact]]></wp:author_login>
<wp:author_email><![CDATA[impact@ieltstestonline.com]]></wp:author_email>
<wp:author_display_name><![CDATA[impact]]></wp:author_display_name>
<wp:author_first_name><![CDATA[Patrick]]></wp:author_first_name>
<wp:author_last_name><![CDATA[Bourne]]></wp:author_last_name>
</wp:author>
<generator>IELTS Course Manager - Master XML Generator</generator>
<item>
<title><![CDATA[Listening Test {test_num} Section {section_num}]]></title>
<link>https://www.ieltstestonline.com/2026/ielts-quiz/listening-test-{test_num}-section-{section_num}/</link>
<pubDate>{now.strftime("%a, %d %b %Y %H:%M:%S +0000")}</pubDate>
<dc:creator><![CDATA[impact]]></dc:creator>
<guid isPermaLink="false">https://www.ieltstestonline.com/2026/?post_type=ielts_quiz&amp;p=AUTO</guid>
<description/>
<content:encoded><![CDATA[]]></content:encoded>
<excerpt:encoded><![CDATA[]]></excerpt:encoded>
<wp:post_id>AUTO</wp:post_id>
<wp:post_date><![CDATA[{now.strftime("%Y-%m-%d %H:%M:%S")}]]></wp:post_date>
<wp:post_date_gmt><![CDATA[{now.strftime("%Y-%m-%d %H:%M:%S")}]]></wp:post_date_gmt>
<wp:post_modified><![CDATA[{now.strftime("%Y-%m-%d %H:%M:%S")}]]></wp:post_modified>
<wp:post_modified_gmt><![CDATA[{now.strftime("%Y-%m-%d %H:%M:%S")}]]></wp:post_modified_gmt>
<wp:comment_status><![CDATA[closed]]></wp:comment_status>
<wp:ping_status><![CDATA[closed]]></wp:ping_status>
<wp:post_name><![CDATA[listening-test-{test_num}-section-{section_num}]]></wp:post_name>
<wp:status><![CDATA[publish]]></wp:status>
<wp:post_parent>0</wp:post_parent>
<wp:menu_order>0</wp:menu_order>
<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>
<wp:post_password><![CDATA[]]></wp:post_password>
<wp:is_sticky>0</wp:is_sticky>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
<wp:meta_value><![CDATA[{questions_serialized}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>
<wp:meta_value><![CDATA[a:0:{{}}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>
<wp:meta_value><![CDATA[70]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>
<wp:meta_value><![CDATA[listening_practice]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>
<wp:meta_value><![CDATA[practice_test]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>
<wp:meta_value><![CDATA[]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>
<wp:meta_value><![CDATA[ielts_listening]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>
<wp:meta_value><![CDATA[]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>
<wp:meta_value><![CDATA[{starting_q_num}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_audio_url]]></wp:meta_key>
<wp:meta_value><![CDATA[{audio_url}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_transcript]]></wp:meta_key>
<wp:meta_value><![CDATA[{transcript}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_audio_sections]]></wp:meta_key>
<wp:meta_value><![CDATA[a:0:{{}}]]></wp:meta_value>
</wp:postmeta>
</item>
</channel>
</rss>
'''
    
    return xml_content

# ============================================================================
# SECTION GENERATION
# ============================================================================

def generate_section(test_num, section_num):
    """Generate one section with automatic question type detection and full feedback."""
    txt_file = f"Listening Test {test_num} Section {section_num}.txt"
    
    if not os.path.exists(txt_file):
        print(f"  ✗ {txt_file} not found")
        return False
    
    with open(txt_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    audio_url = extract_audio(content)
    transcript = extract_transcript(content)
    
    # Extract all {answer} blocks
    answer_blocks = re.findall(r'\{([^}]+)\}', content)
    
    if len(answer_blocks) < 10:
        print(f"  ⚠️  Only found {len(answer_blocks)} answers (expected 10)")
        # Continue anyway - will use what we have
    
    # Create questions with proper type detection and full feedback
    questions = []
    type_counts = {}
    i = 0
    
    while i < min(10, len(answer_blocks)):
        answer_raw = '{' + answer_blocks[i] + '}'
        answer = parse_answer(answer_raw)
        display_answer = answer.split('|')[0].upper()
        
        # Detect question type
        q_type, question_text, options = detect_question_type(content, i, answer_blocks[i])
        
        # Handle multi-select: combine multiple answer slots
        if q_type == 'multi_select':
            # Determine how many answers this multi-select needs
            num_answers = count_multi_select_answers(question_text)
            
            # Ensure we don't try to collect more answers than available
            answers_available = min(num_answers, len(answer_blocks) - i)
            
            # Collect all answers for this multi-select question
            all_answers = [answer]
            for j in range(1, answers_available):
                next_answer_raw = '{' + answer_blocks[i + j] + '}'
                next_answer = parse_answer(next_answer_raw)
                all_answers.append(next_answer)
            
            # Combine all answer variants with pipe separation
            combined_answer = '|'.join(all_answers)
            
            # Create ONE multi-select question with all answers
            question_obj = create_question_object(q_type, question_text, combined_answer, display_answer, options)
            questions.append(question_obj)
            
            # Skip the next answer slots since we've consumed them
            i += answers_available
        else:
            # Regular question handling
            question_obj = create_question_object(q_type, question_text, answer, display_answer, options)
            questions.append(question_obj)
            i += 1
        
        # Track types
        type_counts[q_type] = type_counts.get(q_type, 0) + 1
    
    # Generate XML
    xml_content = generate_xml(test_num, section_num, questions, audio_url, transcript)
    
    xml_file = f"Listening Test {test_num} Section {section_num}.xml"
    with open(xml_file, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    
    # Report
    types_str = ', '.join(f"{count}×{qtype}" for qtype, count in sorted(type_counts.items()))
    print(f"  ✓ Generated {xml_file}")
    print(f"    Question types: {types_str}")
    print(f"    Questions: {len(questions)}/10")
    return True

# ============================================================================
# COMMAND-LINE INTERFACE
# ============================================================================

def main():
    parser = argparse.ArgumentParser(
        description='Master IELTS Listening XML Generator - Handles all tests with full feedback',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog='''
Examples:
  %(prog)s "Listening Test 6 Section 1.txt"    # Single section
  %(prog)s --test 6                              # All sections of Test 6
  %(prog)s --tests 6-10                          # Tests 6 through 10
  %(prog)s --all                                 # All tests in directory
        '''
    )
    
    parser.add_argument('filename', nargs='?', help='TXT file to convert')
    parser.add_argument('--test', type=int, help='Generate all sections for one test (e.g., --test 6)')
    parser.add_argument('--tests', help='Generate range of tests (e.g., --tests 6-10)')
    parser.add_argument('--all', action='store_true', help='Generate all tests found in directory')
    
    args = parser.parse_args()
    
    # Change to XMLs directory
    script_dir = os.path.dirname(os.path.abspath(__file__))
    os.chdir(script_dir)
    
    print("=" * 80)
    print("MASTER IELTS LISTENING XML GENERATOR")
    print("Automatic question type detection + Full feedback generation")
    print("=" * 80)
    print()
    
    if args.filename:
        # Single file
        match = re.match(r'Listening Test (\d+) Section (\d+)\.txt', args.filename)
        if match:
            test_num = int(match.group(1))
            section_num = int(match.group(2))
            print(f"Generating Test {test_num} Section {section_num}...")
            generate_section(test_num, section_num)
        else:
            print(f"Error: Filename must match pattern 'Listening Test N Section M.txt'")
            sys.exit(1)
    
    elif args.test:
        # All sections of one test
        test_num = args.test
        print(f"Generating all sections for Test {test_num}...\n")
        for section_num in [1, 2, 3, 4]:
            generate_section(test_num, section_num)
    
    elif args.tests:
        # Range of tests
        match = re.match(r'(\d+)-(\d+)', args.tests)
        if match:
            start_test = int(match.group(1))
            end_test = int(match.group(2))
            print(f"Generating Tests {start_test} through {end_test}...\n")
            for test_num in range(start_test, end_test + 1):
                print(f"\nTest {test_num}:")
                for section_num in [1, 2, 3, 4]:
                    generate_section(test_num, section_num)
        else:
            print("Error: --tests format should be N-M (e.g., --tests 6-10)")
            sys.exit(1)
    
    elif args.all:
        # All tests in directory
        txt_files = [f for f in os.listdir('.') if re.match(r'Listening Test \d+ Section \d+\.txt', f)]
        tests_found = set()
        for f in txt_files:
            match = re.match(r'Listening Test (\d+) Section \d+\.txt', f)
            if match:
                tests_found.add(int(match.group(1)))
        
        tests_sorted = sorted(tests_found)
        print(f"Found tests: {', '.join(map(str, tests_sorted))}\n")
        
        for test_num in tests_sorted:
            print(f"\nTest {test_num}:")
            for section_num in [1, 2, 3, 4]:
                generate_section(test_num, section_num)
    
    else:
        parser.print_help()
        sys.exit(1)
    
    print("\n" + "=" * 80)
    print("GENERATION COMPLETE!")
    print("All XML files have full feedback (CORRECT, INCORRECT, NO ANSWER)")
    print("=" * 80)

if __name__ == "__main__":
    main()
