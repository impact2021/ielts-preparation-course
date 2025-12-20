#!/usr/bin/env python3
"""
Create properly formatted section files from the complete IELTS reading test.
Each file contains questions of a single compatible format type.
"""

import re

def extract_passage(passage_num, lines):
    """Extract a reading passage by number."""
    pattern_start = rf'\[READING PASSAGE\] Reading Passage {passage_num}'
    pattern_end = r'\[END READING PASSAGE\]'
    
    start = -1
    for i, line in enumerate(lines):
        if re.search(pattern_start, line):
            start = i
            break
    
    if start == -1:
        return []
    
    for i in range(start + 1, len(lines)):
        if re.search(pattern_end, lines[i]):
            return lines[start:i+1]
    
    return []

def extract_questions(start_pattern, end_pattern, lines, skip_first_line=False):
    """Extract a range of questions."""
    start = -1
    end = -1
    
    for i, line in enumerate(lines):
        if start == -1 and re.search(start_pattern, line):
            start = i
            if not skip_first_line:
                start = i  # Include the header line
        elif start != -1 and re.search(end_pattern, line):
            end = i
            break
    
    if start == -1:
        return []
    
    if end == -1:
        end = len(lines) - 1
    
    result = lines[start:end+1] if not skip_first_line else lines[start+1:end+1]
    return result

# Read the complete file
with open('ielts-reading-test-complete.txt', 'r') as f:
    lines = [line.rstrip('\n\r') for line in f.readlines()]

# Extract passages
passage1 = extract_passage(1, lines)
passage2 = extract_passage(2, lines)
passage3 = extract_passage(3, lines)

print(f"Extracted passages:")
print(f"  Passage 1: {len(passage1)} lines")
print(f"  Passage 2: {len(passage2)} lines")
print(f"  Passage 3: {len(passage3)} lines")
print()

files_created = []

# ============================================================================
# Section 1: Questions 1-5 (Headings) - Multiple Choice with [HEADINGS] marker
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 1-5 (Headings)")
file_content.append("")
file_content.extend(passage1)
file_content.append("")

# Extract Q1-5
for i, line in enumerate(lines):
    if re.search(r'^Questions 1.*\[HEADINGS\]', line):
        # Found start, now collect until we hit Q6
        for j in range(i, len(lines)):
            if re.search(r'^Questions 6', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-1-q1-5-headings.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 1-5 (Headings)", "Multiple Choice"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 1: Questions 6-9 (True/False) - True/False format
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 6-9 (True/False/Not Given)")
file_content.append("")
file_content.extend(passage1)
file_content.append("")

# Extract Q6-9
for i, line in enumerate(lines):
    if re.search(r'^Questions 6', line) and 'HEADINGS' not in line and 'MATCHING' not in line:
        for j in range(i, len(lines)):
            if re.search(r'^Questions 10', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-1-q6-9-truefalse.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 6-9 (True/False)", "True/False"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 1: Questions 10-13 (Matching) - Multiple Choice with [MATCHING] marker
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 10-13 (Matching)")
file_content.append("")
file_content.extend(passage1)
file_content.append("")

# Extract Q10-13
for i, line in enumerate(lines):
    if re.search(r'^Questions 10.*\[MATCHING\]', line):
        for j in range(i, len(lines)):
            # Stop at Q14 or passage 2 marker
            if re.search(r'^Questions 14', lines[j]) or re.search(r'Questions 14.*based on Reading Passage 2', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-1-q10-13-matching.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 10-13 (Matching)", "Multiple Choice"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 2: Questions 14-17 (True/False) - True/False format
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 14-17 (True/False/Not Given)")
file_content.append("")
file_content.extend(passage2)
file_content.append("")

# Extract Q14-17
for i, line in enumerate(lines):
    if re.search(r'^Questions 14', line) and 'MATCHING' not in line:
        for j in range(i, len(lines)):
            if re.search(r'^Questions 18', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-2-q14-17-truefalse.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 14-17 (True/False)", "True/False"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 2: Questions 18-24 (Matching) - Multiple Choice with [MATCHING] marker
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 18-24 (Matching)")
file_content.append("")
file_content.extend(passage2)
file_content.append("")

# Extract Q18-24
for i, line in enumerate(lines):
    if re.search(r'^Questions 18.*\[MATCHING\]', line):
        for j in range(i, len(lines)):
            if re.search(r'^Questions 25', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-2-q18-24-matching.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 18-24 (Matching)", "Multiple Choice"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 2: Questions 25-26 (Short Answer) - {ANSWER} format
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 25-26 (Short Answer)")
file_content.append("")
file_content.extend(passage2)
file_content.append("")

# Extract Q25-26
for i, line in enumerate(lines):
    if re.search(r'^Questions 25', line):
        for j in range(i, len(lines)):
            # Stop at Q27 or passage 3 marker
            if re.search(r'^Questions 27', lines[j]) or re.search(r'based on Reading Passage 3', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-2-q25-26-shortanswer.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 25-26 (Short Answer)", "Short Answer"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 3: Questions 27-32 (Short Answer) - {ANSWER} format
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 27-32 (Short Answer)")
file_content.append("")
file_content.extend(passage3)
file_content.append("")

# Extract Q27-32
for i, line in enumerate(lines):
    if re.search(r'^Questions 27', line):
        for j in range(i, len(lines)):
            if re.search(r'^Questions 33', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-3-q27-32-shortanswer.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 27-32 (Short Answer)", "Short Answer"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 3: Questions 33-37 (True/False) - True/False format
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 33-37 (True/False/Not Given)")
file_content.append("")
file_content.extend(passage3)
file_content.append("")

# Extract Q33-37
for i, line in enumerate(lines):
    if re.search(r'^Questions 33', line):
        for j in range(i, len(lines)):
            if re.search(r'^Questions 38', lines[j]):
                break
            file_content.append(lines[j])
        break

filename = 'ielts-section-3-q33-37-truefalse.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 33-37 (True/False)", "True/False"))
print(f"✓ Created {filename}")

# ============================================================================
# Section 3: Questions 38-40 (Matching) - Multiple Choice with [MATCHING] marker
# ============================================================================
file_content = []
file_content.append("IELTS Reading - Questions 38-40 (Matching)")
file_content.append("")
file_content.extend(passage3)
file_content.append("")

# Extract Q38-40
for i, line in enumerate(lines):
    if re.search(r'^Questions 38.*\[MATCHING\]', line):
        for j in range(i, len(lines)):
            # This is the last section, so go to end
            if j == len(lines) - 1:
                file_content.extend(lines[i:])
                break
        break

filename = 'ielts-section-3-q38-40-matching.txt'
with open(filename, 'w') as f:
    f.write('\n'.join(file_content))
files_created.append((filename, "Questions 38-40 (Matching)", "Multiple Choice"))
print(f"✓ Created {filename}")

# ============================================================================
# Summary
# ============================================================================
print(f"\n{'='*70}")
print("SUMMARY: Created 9 importable section files")
print(f"{'='*70}")
for filename, desc, format_type in files_created:
    print(f"  {filename:45} | {desc:30} | {format_type}")
print(f"{'='*70}")
print("\nThese files can now be imported individually using the text import tool.")
print("Each file contains questions of a single compatible format type.")
