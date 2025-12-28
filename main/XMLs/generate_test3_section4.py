#!/usr/bin/env python3
"""
Generate Listening Test 3 Section 4 XML with proper PHP serialized questions.
This script specifically handles the Section 4 format with SHORT ANSWER and TABLE COMPLETION.
"""

import re
import sys
import os
from datetime import datetime
from html import escape

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

def parse_section_4():
    """Parse Listening Test 3 Section 4 from the txt file."""
    
    with open("Listening Test 3 Section 4.txt", "r") as f:
        content = f.read()
    
    # Extract transcript
    transcript_match = re.search(r'(<table[^>]*>.*?</table>)', content, re.DOTALL | re.IGNORECASE)
    if not transcript_match:
        transcript_match = re.search(r'<div[^>]*overflow:\s*scroll[^>]*>(.*?)</div>', content, re.DOTALL | re.IGNORECASE)
    
    transcript = transcript_match.group(1) if transcript_match else ""
    
    questions = []
    
    # Parse Questions 31-35 (SHORT ANSWER - individual questions)
    questions_31_35_instructions = "Questions 31-35\n\nAnswer the following questions using NO MORE THAN THREE WORDS."
    
    # Q31
    questions.append({
        'type': 'summary_completion',
        'instructions': questions_31_35_instructions,
        'question': 'What causes RSI?',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'repeated physical movement|repeated physical movements',
                'correct_feedback': 'Excellent! The speaker states that RSI results from repeated physical movements which damage tendons, nerves, and muscles.',
                'incorrect_feedback': 'Incorrect. Listen to the definition of RSI at the beginning of the lecture - the cause is clearly stated.',
                'no_answer_feedback': 'The correct answer is: repeated physical movement. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Q32
    questions.append({
        'type': 'summary_completion',
        'instructions': '',
        'question': 'The use of what has caused an increase in RSI in the workplace?',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'computers',
                'correct_feedback': 'Correct! The speaker mentions that RSI has become particularly noticeable due to the increasing importance of computers in the workplace.',
                'incorrect_feedback': 'Incorrect. Listen to why RSI has increased in the last twenty years - a specific technology is mentioned.',
                'no_answer_feedback': 'The correct answer is: computers. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Q33
    questions.append({
        'type': 'summary_completion',
        'instructions': '',
        'question': 'RSI is made worse without break periods or if there is no thought given to what?',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'body position',
                'correct_feedback': 'Well done! The speaker says the situation is intensified if people don\'t take regular breaks or spend time considering their body position.',
                'incorrect_feedback': 'Incorrect. Listen to what makes RSI worse besides not taking breaks - the speaker mentions something people should consider.',
                'no_answer_feedback': 'The correct answer is: body position. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Q34
    questions.append({
        'type': 'summary_completion',
        'instructions': '',
        'question': 'What should people who use a computer regularly be able to identify to avoid serious cases?',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'warning signs',
                'correct_feedback': 'Perfect! The speaker states that one way to prevent serious RSI is to recognise the warning signs.',
                'incorrect_feedback': 'Incorrect. Listen to how people can prevent serious RSI - they need to identify something early.',
                'no_answer_feedback': 'The correct answer is: warning signs. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Q35
    questions.append({
        'type': 'summary_completion',
        'instructions': '',
        'question': 'Problems can also occur if the computer monitor is what?',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'too close',
                'correct_feedback': 'Excellent! The speaker mentions that many people sit too close to their monitor, leading to symptoms like headaches and blurred vision.',
                'incorrect_feedback': 'Incorrect. Listen to the problem with monitor positioning - the speaker describes how people sit in relation to the monitor.',
                'no_answer_feedback': 'The correct answer is: too close. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Questions 36-40 (TABLE COMPLETION - one multi-field question)
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 36-40\n\nComplete the notes below using NO MORE THAN THREE WORDS.\n\nErgonomic Setup Guidelines',
        'question': '''Chair: Height should allow arms to be at 90 degrees to the keyboard; Feet should be on the floor or a [field 1]; Space should be allowed between the chair and back of the knees to maintain [field 2]

Monitor: Screen should be just below eye level; Avoid light reflections by positioning at a [field 3]; [field 4] backgrounds are more comfortable to look at

Keyboard: Wrists should remain straight; Elbows close to the body

Posture: Sit up straight or [field 5] a little''',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'foot stool|stool',
                'correct_feedback': 'Correct! The speaker says feet should be flat on the ground, or if not practical, on a foot stool.',
                'incorrect_feedback': 'Incorrect. Listen to the alternative if feet can\'t be flat on the ground - the speaker mentions a specific item.',
                'no_answer_feedback': 'The correct answer is: foot stool. Make sure to listen carefully for key information and take notes while listening.'
            },
            2: {
                'answer': 'proper circulation|circulation|proper blood circulation',
                'correct_feedback': 'Well done! The speaker mentions maintaining proper circulation in the legs by having space between the knees and chair.',
                'incorrect_feedback': 'Incorrect. Listen to why space should be left between the knees and chair seat - the speaker mentions what needs to be maintained.',
                'no_answer_feedback': 'The correct answer is: proper circulation. Make sure to listen carefully for key information and take notes while listening.'
            },
            3: {
                'answer': 'right angle|right-angle',
                'correct_feedback': 'Excellent! The speaker states the monitor should be positioned at a right angle to light sources or windows to avoid glare.',
                'incorrect_feedback': 'Incorrect. Listen to how to position the monitor to avoid glare - a specific angle is mentioned.',
                'no_answer_feedback': 'The correct answer is: right angle. Make sure to listen carefully for key information and take notes while listening.'
            },
            4: {
                'answer': 'light grey|light gray',
                'correct_feedback': 'Perfect! The speaker explains that a light grey background has been proven to be easier on the eyes than bright white.',
                'incorrect_feedback': 'Incorrect. Listen to the recommended background colour alternative to bright white - the speaker gives a specific colour.',
                'no_answer_feedback': 'The correct answer is: light grey. Make sure to listen carefully for key information and take notes while listening.'
            },
            5: {
                'answer': 'lean back',
                'correct_feedback': 'Excellent! The speaker mentions that some people find sitting at 90 degrees too rigid, so they can lean slightly back.',
                'incorrect_feedback': 'Incorrect. Listen to the alternative to sitting rigidly at 90 degrees - the speaker describes what users can do instead.',
                'no_answer_feedback': 'The correct answer is: lean back. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You got all the answers correct.',
        'incorrect_feedback': 'Some answers are incorrect. Review the lecture about ergonomic setup guidelines.',
        'reading_text_id': None
    })
    
    return questions, transcript

def annotate_transcript(transcript, questions):
    """Add answer annotations to transcript with yellow highlighting."""
    if not transcript:
        return transcript
    
    annotated = transcript
    q_number = 31  # Starting question number for Section 4
    
    for q in questions:
        if 'summary_fields' in q:
            for field_num, field_data in q['summary_fields'].items():
                answer_text = field_data['answer']
                variants = answer_text.split('|')
                
                for variant in variants:
                    variant = variant.strip()
                    if not variant:
                        continue
                    
                    # Check if already annotated
                    if f'[Q{q_number}:' in annotated:
                        break
                    
                    # Try to find and annotate (case-insensitive)
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
    
    return annotated

def generate_xml(questions, transcript):
    """Generate the complete XML file."""
    now = datetime.now()
    
    # Create PHP serialized questions
    questions_serialized = serialize_php(questions)
    
    # Annotate transcript
    annotated_transcript = annotate_transcript(transcript, questions)
    
    # Audio URL follows the pattern
    audio_url = "https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0049-4.mp3"
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 This is a WordPress eXtended RSS file for IELTS Course Manager exercise export 
-->
<!--
 Generated by generate_test3_section4.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
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
<generator>IELTS Course Manager - generate_test3_section4.py</generator>
<item>
<title><![CDATA[Listening Test 3 Section 4]]></title>
<link>https://www.ieltstestonline.com/2026/ielts-quiz/listening-test-3-section-4/</link>
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
<wp:post_name><![CDATA[listening-test-3-section-4]]></wp:post_name>
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
<wp:meta_value><![CDATA[31]]></wp:meta_value>
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

def main():
    print("Generating Listening Test 3 Section 4 XML...")
    
    # Parse questions from txt file
    questions, transcript = parse_section_4()
    print(f"Parsed {len(questions)} question groups containing 10 individual questions")
    
    # Generate XML
    xml_content = generate_xml(questions, transcript)
    
    # Save XML file
    output_file = "Listening Test 3 Section 4.xml"
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    print(f"✓ Generated {output_file}")
    
    # Save annotated transcript
    annotated_transcript = annotate_transcript(transcript, questions)
    transcript_file = "Listening Test 3 Section 4-transcript.txt"
    with open(transcript_file, 'w', encoding='utf-8') as f:
        f.write(annotated_transcript)
    print(f"✓ Generated {transcript_file}")
    
    print("\nDone! Section 4 now has 10 questions (31-40).")

if __name__ == "__main__":
    os.chdir("/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/XMLs")
    main()
