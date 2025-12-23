#!/usr/bin/env php
<?php
/**
 * Test script to verify summary completion export includes feedback fields
 */

echo "\n=== Testing Summary Completion Export Transformation ===\n\n";

// Mock __() function since we're not in WordPress
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

// Define the transformation function inline to test the logic
function test_transform_summary_table_group($group, $type) {
    // Default feedback message
    $default_no_answer_feedback = "In the IELTS test, you should always take a guess. You don't lose points for a wrong answer.";
    
    if (count($group) === 1 && isset($group[0]['summary_fields'])) {
        // Already in correct format - ensure all feedback fields have defaults if empty
        $question = $group[0];
        
        // Set default no_answer_feedback at question level if empty
        if (!isset($question['no_answer_feedback']) || $question['no_answer_feedback'] === '') {
            $question['no_answer_feedback'] = $default_no_answer_feedback;
        }
        
        // Set defaults for each summary field if empty
        if (isset($question['summary_fields']) && is_array($question['summary_fields'])) {
            foreach ($question['summary_fields'] as $field_num => $field_data) {
                if (!isset($field_data['no_answer_feedback']) || $field_data['no_answer_feedback'] === '') {
                    $question['summary_fields'][$field_num]['no_answer_feedback'] = $default_no_answer_feedback;
                }
                // Ensure other feedback fields exist (even if empty)
                if (!isset($field_data['correct_feedback'])) {
                    $question['summary_fields'][$field_num]['correct_feedback'] = '';
                }
                if (!isset($field_data['incorrect_feedback'])) {
                    $question['summary_fields'][$field_num]['incorrect_feedback'] = '';
                }
            }
        }
        
        return $question;
    }
    
    return array();
}

// Create a mock question with summary_fields (like what's stored in the database)
$test_question = array(
    'type' => 'summary_completion',
    'instructions' => 'Complete the summary below.',
    'question' => 'Electric vehicles use [field 1] instead of gasoline. Battery costs have fallen by [field 2] percent since 2010.',
    'points' => 1,
    'no_answer_feedback' => '', // Empty - should be filled with default
    'correct_feedback' => '',
    'incorrect_feedback' => '',
    'reading_text_id' => 0,
    'summary_fields' => array(
        1 => array(
            'answer' => 'RECHARGEABLE BATTERIES|BATTERIES',
            'correct_feedback' => '',  // Empty - should stay empty
            'incorrect_feedback' => '', // Empty - should stay empty
            'no_answer_feedback' => ''  // Empty - should be filled with default
        ),
        2 => array(
            'answer' => '80',
            'correct_feedback' => '',  // Empty - should stay empty
            'incorrect_feedback' => '', // Empty - should stay empty
            'no_answer_feedback' => ''  // Empty - should be filled with default
        )
    )
);

// Transform the question (as a group of 1)
$transformed = test_transform_summary_table_group(array($test_question), 'summary_completion');

// Display results
$expected = "In the IELTS test, you should always take a guess. You don't lose points for a wrong answer.";

echo "Question-level no_answer_feedback:\n";
echo "  Value: " . (empty($transformed['no_answer_feedback']) ? '(empty)' : $transformed['no_answer_feedback']) . "\n";
echo "  Expected: '$expected'\n";
echo "  Result: " . ($transformed['no_answer_feedback'] === $expected ? "✓ PASS" : "✗ FAIL") . "\n\n";

foreach ($transformed['summary_fields'] as $field_num => $field_data) {
    echo "Field $field_num:\n";
    echo "  Answer: " . $field_data['answer'] . "\n";
    echo "  correct_feedback: " . (empty($field_data['correct_feedback']) ? '(empty)' : $field_data['correct_feedback']) . "\n";
    echo "  incorrect_feedback: " . (empty($field_data['incorrect_feedback']) ? '(empty)' : $field_data['incorrect_feedback']) . "\n";
    echo "  no_answer_feedback: " . (empty($field_data['no_answer_feedback']) ? '(empty)' : $field_data['no_answer_feedback']) . "\n";
    echo "  Result: " . ($field_data['no_answer_feedback'] === $expected ? "✓ PASS" : "✗ FAIL") . "\n\n";
}

echo "=== Test Complete ===\n\n";
