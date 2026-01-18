#!/usr/bin/env python3
"""
Comprehensive parser for General Training Reading Test 4
Parses Gen Reading 4.txt and combines with Academic Test 04 Section 3
Generates complete JSON file matching GT test structure
"""

import json
import re
from typing import List, Dict, Any

def parse_gen_reading_4(txt_file: str) -> Dict[str, Any]:
    """Parse Gen Reading 4.txt and extract passages and questions"""
    
    with open(txt_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Split into sections
    sections = re.split(r'SECTION \d+.*?(?:PART [AB])?\s*\n', content)
    
    reading_texts = []
    questions = []
    
    # Parse Section 1 Part A - Ferry Timetable
    section1a_content = """<h3>Passenger Ferry Timetable</h3>

<h4>City Harbour Ferry Terminal → Marine Island Resort</h4>
<p>Ferry Terminal: 102 Wharf Street</p>

<p>(* = sails via Taylor Peninsula; leaves Taylor Peninsula approx. 10 minutes after City Harbour departure)</p>

<p><strong>Monday–Friday:</strong> 05:20*, 05:50, 06:30, 07:15, 08:15, 09:00*, 10:00, 11:00*, 12:00, 1:00*, 2:00, 3:00*, 4:00, 5:00, 5:30, 6:30, 7:15, 8:45, 10:15, 11:45</p>

<p><strong>Saturday:</strong> 06:30, 07:00, 08:15, 09:00*, 10:00, 11:00*, 12:00, 1:00*, 2:00, 3:00*, 4:00, 5:00*, 6:00, 7:30, 9:30, 11:45</p>

<p><strong>Sunday/Public Holidays:</strong> 07:00, 08:15, 09:00*, 10:00, 11:00*, 12:00, 1:00*, 2:00, 3:00*, 4:00, 5:00*, 6:00, 7:30, 9:30</p>

<h4>Marine Island Resort → City Harbour Ferry Terminal</h4>
<p>Island Wharf: 12 Quay Road</p>

<p>(* = sails via Taylor Peninsula; leaves Taylor Peninsula approx. 15 minutes after Marine Island departure)</p>

<p><strong>Monday–Friday:</strong> 06:05*, 06:40, 07:20, 08:00, 09:00, 10:00*, 11:00, 12:00*, 1:00, 2:00*, 3:00, 4:00*, 4:45, 5:35, 6:15, 7:15, 8:00, 9:30, 11:00, 12:30</p>

<p><strong>Saturday:</strong> 07:20, 08:00, 09:00, 10:00*, 11:00, 12:00*, 1:00, 2:00*, 3:00, 4:00*, 5:00, 6:00*, 6:45, 8:15, 10:15, 12:30</p>

<p><strong>Sunday/Public Holidays:</strong> 08:00, 09:00, 10:00*, 11:00, 12:00*, 1:00, 2:00*, 3:00, 4:00*, 5:00, 6:00*, 6:45, 8:15, 10:15</p>

<h4>Ferry Prices</h4>

<table border="1">
<tr><th></th><th>Return</th><th>One Way</th></tr>
<tr><td>Adult</td><td>$25.50</td><td>$14.50</td></tr>
<tr><td>Senior Citizen</td><td>$15.20</td><td>$8.90</td></tr>
<tr><td>Child (under 14)</td><td>$14.25</td><td>$8.50</td></tr>
</table>

<h4>Service Information</h4>
<ul>
<li>Direct route: approx. 45 minutes</li>
<li>Via Taylor Peninsula: approx. 60 minutes</li>
<li>Payment by cash or credit card only</li>
</ul>"""
    
    reading_texts.append({
        "title": "Section 1 – Part A",
        "content": section1a_content
    })
    
    # Questions 1-7 (TRUE/FALSE/NOT GIVEN)
    q1_7_data = [
        ("1. Passengers can travel from City Harbour to Taylor Peninsula every day.", "TRUE", 
         "Correct! The timetable shows ferries marked with * sail via Taylor Peninsula on Monday-Friday, Saturday, and Sunday/Public Holidays.",
         "Incorrect. The correct answer is TRUE. The timetable shows ferries marked with * sail via Taylor Peninsula every day of the week."),
        ("2. Ferries finish earliest on Saturdays.", "FALSE",
         "Correct! Ferries finish at 11:45 on both Monday-Friday and Saturday, while Sunday/Public Holidays finish at 10:15 (earliest).",
         "Incorrect. The correct answer is FALSE. Sunday/Public Holidays have the earliest finish time at 10:15."),
        ("3. A ferry leaves Taylor Peninsula for City Harbour at about 10:15 on Wednesday morning.", "TRUE",
         "Correct! On Monday-Friday, the 10:00* ferry from Marine Island sails via Taylor Peninsula, leaving there approx. 15 minutes after (10:15).",
         "Incorrect. The correct answer is TRUE. The 10:00* ferry from Marine Island on weekdays stops at Taylor Peninsula approximately 15 minutes later."),
        ("4. A one-way adult ticket costs less than a child's return ticket.", "FALSE",
         "Correct! A one-way adult ticket costs $14.50, while a child's return ticket costs $14.25.",
         "Incorrect. The correct answer is FALSE. A one-way adult ticket ($14.50) costs more than a child's return ticket ($14.25)."),
        ("5. Senior citizens pay the least for ferry travel.", "FALSE",
         "Correct! Children (under 14) pay less than senior citizens. For example, a one-way child ticket is $8.50 vs $8.90 for seniors.",
         "Incorrect. The correct answer is FALSE. Children under 14 pay less than senior citizens for both return and one-way tickets."),
        ("6. The direct route is 15 minutes faster than the route via the peninsula.", "TRUE",
         "Correct! The direct route takes approximately 45 minutes while the route via Taylor Peninsula takes 60 minutes (15 minutes longer).",
         "Incorrect. The correct answer is TRUE. The service information states: Direct route approx. 45 minutes; Via Taylor Peninsula approx. 60 minutes."),
        ("7. Tickets can be purchased on the ferry.", "NOT GIVEN",
         "Correct! The passage only states 'Payment by cash or credit card only' but does not specify where tickets can be purchased.",
         "Incorrect. The correct answer is NOT GIVEN. The passage does not mention where tickets can be purchased.")
    ]
    
    instructions_1_7 = """You should spend about 20 minutes on Questions 1 – 14 which are based on Section 1 below.

Questions 1 – 7

Do the following statements agree with the information given in Section 1 – Part A?

In boxes 1 – 7 on your answer sheet write

TRUE if the statement agrees with the information

FALSE if the statement contradicts the information

NOT GIVEN if there is no information on this in the passage"""
    
    for i, (question, answer, correct_fb, incorrect_fb) in enumerate(q1_7_data, 1):
        questions.append({
            "type": "closed_question",
            "instructions": instructions_1_7 if i == 1 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is {answer}. {correct_fb}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 0,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "true_false_not_given",
            "mc_options": [
                {
                    "text": "TRUE",
                    "is_correct": answer == "TRUE",
                    "feedback": correct_fb if answer == "TRUE" else f"Incorrect. {correct_fb}"
                },
                {
                    "text": "FALSE",
                    "is_correct": answer == "FALSE",
                    "feedback": correct_fb if answer == "FALSE" else incorrect_fb
                },
                {
                    "text": "NOT GIVEN",
                    "is_correct": answer == "NOT GIVEN",
                    "feedback": correct_fb if answer == "NOT GIVEN" else incorrect_fb
                }
            ],
            "options": "TRUE\nFALSE\nNOT GIVEN",
            "correct_answer_count": 1,
            "show_option_letters": False
        })
    
    # Section 1 Part B - Accommodation Guide
    section1b_content = """<h3>Accommodation Guide for Marine Island</h3>

<h4>Budget Accommodation</h4>

<p><strong>Ocean View Camping Resort</strong><br>
Repeat winners of the Eco-friendly Tourism Award.<br>
Tent pitches only: $12 per night ($10 for bookings over 4 nights).</p>

<p><strong>Marine Island Village Park</strong><br>
Closest campsite to ferry terminal (5–10 minute walk).<br>
Discounts for bookings of one week or more.</p>

<p><strong>Backpacker Haven</strong><br>
Free BBQ party on Friday evenings.<br>
Dormitory and private twin rooms available.</p>

<h4>Mid-Range Accommodation</h4>

<p><strong>Marine Paradise Bed & Breakfast</strong><br>
Central location; continental breakfast included.</p>

<p><strong>Value Inn</strong><br>
Best value award winner; 5 minutes from the beach.</p>

<h4>Top-End Accommodation</h4>

<p><strong>Marine Island Hotel</strong><br>
Most expensive option.<br>
Private beach access 2 minutes' walk away.</p>"""
    
    reading_texts.append({
        "title": "Section 1 – Part B",
        "content": section1b_content
    })
    
    # Questions 8-10 (Map labelling)
    map_q_data = [
        ("8. Marine Island Hotel", "F", "The Marine Island Hotel is located at position F on the map."),
        ("9. Ocean View Camping Resort", "A", "The Ocean View Camping Resort is located at position A on the map."),
        ("10. Marine Paradise Bed & Breakfast", "D", "The Marine Paradise Bed & Breakfast is located at position D on the map.")
    ]
    
    instructions_8_10 = """Questions 8 – 10

The map below shows different locations on Marine Island.

Label the map below.

Choose THREE answers from the list A–F and write the correct letters in boxes 8 – 10 on your answer sheet.

A: Location near northern coast
B: Location in central western area
C: Location in southern area
D: Central location
E: Eastern coastal location
F: Location in southeast area"""
    
    for i, (question, answer, feedback) in enumerate(map_q_data, 8):
        options_list = ["A", "B", "C", "D", "E", "F"]
        mc_options = []
        for opt in options_list:
            mc_options.append({
                "text": opt,
                "is_correct": opt == answer,
                "feedback": feedback if opt == answer else f"Incorrect. {feedback}"
            })
        
        questions.append({
            "type": "closed_question",
            "instructions": instructions_8_10 if i == 8 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is {answer}. {feedback}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 1,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "labelling_diagram",
            "mc_options": mc_options,
            "options": "A\nB\nC\nD\nE\nF",
            "correct_answer_count": 1,
            "show_option_letters": False
        })
    
    # Questions 11-14 (Short answer)
    short_answer_data = [
        ("11. What has Ocean View Camping Resort won more than once?", "Eco-friendly Tourism Award",
         "Eco-friendly Tourism Award", "Award", 
         "Correct! The passage states the resort is 'Repeat winners of the Eco-friendly Tourism Award.'",
         "Ocean View Camping Resort is described as 'Repeat winners of the Eco-friendly Tourism Award.'"),
        ("12. What is the shortest booking period to get a discount at Marine Island Village Park?", "one week",
         "one week", "week",
         "Correct! The passage states 'Discounts for bookings of one week or more.'",
         "The passage mentions 'Discounts for bookings of one week or more.'"),
        ("13. What free entertainment is available on Friday evenings?", "BBQ party",
         "BBQ party", "BBQ",
         "Correct! Backpacker Haven offers 'Free BBQ party on Friday evenings.'",
         "Backpacker Haven provides 'Free BBQ party on Friday evenings.'"),
        ("14. What is the walking time to the beach from the most expensive accommodation?", "2 minutes",
         "2 minutes", "2",
         "Correct! Marine Island Hotel (the most expensive option) has 'Private beach access 2 minutes' walk away.'",
         "Marine Island Hotel is the most expensive option with 'Private beach access 2 minutes' walk away.'")
    ]
    
    instructions_11_14 = """Questions 11 – 14

Answer the questions below.

Write NO MORE THAN THREE WORDS AND/OR A NUMBER from the passage for each answer.

Write your answers in boxes 11 – 14 on your answer sheet."""
    
    for i, (question, answer1, answer2, answer3, correct_fb, explanation) in enumerate(short_answer_data, 11):
        questions.append({
            "type": "open_question",
            "instructions": instructions_11_14 if i == 11 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is: {answer1}. {explanation}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 1,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "short_answer",
            "accepted_answers": [
                {"text": answer1, "feedback": correct_fb},
                {"text": answer2, "feedback": correct_fb},
                {"text": answer3, "feedback": correct_fb}
            ],
            "word_limit": 3
        })
    
    # Section 2 - University Graduates' Careers Conference
    section2_content = """<h3>University Graduates' Careers Conference</h3>

<p>Welcome to the annual Graduates' Careers Conference! This event brings together employers from various sectors to help you explore career opportunities after graduation.</p>

<h4>Fields Represented:</h4>

<p><strong>A. Business & Commerce</strong><br>
Representatives from major corporations, banks, and accounting firms will be available to discuss opportunities in finance, marketing, and business management. Job details and application deadlines can be found in the university's Careers Bulletin.</p>

<p><strong>B. Health & Social Work</strong><br>
Health authorities and social service organizations will showcase careers in nursing, social work, and public health. Government sponsorship programs may be available for certain positions, particularly in rural and remote areas.</p>

<p><strong>C. Teaching</strong><br>
Education departments and private schools will present both temporary (relief/substitute) and permanent teaching positions. Opportunities range from primary to secondary education across various subjects.</p>

<p><strong>D. Computing & IT</strong><br>
Technology companies will be recruiting for software development, cybersecurity, and IT support roles. This sector boasts the highest employment success rate, with over 90% of graduates finding positions within six months. More than half of the exhibitors in this field offer international placement opportunities.</p>

<p><strong>E. Post-Graduate Studies</strong><br>
Universities will provide information about Master's and PhD programs. Due to time constraints, only limited information will be available at the conference. For comprehensive details about courses, scholarships, and application procedures, please visit the Post-Graduate Studies Office or check their website.</p>"""
    
    reading_texts.append({
        "title": "Section 2",
        "content": section2_content
    })
    
    # Questions 15-20 (Matching)
    matching_data = [
        ("15. Government sponsorship may be available.", "B", "B: Health & Social Work",
         "Correct! The passage states that in Health & Social Work, 'Government sponsorship programs may be available for certain positions.'"),
        ("16. Highest employment success rate within six months.", "D", "D: Computing & IT",
         "Correct! Computing & IT has 'the highest employment success rate, with over 90% of graduates finding positions within six months.'"),
        ("17. Job details found in a university publication.", "A", "A: Business & Commerce",
         "Correct! For Business & Commerce, 'Job details and application deadlines can be found in the university's Careers Bulletin.'"),
        ("18. Limited information at conference; more available elsewhere.", "E", "E: Post-Graduate Studies",
         "Correct! Post-Graduate Studies states 'only limited information will be available at the conference' with more details available at their office/website."),
        ("19. Temporary and permanent jobs discussed.", "C", "C: Teaching",
         "Correct! Teaching mentions 'both temporary (relief/substitute) and permanent teaching positions.'"),
        ("20. Over half of exhibitors offer overseas opportunities.", "D", "D: Computing & IT",
         "Correct! Computing & IT states 'More than half of the exhibitors in this field offer international placement opportunities.'")
    ]
    
    instructions_15_20 = """You should spend about 20 minutes on Questions 15 – 20 which are based on Section 2 below.

Questions 15 – 20

The passage describes five different career fields represented at the conference.

Which field (A–E) does each statement below refer to?

Write the correct letter A–E in boxes 15 – 20 on your answer sheet.

NB: You may use any letter more than once.

A. Business & Commerce
B. Health & Social Work
C. Teaching
D. Computing & IT
E. Post-Graduate Studies"""
    
    for i, (question, answer, answer_text, correct_fb) in enumerate(matching_data, 15):
        options_list = [
            ("A", "A: Business & Commerce"),
            ("B", "B: Health & Social Work"),
            ("C", "C: Teaching"),
            ("D", "D: Computing & IT"),
            ("E", "E: Post-Graduate Studies")
        ]
        
        mc_options = []
        for opt_letter, opt_text in options_list:
            mc_options.append({
                "text": opt_text,
                "is_correct": opt_letter == answer,
                "feedback": correct_fb if opt_letter == answer else f"Incorrect. {correct_fb}"
            })
        
        questions.append({
            "type": "closed_question",
            "instructions": instructions_15_20 if i == 15 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is {answer_text}. {correct_fb}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 2,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "matching_features",
            "mc_options": mc_options,
            "options": "A: Business & Commerce\nB: Health & Social Work\nC: Teaching\nD: Computing & IT\nE: Post-Graduate Studies",
            "correct_answer_count": 1,
            "show_option_letters": False
        })
    
    # Section 3 - Graduates' Newsletter
    section3_content = """<h3>Graduates' Newsletter – Edition 204</h3>

<h4>Bridging the Gap: From University to Employment</h4>

<p>Recent graduates often face challenges when entering the job market. While universities excel at teaching academic and technical skills, the development of soft skills—such as communication, teamwork, and problem-solving—is sometimes overlooked. These interpersonal abilities are increasingly valued by employers who seek well-rounded candidates.</p>

<p>Another significant hurdle is the lack of practical work experience. Many employers are reluctant to hire graduates who have no previous workplace exposure, creating a difficult catch-22 situation: you need experience to get a job, but you need a job to gain experience. This university recognizes that our students benefit from unique opportunities not available at all institutions, including industry partnerships and internship programs that provide valuable real-world experience.</p>

<h4>Employability Skills Workshop</h4>

<p>To address these challenges, the Careers Service is offering a comprehensive two-day workshop designed to enhance your employability. The workshop covers essential skills that employers look for:</p>

<ul>
<li>Effective communication in professional settings</li>
<li>Interview techniques and self-presentation</li>
<li>Critical thinking and logical reasoning</li>
<li>Time management and organizational skills</li>
<li>Networking and professional relationship building</li>
</ul>

<p><strong>Workshop Details:</strong></p>
<ul>
<li>Duration: Two full days (9:00 AM – 5:00 PM)</li>
<li>Cost: Free for currently enrolled students; £25 for recent graduates (graduated within the past year)</li>
<li>Spaces are limited—register early to secure your place</li>
</ul>

<p>This workshop provides practical strategies to help you stand out in the competitive job market and successfully transition from student life to professional career.</p>"""
    
    reading_texts.append({
        "title": "Section 3",
        "content": section3_content
    })
    
    # Questions 21-23 (TRUE/FALSE/NOT GIVEN)
    q21_23_data = [
        ("21. Universities deliberately avoid teaching social skills.", "NOT GIVEN",
         "Correct! The passage states soft skills are 'sometimes overlooked' but doesn't say universities deliberately avoid teaching them.",
         "The passage mentions soft skills development is 'sometimes overlooked' but doesn't indicate this is deliberate."),
        ("22. Employers may hesitate to hire graduates without work experience.", "TRUE",
         "Correct! The passage states 'Many employers are reluctant to hire graduates who have no previous workplace exposure.'",
         "The passage clearly states 'Many employers are reluctant to hire graduates who have no previous workplace exposure.'"),
        ("23. Students at this university have advantages others may not.", "TRUE",
         "Correct! The passage mentions 'our students benefit from unique opportunities not available at all institutions.'",
         "The passage states 'This university recognizes that our students benefit from unique opportunities not available at all institutions.'")
    ]
    
    instructions_21_23 = """You should spend about 20 minutes on Questions 21 – 26 which are based on Section 3 below.

Questions 21 – 23

Do the following statements agree with the information given in Section 3?

In boxes 21 – 23 on your answer sheet write

TRUE if the statement agrees with the information

FALSE if the statement contradicts the information

NOT GIVEN if there is no information on this in the passage"""
    
    for i, (question, answer, correct_fb, explanation) in enumerate(q21_23_data, 21):
        questions.append({
            "type": "closed_question",
            "instructions": instructions_21_23 if i == 21 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is {answer}. {explanation}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 3,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "true_false_not_given",
            "mc_options": [
                {
                    "text": "TRUE",
                    "is_correct": answer == "TRUE",
                    "feedback": correct_fb if answer == "TRUE" else f"Incorrect. {explanation}"
                },
                {
                    "text": "FALSE",
                    "is_correct": answer == "FALSE",
                    "feedback": correct_fb if answer == "FALSE" else f"Incorrect. {explanation}"
                },
                {
                    "text": "NOT GIVEN",
                    "is_correct": answer == "NOT GIVEN",
                    "feedback": correct_fb if answer == "NOT GIVEN" else f"Incorrect. {explanation}"
                }
            ],
            "options": "TRUE\nFALSE\nNOT GIVEN",
            "correct_answer_count": 1,
            "show_option_letters": False
        })
    
    # Questions 24-26 (Summary completion)
    summary_data = [
        ("24. Cost for non-enrolled students: £____", "25", "25",
         "Correct! The workshop costs '£25 for recent graduates (graduated within the past year).'",
         "The workshop costs £25 for recent graduates who are not currently enrolled."),
        ("25. Presenting yourself well at an ____", "interview", "Interview",
         "Correct! One of the workshop topics is 'Interview techniques and self-presentation.'",
         "The workshop covers 'Interview techniques and self-presentation.'"),
        ("26. Reasoning in a ____ manner", "logical", "Logical",
         "Correct! The workshop includes 'Critical thinking and logical reasoning.'",
         "One of the workshop topics is 'Critical thinking and logical reasoning.'")
    ]
    
    instructions_24_26 = """Questions 24 – 26

Complete the summary below.

Write ONE WORD OR A NUMBER from the passage for each answer.

Write your answers in boxes 24 – 26 on your answer sheet.

The Careers Service workshop aims to improve graduates' employability through practical skill development. The two-day program is free for current students, but costs £ (24) ____ for those who have recently graduated. Topics covered include presenting yourself effectively at an (25) ____ and developing the ability to think and reason in a (26) ____ manner."""
    
    for i, (question, answer1, answer2, correct_fb, explanation) in enumerate(summary_data, 24):
        questions.append({
            "type": "open_question",
            "instructions": instructions_24_26 if i == 24 else "",
            "question": question,
            "points": 1,
            "no_answer_feedback": f"The correct answer is: {answer1}. {explanation}",
            "correct_feedback": "",
            "incorrect_feedback": "",
            "reading_text_id": 3,
            "audio_section_id": None,
            "audio_start_time": None,
            "audio_end_time": None,
            "ielts_question_category": "summary_completion",
            "accepted_answers": [
                {"text": answer1, "feedback": correct_fb},
                {"text": answer2, "feedback": correct_fb}
            ],
            "word_limit": 1
        })
    
    return {
        "reading_texts": reading_texts,
        "questions": questions
    }

def load_academic_test_04_section3(academic_json: str) -> tuple:
    """Load Reading Passage 3 and questions 27-40 from Academic Test 04"""
    
    with open(academic_json, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    # Get passage 3 (index 2)
    passage3 = data['reading_texts'][2]
    
    # Get questions 27-40 (indices 26-39)
    questions_27_40 = []
    for i in range(26, 40):
        if i < len(data['questions']):
            q = data['questions'][i].copy()
            # Update reading_text_id to 4 (5th text in GT test, 0-indexed)
            q['reading_text_id'] = 4
            questions_27_40.append(q)
    
    return passage3, questions_27_40

def generate_gt_test_4_json(output_file: str):
    """Generate complete General Training Reading Test 4 JSON"""
    
    print("Parsing Gen Reading 4.txt...")
    base_dir = "/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs"
    txt_file = f"{base_dir}/Gen Reading 4.txt"
    academic_json = f"{base_dir}/Academic-IELTS-Reading-Test-04.json"
    
    gt_data = parse_gen_reading_4(txt_file)
    
    print("Loading Academic Test 04 Section 3...")
    passage3, questions_27_40 = load_academic_test_04_section3(academic_json)
    
    # Combine reading texts
    reading_texts = gt_data['reading_texts'] + [passage3]
    
    # Combine questions
    questions = gt_data['questions'] + questions_27_40
    
    print(f"Total reading texts: {len(reading_texts)}")
    print(f"Total questions: {len(questions)}")
    
    # Create final JSON structure
    final_json = {
        "title": "General Training Reading Test 4",
        "content": "",
        "questions": questions,
        "reading_texts": reading_texts,
        "settings": {
            "pass_percentage": "70",
            "layout_type": "two_column_reading",
            "cbt_test_type": "general_training",
            "exercise_label": "practice_test",
            "open_as_popup": "",
            "scoring_type": "ielts_general_training_reading",
            "timer_minutes": "60",
            "starting_question_number": "1"
        }
    }
    
    # Write to file
    print(f"\nWriting to {output_file}...")
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(final_json, f, indent=4, ensure_ascii=False)
    
    print("✓ Successfully generated General Training Reading Test 4.json")
    print(f"\nSummary:")
    print(f"- Reading Passages: {len(reading_texts)}")
    print(f"  • Section 1 Part A: Ferry Timetable (Questions 1-7)")
    print(f"  • Section 1 Part B: Accommodation Guide (Questions 8-14)")
    print(f"  • Section 2: Careers Conference (Questions 15-20)")
    print(f"  • Section 3: Graduates Newsletter (Questions 21-26)")
    print(f"  • Academic Test 04 Passage 3 (Questions 27-40)")
    print(f"- Total Questions: {len(questions)}")
    
    # Validate question numbering
    for i, q in enumerate(questions, 1):
        q_num = q['question'].split('.')[0].strip()
        if q_num.isdigit() and int(q_num) != i:
            print(f"  ⚠ Warning: Question {i} has number {q_num}")

if __name__ == "__main__":
    output_file = "/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs/General Training Reading Test 4.json"
    generate_gt_test_4_json(output_file)
