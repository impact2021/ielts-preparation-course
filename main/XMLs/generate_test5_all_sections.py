#!/usr/bin/env python3
"""
Generate Listening Test 5 All Sections with proper question parsing.
Based on test4 generator but customized for Test 5 specific formats.
"""

import re
import sys
import os
from datetime import datetime
from html import escape, unescape

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

def extract_audio_url(content):
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

def parse_answer_variants(answer_text):
    answer_text = answer_text.strip('{}')
    variants = re.findall(r'\[([^\]]+)\]', answer_text)
    if variants:
        return '|'.join(v.lower() for v in variants)
    else:
        return answer_text.lower()

# Section-specific generators...
def generate_section_1():
    """Test 5 Section 1: Form completion + Diagram matching."""
    with open("Listening Test 5 Section 1.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    questions = []
    
    # Q1-5: Form/Table completion - ONE multi-field question with 5 fields
    summary_fields = {
        1: {
            'answer': 'haltwell',
            'correct_feedback': 'Correct! The answer is HALTWELL.',
            'incorrect_feedback': 'Incorrect. Listen to the surname.',
            'no_answer_feedback': 'The correct answer is: HALTWELL. Make sure to listen carefully for key information and take notes while listening.'
        },
        2: {
            'answer': '12py',
            'correct_feedback': 'Correct! The answer is 12PY.',
            'incorrect_feedback': 'Incorrect. Listen to the postcode.',
            'no_answer_feedback': 'The correct answer is: 12PY. Make sure to listen carefully for key information and take notes while listening.'
        },
        3: {
            'answer': 'november',
            'correct_feedback': 'Correct! The answer is NOVEMBER.',
            'incorrect_feedback': 'Incorrect. Listen to the availability date.',
            'no_answer_feedback': 'The correct answer is: NOVEMBER. Make sure to listen carefully for key information and take notes while listening.'
        },
        4: {
            'answer': '4 years|four years|4 years\'|four years\'|4years|4years\'',
            'correct_feedback': 'Correct! The answer is 4 YEARS.',
            'incorrect_feedback': 'Incorrect. Listen to the length of experience.',
            'no_answer_feedback': 'The correct answer is: 4 YEARS. Make sure to listen carefully for key information and take notes while listening.'
        },
        5: {
            'answer': 'weekends|weekend|on weekends|at weekends|at the weekend|on the weekends',
            'correct_feedback': 'Correct! The answer is WEEKENDS.',
            'incorrect_feedback': 'Incorrect. Listen to the availability restrictions.',
            'no_answer_feedback': 'The correct answer is: WEEKENDS. Make sure to listen carefully for key information and take notes while listening.'
        }
    }
    
    # Create ONE table completion question with 5 fields
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 1-5\n\nComplete the form below.\n\nWrite NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.',
        'question': 'Applicant Details Form',
        'points': 1.0,
        'summary_fields': summary_fields,
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You completed the form correctly.',
        'incorrect_feedback': 'Some answers are incorrect. Review the applicant details.',
        'reading_text_id': None
    })
    
    # Q6-10: Diagram/Flowchart matching - ONE multi-field question with 5 fields
    diagram_fields = {
        1: {
            'answer': 'j',
            'correct_feedback': 'Correct! The answer is J (Computing skills).',
            'incorrect_feedback': 'Incorrect. Review the registration sequence.',
            'no_answer_feedback': 'The correct answer is: J. Make sure to listen carefully for key information and take notes while listening.'
        },
        2: {
            'answer': 'h',
            'correct_feedback': 'Correct! The answer is H (Over the phone).',
            'incorrect_feedback': 'Incorrect. Review the registration sequence.',
            'no_answer_feedback': 'The correct answer is: H. Make sure to listen carefully for key information and take notes while listening.'
        },
        3: {
            'answer': 'i',
            'correct_feedback': 'Correct! The answer is I (Representatives).',
            'incorrect_feedback': 'Incorrect. Review the registration sequence.',
            'no_answer_feedback': 'The correct answer is: I. Make sure to listen carefully for key information and take notes while listening.'
        },
        4: {
            'answer': 'a',
            'correct_feedback': 'Correct! The answer is A (Within 24 hours).',
            'incorrect_feedback': 'Incorrect. Review the registration sequence.',
            'no_answer_feedback': 'The correct answer is: A. Make sure to listen carefully for key information and take notes while listening.'
        },
        5: {
            'answer': 'c',
            'correct_feedback': 'Correct! The answer is C (Employment contract).',
            'incorrect_feedback': 'Incorrect. Review the registration sequence.',
            'no_answer_feedback': 'The correct answer is: C. Make sure to listen carefully for key information and take notes while listening.'
        }
    }
    
    # Create ONE flowchart completion question with 5 fields
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 6-10\n\nComplete the flowchart below with words from the box. Write the corresponding letter A-J in the spaces provided.',
        'question': 'Sequence for registration flowchart',
        'points': 1.0,
        'summary_fields': diagram_fields,
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You completed the flowchart correctly.',
        'incorrect_feedback': 'Some answers are incorrect. Review the registration sequence.',
        'reading_text_id': None
    })
    
    return questions, audio_url, transcript

def generate_section_2():
    """Test 5 Section 2: Multiple choice + Diagram labeling."""
    with open("Listening Test 5 Section 2.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    questions = []
    
    # Q11-15: Multiple choice
    mc_data = [
        (11, 'Records…', ['make more money than CDs.', 'sell more units than CDs.', 'are catching up with CD sales.'], 'C'),
        (12, 'The first records…', ['were sold by Thomas Edison.', 'were only made of firm rubber.', 'were not long lasting.'], 'C'),
        (13, 'Shellac records…', ['could not be played many times.', 'were not popular with the public.', 'were produced for over half a century.'], 'C'),
        (14, '\'Unbreakable\' records…', ['were made from shellac.', 'were made of plastic.', 'had a high quality sound.'], 'B'),
        (15, 'Vinyl records…', ['offered a wider choice in appearance.', 'could not be damaged.', 'dropped in popularity in the 1950s.'], 'A')
    ]
    
    for q_num, question_text, options_list, correct_answer in mc_data:
        options_text = '\n'.join(f"{chr(65+i)}. {opt}" for i, opt in enumerate(options_list))
        correct_idx = ord(correct_answer) - ord('A')
        
        mc_options = []
        for i, opt in enumerate(options_list):
            mc_options.append({
                'text': f"{chr(65+i)}. {opt}",
                'is_correct': i == correct_idx,
                'feedback': f"Correct! The answer is {correct_answer}." if i == correct_idx else f"Incorrect. The correct answer is {correct_answer}."
            })
        
        questions.append({
            'type': 'multiple_choice',
            'instructions': 'Questions 11-15\n\nChoose the correct answer A-C.' if q_num == 11 else '',
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
    
    # Q16-20: Diagram labeling
    diagram_answers = {
        16: ('the record plate|the plate|record plate|plate', 'THE RECORD PLATE'),
        17: ('the power light|power light|power|the power', 'THE POWER LIGHT'),
        18: ('thirty three|33|thirty-three', '33'),
        19: ('forty five|45|forty-five', '45'),
        20: ('tone arm', 'TONE ARM')
    }
    
    for q_num, (answer, display) in diagram_answers.items():
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 16-20\n\nLabel the diagram below. Use NO MORE THAN TWO WORDS OR A NUMBER for each answer.' if q_num == 16 else '',
            'question': f'Label {q_num - 15}',
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Listen to the description of the record player parts.',
                    'no_answer_feedback': f'The correct answer is: {display}. Make sure to listen carefully for key information and take notes while listening.'
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct!',
            'incorrect_feedback': 'Incorrect.',
            'reading_text_id': None
        })
    
    return questions, audio_url, transcript

def generate_section_3():
    """Test 5 Section 3: Table completion with 10 answers."""
    with open("Listening Test 5 Section 3.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    questions = []
    
    # Q21-30: Table completion (all 10 in one multi-field question)
    table_answers = {
        21: ('quite nervous|nervous', 'QUITE NERVOUS'),
        22: ('rude', 'RUDE'),
        23: ('recorded', 'RECORDED'),
        24: ('culture|cultures', 'CULTURE'),
        25: ('equal number', 'EQUAL NUMBER'),
        26: ('alcohol', 'ALCOHOL'),
        27: ('individual exercise', 'INDIVIDUAL EXERCISE'),
        28: ('reading', 'READING'),
        29: ('junk food', 'JUNK FOOD'),
        30: ('real', 'REAL')
    }
    
    summary_fields = {}
    for q_num, (answer, display) in table_answers.items():
        field_num = q_num - 20
        summary_fields[field_num] = {
            'answer': answer,
            'correct_feedback': f'Correct! The answer is {display}.',
            'incorrect_feedback': 'Incorrect. Review the life and leisure survey discussion.',
            'no_answer_feedback': f'The correct answer is: {display}. Make sure to listen carefully for key information and take notes while listening.'
        }
    
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 21-30\n\nComplete the table below\n\nWrite NO MORE THAN THREE WORDS for each answer',
        'question': 'Life and Leisure Survey table with 10 fields',
        'points': 1.0,
        'summary_fields': summary_fields,
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You got all the answers correct.',
        'incorrect_feedback': 'Some answers are incorrect. Review the survey discussion.',
        'reading_text_id': None
    })
    
    return questions, audio_url, transcript

def generate_section_4():
    """Test 5 Section 4: Sentence + Summary + Matching."""
    with open("Listening Test 5 Section 4.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    questions = []
    
    # Q31: Single sentence completion
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Question 31\n\nComplete the sentence using NO MORE THAN TWO WORDS',
        'question': 'The term \'the Goldilocks Effect\' is being used to describe a situation where everything is __________.',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'just right',
                'correct_feedback': 'Correct! The answer is JUST RIGHT.',
                'incorrect_feedback': 'Incorrect. Listen for the definition of the Goldilocks Effect.',
                'no_answer_feedback': 'The correct answer is: JUST RIGHT. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct!',
        'incorrect_feedback': 'Incorrect.',
        'reading_text_id': None
    })
    
    # Q32-36: Summary with letter matching (5 answers)
    summary_fields = {
        1: ('c', 'C - predators'),
        2: ('h', 'H - streams'),
        3: ('b', 'B - earth scientists'),
        4: ('f', 'F - carbon dioxide'),
        5: ('d', 'D - greenhouse gases')
    }
    
    for field_num, (answer, display) in summary_fields.items():
        q_num = 31 + field_num
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 32-36\n\nComplete the summary below using words in the box below. Write the correct letter A-J in the answer sheet.' if q_num == 32 else '',
            'question': f'Gap {q_num}',
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Review the Goldilocks Effect examples.',
                    'no_answer_feedback': f'The correct answer is: {display.split("-")[0].strip()}. Make sure to listen carefully for key information and take notes while listening.'
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct!',
            'incorrect_feedback': 'Incorrect.',
            'reading_text_id': None
        })
    
    # Q37-40: Sentence matching (4 answers)
    matching_answers = {
        37: ('f', 'F'),
        38: ('e', 'E'),
        39: ('a', 'A'),
        40: ('b', 'B')
    }
    
    for q_num, (answer, display) in matching_answers.items():
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 37-40\n\nComplete each sentence with the correct ending A-G below' if q_num == 37 else '',
            'question': f'Statement {q_num}',
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Review the Goldilocks applications.',
                    'no_answer_feedback': f'The correct answer is: {display}. Make sure to listen carefully for key information and take notes while listening.'
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct!',
            'incorrect_feedback': 'Incorrect.',
            'reading_text_id': None
        })
    
    return questions, audio_url, transcript

def annotate_transcript(transcript, questions, starting_q_num):
    """Add answer annotations to transcript."""
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

def generate_xml(section_num, questions, audio_url, transcript):
    """Generate complete XML."""
    now = datetime.now()
    
    questions_serialized = serialize_php(questions)
    starting_q_num = (section_num - 1) * 10 + 1
    annotated_transcript = annotate_transcript(transcript, questions, starting_q_num)
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 This is a WordPress eXtended RSS file for IELTS Course Manager exercise export 
-->
<!--
 Generated by generate_test5_all_sections.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
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
<generator>IELTS Course Manager - generate_test5_all_sections.py</generator>
<item>
<title><![CDATA[Listening Test 5 Section {section_num}]]></title>
<link>https://www.ieltstestonline.com/2026/ielts-quiz/listening-test-5-section-{section_num}/</link>
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
<wp:post_name><![CDATA[listening-test-5-section-{section_num}]]></wp:post_name>
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

def main():
    os.chdir("/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/XMLs")
    
    generators = [
        (1, generate_section_1),
        (2, generate_section_2),
        (3, generate_section_3),
        (4, generate_section_4)
    ]
    
    for section_num, generator_func in generators:
        print(f"Generating Section {section_num}...")
        questions, audio_url, transcript = generator_func()
        
        # Count actual questions
        q_count = sum(len(q.get('summary_fields', {1: None})) if 'summary_fields' in q else 1 for q in questions)
        print(f"  Parsed {len(questions)} question groups = {q_count} actual questions")
        
        xml_content = generate_xml(section_num, questions, audio_url, transcript)
        
        output_file = f"Listening Test 5 Section {section_num}.xml"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(xml_content)
        print(f"  ✓ Generated {output_file}")
    
    print("\n✅ All 4 sections of Listening Test 5 generated successfully!")
    print("Each section has 10 questions (Q1-10, Q11-20, Q21-30, Q31-40)")

if __name__ == "__main__":
    main()
