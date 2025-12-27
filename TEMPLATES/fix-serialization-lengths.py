#!/usr/bin/env python3
"""
Fix PHP serialization string length issues in XML files

This script fixes broken PHP serialized data where string length declarations
don't match the actual string lengths, causing unserialize() to fail.
"""

import sys
import re
import argparse


def fix_serialization(serialized_data):
    """
    Fix string length declarations in PHP serialized data.
    
    PHP serialized format for strings: s:LENGTH:"content"
    This function recalculates all LENGTH values to match actual string lengths.
    """
    def replace_string_length(match):
        # Extract the declared length and the string content
        declared_length = match.group(1)
        string_content = match.group(2)
        
        # Calculate actual byte length
        actual_length = len(string_content.encode('utf-8'))
        
        # If lengths don't match, report and fix
        if int(declared_length) != actual_length:
            print(f"    Fixing: s:{declared_length}: -> s:{actual_length}: (string starts with: {string_content[:30]}...)")
        
        # Return with corrected length
        return f's:{actual_length}:"{string_content}"'
    
    # Pattern to match PHP serialized strings: s:LENGTH:"content"
    # We need to be careful to match the correct closing quote
    pattern = r's:(\d+):"((?:[^"\\]|\\.)*)\"'
    
    fixed_data = re.sub(pattern, replace_string_length, serialized_data)
    
    return fixed_data


def fix_xml_file(input_file, output_file):
    """Fix serialization issues in an XML file."""
    print(f"Input file:  {input_file}")
    print(f"Output file: {output_file}")
    print("=" * 70)
    print()
    
    # Read the input file
    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Find and fix all _ielts_cm_questions sections
    def fix_meta_value(match):
        meta_key = match.group(1)
        serialized_data = match.group(2)
        
        print(f"Processing {meta_key}...")
        
        fixed_data = fix_serialization(serialized_data)
        
        if fixed_data == serialized_data:
            print(f"  ✓ No changes needed for {meta_key}")
        else:
            print(f"  ✓ Fixed {meta_key}")
        
        return f'<wp:meta_key><![CDATA[{meta_key}]]></wp:meta_key>\n<wp:meta_value><![CDATA[{fixed_data}]]></wp:meta_value>'
    
    # Pattern to match meta_key and meta_value pairs
    pattern = r'<wp:meta_key><!\[CDATA\[(_ielts_cm_questions|_ielts_cm_reading_texts)\]\]></wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]></wp:meta_value>'
    
    fixed_content = re.sub(pattern, fix_meta_value, content, flags=re.DOTALL)
    
    # Write the output file
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(fixed_content)
    
    print()
    print(f"✓ Fixed file saved to: {output_file}")
    print()
    print("Please validate the fixed file using:")
    print(f"  php TEMPLATES/validate-xml.php \"{output_file}\"")


def main():
    parser = argparse.ArgumentParser(
        description='Fix PHP serialization string length issues in XML files'
    )
    parser.add_argument('input', help='Input XML file')
    parser.add_argument('output', nargs='?', help='Output XML file (default: input-fixed.xml)')
    
    args = parser.parse_args()
    
    input_file = args.input
    output_file = args.output
    
    if not output_file:
        if input_file.endswith('.xml'):
            output_file = input_file[:-4] + '-fixed.xml'
        else:
            output_file = input_file + '-fixed.xml'
    
    try:
        fix_xml_file(input_file, output_file)
        return 0
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1


if __name__ == '__main__':
    sys.exit(main())
