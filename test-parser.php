<?php
/**
 * Test script to verify the text file parses correctly
 */

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

// Use reflection to access the private parse method
$reflection = new ReflectionClass($creator);
$method = $reflection->getMethod('parse_exercise_text');
$method->setAccessible(true);

try {
    $parsed = $method->invoke($creator, $text);
    
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
    foreach ($parsed['questions'] as $q) {
        if ($q['type'] === 'headings') {
            $headings_count++;
        }
    }
    
    if ($headings_count > 0) {
        echo "✓ Headings questions found: $headings_count\n";
    } else {
        echo "✗ WARNING: No headings questions found!\n";
    }
    
    // Verify we have 40 questions
    $total = count($parsed['questions']);
    if ($total === 40) {
        echo "✓ All 40 questions present!\n";
    } else {
        echo "✗ WARNING: Expected 40 questions, got $total\n";
    }
    
    // Verify reading passages match questions
    if (count($parsed['reading_texts']) === 3) {
        echo "✓ All 3 reading passages present!\n";
    } else {
        echo "✗ WARNING: Expected 3 reading passages, got " . count($parsed['reading_texts']) . "\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
    // Exit with success if all checks passed
    if ($total === 40 && $headings_count > 0 && count($parsed['reading_texts']) === 3) {
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
