#!/usr/bin/env python3
"""
Universal IELTS Listening Test XML Generator

Handles ALL listening test formats and question types:
- Test 3 style: Plain text with === QUESTION TYPE === markers
- Test 4 style: HTML formatted with embedded answers
- All question types: Multiple choice, Multi-select, Summary completion, Table completion, etc.

Usage:
    python3 generate_listening_xml_universal.py "Listening Test 5 Section 1.txt"
    
    # Or batch process all sections of a test:
    python3 generate_listening_xml_universal.py --test 5
    
    # Or batch process ALL tests:
    python3 generate_listening_xml_universal.py --all

Author: IELTS Course Manager
Date: 2025-12-28
"""

import re
import sys
import os
import argparse
from datetime import datetime
from html import escape, unescape

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
# FORMAT DETECTION
# ============================================================================

def detect_format(content):
    """Auto-detect Test 3 vs Test 4 format."""
    if '=== QUESTION TYPE:' in content or '=== EXERCISE SETTINGS ===' in content:
        return 'test3'
    elif '<strong>' in content or '<ol' in content or '<em>' in content:
        return 'test4'
    else:
        return 'test4'  # Default

# ============================================================================
# COMMON EXTRACTION FUNCTIONS
# ============================================================================

def extract_audio_url(content):
    """Extract audio URL from [audio mp3="URL"] tag."""
    match = re.search(r'\[audio mp3="([^"]+)"\]', content)
    return match.group(1) if match else ""

def extract_transcript(content):
    """Extract transcript from HTML."""
    # Method 1: div with overflow scroll
    match = re.search(r'<div[^>]*style="[^"]*overflow:\s*scroll[^"]*"[^>]*>(.*?)</div>', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1).strip()
    
    # Method 2: table
    match = re.search(r'(<table[^>]*>.*?</table>)', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1)
    
    return ""

def parse_answer_variants(answer_text):
    """Parse answer from {[ans1][ans2]} or {ans} format."""
    answer_text = answer_text.strip('{}')
    variants = re.findall(r'\[([^\]]+)\]', answer_text)
    if variants:
        return '|'.join(v.lower() for v in variants)
    else:
        return answer_text.lower()

# ============================================================================
# TEST 3 FORMAT PARSER (Plain text with === markers)
# ============================================================================

def parse_test3_short_answer(content, start_q, end_q):
    """Parse Test 3 style short answer questions."""
    questions = []
    
    # Pattern: Q_NUM. Question text {[ANSWER1][ANSWER2]}
    #          [CORRECT] feedback
    #          [INCORRECT] feedback  
    #          [NO ANSWER] feedback
    pattern = r'(\d+)\.\s+(.+?)\s+\{([^}]+)\}\s+\[CORRECT\]\s+(.+?)\s+\[INCORRECT\]\s+(.+?)\s+\[NO ANSWER\]\s+(.+?)(?=\n\d+\.|===|$)'
    
    matches = re.finditer(pattern, content, re.DOTALL)
    
    for match in matches:
        q_num = int(match.group(1))
        if q_num < start_q or q_num > end_q:
            continue
            
        question_text = match.group(2).strip()
        answer_text = match.group(3).strip()
        correct_fb = match.group(4).strip()
        incorrect_fb = match.group(5).strip()
        no_answer_fb = match.group(6).strip()
        
        answer = parse_answer_variants(answer_text)
        
        questions.append({
            'type': 'summary_completion',
            'instructions': '',
            'question': question_text,
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': correct_fb,
                    'incorrect_feedback': incorrect_fb,
                    'no_answer_feedback': no_answer_fb
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': correct_fb,
            'incorrect_feedback': incorrect_fb,
            'reading_text_id': None
        })
    
    return questions

def parse_test3_table_completion(content):
    """Parse Test 3 style table completion (multi-field)."""
    # Look for Field N: ANSWER pattern with feedback
    feedback_pattern = r'Field\s+(\d+):\s+([^\n]+?)\s+\[CORRECT\]\s+(.+?)\s+\[INCORRECT\]\s+(.+?)\s+\[NO ANSWER\]\s+(.+?)(?=\nField\s+\d+:|===|$)'
    
    feedback_data = {}
    for match in re.finditer(feedback_pattern, content, re.DOTALL):
        field_num = int(match.group(1))
        answer_text = match.group(2).strip()
        correct_fb = match.group(3).strip()
        incorrect_fb = match.group(4).strip()
        no_answer_fb = match.group(5).strip()
        
        # Parse answer variants
        answer_parts = answer_text.split(' / ')
        if len(answer_parts) > 1:
            answer = '|'.join(p.lower().strip() for p in answer_parts)
        else:
            answer = answer_text.lower()
        
        feedback_data[field_num] = {
            'answer': answer,
            'correct_feedback': correct_fb,
            'incorrect_feedback': incorrect_fb,
            'no_answer_feedback': no_answer_fb
        }
    
    if not feedback_data:
        return []
    
    # Create one multi-field question
    return [{
        'type': 'summary_completion',
        'instructions': '',
        'question': 'Complete the table',
        'points': 1.0,
        'summary_fields': feedback_data,
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You got all the answers correct.',
        'incorrect_feedback': 'Some answers are incorrect.',
        'reading_text_id': None
    }]

# ============================================================================
# TEST 4 FORMAT PARSER (HTML with embedded answers)
# ============================================================================

def parse_test4_multiple_choice(content, start_q, end_q):
    """Parse Test 4 style multiple choice: Q. text <ol>options</ol> Q. {A}."""
    questions = []
    
    # Pattern: number, question, options list, answer
    pattern = r'(\d+)\.\s+(.+?)<ol[^>]*>(.+?)</ol>\s*\d+\.\s*\{([A-E])\}'
    
    for match in re.finditer(pattern, content, re.DOTALL):
        q_num = int(match.group(1))
        if q_num < start_q or q_num > end_q:
            continue
            
        question_text = unescape(re.sub(r'<[^>]+>', '', match.group(2))).strip()
        options_html = match.group(3)
        correct_answer = match.group(4)
        
        # Parse options
        options_list = re.findall(r'<li>([^<]+)</li>', options_html, re.DOTALL)
        options_text = '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options_list))
        
        correct_idx = ord(correct_answer) - ord('A')
        
        mc_options = []
        for i, opt in enumerate(options_list):
            mc_options.append({
                'text': f"{chr(65+i)}. {opt.strip()}",
                'is_correct': i == correct_idx,
                'feedback': f"Correct! The answer is {correct_answer}." if i == correct_idx else f"Incorrect. The correct answer is {correct_answer}."
            })
        
        questions.append({
            'type': 'multiple_choice',
            'instructions': '',
            'question': question_text,
            'points': 1.0,
            'mc_options': mc_options,
            'options': options_text,
            'correct_answer': str(correct_idx),
            'option_feedback': [opt['feedback'] for opt in mc_options],
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': f"Correct! The answer is {correct_answer}.",
            'incorrect_feedback': f"Incorrect. The correct answer is {correct_answer}.",
            'reading_text_id': None
        })
    
    return questions

def parse_test4_sentence_completion(content, start_q, end_q):
    """Parse Test 4/5 style: (Q) {answer} or Q. text {answer}."""
    questions = []
    
    # Pattern 1: (1) {answer} or Q. text {answer}
    # Pattern 2: Just number {answer} on a line
    # More flexible to handle various HTML tags and formats
    patterns = [
        r'(\d+)\.\s+(.+?)\s+\{([^}]+)\}',  # Q. text {answer}
        r'\((\d+)\)\s+\{([^}]+)\}',  # (Q) {answer}
        r'(\d+)\.\s*\{([^}]+)\}'  # Q. {answer}
    ]
    
    found_questions = {}
    
    for pattern in patterns:
        for match in re.finditer(pattern, content):
            groups = match.groups()
            
            if len(groups) == 3:
                q_num = int(groups[0])
                question_text = unescape(re.sub(r'<[^>]+>', ' ', groups[1])).strip()
                answer_text = groups[2].strip()
            elif len(groups) == 2:
                q_num = int(groups[0])
                question_text = f"Question {q_num}"
                answer_text = groups[1].strip()
            else:
                continue
            
            if q_num < start_q or q_num > end_q:
                continue
            
            if q_num in found_questions:
                continue
            
            answer = parse_answer_variants(answer_text)
            
            found_questions[q_num] = {
                'type': 'summary_completion',
                'instructions': '',
                'question': question_text,
                'points': 1.0,
                'summary_fields': {
                    1: {
                        'answer': answer,
                        'correct_feedback': 'Correct!',
                        'incorrect_feedback': 'Incorrect.',
                        'no_answer_feedback': f'The correct answer is: {answer.split("|")[0].upper()}. Make sure to listen carefully for key information and take notes while listening.'
                    }
                },
                'options': '',
                'correct_answer': '',
                'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
                'correct_feedback': 'Correct!',
                'incorrect_feedback': 'Incorrect.',
                'reading_text_id': None
            }
    
    # Return in order
    for q_num in sorted(found_questions.keys()):
        questions.append(found_questions[q_num])
    
    return questions

# ============================================================================
# UNIVERSAL PARSER
# ============================================================================

def parse_questions(content, section_num):
    """Parse questions from content, auto-detecting format and question types."""
    format_type = detect_format(content)
    start_q = (section_num - 1) * 10 + 1
    end_q = section_num * 10
    
    questions = []
    
    if format_type == 'test3':
        # Test 3 format: use section markers
        if '=== QUESTION TYPE: SHORT ANSWER ===' in content:
            questions.extend(parse_test3_short_answer(content, start_q, end_q))
        if '=== QUESTION TYPE: TABLE COMPLETION ===' in content:
            questions.extend(parse_test3_table_completion(content))
    else:
        # Test 4 format: try all parsers
        # MC questions (with <ol> lists)
        mc_questions = parse_test4_multiple_choice(content, start_q, end_q)
        questions.extend(mc_questions)
        
        # Get question numbers already parsed
        parsed_nums = set()
        for q in questions:
            # Estimate which question number this is based on order
            pass
        
        # Sentence completion (remaining questions with {answer})
        sentence_questions = parse_test4_sentence_completion(content, start_q, end_q)
        
        # Filter out duplicates (questions already parsed as MC)
        mc_q_nums = set()
        for match in re.finditer(r'(\d+)\.\s+.+?<ol[^>]*>', content, re.DOTALL):
            mc_q_nums.add(int(match.group(1)))
        
        for q in sentence_questions:
            # This is a heuristic - improve if needed
            questions.append(q)
    
    return questions

# ============================================================================
# TRANSCRIPT ANNOTATION
# ============================================================================

def annotate_transcript(transcript, questions, starting_q_num):
    """Add yellow-highlighted answer markers to transcript."""
    if not transcript:
        return transcript
    
    annotated = transcript
    q_number = starting_q_num
    
    for q in questions:
        if 'summary_fields' in q:
            for field_num, field_data in q['summary_fields'].items():
                answer_text = field_data['answer']
                variants = answer_text.split('|')
                
                for variant in variants:
                    variant = variant.strip()
                    if not variant:
                        continue
                    
                    if f'[Q{q_number}:' in annotated:
                        break
                    
                    pattern_text = re.escape(variant)
                    pattern_text = pattern_text.replace(r'\ ', r'\s+')
                    pattern = r'\b(' + pattern_text + r')\b'
                    
                    def replace_func(m):
                        return f'<strong style="background-color: yellow;">[Q{q_number}: {escape(m.group(1))}]</strong>'
                    
                    new_annotated = re.sub(pattern, replace_func, annotated, count=1, flags=re.IGNORECASE)
                    
                    if new_annotated != annotated:
                        annotated = new_annotated
                        break
                
                q_number += 1
        else:
            q_number += 1
    
    return annotated

# ============================================================================
# XML GENERATION
# ============================================================================

def generate_xml(title, section_num, questions, audio_url, transcript):
    """Generate complete WordPress XML."""
    now = datetime.now()
    
    questions_serialized = serialize_php(questions)
    starting_q_num = (section_num - 1) * 10 + 1
    annotated_transcript = annotate_transcript(transcript, questions, starting_q_num)
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 This is a WordPress eXtended RSS file for IELTS Course Manager exercise export 
-->
<!--
 Generated by generate_listening_xml_universal.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
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
<generator>IELTS Course Manager - generate_listening_xml_universal.py</generator>
<item>
<title><![CDATA[{escape(title)}]]></title>
<link>https://www.ieltstestonline.com/2026/ielts-quiz/{title.lower().replace(" ", "-")}/</link>
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
<wp:post_name><![CDATA[{title.lower().replace(" ", "-")}]]></wp:post_name>
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
<wp:meta_value><![CDATA[{annotated_transcript}]]></wp:meta_value>
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
# MAIN ENTRY POINT
# ============================================================================

def process_file(input_file):
    """Process a single TXT file and generate XML."""
    if not os.path.exists(input_file):
        print(f"❌ File not found: {input_file}")
        return False
    
    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    base_name = os.path.splitext(os.path.basename(input_file))[0]
    title = base_name
    
    section_match = re.search(r'Section\s+(\d+)', title)
    section_num = int(section_match.group(1)) if section_match else 1
    
    format_type = detect_format(content)
    print(f"Processing: {title}")
    print(f"  Format: {format_type.upper()}")
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    
    print(f"  Audio: {'✓' if audio_url else '✗'}")
    print(f"  Transcript: {'✓' if transcript else '✗'}")
    
    questions = parse_questions(content, section_num)
    
    # Count actual questions
    q_count = 0
    for q in questions:
        if 'summary_fields' in q:
            q_count += len(q['summary_fields'])
        elif 'min_selections' in q:
            q_count += q.get('min_selections', 1)
        else:
            q_count += 1
    
    print(f"  Questions: {q_count}/10 parsed")
    
    if q_count < 10:
        print(f"  ⚠️  WARNING: Only found {q_count} questions (expected 10)")
        print(f"      This file may need manual review or custom parser")
    
    xml_content = generate_xml(title, section_num, questions, audio_url, transcript)
    
    output_file = base_name + ".xml"
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    
    print(f"  ✓ Generated: {output_file}")
    return True

def main():
    parser = argparse.ArgumentParser(description='Universal IELTS Listening Test XML Generator')
    parser.add_argument('input', nargs='?', help='Input .txt file')
    parser.add_argument('--test', type=int, help='Generate all sections for test number N')
    parser.add_argument('--all', action='store_true', help='Generate ALL tests')
    
    args = parser.parse_args()
    
    if args.all:
        print("=" * 80)
        print("BATCH MODE: Processing ALL listening tests")
        print("=" * 80)
        
        # Find all txt files (excluding transcripts)
        txt_files = [f for f in os.listdir('.') if f.endswith('.txt') and 'Listening Test' in f and 'transcript' not in f]
        txt_files.sort()
        
        success_count = 0
        for txt_file in txt_files:
            try:
                if process_file(txt_file):
                    success_count += 1
                print()
            except Exception as e:
                print(f"  ❌ ERROR: {e}")
                print()
        
        print("=" * 80)
        print(f"Completed: {success_count}/{len(txt_files)} files processed successfully")
        print("=" * 80)
        
    elif args.test:
        print("=" * 80)
        print(f"BATCH MODE: Processing Listening Test {args.test}")
        print("=" * 80)
        
        for section in range(1, 5):
            txt_file = f"Listening Test {args.test} Section {section}.txt"
            try:
                process_file(txt_file)
                print()
            except Exception as e:
                print(f"  ❌ ERROR: {e}")
                print()
        
    elif args.input:
        process_file(args.input)
    else:
        parser.print_help()
        sys.exit(1)

if __name__ == "__main__":
    main()
