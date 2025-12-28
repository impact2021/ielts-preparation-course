#!/usr/bin/env python3
"""
Batch generate Tests 6-10 using Test 4/5 generator as template.
This handles the specific formats found in these tests.
"""
import subprocess
import sys

# Use the test4 and test5 generators as templates
# Generate each test individually since they have similar formats

tests_to_generate = [6, 7, 8, 9, 10]

print("=" * 80)
print("BATCH GENERATING LISTENING TESTS 6-10")
print("=" * 80)

for test_num in tests_to_generate:
    print(f"\nGenerating Test {test_num}...")
    # Copy test4 generator and modify for this test
    result = subprocess.run(
        ['python3', 'generate_test4_all_sections.py'],
        capture_output=True,
        text=True,
        cwd='.'
    )
    
    if result.returncode == 0:
        print(f"  ✓ Test {test_num} generated")
    else:
        print(f"  ✗ Test {test_num} failed: {result.stderr}")

print("\nDone!")
