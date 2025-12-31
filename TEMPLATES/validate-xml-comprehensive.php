#!/usr/bin/env php
<?php
/**
 * Comprehensive XML Validator for IELTS Quiz Files
 * 
 * This script performs deep validation of XML template files including:
 * - PHP serialization syntax
 * - Question count verification
 * - Question structure validation
 * - Required fields checking
 * - CDATA formatting
 * 
 * Usage:
 *   php validate-xml-comprehensive.php <file.xml>
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$file = $argc > 1 ? $argv[1] : null;

if (!$file) {
    echo "Usage: php validate-xml-comprehensive.php <file.xml>\n";
    exit(1);
}

if (!file_exists($file)) {
    die("Error: File not found: $file\n");
}

echo "========================================================================\n";
echo "COMPREHENSIVE XML VALIDATION\n";
echo "========================================================================\n";
echo "File: $file\n\n";

$content = file_get_contents($file);
$issues = [];
$warnings = [];
$info = [];

// Check 1: CDATA spaces
echo "[1/8] Checking CDATA formatting...\n";
if (preg_match('/<!\[CDATA\[\s+/i', $content) || preg_match('/\s+\]\]>/i', $content)) {
    $issues[] = "Spaces found in CDATA sections (e.g., <![CDATA[ content ]])";
    echo "  ❌ FAIL: Spaces in CDATA sections\n";
} else {
    echo "  ✓ PASS: CDATA formatting correct\n";
}

// Check 2: Post type
echo "\n[2/8] Checking post type...\n";
if (preg_match('/<wp:post_type><!\[CDATA\[(.*?)\]\]><\/wp:post_type>/i', $content, $match)) {
    $post_type = $match[1];
    if ($post_type === 'ielts_quiz' || $post_type === 'listening_practice') {
        echo "  ✓ PASS: Post type is '$post_type'\n";
        $info['post_type'] = $post_type;
    } else {
        $warnings[] = "Unexpected post type: $post_type";
        echo "  ⚠ WARNING: Post type is '$post_type'\n";
    }
} else {
    $issues[] = "No post_type found";
    echo "  ❌ FAIL: No post_type found\n";
}

// Check 3: Required postmeta fields
echo "\n[3/8] Checking required postmeta fields...\n";
$required_fields = [
    '_ielts_cm_questions' => 'required',
    'questions' => 'required',  // Alternative field name
    '_ielts_cm_pass_percentage' => 'optional',
    '_ielts_cm_layout_type' => 'optional',
    'scoring_system' => 'optional',
    '_ielts_cm_timer_minutes' => 'optional',
];

$found_questions_key = null;
foreach (['_ielts_cm_questions', 'questions'] as $key) {
    if (preg_match('/<wp:meta_key><!\[CDATA\[' . preg_quote($key, '/') . '\]\]><\/wp:meta_key>/i', $content)) {
        echo "  ✓ Found: $key\n";
        $found_questions_key = $key;
        break;
    }
}

if (!$found_questions_key) {
    $issues[] = "Missing required field: questions or _ielts_cm_questions";
    echo "  ❌ FAIL: No questions field found\n";
}

// Check 4: PHP serialization validation
echo "\n[4/8] Validating PHP serialized data...\n";
if ($found_questions_key && preg_match('/<wp:meta_key><!\[CDATA\[' . preg_quote($found_questions_key, '/') . '\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $content, $matches)) {
    $serialized = $matches[1];
    
    // Try to unserialize
    $data = @unserialize($serialized);
    
    if ($data === false && $serialized !== 'b:0;') {
        $issues[] = "Invalid serialized data in $found_questions_key";
        echo "  ❌ FAIL: Cannot unserialize questions data\n";
        echo "      First 100 chars: " . substr($serialized, 0, 100) . "...\n";
    } else {
        echo "  ✓ PASS: Questions data is valid serialized PHP\n";
        
        if (is_array($data)) {
            $question_count = count($data);
            $info['question_count'] = $question_count;
            echo "      Question elements found: $question_count\n";
            
            // Check 5: Question count analysis
            echo "\n[5/8] Analyzing question structure...\n";
            
            // Count total question numbers (including multi-select fields)
            $total_question_numbers = 0;
            foreach ($data as $question) {
                if (is_array($question)) {
                    $type = $question['type'] ?? $question['question_type'] ?? '';
                    $question_text = $question['question'] ?? $question['question_title'] ?? '';
                    
                    // Check if it's a "Choose TWO" or "Choose THREE" question
                    if (preg_match('/choose\s+(two|three|2|3)/i', $question_text, $match)) {
                        $num_str = strtolower($match[1]);
                        $num_answers = ($num_str === 'two' || $num_str === '2') ? 2 : 3;
                        $total_question_numbers += $num_answers;
                    } else {
                        $total_question_numbers++;
                    }
                }
            }
            
            echo "      Question elements: $question_count\n";
            echo "      Question numbers covered: $total_question_numbers\n";
            
            // Detect expected question count from title/metadata
            $expected_count = null;
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $tmatch)) {
                $title = $tmatch[1];
                // Look for patterns like "40 Questions", "Complete", "All 40", etc.
                if (preg_match('/\b(\d+)\s+questions?\b/i', $title, $qmatch)) {
                    $expected_count = (int)$qmatch[1];
                } elseif (preg_match('/\ball\s+(\d+)\b/i', $title, $qmatch)) {
                    $expected_count = (int)$qmatch[1];
                } elseif (preg_match('/complete/i', $title) && preg_match('/test\s+\d+/i', $title)) {
                    $expected_count = 40; // Full IELTS listening test
                } elseif (preg_match('/section\s+\d+/i', $title)) {
                    $expected_count = 10; // Single section
                }
                
                if ($expected_count) {
                    echo "      Expected questions (from title): $expected_count\n";
                    if ($total_question_numbers !== $expected_count) {
                        $issues[] = "Question count mismatch: covers $total_question_numbers question numbers, expected $expected_count";
                        echo "      ❌ FAIL: Question numbers don't match expected\n";
                    } else {
                        echo "      ✓ PASS: Question numbers match expected ($expected_count)\n";
                    }
                } else {
                    echo "      ℹ INFO: Could not determine expected question count from title\n";
                }
            }
            
            // Check 6: Question structure validation
            echo "\n[6/8] Validating question structure...\n";
            $structure_valid = true;
            $question_types = [];
            
            foreach ($data as $idx => $question) {
                if (!is_array($question)) {
                    $issues[] = "Question at index $idx is not an array";
                    echo "      ❌ Question $idx: Not an array\n";
                    $structure_valid = false;
                    continue;
                }
                
                // Check for required question fields
                $has_type = isset($question['type']) || isset($question['question_type']);
                $has_question = isset($question['question']) || isset($question['question_title']);
                
                if (!$has_type) {
                    $warnings[] = "Question $idx missing 'type' or 'question_type' field";
                    $structure_valid = false;
                }
                
                if (!$has_question) {
                    $warnings[] = "Question $idx missing 'question' or 'question_title' field";
                    $structure_valid = false;
                }
                
                // Track question types
                $type = $question['type'] ?? $question['question_type'] ?? 'unknown';
                if (!isset($question_types[$type])) {
                    $question_types[$type] = 0;
                }
                $question_types[$type]++;
                
                // Check for feedback fields
                $feedback_fields = [
                    'correct_feedback' => 'correct_answer_feedback',
                    'incorrect_feedback' => 'incorrect_answer_feedback',
                    'no_answer_feedback' => 'no_answer_feedback'
                ];
                
                foreach ($feedback_fields as $key => $alt_key) {
                    if (!isset($question[$key]) && !isset($question[$alt_key])) {
                        $warnings[] = "Question $idx missing feedback field: $key";
                    }
                }
            }
            
            if ($structure_valid && empty($warnings)) {
                echo "      ✓ PASS: All questions have required fields\n";
            } else {
                echo "      ⚠ WARNING: Some questions missing fields\n";
            }
            
            echo "      Question types distribution:\n";
            $special_handling_info = [];
            foreach ($question_types as $type => $count) {
                echo "        - $type: $count\n";
            }
            
            // Add special info for closed_question and open_question types
            foreach ($data as $idx => $question) {
                $type = $question['type'] ?? $question['question_type'] ?? 'unknown';
                if ($type === 'closed_question' && isset($question['correct_answer_count'])) {
                    $count = intval($question['correct_answer_count']);
                    $special_handling_info[] = "Question $idx (closed): covers $count question number(s)";
                } elseif ($type === 'open_question' && isset($question['field_count'])) {
                    $count = intval($question['field_count']);
                    $special_handling_info[] = "Question $idx (open): covers $count question number(s)";
                }
            }
            
            if (!empty($special_handling_info)) {
                echo "      Special question numbering:\n";
                foreach ($special_handling_info as $msg) {
                    echo "        - $msg\n";
                }
            }
            
        } else {
            $warnings[] = "Questions data is not an array";
            echo "      ⚠ WARNING: Questions data is not an array\n";
        }
    }
} else {
    echo "  ⚠ WARNING: No questions data found to validate\n";
}

// Check 7: Starting question number
echo "\n[7/8] Checking starting question number...\n";
if (preg_match('/<wp:meta_key><!\[CDATA\[(_ielts_cm_starting_question_number|starting_question_number)\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $content, $matches)) {
    $start_num_serialized = $matches[2];
    $start_num = @unserialize($start_num_serialized);
    if ($start_num !== false) {
        echo "  ✓ Found: Starting question number is $start_num\n";
        $info['starting_number'] = $start_num;
        
        // Validate this makes sense with question count
        if (isset($info['question_count'])) {
            $end_num = $start_num + $info['question_count'] - 1;
            echo "      Question range: Q$start_num - Q$end_num\n";
        }
    }
} else {
    echo "  ℹ INFO: No starting_question_number found (will default to 1)\n";
}

// Check 8: Transcript presence
echo "\n[8/8] Checking for transcript...\n";
if (preg_match('/<wp:meta_key><!\[CDATA\[(_ielts_cm_transcript|transcript_content)\]\]><\/wp:meta_key>/i', $content)) {
    echo "  ✓ Found: Transcript included\n";
} else {
    $warnings[] = "No transcript found";
    echo "  ⚠ WARNING: No transcript found\n";
}

// Summary
echo "\n========================================================================\n";
echo "VALIDATION SUMMARY\n";
echo "========================================================================\n\n";

if (!empty($info)) {
    echo "FILE INFORMATION:\n";
    foreach ($info as $key => $value) {
        echo "  - " . ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
    }
    echo "\n";
}

if (empty($issues) && empty($warnings)) {
    echo "✅ ALL CHECKS PASSED\n";
    echo "This XML file should import successfully into WordPress.\n";
    exit(0);
}

if (!empty($issues)) {
    echo "❌ ISSUES FOUND (" . count($issues) . "):\n";
    foreach ($issues as $i => $issue) {
        echo "  " . ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
    }
    echo "\n";
}

if (!empty($issues)) {
    echo "❌ VALIDATION FAILED\n";
    echo "Please fix the issues above before importing this XML.\n";
    exit(1);
}

if (!empty($warnings)) {
    echo "⚠ VALIDATION PASSED WITH WARNINGS\n";
    echo "The file may import but could have issues. Review warnings above.\n";
    exit(0);
}

exit(0);
