#!/usr/bin/env python3
"""
Extract transcript from existing XML file and generate an annotated transcript.

Usage:
    python extract-annotated-transcript-from-xml.py "Listening Test 1 Section 1.xml"
    
This will generate:
    - Listening Test 1 Section 1-transcript.txt
"""

import re
import sys
import os
from html import unescape, escape
import xml.etree.ElementTree as ET

def parse_serialized_php(data):
    """Parse PHP serialized data (simplified parser for arrays)."""
    # This is a simplified parser - only handles what we need
    result = []
    
    # Find all summary_fields sections
    pattern = r's:14:"summary_fields";a:(\d+):{(.*?)}'
    matches = re.finditer(pattern, data, re.DOTALL)
    
    for match in matches:
        field_count = int(match.group(1))
        fields_data = match.group(2)
        
        # Extract answers from each field
        answer_pattern = r's:6:"answer";s:\d+:"([^"]+)"'
        for answer_match in re.finditer(answer_pattern, fields_data):
            answer = answer_match.group(1)
            result.append(answer)
    
    # Also check for correct_answer fields (multiple choice questions)
    mc_pattern = r's:14:"correct_answer";s:\d+:"(\d+)"'
    mc_matches = re.finditer(mc_pattern, data)
    
    # Also check for mc_options with is_correct
    option_pattern = r's:10:"is_correct";b:1;'
    
    return result

def extract_questions_from_xml(xml_content):
    """Extract questions and answers from XML file."""
    questions = []
    
    # Find the _ielts_cm_questions meta value
    pattern = r'<wp:meta_key><!\[CDATA\[_ielts_cm_questions\]\]></wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>'
    match = re.search(pattern, xml_content, re.DOTALL)
    
    if not match:
        print("Warning: No questions found in XML")
        return questions
    
    questions_data = match.group(1)
    
    # Extract summary_fields answers
    field_pattern = r's:6:"answer";s:\d+:"([^"]+)"'
    question_number = 1
    
    for answer_match in re.finditer(field_pattern, questions_data):
        answer_text = answer_match.group(1)
        # Unescape HTML entities
        answer_text = unescape(answer_text)
        
        questions.append({
            'number': question_number,
            'answer': answer_text.lower()
        })
        question_number += 1
    
    # Also extract multiple choice correct answers
    # This is more complex and would require full parsing...
    # For now, focus on summary completion questions
    
    return questions

def extract_transcript_from_xml(xml_content):
    """Extract the transcript from XML file."""
    # Find the _ielts_cm_transcript meta value
    pattern = r'<wp:meta_key><!\[CDATA\[_ielts_cm_transcript\]\]></wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>'
    match = re.search(pattern, xml_content, re.DOTALL)
    
    if match:
        transcript = match.group(1)
        # Unescape HTML entities
        transcript = unescape(transcript)
        return transcript
    
    # Also check for audio_sections
    sections_pattern = r'<wp:meta_key><!\[CDATA\[_ielts_cm_audio_sections\]\]></wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>'
    match = re.search(sections_pattern, xml_content, re.DOTALL)
    
    if match:
        sections_data = match.group(1)
        # Extract transcript from serialized data
        transcript_pattern = r's:10:"transcript";s:\d+:"([^"]*)"'
        transcript_match = re.search(transcript_pattern, sections_data)
        if transcript_match:
            transcript = transcript_match.group(1)
            transcript = unescape(transcript)
            return transcript
    
    return ""

def annotate_transcript(transcript, questions):
    """Annotate transcript with answer markers like [Q1: answer]."""
    if not transcript or not questions:
        return transcript
    
    annotated = transcript
    
    for q in questions:
        q_num = q['number']
        answer_text = q['answer']
        
        # Split by | to get all variants
        variants = answer_text.split('|')
        
        # Try each variant
        for variant in variants:
            variant = variant.strip()
            if not variant:
                continue
            
            # Check if already annotated
            if f'[Q{q_num}:' in annotated:
                break
            
            # Build regex pattern with word boundaries
            # Allow flexible spacing
            pattern_text = re.escape(variant)
            pattern_text = pattern_text.replace(r'\ ', r'\s+')
            pattern = r'\b(' + pattern_text + r')\b'
            
            # Try to find and annotate (case-insensitive)
            def replace_func(m):
                return f'<strong>[Q{q_num}: {escape(m.group(1))}]</strong>'
            
            new_annotated = re.sub(pattern, replace_func, annotated, count=1, flags=re.IGNORECASE)
            
            if new_annotated != annotated:
                annotated = new_annotated
                break
    
    return annotated

def main():
    if len(sys.argv) < 2:
        print("Usage: python extract-annotated-transcript-from-xml.py <input.xml>")
        print("Example: python extract-annotated-transcript-from-xml.py 'Listening Test 1 Section 1.xml'")
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    if not os.path.exists(input_file):
        print(f"Error: File '{input_file}' not found")
        sys.exit(1)
    
    # Read input file
    with open(input_file, 'r', encoding='utf-8') as f:
        xml_content = f.read()
    
    # Extract title from filename
    base_name = os.path.splitext(os.path.basename(input_file))[0]
    
    # Extract questions
    questions = extract_questions_from_xml(xml_content)
    print(f"Extracted {len(questions)} questions")
    
    # Extract transcript
    transcript = extract_transcript_from_xml(xml_content)
    print(f"Extracted transcript ({len(transcript)} chars)")
    
    if not transcript:
        print("Error: No transcript found in XML file")
        sys.exit(1)
    
    # Annotate transcript
    annotated_transcript = annotate_transcript(transcript, questions)
    print(f"Annotated transcript with answer markers")
    
    # Save annotated transcript file
    transcript_output = base_name + "-transcript.txt"
    with open(transcript_output, 'w', encoding='utf-8') as f:
        f.write(annotated_transcript)
    print(f"✓ Generated {transcript_output}")
    
    print("\nDone!")

if __name__ == "__main__":
    main()
