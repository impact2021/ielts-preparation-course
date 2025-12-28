#!/usr/bin/env python3
"""
Generate complete listening test XML with proper PHP serialized questions.
This script parses the TXT format and creates a fully structured XML file.
"""

import re
import sys
import os
from datetime import datetime
from html import escape

def parse_txt_file(txt_content):
    """Parse the TXT file to extract all necessary information."""
    data = {
        'title': '',
        'layout_type': 'listening_practice',
        'exercise_label': 'practice_test',
        'scoring_type': 'ielts_listening',
        'starting_question_number': 1,
        'audio_url': '',
        'transcript': '',
        'questions': []
    }
    
    # Extract settings
    settings_match = re.search(r'===\s*EXERCISE SETTINGS\s*===(.+?)===\s*END EXERCISE SETTINGS\s*===', txt_content, re.DOTALL)
    if settings_match:
        settings = settings_match.group(1)
        
        # Extract starting question number
        start_q_match = re.search(r'Starting Question Number:\s*(\d+)', settings)
        if start_q_match:
            data['starting_question_number'] = int(start_q_match.group(1))
    
    # Extract audio URL
    audio_match = re.search(r'\[audio mp3="([^"]+)"\]', txt_content)
    if audio_match:
        data['audio_url'] = audio_match.group(1)
    
    # Extract transcript
    transcript_match = re.search(r'(<table[^>]*>.*?</table>)', txt_content, re.DOTALL | re.IGNORECASE)
    if transcript_match:
        data['transcript'] = transcript_match.group(1)
    
    # Parse questions
    data['questions'] = parse_questions(txt_content)
    
    return data

def parse_questions(txt_content):
    """Parse questions from the TXT format."""
    questions = []
    
    # Split by question type sections
    question_type_sections = re.split(r'===\s*QUESTION TYPE:\s*([^=]+?)\s*===', txt_content)
    
    for i in range(1, len(question_type_sections), 2):
        question_type = question_type_sections[i].strip().upper()
        content = question_type_sections[i + 1]
        
        if question_type == 'SHORT ANSWER':
            questions.extend(parse_short_answer_questions(content))
        elif question_type == 'TABLE COMPLETION':
            questions.extend(parse_table_completion_questions(content))
        elif question_type == 'SUMMARY COMPLETION':
            questions.extend(parse_summary_completion_questions(content))
    
    return questions

def parse_short_answer_questions(content):
    """Parse short answer questions."""
    questions = []
    
    # Find individual questions
    # Pattern: question number, question text, answer in braces, feedback
    pattern = r'(\d+)\.\s+([^{]+?)\s+\{([^}]+)\}\s+\[CORRECT\]\s+(.+?)\s+\[INCORRECT\]\s+(.+?)\s+\[NO ANSWER\]\s+(.+?)(?=\n\d+\.|===|$)'
    
    matches = re.finditer(pattern, content, re.DOTALL)
    
    for match in matches:
        q_num = int(match.group(1))
        question_text = match.group(2).strip()
        answer_text = match.group(3).strip()
        correct_fb = match.group(4).strip()
        incorrect_fb = match.group(5).strip()
        no_answer_fb = match.group(6).strip()
        
        # Parse answer variants
        answer_variants = re.findall(r'\[([^\]]+)\]', answer_text)
        if answer_variants:
            answer = '|'.join(v.lower() for v in answer_variants)
        else:
            answer = answer_text.lower()
        
        # Extract instructions (if present before first question)
        instructions = ""
        if q_num == questions[0]['number'] if questions else True:
            instr_match = re.search(r'Questions\s+\d+[-–]\d+[:\s]*(.+?)(?=\n\d+\.)', content, re.DOTALL)
            if instr_match:
                instructions = instr_match.group(1).strip()
        
        questions.append({
            'number': q_num,
            'type': 'summary_completion',
            'instructions': instructions,
            'question': question_text,
            'answer': answer,
            'correct_feedback': correct_fb,
            'incorrect_feedback': incorrect_fb,
            'no_answer_feedback': no_answer_fb
        })
    
    return questions

def parse_table_completion_questions(content):
    """Parse table completion questions."""
    questions = []
    
    # Extract instructions
    instr_match = re.search(r'Questions\s+(\d+)[-–](\d+)[:\s]*(.+?)(?=\n\||===)', content, re.DOTALL)
    if not instr_match:
        return questions
    
    start_q = int(instr_match.group(1))
    end_q = int(instr_match.group(2))
    instructions = instr_match.group(3).strip()
    
    # Find the table and ANSWER placeholders
    answer_pattern = r'\[ANSWER\s+(\d+)\]'
    answer_matches = list(re.finditer(answer_pattern, content))
    
    # Parse the feedback section
    feedback_pattern = r'Field\s+(\d+):\s+([^\n]+?)\s+\[CORRECT\]\s+(.+?)\s+\[INCORRECT\]\s+(.+?)\s+\[NO ANSWER\]\s+(.+?)(?=\nField\s+\d+:|===|$)'
    feedback_matches = re.finditer(feedback_pattern, content, re.DOTALL)
    
    feedback_data = {}
    for fb_match in feedback_matches:
        field_num = int(fb_match.group(1))
        answer_text = fb_match.group(2).strip()
        correct_fb = fb_match.group(3).strip()
        incorrect_fb = fb_match.group(4).strip()
        no_answer_fb = fb_match.group(5).strip()
        
        # Parse answer variants
        answer_variants = answer_text.split(' / ')
        if len(answer_variants) > 1:
            answer = '|'.join(v.lower().strip() for v in answer_variants)
        else:
            answer = answer_text.lower()
        
        feedback_data[field_num] = {
            'answer': answer,
            'correct_feedback': correct_fb,
            'incorrect_feedback': incorrect_fb,
            'no_answer_feedback': no_answer_fb
        }
    
    # Extract the table structure to build the question text
    table_match = re.search(r'\|[^\n]+\|\s*\n\|[-\s|]+\|\s*\n((?:\|[^\n]+\|\s*\n)+)', content)
    if table_match:
        table_rows = table_match.group(1).strip()
        
        # Build question text from table
        question_text = "Ergonomic Setup Guidelines\n\n"
        question_text += table_rows
    else:
        question_text = "Complete the table below."
    
    # Create one summary_completion question with multiple fields
    if feedback_data:
        fields = {}
        for field_num in sorted(feedback_data.keys()):
            fb = feedback_data[field_num]
            fields[field_num] = {
                'answer': fb['answer'],
                'correct_feedback': fb['correct_feedback'],
                'incorrect_feedback': fb['incorrect_feedback'],
                'no_answer_feedback': fb['no_answer_feedback']
            }
        
        questions.append({
            'number': start_q,
            'type': 'summary_completion',
            'instructions': instructions,
            'question': question_text,
            'fields': fields,
            'correct_feedback': "Excellent! You got all the answers correct.",
            'incorrect_feedback': "Some answers are incorrect. Review the lecture about ergonomic setup guidelines.",
            'no_answer_feedback': "In the IELTS test, you should always take a guess. You don't lose points for a wrong answer."
        })
    
    return questions

def parse_summary_completion_questions(content):
    """Parse summary completion questions (similar to short answer but formatted differently)."""
    # This is similar to short_answer but may have different formatting
    return parse_short_answer_questions(content)

def serialize_php(data):
    """Convert Python data to PHP serialized format."""
    if data is None:
        return 'N;'
    elif isinstance(data, bool):
        return 'b:1;' if data else 'b:0;'
    elif isinstance(data, int):
        return f'i:{data};'
    elif isinstance(data, float):
        return f'd:{data};'
    elif isinstance(data, str):
        # Important: use byte length, not character length
        byte_length = len(data.encode('utf-8'))
        return f's:{byte_length}:"{data}";'
    elif isinstance(data, dict):
        # Serialize as associative array
        items = []
        for key, value in data.items():
            items.append(serialize_php(key) + serialize_php(value))
        items_str = ''.join(items)
        return f'a:{len(data)}:{{{items_str}}}'
    elif isinstance(data, list):
        # Serialize as indexed array
        items = []
        for i, value in enumerate(data):
            items.append(serialize_php(i) + serialize_php(value))
        items_str = ''.join(items)
        return f'a:{len(data)}:{{{items_str}}}'
    else:
        return 'N;'

def create_question_array(questions):
    """Create the PHP serialized questions array."""
    question_objects = []
    
    for q in questions:
        q_obj = {
            'type': 'summary_completion',
            'instructions': q.get('instructions', ''),
            'question': q['question'],
            'points': 1.0,
            'no_answer_feedback': q.get('no_answer_feedback', 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.'),
            'correct_feedback': q.get('correct_feedback', 'Correct!'),
            'incorrect_feedback': q.get('incorrect_feedback', 'Incorrect.'),
            'reading_text_id': None,
        }
        
        # Handle different question types
        if 'fields' in q:
            # Multi-field summary completion (like table completion)
            summary_fields = {}
            for field_num, field_data in q['fields'].items():
                summary_fields[field_num] = {
                    'answer': field_data['answer'],
                    'correct_feedback': field_data['correct_feedback'],
                    'incorrect_feedback': field_data['incorrect_feedback'],
                    'no_answer_feedback': field_data['no_answer_feedback']
                }
            q_obj['summary_fields'] = summary_fields
        else:
            # Single answer summary completion
            q_obj['summary_fields'] = {
                1: {
                    'answer': q['answer'],
                    'correct_feedback': q['correct_feedback'],
                    'incorrect_feedback': q['incorrect_feedback'],
                    'no_answer_feedback': q['no_answer_feedback']
                }
            }
        
        q_obj['options'] = ''
        q_obj['correct_answer'] = ''
        
        question_objects.append(q_obj)
    
    return serialize_php(question_objects)

def annotate_transcript(transcript, questions):
    """Add answer annotations to transcript."""
    if not transcript or not questions:
        return transcript
    
    annotated = transcript
    
    for q in questions:
        q_num = q['number']
        
        # Get all answer variants
        if 'fields' in q:
            # Multi-field question
            for field_num, field_data in q['fields'].items():
                answer_text = field_data['answer']
                variants = answer_text.split('|')
                
                for variant in variants:
                    variant = variant.strip()
                    if not variant:
                        continue
                    
                    # Check if already annotated
                    actual_q_num = q_num + field_num - 1
                    if f'[Q{actual_q_num}:' in annotated:
                        break
                    
                    # Try to find and annotate
                    pattern_text = re.escape(variant)
                    pattern_text = pattern_text.replace(r'\ ', r'\s+')
                    pattern = r'\b(' + pattern_text + r')\b'
                    
                    def replace_func(m):
                        return f'<strong style="background-color: yellow;">[Q{actual_q_num}: {escape(m.group(1))}]</strong>'
                    
                    new_annotated = re.sub(pattern, replace_func, annotated, count=1, flags=re.IGNORECASE)
                    
                    if new_annotated != annotated:
                        annotated = new_annotated
                        break
        else:
            # Single answer question
            answer_text = q['answer']
            variants = answer_text.split('|')
            
            for variant in variants:
                variant = variant.strip()
                if not variant:
                    continue
                
                if f'[Q{q_num}:' in annotated:
                    break
                
                pattern_text = re.escape(variant)
                pattern_text = pattern_text.replace(r'\ ', r'\s+')
                pattern = r'\b(' + pattern_text + r')\b'
                
                def replace_func(m):
                    return f'<strong style="background-color: yellow;">[Q{q_num}: {escape(m.group(1))}]</strong>'
                
                new_annotated = re.sub(pattern, replace_func, annotated, count=1, flags=re.IGNORECASE)
                
                if new_annotated != annotated:
                    annotated = new_annotated
                    break
    
    return annotated

def generate_xml(title, data):
    """Generate the complete XML file."""
    now = datetime.now()
    
    # Create PHP serialized questions
    questions_serialized = create_question_array(data['questions'])
    
    # Annotate transcript
    annotated_transcript = annotate_transcript(data['transcript'], data['questions'])
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 This is a WordPress eXtended RSS file for IELTS Course Manager exercise export 
-->
<!--
 Generated by generate_listening_xml.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
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
<generator>IELTS Course Manager - generate_listening_xml.py</generator>
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
<wp:meta_value><![CDATA[{data['layout_type']}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>
<wp:meta_value><![CDATA[{data['exercise_label']}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>
<wp:meta_value><![CDATA[]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>
<wp:meta_value><![CDATA[{data['scoring_type']}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>
<wp:meta_value><![CDATA[]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>
<wp:meta_value><![CDATA[{data['starting_question_number']}]]></wp:meta_value>
</wp:postmeta>
<wp:postmeta>
<wp:meta_key><![CDATA[_ielts_cm_audio_url]]></wp:meta_key>
<wp:meta_value><![CDATA[{data['audio_url']}]]></wp:meta_value>
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

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 generate_listening_xml.py <input.txt>")
        print("Example: python3 generate_listening_xml.py 'Listening Test 3 Section 4.txt'")
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    if not os.path.exists(input_file):
        print(f"Error: File '{input_file}' not found")
        sys.exit(1)
    
    # Read input file
    with open(input_file, 'r', encoding='utf-8') as f:
        txt_content = f.read()
    
    # Extract title from filename
    base_name = os.path.splitext(os.path.basename(input_file))[0]
    title = base_name
    
    # Parse the TXT file
    data = parse_txt_file(txt_content)
    print(f"Parsed {len(data['questions'])} questions")
    
    # Generate XML
    xml_content = generate_xml(title, data)
    
    # Save XML file
    xml_output = base_name + ".xml"
    with open(xml_output, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    print(f"✓ Generated {xml_output}")
    
    # Save annotated transcript
    annotated_transcript = annotate_transcript(data['transcript'], data['questions'])
    transcript_output = base_name + "-transcript.txt"
    with open(transcript_output, 'w', encoding='utf-8') as f:
        f.write(annotated_transcript)
    print(f"✓ Generated {transcript_output}")
    
    print("\nDone!")

if __name__ == "__main__":
    main()
