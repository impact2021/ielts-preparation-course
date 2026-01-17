#!/usr/bin/env python3
"""
Generate Quality Dashboard for IELTS Reading Tests

This script correctly counts student-facing questions according to IELTS standards:
- Summary completion: count fields in summary_fields
- Open questions: use field_count value
- Closed questions: use correct_answer_count value (default 1)

See QUESTION_COUNTING_RULES.md for detailed explanation.
"""

import json
import os
import glob
from datetime import datetime, timezone

def count_student_questions(question):
    """Count actual student-facing questions according to IELTS standards"""
    q_type = question.get('type', '')
    
    # Rule 1: Summary Completion - count fields
    if q_type == 'summary_completion':
        return len(question.get('summary_fields', {}))
    
    # Rule 2: Open Questions - count field_count
    elif q_type == 'open_question':
        return question.get('field_count', 1)
    
    # Rule 3: Closed Questions - check correct_answer_count
    else:
        return question.get('correct_answer_count', 1)

def analyze_test(file_path):
    """Analyze a single test file for quality metrics"""
    with open(file_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    questions = data.get('questions', [])
    json_objects = len(questions)
    student_questions = sum(count_student_questions(q) for q in questions)
    
    test_name = os.path.basename(file_path)
    test_num = test_name.split('-')[-1].replace('.json', '')
    
    # Analyze quality metrics
    missing_feedback = []
    not_linked = []
    grammar_issues = []
    
    for i, q in enumerate(questions, 1):
        # Check feedback based on question type
        q_type = q.get('type', '')
        has_feedback = False
        
        # For questions with mc_options (headings, matching_classifying, multiple_choice, etc.)
        if q.get('mc_options'):
            mc_options = q.get('mc_options', [])
            for opt in mc_options:
                if opt.get('feedback', '').strip():
                    has_feedback = True
                    break
        # For open_question type with field_feedback
        elif q_type == 'open_question' and q.get('field_feedback'):
            field_feedback = q.get('field_feedback', {})
            field_count = q.get('field_count', 1)
            for field_num in range(1, field_count + 1):
                field_fb = field_feedback.get(str(field_num), {})
                if (field_fb.get('correct', '').strip() or 
                    field_fb.get('incorrect', '').strip() or
                    field_fb.get('no_answer', '').strip()):
                    has_feedback = True
                    break
        # For types that correctly use top-level feedback (true_false, short_answer, summary_completion, etc.)
        else:
            no_answer = q.get('no_answer_feedback', '').strip()
            correct = q.get('correct_feedback', '').strip()
            incorrect = q.get('incorrect_feedback', '').strip()
            if no_answer or correct or incorrect:
                has_feedback = True
        
        if not has_feedback:
            missing_feedback.append(i)
        
        # Check if linked to passage
        reading_text_id = q.get('reading_text_id')
        if reading_text_id is None:
            not_linked.append(i)
        
        # Check for double spacing
        question_text = q.get('question', '')
        if '  ' in question_text:
            grammar_issues.append(i)
    
    return {
        'test_num': test_num,
        'json_objects': json_objects,
        'student_questions': student_questions,
        'missing_feedback': missing_feedback,
        'not_linked': not_linked,
        'grammar_issues': grammar_issues,
        'file_path': file_path
    }

def generate_gt_test_row(result):
    """Generate a table row for a General Training test"""
    test_num = result['test_num']
    questions = result['student_questions']
    complete = '✓ Complete' if questions == 40 else f'{questions}/40'
    
    # Feedback status
    missing_fb = len(result['missing_feedback'])
    feedback_status = '✓ All' if missing_fb == 0 else f'⚠ {missing_fb} missing'
    feedback_class = 'yes' if missing_fb == 0 else 'warning'
    
    # Link status
    not_linked = len(result['not_linked'])
    link_status = '✓ Present' if not_linked == 0 else f'⚠ {not_linked} missing'
    link_class = 'yes' if not_linked == 0 else 'warning'
    
    # Overall status
    if questions == 40 and missing_fb == 0 and not_linked == 0:
        status = '✓ EXCELLENT'
        status_class = 'excellent'
    elif questions == 40:
        status = '✓ Good'
        status_class = 'good'
    else:
        status = '⚠ Incomplete'
        status_class = 'warning'
    
    return f'''
                                <tr>
                                    <td class="test-number">{test_num}</td>
                                    <td>{questions}</td>
                                    <td><span class="badge {'yes' if questions == 40 else 'warning'}">{complete}</span></td>
                                    <td><span class="badge {feedback_class}">{feedback_status}</span></td>
                                    <td><span class="badge {link_class}">{link_status}</span></td>
                                    <td><span class="badge {status_class}">{status}</span></td>
                                </tr>'''

def generate_html_dashboard(test_results, gt_test_results):
    """Generate the HTML quality dashboard"""
    
    # Calculate statistics for Academic tests
    total_tests = len(test_results)
    total_questions = sum(r['student_questions'] for r in test_results)
    complete_tests = sum(1 for r in test_results if r['student_questions'] == 40)
    incomplete_tests = total_tests - complete_tests
    
    total_missing_feedback = sum(len(r['missing_feedback']) for r in test_results)
    total_not_linked = sum(len(r['not_linked']) for r in test_results)
    total_grammar_issues = sum(len(r['grammar_issues']) for r in test_results)
    
    # Count tests by status
    broken_tests = sum(1 for r in test_results if len(r['not_linked']) > 10)
    tests_with_issues = sum(1 for r in test_results if 
                           r['student_questions'] != 40 or 
                           len(r['missing_feedback']) > 0 or
                           len(r['not_linked']) > 0 or
                           len(r['grammar_issues']) > 0)
    good_tests = total_tests - tests_with_issues
    
    timestamp = datetime.now(timezone.utc).strftime('%Y-%m-%d %H:%M:%S UTC')
    
    html = f'''<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IELTS Practice Tests - Quality Dashboard</title>
    <style>
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }}
        
        .container {{
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }}
        
        .header {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }}
        
        .header h1 {{
            font-size: 2rem;
            margin-bottom: 10px;
        }}
        
        .header .subtitle {{
            font-size: 1rem;
            opacity: 0.9;
        }}
        
        .header .timestamp {{
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 10px;
        }}
        
        .tabs {{
            display: flex;
            background: #f8f9fa;
            border-bottom: 2px solid #667eea;
        }}
        
        .tab {{
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            cursor: pointer;
            background: #e9ecef;
            border: none;
            font-size: 1rem;
            font-weight: 500;
            color: #495057;
            transition: all 0.3s ease;
        }}
        
        .tab:hover {{
            background: #dee2e6;
        }}
        
        .tab.active {{
            background: white;
            color: #667eea;
            border-bottom: 3px solid #667eea;
            font-weight: 600;
        }}
        
        .tab-content {{
            display: none;
        }}
        
        .tab-content.active {{
            display: block;
        }}
        
        .stats-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }}
        
        .stat-card {{
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }}
        
        .stat-card .number {{
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }}
        
        .stat-card .label {{
            font-size: 0.9rem;
            color: #666;
        }}
        
        .stat-card.good .number {{ color: #28a745; }}
        .stat-card.warning .number {{ color: #ffc107; }}
        .stat-card.critical .number {{ color: #dc3545; }}
        .stat-card.info .number {{ color: #667eea; }}
        
        .content {{
            padding: 30px;
        }}
        
        .table-container {{
            overflow-x: auto;
            margin-bottom: 30px;
        }}
        
        table {{
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }}
        
        th {{
            background: #667eea;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }}
        
        td {{
            padding: 10px 8px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }}
        
        tr:hover {{
            background: #f8f9fa;
        }}
        
        tr.highlighted {{
            background: #fff3cd;
            font-weight: bold;
        }}
        
        .badge {{
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }}
        
        .badge.good {{
            background: #d4edda;
            color: #155724;
        }}
        
        .badge.warning {{
            background: #fff3cd;
            color: #856404;
        }}
        
        .badge.critical {{
            background: #f8d7da;
            color: #721c24;
        }}
        
        .badge.yes {{
            background: #d4edda;
            color: #155724;
        }}
        
        .badge.no {{
            background: #f8d7da;
            color: #721c24;
        }}
        
        .badge.excellent {{
            background: #d4edda;
            color: #155724;
        }}
        
        .section {{
            margin-bottom: 30px;
        }}
        
        .section h2 {{
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }}
        
        .section h3 {{
            font-size: 1.2rem;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #666;
        }}
        
        .issue-list {{
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }}
        
        .issue-list ul {{
            list-style: none;
            padding-left: 0;
        }}
        
        .issue-list li {{
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }}
        
        .issue-list li:last-child {{
            border-bottom: none;
        }}
        
        .test-number {{
            font-weight: bold;
            color: #667eea;
        }}
        
        .footer {{
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 0.85rem;
        }}
        
        @media print {{
            body {{
                background: white;
            }}
            .container {{
                box-shadow: none;
            }}
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 IELTS Practice Tests - Quality Dashboard</h1>
            <div class="subtitle">Comprehensive Quality Review for All Test Types</div>
            <div class="timestamp">Last Updated: {timestamp}</div>
        </div>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('reading')">📖 Academic Reading (21 Tests)</button>
            <button class="tab" onclick="switchTab('listening')">🎧 Listening (15 Tests)</button>
            <button class="tab" onclick="switchTab('general')">📝 General Training (Coming Soon)</button>
        </div>
        
        <!-- ACADEMIC READING TAB -->
        <div id="reading" class="tab-content active">
        
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="number">{total_tests}</div>
                <div class="label">Total Tests</div>
            </div>
            <div class="stat-card info">
                <div class="number">{total_questions}</div>
                <div class="label">Total Questions</div>
            </div>
            <div class="stat-card good">
                <div class="number">{complete_tests}</div>
                <div class="label">✓ Complete (40 Qs)</div>
            </div>
            <div class="stat-card warning">
                <div class="number">{tests_with_issues}</div>
                <div class="label">⚠ Issues</div>
            </div>
            <div class="stat-card critical">
                <div class="number">{incomplete_tests}</div>
                <div class="label">🔴 Incomplete</div>
            </div>
        </div>
        
        <div class="content">
            <div class="section">
                <h2>Quality Summary Table</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Test</th>
                                <th>Questions</th>
                                <th>Full Feedback</th>
                                <th>Linked to Passage</th>
                                <th>Grammar</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
'''
    
    # Generate table rows
    for result in test_results:
        test_num = result['test_num']
        student_q = result['student_questions']
        missing_fb = len(result['missing_feedback'])
        not_linked = len(result['not_linked'])
        grammar = len(result['grammar_issues'])
        
        # Determine badges
        q_badge = 'good' if student_q == 40 else 'critical'
        fb_badge = 'yes' if missing_fb == 0 else 'no'
        fb_text = '✓ Yes' if missing_fb == 0 else f'✗ No ({missing_fb})'
        link_badge = 'yes' if not_linked == 0 else 'no'
        link_text = '✓ Yes' if not_linked == 0 else f'✗ No ({not_linked})'
        grammar_badge = 'excellent' if grammar == 0 else 'warning'
        grammar_text = '✓ EXCELLENT' if grammar == 0 else f'⚠ Issues ({grammar})'
        
        # Determine overall status
        if student_q == 40 and missing_fb == 0 and not_linked == 0 and grammar == 0:
            status_badge = 'good'
            status_text = '✓ COMPLETE'
        elif student_q != 40:
            status_badge = 'critical'
            status_text = '🔴 INCOMPLETE'
        else:
            status_badge = 'good'
            status_text = '✓ COMPLETE'
        
        html += f'''                            <tr>
                                <td class="test-number">{test_num}</td>
                                <td><span class="badge {q_badge}">{student_q}</span></td>
                                <td><span class="badge {fb_badge}">{fb_text}</span></td>
                                <td><span class="badge {link_badge}">{link_text}</span></td>
                                <td><span class="badge {grammar_badge}">{grammar_text}</span></td>
                                <td><span class="badge {status_badge}">{status_text}</span></td>
                            </tr>
'''
    
    html += '''                        </tbody>
                    </table>
                </div>
            </div>
            
'''
    
    # Generate issue sections
    critical_issues = [r for r in test_results if len(r['not_linked']) > 10]
    if critical_issues:
        html += '''            <div class="section">
                <h2>🚨 Critical Issues</h2>
'''
        for result in critical_issues:
            html += f'''                <div class="issue-list">
                    <h3>🔴 Test {result['test_num']} - CRITICAL</h3>
                    <ul>
                        <li><strong>⚠️ Not Linked to Reading Passage:</strong> Questions {', '.join(map(str, result['not_linked']))}</li>
                        <li>reading_text_id is null or invalid</li>
                    </ul>
                </div>
'''
        html += '''            </div>
            
'''
    
    # Other issues
    other_issues = [r for r in test_results if 
                   len(r['missing_feedback']) > 0 or 
                   len(r['grammar_issues']) > 0 or
                   (len(r['not_linked']) > 0 and len(r['not_linked']) <= 10)]
    
    if other_issues:
        html += '''            <div class="section">
                <h2>⚠️ Other Issues</h2>
                
'''
        for result in other_issues:
            if len(result['missing_feedback']) == 0 and len(result['grammar_issues']) == 0 and len(result['not_linked']) == 0:
                continue
            
            html += f'''                <div class="issue-list">
                    <h3>Test {result['test_num']}</h3>
                    <ul>
'''
            if len(result['missing_feedback']) > 0:
                fb_list = ', '.join(map(str, result['missing_feedback'][:10]))
                if len(result['missing_feedback']) > 10:
                    fb_list += f' ... and {len(result["missing_feedback"]) - 10} more'
                html += f'''                        <li><strong>Missing Feedback:</strong> Questions {fb_list}</li>
'''
            if len(result['not_linked']) > 0 and len(result['not_linked']) <= 10:
                html += f'''                        <li><strong>Not Linked to Passage:</strong> Questions {', '.join(map(str, result['not_linked']))}</li>
'''
            if len(result['grammar_issues']) > 0:
                html += f'''                        <li><strong>Grammar Issues (double spacing):</strong> Questions {', '.join(map(str, result['grammar_issues']))}</li>
'''
            html += '''                    </ul>
                </div>
                
'''
        html += '''            </div>
            
'''
    
    # Statistics
    html += f'''            <div class="section">
                <h2>📈 Statistics Summary</h2>
                <div class="issue-list">
                    <h3>🚨 Critical Issues</h3>
                    <ul>
                        <li>Tests with BROKEN questions: <strong>{broken_tests}/{total_tests}</strong></li>
                        <li>Total questions not linked: <strong>{total_not_linked}</strong></li>
                    </ul>
                    
                    <h3>⚠️ Other Issues</h3>
                    <ul>
                        <li>Questions missing feedback: <strong>{total_missing_feedback}</strong></li>
                        <li>Questions with grammar issues: <strong>{total_grammar_issues}</strong></li>
                    </ul>
                    
                    <h3>Overall Quality</h3>
                    <ul>
                        <li>✓ Complete: <strong>{complete_tests}/{total_tests} tests</strong> ({100*complete_tests//total_tests}%)</li>
                        <li>⚠ Issues: <strong>{tests_with_issues}/{total_tests} tests</strong> ({100*tests_with_issues//total_tests}%)</li>
                        <li>🔴 BROKEN: <strong>{broken_tests}/{total_tests} tests</strong> ({100*broken_tests//total_tests if total_tests > 0 else 0}%)</li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
        
        <!-- LISTENING TAB -->
        <div id="listening" class="tab-content">
            <div class="stats-grid">
                <div class="stat-card info">
                    <div class="number">15</div>
                    <div class="label">Total Tests</div>
                </div>
                <div class="stat-card info">
                    <div class="number">600</div>
                    <div class="label">Total Questions</div>
                </div>
                <div class="stat-card good">
                    <div class="number">10</div>
                    <div class="label">✓ Excellent</div>
                </div>
                <div class="stat-card warning">
                    <div class="number">5</div>
                    <div class="label">⚠ Good</div>
                </div>
                <div class="stat-card critical">
                    <div class="number">0</div>
                    <div class="label">🔴 Broken</div>
                </div>
            </div>
            
            <div class="content">
                <div class="section">
                    <h2>Quality Summary Table</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Questions</th>
                                    <th>Feedback</th>
                                    <th>Transcripts (4 Sections)</th>
                                    <th>Audio URL</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="test-number">01</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">02</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">03</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">04</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">05</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">06</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge warning">⚠ Missing</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge good">✓ Good</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">07</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge warning">⚠ Missing</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge good">✓ Good</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">08</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge warning">⚠ Missing</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge good">✓ Good</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">09</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge warning">⚠ Missing</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge good">✓ Good</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">10</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge warning">⚠ Missing</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge good">✓ Good</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">11</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">12</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">13</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">14</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                                <tr>
                                    <td class="test-number">15</td>
                                    <td>40</td>
                                    <td><span class="badge yes">✓ Complete</span></td>
                                    <td><span class="badge yes">✓ All 4</span></td>
                                    <td><span class="badge yes">✓ Present</span></td>
                                    <td><span class="badge excellent">✓ EXCELLENT</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="section">
                    <h2>🎯 Issue Details</h2>
                    <div class="issue-list">
                        <h3>Tests 06-10</h3>
                        <ul>
                            <li><strong>Missing Transcripts:</strong> Section transcripts are empty (only audio URLs provided)</li>
                            <li><strong>Impact:</strong> Students cannot read along or review specific parts of the listening</li>
                            <li><strong>Recommendation:</strong> Add transcript text for all 4 sections to match Tests 01-05 and 11-15</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section">
                    <h2>📈 Statistics Summary</h2>
                    <div class="issue-list">
                        <h3>✅ Overall Quality</h3>
                        <ul>
                            <li>All tests have <strong>40 questions</strong> ✓</li>
                            <li>All tests have <strong>complete feedback</strong> ✓</li>
                            <li>All tests have <strong>audio URLs</strong> ✓</li>
                            <li><strong>10/15 tests</strong> have complete transcripts (67%)</li>
                        </ul>
                        
                        <h3>⚠️ Issues</h3>
                        <ul>
                            <li>Tests missing transcripts: <strong>5/15</strong> (Tests 06-10)</li>
                            <li>No critical or broken tests: <strong>0</strong> ✓</li>
                        </ul>
                        
                        <h3>Quality Breakdown</h3>
                        <ul>
                            <li>✓ EXCELLENT: <strong>10/15 tests</strong> (67%)</li>
                            <li>✓ Good: <strong>5/15 tests</strong> (33%)</li>
                            <li>🔴 BROKEN: <strong>0/15 tests</strong> (0%)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- GENERAL TRAINING TAB -->
        <div id="general" class="tab-content">
            <div class="content">
                <div class="stats-grid">
                    <div class="stat-card info">
                        <div class="number">{len(gt_test_results)}</div>
                        <div class="label">Total Tests</div>
                    </div>
                    <div class="stat-card {'good' if sum(r['student_questions'] for r in gt_test_results) else 'info'}">
                        <div class="number">{sum(r['student_questions'] for r in gt_test_results)}</div>
                        <div class="label">Total Questions</div>
                    </div>
                    <div class="stat-card {'good' if sum(1 for r in gt_test_results if r['student_questions'] == 40) else 'warning'}">
                        <div class="number">{sum(1 for r in gt_test_results if r['student_questions'] == 40)}</div>
                        <div class="label">Complete Tests (40Q)</div>
                    </div>
                    <div class="stat-card {'good' if sum(len(r['missing_feedback']) for r in gt_test_results) == 0 else 'warning'}">
                        <div class="number">{sum(len(r['missing_feedback']) for r in gt_test_results)}</div>
                        <div class="label">Missing Feedback</div>
                    </div>
                </div>
                
                <div class="section">
                    <h2>📊 General Training Tests Overview</h2>
                    <div class="table-container">
                        <table class="test-table">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Questions</th>
                                    <th>Complete</th>
                                    <th>Feedback</th>
                                    <th>Linked</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {''.join(generate_gt_test_row(r) for r in sorted(gt_test_results, key=lambda x: x['test_num']))}
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="section">
                    <h2>📋 Summary</h2>
                    <div class="summary-box">
                        <h3>✅ Accomplishments</h3>
                        <ul>
                            {'<li>Test 1: <strong>40 questions</strong> ✓</li>' if any(r['student_questions'] == 40 for r in gt_test_results) else '<li>No complete tests yet</li>'}
                            {'<li>Test 1: <strong>All questions have feedback</strong> ✓</li>' if any(len(r['missing_feedback']) == 0 for r in gt_test_results) else ''}
                        </ul>
                        
                        <h3>Quality Breakdown</h3>
                        <ul>
                            <li>{'✓ EXCELLENT' if sum(1 for r in gt_test_results if len(r['missing_feedback']) == 0 and len(r['not_linked']) == 0 and r['student_questions'] == 40) > 0 else '⚠ Working on it'}: <strong>{sum(1 for r in gt_test_results if len(r['missing_feedback']) == 0 and len(r['not_linked']) == 0 and r['student_questions'] == 40)}/{len(gt_test_results)} tests</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            IELTS Preparation Course - Practice Tests Quality Dashboard<br>
            Bookmark this page for quick access to quality metrics
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {{
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            const clickedTab = Array.from(tabs).find(tab => 
                tab.getAttribute('onclick').includes(tabName)
            );
            if (clickedTab) {{
                clickedTab.classList.add('active');
            }}
        }}
    </script>
</body>
</html>
'''
    
    return html

def main():
    """Main execution"""
    # Analyze Academic Reading Tests
    academic_test_dir = '/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/Academic Read Test JSONs'
    academic_test_files = sorted(glob.glob(f'{academic_test_dir}/Academic-IELTS-Reading-Test-*.json'))
    
    print("Analyzing Academic reading tests...")
    academic_test_results = []
    for test_file in academic_test_files:
        result = analyze_test(test_file)
        academic_test_results.append(result)
        print(f"Academic Test {result['test_num']}: {result['student_questions']} questions")
    
    # Analyze General Training Reading Tests
    gt_test_dir = '/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/General Training Reading Test JSONs'
    gt_test_files = sorted(glob.glob(f'{gt_test_dir}/General Training Reading Test*.json'))
    
    print("\nAnalyzing General Training reading tests...")
    gt_test_results = []
    for test_file in gt_test_files:
        result = analyze_test(test_file)
        gt_test_results.append(result)
        print(f"General Training Test {result['test_num']}: {result['student_questions']} questions")
    
    print("\nGenerating quality dashboard HTML...")
    html_content = generate_html_dashboard(academic_test_results, gt_test_results)
    
    output_path = '/home/runner/work/ielts-preparation-course/ielts-preparation-course/main/Practice-Tests/quality-dashboard.html'
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(html_content)
    
    print(f"\n✓ Quality dashboard generated: {output_path}")
    
    # Summary
    print(f"\nAcademic Tests Summary:")
    print(f"  Total tests: {len(academic_test_results)}")
    print(f"  Complete tests (40 questions): {sum(1 for r in academic_test_results if r['student_questions'] == 40)}")
    print(f"  Incomplete tests: {len(academic_test_results) - sum(1 for r in academic_test_results if r['student_questions'] == 40)}")
    
    print(f"\nGeneral Training Tests Summary:")
    print(f"  Total tests: {len(gt_test_results)}")
    print(f"  Complete tests (40 questions): {sum(1 for r in gt_test_results if r['student_questions'] == 40)}")
    print(f"  Incomplete tests: {len(gt_test_results) - sum(1 for r in gt_test_results if r['student_questions'] == 40)}")

if __name__ == '__main__':
    main()
