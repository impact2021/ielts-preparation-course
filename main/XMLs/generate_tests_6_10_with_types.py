#!/usr/bin/env python3
"""
Enhanced generator for Tests 6-10 that properly detects question types.
Based on generate_simple_tests_6_10.py but with proper type detection.
"""

import re, os, sys
from datetime import datetime
from html import escape

# Regex patterns for question type detection
PATTERN_STRONG_NUMBER = r'<strong>\s*\d+\s*</strong>\s*$'
PATTERN_SENTENCE_BEFORE = r'[a-z]\s*\d*\.?\s*$'
PATTERN_SENTENCE_AFTER = r'^\s*\.?\s*[A-Z]?[a-z]'
PATTERN_ORDERED_LIST = r'<ol[^>]*>(.*?)</ol>'
PATTERN_LIST_ITEMS = r'<li>([^<]+)</li>'
PATTERN_LABEL_INSTRUCTION = r'(Label\s+the\s+[^<\n]+)'
PATTERN_COMPLETE_INSTRUCTION = r'(Complete\s+the\s+[^<\n]+)'

def serialize_php(data):
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

def parse_answer(answer_text):
    """Parse {[ans1][ans2]|ans3} format"""
    answer_text = answer_text.strip('{}')
    variants = re.findall(r'\[([^\]]+)\]', answer_text)
    if variants:
        return '|'.join(v.lower() for v in variants)
    else:
        return answer_text.lower()

def extract_audio(content):
    match = re.search(r'\[audio mp3="([^"]+)"\]', content)
    return match.group(1) if match else ""

def extract_transcript(content):
    match = re.search(r'<div[^>]*style="[^"]*overflow:\s*scroll[^"]*"[^>]*>(.*?)</div>', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1).strip()
    match = re.search(r'(<table[^>]*>.*?</table>)', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1)
    return ""

def extract_ordered_list_options(html_content):
    """Extract options from an ordered list in HTML."""
    ol_match = re.search(PATTERN_ORDERED_LIST, html_content, re.DOTALL)
    if ol_match:
        options_html = ol_match.group(1)
        return re.findall(PATTERN_LIST_ITEMS, options_html)
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
    """Helper function to extract summary completion text from paragraph."""
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
    
    # Get context around the answer
    line_start = content.rfind('\n', 0, answer_start) + 1
    same_line_text = content[line_start:answer_start].strip()
    
    # Look back for question type indicators
    lookback = content[max(0, answer_start - 1000):answer_start]
    before_answer = content[max(0, answer_start - 200):answer_start]
    after_answer = content[answer_end:min(len(content), answer_end + 200)]
    
    # First priority: Check for summary/form completion (answer embedded in paragraph)
    # This must come BEFORE checking for MC/multi-select to avoid false positives
    
    if is_summary_completion_context(before_answer, after_answer):
        summary_text = extract_summary_text(content, answer_start, answer_end, answer_index)
        return ('summary_completion', summary_text, None)
    
    # Check for "Choose TWO letters" style multi-select
    # Only if answer contains multiple letter choices (e.g., {[B][D]})
    if 'choose two' in lookback.lower() or 'choose 2' in lookback.lower():
        # Check if this answer is close to the instruction (within 300 chars)
        recent_lookback = content[max(0, answer_start - 300):answer_start]
        if 'choose two' in recent_lookback.lower() or 'choose 2' in recent_lookback.lower():
            # Extract options
            options_list = extract_ordered_list_options(lookback)
            if options_list:
                    # Find instruction text
                    instr_match = re.search(r'(Choose\s+TWO\s+letters[^<]+)', recent_lookback, re.IGNORECASE)
                    if instr_match:
                        q_text = instr_match.group(1).strip()
                        return ('multi_select', q_text, options_list)
    
    # Check for multiple choice (has options list with <ol> and answer is single letter)
    if '<ol' in lookback and '</ol>' in lookback:
        # Check if answer is a single letter (A-E) - strong indicator of MC
        answer_clean = answer_text.strip('{}[]').strip().upper()
        if len(answer_clean) == 1 and answer_clean in 'ABCDE':
            # Make sure the <ol> is recent (within 300 chars)
            recent_lookback = content[max(0, answer_start - 300):answer_start]
            if '<ol' in recent_lookback:
                # Extract the options list
                options_list = extract_ordered_list_options(lookback)
                if options_list:
                        # Find the question text before the options
                        question_match = re.search(r'(\d+)\.\s+(.+?)<ol', lookback, re.DOTALL)
                        if question_match:
                            q_text = re.sub(r'<[^>]+>', '', question_match.group(2)).strip()
                            return ('multiple_choice', q_text, options_list)
    
    # Check for map/diagram labeling (has "Label the map" or similar)
    if 'label' in lookback.lower() and ('map' in lookback.lower() or 'diagram' in lookback.lower()):
        # Single letter answer
        answer_clean = answer_text.strip('{}[]').strip().upper()
        if len(answer_clean) <= 2 and answer_clean.isalpha():
            # Extract the instruction
            label_match = re.search(PATTERN_LABEL_INSTRUCTION, lookback, re.IGNORECASE)
            if label_match:
                instr = label_match.group(1).strip()
                return ('matching', instr, None)
    
    # Check if question has a question mark (standard short answer)
    same_line_clean = re.sub(r'<[^>]+>', '', same_line_text)
    if '?' in same_line_clean:
        q_text = re.sub(r'^\d+\.\s*', '', same_line_clean).strip()
        return ('short_answer', q_text, None)
    
    # Check for sentence/table completion (has "Complete the" in context)
    if 'complete the' in lookback.lower():
        # Look for the sentence or table prompt
        complete_match = re.search(PATTERN_COMPLETE_INSTRUCTION, lookback, re.IGNORECASE)
        if complete_match:
            # Extract the actual text to complete
            q_text = same_line_clean.strip()
            if q_text:
                return ('sentence_completion', q_text, None)
            else:
                return ('sentence_completion', complete_match.group(1), None)
    
    # Default: treat as short answer
    q_text = re.sub(r'<[^>]+>', '', same_line_text)
    q_text = re.sub(r'^\d+\.\s*', '', q_text).strip()
    if not q_text:
        q_text = f"Question {answer_index + 1}"
    
    return ('short_answer', q_text, None)

def create_question_object(q_type, question_text, answer, display_answer, options=None):
    """Create a question object based on type."""
    
    base_question = {
        'type': q_type,
        'instructions': '',
        'question': question_text,
        'points': 1.0,
        'reading_text_id': None
    }
    
    if q_type == 'multiple_choice' and options:
        # Find which option matches the answer
        answer_clean = answer.split('|')[0].strip().upper()
        correct_idx = ord(answer_clean) - ord('A') if len(answer_clean) == 1 else 0
        
        mc_options = []
        for i, opt in enumerate(options):
            mc_options.append({
                'text': f"{chr(65+i)}. {opt.strip()}",
                'is_correct': i == correct_idx,
                'feedback': f"Correct! The answer is {answer_clean}." if i == correct_idx else f"Incorrect. The correct answer is {answer_clean}."
            })
        
        base_question.update({
            'mc_options': mc_options,
            'options': '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options)),
            'correct_answer': str(correct_idx),
            'option_feedback': [opt['feedback'] for opt in mc_options],
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': f"Correct! The answer is {answer_clean}.",
            'incorrect_feedback': f"Incorrect. The correct answer is {answer_clean}."
        })
    
    elif q_type == 'multi_select' and options:
        # Parse multiple correct answers
        answer_letters = answer.upper().split('|')
        correct_indices = [ord(a.strip()) - ord('A') for a in answer_letters if len(a.strip()) == 1]
        
        mc_options = []
        for i, opt in enumerate(options):
            is_correct = i in correct_indices
            mc_options.append({
                'text': f"{chr(65+i)}. {opt.strip()}",
                'is_correct': is_correct,
                'feedback': "Correct choice." if is_correct else "This is not one of the correct answers."
            })
        
        base_question.update({
            'mc_options': mc_options,
            'options': '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options)),
            'correct_answer': '|'.join(str(i) for i in sorted(correct_indices)),
            'option_feedback': [opt['feedback'] for opt in mc_options],
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct! You selected the right answers.',
            'incorrect_feedback': 'Not quite. Please review the correct answers.'
        })
    
    elif q_type in ['summary_completion', 'sentence_completion', 'matching', 'short_answer']:
        # These all use summary_fields format
        base_question.update({
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'✓ Excellent! "{display_answer}" is correct. You listened carefully and identified the key information.',
                    'incorrect_feedback': f'✗ Not quite. The correct answer is "{display_answer}". Listen to the audio again and check the transcript. Pay attention to keywords and phrases that directly relate to the question. Try to identify signal words that indicate important information is coming.',
                    'no_answer_feedback': f'No answer provided. The correct answer is "{display_answer}". In the IELTS Listening test, you should always attempt every question - there\'s no penalty for wrong answers. Listen to the audio and review the transcript to understand where this information appears and how it\'s presented.'
                }
            },
            'options': '',
            'correct_answer': answer,
            'no_answer_feedback': f'No answer provided. The correct answer is "{display_answer}". In the IELTS Listening test, you should always attempt every question - there\'s no penalty for wrong answers. Listen to the audio and review the transcript to understand where this information appears and how it\'s presented.',
            'correct_feedback': f'✓ Excellent! "{display_answer}" is correct. You listened carefully and identified the key information.',
            'incorrect_feedback': f'✗ Not quite. The correct answer is "{display_answer}". Listen to the audio again and check the transcript. Pay attention to keywords and phrases that directly relate to the question. Try to identify signal words that indicate important information is coming.'
        })
    
    return base_question

def generate_xml(test_num, section_num, questions, audio_url, transcript):
    """Generate complete XML."""
    now = datetime.now()
    
    questions_serialized = serialize_php(questions)
    starting_q_num = (section_num - 1) * 10 + 1
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 Generated by generate_tests_6_10_with_types.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
 This version properly detects question types instead of hardcoding all as short_answer
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
<generator>IELTS Course Manager - generate_tests_6_10_with_types.py</generator>
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

def generate_section(test_num, section_num):
    """Generate one section with proper question type detection."""
    txt_file = f"Listening Test {test_num} Section {section_num}.txt"
    
    if not os.path.exists(txt_file):
        print(f"  ✗ {txt_file} not found")
        return False
    
    with open(txt_file, 'r') as f:
        content = f.read()
    
    audio_url = extract_audio(content)
    transcript = extract_transcript(content)
    
    # Extract all {answer} blocks
    answer_blocks = re.findall(r'\{([^}]+)\}', content)
    
    if len(answer_blocks) < 10:
        print(f"  ⚠️  Only found {len(answer_blocks)} answers")
        return False
    
    # Create questions with proper type detection
    questions = []
    type_counts = {}
    
    for i in range(10):
        answer_raw = '{' + answer_blocks[i] + '}'
        answer = parse_answer(answer_raw)
        display_answer = answer.split('|')[0].upper()
        
        # Detect question type
        q_type, question_text, options = detect_question_type(content, i, answer_blocks[i])
        
        # Track types
        type_counts[q_type] = type_counts.get(q_type, 0) + 1
        
        # Create question object
        question_obj = create_question_object(q_type, question_text, answer, display_answer, options)
        questions.append(question_obj)
    
    # Generate XML
    xml_content = generate_xml(test_num, section_num, questions, audio_url, transcript)
    
    xml_file = f"Listening Test {test_num} Section {section_num}.xml"
    with open(xml_file, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    
    # Report
    types_str = ', '.join(f"{count}×{qtype}" for qtype, count in sorted(type_counts.items()))
    print(f"  ✓ Generated {xml_file} - Types: {types_str}")
    return True

def main():
    os.chdir("/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/XMLs")
    
    print("=" * 80)
    print("GENERATING TESTS 6-10 (With Proper Question Type Detection)")
    print("=" * 80)
    
    for test_num in [6, 7, 8, 9, 10]:
        print(f"\nTest {test_num}:")
        for section_num in [1, 2, 3, 4]:
            generate_section(test_num, section_num)
    
    print("\n" + "=" * 80)
    print("DONE!")
    print("=" * 80)

if __name__ == "__main__":
    main()
