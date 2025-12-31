#!/usr/bin/env python3
"""
Combine multiple section XML files into a single complete test XML
"""

import sys
import re
import xml.etree.ElementTree as ET
from pathlib import Path

def extract_questions_array(xml_content):
    """Extract the serialized questions array from XML content"""
    match = re.search(
        r'<wp:meta_key><!\[CDATA\[_ielts_cm_questions\]\]></wp:meta_key>\s*'
        r'<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>',
        xml_content,
        re.DOTALL
    )
    if match:
        return match.group(1)
    return None

def extract_transcript(xml_content):
    """Extract transcript from XML"""
    match = re.search(
        r'<wp:meta_key><!\[CDATA\[_ielts_cm_transcript\]\]></wp:meta_key>\s*'
        r'<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>',
        xml_content,
        re.DOTALL
    )
    if match:
        return match.group(1)
    return ""

def combine_sections(section_files, output_file, test_name):
    """Combine multiple section files into one complete test"""
    
    all_questions = []
    all_transcripts = []
    
    for i, section_file in enumerate(section_files, 1):
        print(f"Processing {section_file}...")
        
        with open(section_file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Extract questions
        questions_str = extract_questions_array(content)
        if questions_str:
            # Parse the PHP serialized array
            import phpserialize
            questions = phpserialize.loads(questions_str.encode('utf-8'), decode_strings=True)
            all_questions.extend(questions)
            print(f"  - Found {len(questions)} questions")
        else:
            print(f"  - WARNING: No questions found")
        
        # Extract transcript
        transcript = extract_transcript(content)
        if transcript:
            all_transcripts.append(f"<strong>SECTION {i}</strong>\n{transcript}")
    
    print(f"\nTotal questions: {len(all_questions)}")
    
    # Reindex questions from 0 to n-1
    reindexed = {}
    for idx, question in enumerate(all_questions):
        # Ensure question is a dict (associative array in PHP)
        if isinstance(question, dict):
            reindexed[idx] = question
        else:
            print(f"  WARNING: Question {idx} is not a dict: {type(question)}")
            reindexed[idx] = question
    
    # Serialize combined questions using object_hook to preserve associative arrays
    import phpserialize
    combined_serialized = phpserialize.dumps(reindexed, object_hook=phpserialize.phpobject).decode('utf-8')
    
    # Combine transcripts
    combined_transcript = "\n\n<hr>\n\n".join(all_transcripts)
    
    # Create XML structure
    xml_template = '''<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <item>
      <title>{title}</title>
      <wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>
      <wp:status><![CDATA[publish]]></wp:status>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:60;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>
        <wp:meta_value><![CDATA[listening_practice]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:40;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:1;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
        <wp:meta_value><![CDATA[{questions}]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_transcript]]></wp:meta_key>
        <wp:meta_value><![CDATA[{transcript}]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>
        <wp:meta_value><![CDATA[a:0:{{}}]]></wp:meta_value>
      </wp:postmeta>
    </item>
  </channel>
</rss>'''
    
    xml_content = xml_template.format(
        title=test_name,
        questions=combined_serialized,
        transcript=combined_transcript
    )
    
    # Write output
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(xml_content)
    
    print(f"\n✓ Combined XML written to: {output_file}")
    print(f"  Total questions: {len(all_questions)}")
    print(f"  Array declaration: a:{len(all_questions)}:{{")

if __name__ == '__main__':
    # Install phpserialize if needed
    try:
        import phpserialize
    except ImportError:
        print("Installing phpserialize...")
        import subprocess
        subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'phpserialize', '-q'])
        import phpserialize
    
    # Test 6 sections
    base_dir = Path(__file__).parent
    sections = [
        base_dir / "Listening Test 6 Section 1.xml",
        base_dir / "Listening Test 6 Section 2.xml",
        base_dir / "Listening Test 6 Section 3.xml",
        base_dir / "Listening Test 6 Section 4.xml",
    ]
    
    output = base_dir / "Listening-Test-6-Complete-FIXED.xml"
    
    combine_sections(sections, output, "Listening Test 6 - Complete (All 40 Questions)")
