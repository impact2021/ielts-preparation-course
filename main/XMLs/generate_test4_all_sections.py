#!/usr/bin/env python3
"""
Generate all 4 sections of Listening Test 4 with proper PHP serialized questions.
Parses HTML-formatted .txt files with embedded answers.
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
    """Extract audio URL from content."""
    match = re.search(r'\[audio mp3="([^"]+)"\]', content)
    return match.group(1) if match else ""

def extract_transcript(content):
    """Extract transcript from HTML table."""
    # Find transcript in table structure
    match = re.search(r'<div[^>]*style="[^"]*overflow:\s*scroll[^"]*"[^>]*>(.*?)</div>', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1).strip()
    
    # Alternative: find table
    match = re.search(r'(<table[^>]*>.*?</table>)', content, re.DOTALL | re.IGNORECASE)
    if match:
        return match.group(1)
    
    return ""

def parse_answer_variants(answer_text):
    """Parse answer variants from {[answer1][answer2]} format."""
    # Remove outer braces
    answer_text = answer_text.strip('{}')
    
    # Extract all variants between brackets
    variants = re.findall(r'\[([^\]]+)\]', answer_text)
    if variants:
        return '|'.join(v.lower() for v in variants)
    else:
        # Simple answer without brackets
        return answer_text.lower()

def generate_section_1():
    """Generate Section 1: Multiple choice and multi-select questions."""
    with open("Listening Test 4 Section 1.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    
    questions = []
    
    # Q1-3: Multiple choice
    mc_pattern = r'(\d+)\.\s+(.+?)<ol[^>]*>(.+?)</ol>\s*\d+\.\s*\{([A-E])\}'
    mc_matches = re.finditer(mc_pattern, content, re.DOTALL)
    
    for i, match in enumerate(mc_matches):
        q_num = int(match.group(1))
        if q_num > 3:
            continue
            
        question_text = unescape(re.sub(r'<[^>]+>', '', match.group(2))).strip()
        options_html = match.group(3)
        correct_answer = match.group(4)
        
        # Parse options
        options_list = re.findall(r'<li>([^<]+)</li>', options_html, re.DOTALL)
        options_text = '\n'.join(f"{chr(65+i)}. {opt.strip()}" for i, opt in enumerate(options_list))
        
        # Find correct answer index
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
            'instructions': 'Questions 1-3\n\nChoose the correct letter A-C' if q_num == 1 else '',
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
    
    # Q4-5: Multi-answer (ANY OF)
    # These are tricky - they accept multiple answers
    for q_num in [4, 5]:
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 4-5\n\nName TWO items the man is interested in looking at buying from the museum shop' if q_num == 4 else '',
            'question': f'Item {q_num - 3}',
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': 'new books|books|calendars|calendar|a calendar|souvenir|souvenirs',
                    'correct_feedback': 'Correct! The man mentions he looks at new books, buys calendars, and checks out souvenirs.',
                    'incorrect_feedback': 'Incorrect. Listen to what the man says he looks at or buys in the museum shop.',
                    'no_answer_feedback': 'The correct answers include: new books, calendars, or souvenirs. Make sure to listen carefully for key information and take notes while listening.'
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct!',
            'incorrect_feedback': 'Incorrect.',
            'reading_text_id': None
        })
    
    # Q6-8: Short answer/form filling
    form_answers = {
        6: ('36|thirty six|thirty-six', 'age group', '36'),
        7: ('economics', 'subject taught', 'Economics'),
        8: ('city centre|city center|inner city|the city centre|the city center|the inner city', 'part of city', 'City centre')
    }
    
    for q_num, (answer, field_name, display_answer) in form_answers.items():
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 6-8\n\nWrite NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.' if q_num == 6 else '',
            'question': f'{field_name.capitalize()}: __________',
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display_answer}.',
                    'incorrect_feedback': f'Incorrect. Listen for the man\'s {field_name}.',
                    'no_answer_feedback': f'The correct answer is: {display_answer}. Make sure to listen carefully for key information and take notes while listening.'
                }
            },
            'options': '',
            'correct_answer': '',
            'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
            'correct_feedback': 'Correct!',
            'incorrect_feedback': 'Incorrect.',
            'reading_text_id': None
        })
    
    # Q9-10: Multi-select (choose TWO from A-E)
    questions.append({
        'type': 'multi_select',
        'instructions': 'Questions 9-10\n\nChoose TWO LETTERS from the following list.\n\nWhat areas does the man think need improving at the Art Gallery?',
        'question': 'A. The range of exhibits\nB. The frequency of new exhibits\nC. Site of the Museum\nD. Parking\nE. Add a coffee shop',
        'points': 1.0,
        'mc_options': [
            {'text': 'A. The range of exhibits', 'is_correct': False, 'feedback': 'Incorrect. The man is happy with the range.'},
            {'text': 'B. The frequency of new exhibits', 'is_correct': True, 'feedback': 'Correct! The man mentions new exhibits more often.'},
            {'text': 'C. Site of the Museum', 'is_correct': False, 'feedback': 'Incorrect. The man says the location is good.'},
            {'text': 'D. Parking', 'is_correct': False, 'feedback': 'Incorrect. The man says there is good parking.'},
            {'text': 'E. Add a coffee shop', 'is_correct': True, 'feedback': 'Correct! The man suggests adding a café or coffee shop.'}
        ],
        'options': 'A. The range of exhibits\nB. The frequency of new exhibits\nC. Site of the Museum\nD. Parking\nE. Add a coffee shop',
        'correct_answer': '[1,4]',
        'option_feedback': [opt['feedback'] for opt in [
            {'feedback': 'Incorrect. The man is happy with the range.'},
            {'feedback': 'Correct! The man mentions new exhibits more often.'},
            {'feedback': 'Incorrect. The man says the location is good.'},
            {'feedback': 'Incorrect. The man says there is good parking.'},
            {'feedback': 'Correct! The man suggests adding a café or coffee shop.'}
        ]],
        'min_selections': 2,
        'max_selections': 2,
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Correct! Both B and E are correct.',
        'incorrect_feedback': 'Review the conversation about improvements. The man mentions new exhibits more often and adding a coffee shop.',
        'reading_text_id': None
    })
    
    return questions, audio_url, transcript

def generate_section_2():
    """Generate Section 2: Form filling and table completion."""
    with open("Listening Test 4 Section 2.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    
    questions = []
    
    # Q11-15: Sentence completion
    sentence_answers = [
        (11, 'travel pack|travel packs', 'TRAVEL PACK', 'Brochures and leaflets from the travel companies are in the __________'),
        (12, 'south pacific', 'SOUTH PACIFIC', 'The winner of the travel raffle will have a holiday in the __________'),
        (13, 'right hand|right-hand|right', 'RIGHT HAND', 'The airline exhibitors are along the __________ side of the hall.'),
        (14, 'glass roofs|glass roof', 'GLASS ROOFS', 'The train through the Central Mountains has rail cars with __________'),
        (15, 'summer months|summer', 'SUMMER', 'Currently, cruise ships operate out of Victoria Bay during the __________')
    ]
    
    for q_num, answer, display, question_text in sentence_answers:
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 11-15\n\nComplete the sentences below.\n\nWrite NO MORE THAN TWO WORDS for each answer.' if q_num == 11 else '',
            'question': question_text,
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Listen to the introduction about the Travel Expo.',
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
    
    # Q16-20: Table completion (one multi-field question)
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 16-20\n\nComplete the table below.\n\nWrite NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.\n\nVIEWING ROOM SCHEDULE',
        'question': '''Viewing Room A - 11 a.m.: [field 1]
11:45 a.m. - Time: [field 2]
Viewing Room A - 2:30 p.m.: Includes [field 3] & Argentina
Viewing Room B - Time: [field 4]
Viewing Room B - 3 p.m.: Overview of [field 5]''',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': 'south-east asia|south east asia|southeast asia',
                'correct_feedback': 'Correct! Viewing Room A opens at 11 a.m. with South-East Asia.',
                'incorrect_feedback': 'Incorrect. Listen for what is showing at 11 a.m. in Viewing Room A.',
                'no_answer_feedback': 'The correct answer is: SOUTH-EAST ASIA. Make sure to listen carefully for key information and take notes while listening.'
            },
            2: {
                'answer': '11.45|11:45|eleven forty five|eleven forty-five',
                'correct_feedback': 'Correct! The Canada & USA presentation is at 11:45 a.m.',
                'incorrect_feedback': 'Incorrect. Listen for when the Canada and USA viewing starts.',
                'no_answer_feedback': 'The correct answer is: 11:45. Make sure to listen carefully for key information and take notes while listening.'
            },
            3: {
                'answer': 'brazil',
                'correct_feedback': 'Correct! The South America section includes Brazil and Argentina.',
                'incorrect_feedback': 'Incorrect. Listen to which countries are mentioned for South America.',
                'no_answer_feedback': 'The correct answer is: BRAZIL. Make sure to listen carefully for key information and take notes while listening.'
            },
            4: {
                'answer': '12.30|12:30|twelve thirty',
                'correct_feedback': 'Correct! Viewing Room B shows Europe travel at 12:30 p.m.',
                'incorrect_feedback': 'Incorrect. Listen for when Viewing Room B starts its presentations.',
                'no_answer_feedback': 'The correct answer is: 12:30. Make sure to listen carefully for key information and take notes while listening.'
            },
            5: {
                'answer': 'air travel',
                'correct_feedback': 'Correct! The 3 p.m. presentation is an overview of air travel.',
                'incorrect_feedback': 'Incorrect. Listen for what the 3 o\'clock presentation is about.',
                'no_answer_feedback': 'The correct answer is: AIR TRAVEL. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You got all the answers correct.',
        'incorrect_feedback': 'Some answers are incorrect. Review the schedule information.',
        'reading_text_id': None
    })
    
    return questions, audio_url, transcript

def generate_section_3():
    """Generate Section 3: Sentence completion and multiple choice."""
    with open("Listening Test 4 Section 3.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    
    questions = []
    
    # Q21-25: Sentence completion
    sentence_answers = [
        (21, 'endicott', 'ENDICOTT', 'The new employee\'s name is Jason __________'),
        (22, 'junior', 'JUNIOR', 'Jason will begin working as a __________ in the accounts department.'),
        (23, 'export', 'EXPORT', 'There are new __________ markets opening.'),
        (24, 'monitoring', 'MONITORING', 'Jason\'s main role will be __________ new markets and making presentations.'),
        (25, 'investigating', 'INVESTIGATING', 'Jason\'s previous work experience involved __________ potential new developments.')
    ]
    
    for q_num, answer, display, question_text in sentence_answers:
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 21-25\n\nComplete the sentences below.\n\nWrite NO MORE THAN ONE WORD for each answer.' if q_num == 21 else '',
            'question': question_text,
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Listen to the discussion about Jason\'s new role.',
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
    
    # Q26-30: Multiple choice
    mc_questions = [
        (26, 'Jason will report to', ['no one.', 'the accountant.', 'two managers.'], 'C'),
        (27, 'Jason will mostly be trained by', ['Darren Smith.', 'John Owens.', 'Sharon Greenslade.'], 'C'),
        (28, 'Jason may have to work overtime', ['as part of his salary.', 'if reports are needed.', 'during weekends in extreme cases.'], 'B'),
        (29, 'The option to work from home', ['is soon going to be withdrawn.', 'will depend on technical concerns.', 'will affect employee wages.'], 'B'),
        (30, 'After three months, there is', ['a requirement to join the Human Resources department.', 'a review.', 'a move.'], 'B')
    ]
    
    for q_num, question_text, options_list, correct_answer in mc_questions:
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
            'instructions': 'Questions 26-30\n\nChoose the correct letters A-C.' if q_num == 26 else '',
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
    
    return questions, audio_url, transcript

def generate_section_4():
    """Generate Section 4: Sentence and summary completion."""
    with open("Listening Test 4 Section 4.txt", "r") as f:
        content = f.read()
    
    audio_url = extract_audio_url(content)
    transcript = extract_transcript(content)
    
    questions = []
    
    # Q31-35: Sentence completion
    sentence_answers = [
        (31, 'saving|saving money', 'SAVING', 'Simon Felder believes that younger people are less concerned about buying property and __________ than previous generations.'),
        (32, 'increasing house prices|house prices|increasing prices|increase in price|increase in prices', 'INCREASING HOUSE PRICES', 'Many under-30s may wish to buy their own home but cannot due to __________.'),
        (33, 'nurses', 'NURSES', 'In particular, people working as teachers or __________ may find it very difficult to afford their own home.'),
        (34, 'first time buyers|1st time buyers|first-time buyers', 'FIRST TIME BUYERS', 'The trend towards buying rental properties has created more difficulties for __________.'),
        (35, 'married', 'MARRIED', 'Many people are now choosing to wait until their mid-thirties before getting __________.')
    ]
    
    for q_num, answer, display, question_text in sentence_answers:
        questions.append({
            'type': 'summary_completion',
            'instructions': 'Questions 31-35\n\nComplete the sentences below\n\nWrite NO MORE THAN THREE WORDS for each answer.' if q_num == 31 else '',
            'question': question_text,
            'points': 1.0,
            'summary_fields': {
                1: {
                    'answer': answer,
                    'correct_feedback': f'Correct! The answer is {display}.',
                    'incorrect_feedback': 'Incorrect. Listen to the lecture about generational attitudes to money.',
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
    
    # Q36-40: Summary completion (one multi-field question)
    questions.append({
        'type': 'summary_completion',
        'instructions': 'Questions 36-40\n\nComplete the summary below\n\nWrite NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.\n\nDIFFERENT OPINIONS TO SAVING',
        'question': '''People born between the years of 1945 and [field 1] have been observed to have more interest in saving than younger generations, younger people being more focused on their [field 2] situation than the long term.

Younger people would benefit from being given more information on [field 3] and long term planning so they can develop the ability to manage their own [field 4]. Many young people now find it impossible to save because of the increasing [field 5]. Advice from banks and other financial institutions could benefit customers.''',
        'points': 1.0,
        'summary_fields': {
            1: {
                'answer': '1964',
                'correct_feedback': 'Correct! The Baby Boomer generation was born between 1945 and 1964.',
                'incorrect_feedback': 'Incorrect. Listen for when the Baby Boomer generation ended.',
                'no_answer_feedback': 'The correct answer is: 1964. Make sure to listen carefully for key information and take notes while listening.'
            },
            2: {
                'answer': 'present',
                'correct_feedback': 'Correct! Younger generations focus on the present rather than the future.',
                'incorrect_feedback': 'Incorrect. Listen to what younger people focus on versus long-term planning.',
                'no_answer_feedback': 'The correct answer is: PRESENT. Make sure to listen carefully for key information and take notes while listening.'
            },
            3: {
                'answer': 'money management',
                'correct_feedback': 'Correct! Education about money management is vital for young people.',
                'incorrect_feedback': 'Incorrect. Listen for what type of education young people need.',
                'no_answer_feedback': 'The correct answer is: MONEY MANAGEMENT. Make sure to listen carefully for key information and take notes while listening.'
            },
            4: {
                'answer': 'budget',
                'correct_feedback': 'Correct! Young people need skills to manage their own budget.',
                'incorrect_feedback': 'Incorrect. Listen for what young people need to manage.',
                'no_answer_feedback': 'The correct answer is: BUDGET. Make sure to listen carefully for key information and take notes while listening.'
            },
            5: {
                'answer': 'cost of living',
                'correct_feedback': 'Correct! Many struggle to save due to the increasing cost of living.',
                'incorrect_feedback': 'Incorrect. Listen for why many young people find it difficult to save.',
                'no_answer_feedback': 'The correct answer is: COST OF LIVING. Make sure to listen carefully for key information and take notes while listening.'
            }
        },
        'options': '',
        'correct_answer': '',
        'no_answer_feedback': 'In the IELTS test, you should always take a guess. You don\'t lose points for a wrong answer.',
        'correct_feedback': 'Excellent! You got all the answers correct.',
        'incorrect_feedback': 'Some answers are incorrect. Review the lecture about saving attitudes.',
        'reading_text_id': None
    })
    
    return questions, audio_url, transcript

def annotate_transcript(transcript, questions, starting_q_num):
    """Add answer annotations to transcript with yellow highlighting."""
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
            # For MC and multi-select, just increment
            q_number += 1
    
    return annotated

def generate_xml(section_num, questions, audio_url, transcript):
    """Generate the complete XML file for a section."""
    now = datetime.now()
    
    questions_serialized = serialize_php(questions)
    starting_q_num = (section_num - 1) * 10 + 1
    annotated_transcript = annotate_transcript(transcript, questions, starting_q_num)
    
    xml_content = f'''<?xml version="1.0" encoding="UTF-8"?>
<!--
 This is a WordPress eXtended RSS file for IELTS Course Manager exercise export 
-->
<!--
 Generated by generate_test4_all_sections.py on {now.strftime("%Y-%m-%d %H:%M:%S")} 
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
<generator>IELTS Course Manager - generate_test4_all_sections.py</generator>
<item>
<title><![CDATA[Listening Test 4 Section {section_num}]]></title>
<link>https://www.ieltstestonline.com/2026/ielts-quiz/listening-test-4-section-{section_num}/</link>
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
<wp:post_name><![CDATA[listening-test-4-section-{section_num}]]></wp:post_name>
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
        
        # Generate XML
        xml_content = generate_xml(section_num, questions, audio_url, transcript)
        
        # Save XML file
        output_file = f"Listening Test 4 Section {section_num}.xml"
        with open(output_file, 'w', encoding='utf-8') as f:
            f.write(xml_content)
        print(f"  ✓ Generated {output_file}")
    
    print("\n✅ All 4 sections of Listening Test 4 generated successfully!")
    print("Each section has 10 questions (Q1-10, Q11-20, Q21-30, Q31-40)")

if __name__ == "__main__":
    main()
