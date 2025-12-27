#!/usr/bin/env python3
"""
UTF-8 Character Fixer for IELTS XML Files

This script fixes XML files that contain problematic UTF-8 characters
(like en-dashes, em-dashes, curly quotes) in PHP serialized data.

Usage:
    python3 fix-utf8-in-xml.py <input.xml> [output.xml]

If output.xml is not specified, it will be saved as <input>-fixed.xml
"""

import sys
import re

def fix_serialized_string_lengths(data):
    """
    Fix PHP serialized string length declarations after character replacements.
    
    PHP serialization format: s:LENGTH:"string content"
    After replacing multi-byte UTF-8 chars with single-byte ASCII, the LENGTH
    declarations need to be updated to match the new byte count.
    """
    output = bytearray()
    i = 0
    
    while i < len(data):
        # Check for s:LENGTH:" pattern
        if i + 2 < len(data) and data[i:i+2] == b's:':
            # Read the length digits
            j = i + 2
            while j < len(data) and chr(data[j]).isdigit():
                j += 1
            
            # Check if followed by :"
            if j + 1 < len(data) and data[j:j+2] == b':"':
                # Find the actual string (accounting for PHP escape sequences)
                str_start = j + 2
                k = str_start
                escaped = False
                
                while k < len(data):
                    if not escaped and data[k] == ord('"'):
                        break
                    escaped = (data[k] == ord('\\')) and not escaped
                    k += 1
                
                if k < len(data):
                    # Extract and measure the actual string
                    actual_str = data[str_start:k]
                    actual_len = len(actual_str)
                    
                    # Write corrected version
                    output.extend(b's:')
                    output.extend(str(actual_len).encode('ascii'))
                    output.extend(b':"')
                    output.extend(actual_str)
                    output.extend(b'"')
                    i = k + 1
                    continue
        
        output.append(data[i])
        i += 1
    
    return bytes(output)

def fix_xml_file(input_file, output_file):
    """Fix UTF-8 characters in an XML file."""
    
    # Read the file
    with open(input_file, 'rb') as f:
        content = f.read()
    
    # Define problematic UTF-8 characters and their ASCII replacements
    replacements = {
        b'\xe2\x80\x93': b'-',   # en-dash → hyphen
        b'\xe2\x80\x94': b'--',  # em-dash → double hyphen
        b'\xe2\x80\x98': b"'",   # left single quote → apostrophe
        b'\xe2\x80\x99': b"'",   # right single quote → apostrophe
        b'\xe2\x80\x9c': b'"',   # left double quote → straight quote
        b'\xe2\x80\x9d': b'"',   # right double quote → straight quote
    }
    
    # Count replacements
    total_replaced = 0
    for utf8_char, ascii_char in replacements.items():
        count = content.count(utf8_char)
        if count > 0:
            char_name = {
                b'\xe2\x80\x93': 'en-dash',
                b'\xe2\x80\x94': 'em-dash',
                b'\xe2\x80\x98': 'left single quote',
                b'\xe2\x80\x99': 'right single quote',
                b'\xe2\x80\x9c': 'left double quote',
                b'\xe2\x80\x9d': 'right double quote',
            }[utf8_char]
            print(f"Replacing {count} {char_name} character(s)")
            content = content.replace(utf8_char, ascii_char)
            total_replaced += count
    
    if total_replaced == 0:
        print("No problematic UTF-8 characters found.")
        return False
    
    print(f"\nTotal replacements: {total_replaced}")
    
    # Fix PHP serialized data string lengths
    print("\nFixing PHP serialized string lengths...")
    
    pattern = rb'<wp:meta_value><!\[CDATA\[(a:.*?)\]\]></wp:meta_value>'
    
    def fix_match(match):
        serialized = match.group(1)
        fixed = fix_serialized_string_lengths(serialized)
        return b'<wp:meta_value><![CDATA[' + fixed + b']]></wp:meta_value>'
    
    content = re.sub(pattern, fix_match, content, flags=re.DOTALL)
    
    # Save the fixed file
    with open(output_file, 'wb') as f:
        f.write(content)
    
    print(f"\n✓ Fixed file saved to: {output_file}")
    print("\nPlease validate the fixed file using:")
    print(f"  php TEMPLATES/validate-xml.php \"{output_file}\"")
    
    return True

def main():
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    if len(sys.argv) > 2:
        output_file = sys.argv[2]
    else:
        # Generate output filename
        if input_file.endswith('.xml'):
            output_file = input_file[:-4] + '-fixed.xml'
        else:
            output_file = input_file + '-fixed.xml'
    
    print(f"Input file:  {input_file}")
    print(f"Output file: {output_file}")
    print("=" * 70)
    print()
    
    try:
        success = fix_xml_file(input_file, output_file)
        sys.exit(0 if success else 1)
    except FileNotFoundError:
        print(f"Error: File not found: {input_file}")
        sys.exit(1)
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()
