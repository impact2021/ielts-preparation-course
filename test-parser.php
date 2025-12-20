<?php
/**
 * Test script to verify the mixed format parser works correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define WordPress constants
define('ABSPATH', __DIR__ . '/');

// Minimal WordPress functions stubs for testing
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return trim(strip_tags($str)); }
}
if (!function_exists('wp_kses_post')) {
    function wp_kses_post($str) { return $str; }
}

// Include the text exercises creator class
require_once __DIR__ . '/includes/admin/class-text-exercises-creator.php';

// Read the test file
$file_path = __DIR__ . '/ielts-reading-test-complete.txt';
if (!file_exists($file_path)) {
    die("ERROR: File not found: $file_path\n");
}

$text = file_get_contents($file_path);

echo "=== Testing IELTS Reading Test Complete Parser ===\n\n";
echo "File size: " . strlen($text) . " bytes\n";
echo "File lines: " . substr_count($text, "\n") . "\n\n";

// Create an instance of the parser
$creator = new IELTS_CM_Text_Exercises_Creator();

// Use reflection to access the private methods
$reflection = new ReflectionClass($creator);

// Test mixed format detection
$is_mixed_method = $reflection->getMethod('is_mixed_format');
$is_mixed_method->setAccessible(true);
$is_mixed = $is_mixed_method->invoke($creator, $text);

echo "Mixed format detected: " . ($is_mixed ? "YES" : "NO") . "\n\n";

// Parse the text
$parse_method = $reflection->getMethod('parse_exercise_text');
$parse_method->setAccessible(true);

try {
    $parsed = $parse_method->invoke($creator, $text);
    
    if ($parsed === null) {
        echo "ERROR: Parser returned null\n";
        exit(1);
    }
    
    echo "SUCCESS: File parsed successfully!\n\n";
    echo "Title: " . $parsed['title'] . "\n";
    echo "Question count: " . count($parsed['questions']) . "\n";
    echo "Reading passages: " . count($parsed['reading_texts']) . "\n\n";
    
    // Count questions by type
    $types = array();
    foreach ($parsed['questions'] as $q) {
        $type = $q['type'];
        if (!isset($types[$type])) {
            $types[$type] = 0;
        }
        $types[$type]++;
    }
    
    echo "Questions by type:\n";
    foreach ($types as $type => $count) {
        echo "  - $type: $count\n";
    }
    
    echo "\n";
    
    // Show reading passage titles
    if (!empty($parsed['reading_texts'])) {
        echo "Reading passages found:\n";
        foreach ($parsed['reading_texts'] as $idx => $passage) {
            $title = !empty($passage['title']) ? $passage['title'] : "(no title)";
            $content_len = strlen($passage['content']);
            echo "  " . ($idx + 1) . ". $title ($content_len chars)\n";
        }
    }
    
    echo "\n";
    
    // Check for headings questions
    $headings_count = 0;
    $matching_count = 0;
    $true_false_count = 0;
    $short_answer_count = 0;
    
    foreach ($parsed['questions'] as $q) {
        if ($q['type'] === 'headings') {
            $headings_count++;
        } elseif ($q['type'] === 'matching_classifying') {
            $matching_count++;
        } elseif ($q['type'] === 'true_false') {
            $true_false_count++;
        } elseif ($q['type'] === 'short_answer') {
            $short_answer_count++;
        }
    }
    
    echo "Question type breakdown:\n";
    echo "  Headings: $headings_count\n";
    echo "  Matching: $matching_count\n";
    echo "  True/False: $true_false_count\n";
    echo "  Short Answer: $short_answer_count\n";
    echo "\n";
    
    // Verify expectations
    $total = count($parsed['questions']);
    $all_checks_passed = true;
    
    if ($total === 40) {
        echo "✓ All 40 questions present!\n";
    } else {
        echo "✗ WARNING: Expected 40 questions, got $total\n";
        $all_checks_passed = false;
    }
    
    if ($headings_count === 5) {
        echo "✓ All 5 headings questions found!\n";
    } else {
        echo "✗ WARNING: Expected 5 headings questions, got $headings_count\n";
        $all_checks_passed = false;
    }
    
    if ($matching_count === 14) {
        echo "✓ All 14 matching questions found!\n";
    } else {
        echo "✗ WARNING: Expected 14 matching questions, got $matching_count\n";
        $all_checks_passed = false;
    }
    
    if ($true_false_count === 13) {
        echo "✓ All 13 true/false questions found!\n";
    } else {
        echo "✗ WARNING: Expected 13 true/false questions, got $true_false_count\n";
        $all_checks_passed = false;
    }
    
    if ($short_answer_count === 8) {
        echo "✓ All 8 short answer questions found!\n";
    } else {
        echo "✗ WARNING: Expected 8 short answer questions, got $short_answer_count\n";
        $all_checks_passed = false;
    }
    
    if (count($parsed['reading_texts']) === 3) {
        echo "✓ All 3 reading passages present!\n";
    } else {
        echo "✗ WARNING: Expected 3 reading passages, got " . count($parsed['reading_texts']) . "\n";
        $all_checks_passed = false;
    }
    
    echo "\n=== Test Complete ===\n";
    
    if ($all_checks_passed) {
        echo "RESULT: ALL CHECKS PASSED ✓\n";
        exit(0);
    } else {
        echo "RESULT: SOME CHECKS FAILED ✗\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: Exception during parsing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
