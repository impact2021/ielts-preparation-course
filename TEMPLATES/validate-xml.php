#!/usr/bin/env php
<?php
/**
 * XML Template Validator for IELTS Quiz Files
 * 
 * This script validates XML template files and checks for common issues
 * that cause "No questions available" errors.
 * 
 * Usage:
 *   php validate-xml.php <file.xml> [--fix]
 * 
 * Options:
 *   --fix    Automatically fix issues and save to <file>-fixed.xml
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$file = $argv[1] ?? null;
$fix = in_array('--fix', $argv);

if (!$file) {
    echo "Usage: php validate-xml.php <file.xml> [--fix]\n";
    echo "\nOptions:\n";
    echo "  --fix    Automatically fix issues and save to <file>-fixed.xml\n";
    exit(1);
}

if (!file_exists($file)) {
    die("Error: File not found: $file\n");
}

echo "Validating: $file\n";
echo str_repeat("=", 70) . "\n\n";

$content = file_get_contents($file);
$issues = [];
$warnings = [];
$fixed_content = $content;

// Check 1: CDATA spaces
echo "[1/4] Checking for spaces in CDATA sections...\n";
if (preg_match('/<!\[CDATA\[\s+/i', $content) || preg_match('/\s+\]\]>/i', $content)) {
    $issues[] = "Found spaces inside CDATA sections (e.g., <![CDATA[ content ]])";
    echo "  ❌ FAIL: Spaces found in CDATA sections\n";
    
    if ($fix) {
        // Fix: Remove spaces after <![CDATA[ and before ]]>
        $fixed_content = preg_replace('/<!\[CDATA\[\s+/', '<![CDATA[', $fixed_content);
        $fixed_content = preg_replace('/\s+\]\]>/', ']]>', $fixed_content);
        echo "  ✓ Fixed: Removed spaces from CDATA sections\n";
    }
} else {
    echo "  ✓ PASS: No spaces in CDATA sections\n";
}

// Check 2: PHP serialized data validation
echo "\n[2/4] Validating PHP serialized data...\n";
if (preg_match_all('/<wp:meta_key><!\[CDATA\[(_ielts_cm_questions|_ielts_cm_reading_texts)\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $fixed_content, $matches)) {
    foreach ($matches[1] as $i => $key) {
        $serialized = $matches[2][$i];
        $data = @unserialize($serialized);
        
        if ($data === false && $serialized !== 'b:0;') {
            $issues[] = "Invalid serialized data in $key";
            echo "  ❌ FAIL: Cannot unserialize $key\n";
            echo "      First 100 chars: " . substr($serialized, 0, 100) . "...\n";
        } else {
            echo "  ✓ PASS: $key is valid serialized data\n";
            if (is_array($data)) {
                echo "      Contains " . count($data) . " items\n";
            }
        }
    }
} else {
    $warnings[] = "No _ielts_cm_questions or _ielts_cm_reading_texts found";
    echo "  ⚠ WARNING: No quiz data found in XML\n";
}

// Check 3: Required post meta fields
echo "\n[3/4] Checking for required postmeta fields...\n";
$required_fields = [
    '_ielts_cm_questions',
    '_ielts_cm_pass_percentage',
    '_ielts_cm_layout_type',
    '_ielts_cm_timer_minutes',
];

foreach ($required_fields as $field) {
    if (preg_match('/<wp:meta_key><!\[CDATA\[' . preg_quote($field, '/') . '\]\]><\/wp:meta_key>/i', $fixed_content)) {
        echo "  ✓ Found: $field\n";
    } else {
        $warnings[] = "Missing optional/required field: $field";
        echo "  ⚠ WARNING: Missing $field\n";
    }
}

// Check 4: Post type
echo "\n[4/4] Checking post type...\n";
if (preg_match('/<wp:post_type><!\[CDATA\[(.*?)\]\]><\/wp:post_type>/i', $fixed_content, $match)) {
    $post_type = $match[1];
    if ($post_type === 'ielts_quiz') {
        echo "  ✓ PASS: Correct post type (ielts_quiz)\n";
    } else {
        $warnings[] = "Unexpected post type: $post_type (expected: ielts_quiz)";
        echo "  ⚠ WARNING: Post type is '$post_type' (expected 'ielts_quiz')\n";
    }
} else {
    $issues[] = "No post_type found";
    echo "  ❌ FAIL: No post_type found\n";
}

// Summary
echo "\n" . str_repeat("=", 70) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat("=", 70) . "\n\n";

if (empty($issues) && empty($warnings)) {
    echo "✓ ALL CHECKS PASSED\n";
    echo "This XML file should import successfully.\n";
    exit(0);
}

if (!empty($issues)) {
    echo "ISSUES FOUND (" . count($issues) . "):\n";
    foreach ($issues as $i => $issue) {
        echo "  " . ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "  " . ($i + 1) . ". $warning\n";
    }
    echo "\n";
}

if ($fix && !empty($issues)) {
    $output_file = preg_replace('/\.xml$/', '-fixed.xml', $file);
    file_put_contents($output_file, $fixed_content);
    echo "✓ FIXED VERSION SAVED TO: $output_file\n";
    echo "\nPlease re-run validation on the fixed file to confirm all issues are resolved.\n";
    exit(0);
}

if (!empty($issues)) {
    echo "❌ VALIDATION FAILED\n";
    echo "\nTo automatically fix issues, run:\n";
    echo "  php " . $argv[0] . " $file --fix\n";
    exit(1);
}

exit(0);
