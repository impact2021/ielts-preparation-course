#!/usr/bin/env php
<?php
/**
 * Fix XML files with UTF-8 encoding issues in PHP serialized data
 * 
 * This script uses PHP's native serialization handling to properly
 * fix the data without breaking the structure.
 * 
 * Usage: php fix-xml-with-php.php <input.xml> [output.xml]
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$input_file = $argc > 1 ? $argv[1] : null;
$output_file = $argc > 2 ? $argv[2] : null;

if (!$input_file) {
    echo "Usage: php fix-xml-with-php.php <input.xml> [output.xml]\n";
    exit(1);
}

if (!$output_file) {
    $output_file = preg_replace('/\.xml$/i', '', $input_file) . '-fixed.xml';
}

if (!file_exists($input_file)) {
    die("Error: File not found: $input_file\n");
}

echo "Input file:  $input_file\n";
echo "Output file: $output_file\n";
echo str_repeat("=", 70) . "\n\n";

$content = file_get_contents($input_file);

// Define UTF-8 character replacements
$replacements = [
    "\xE2\x80\x93" => '-',   // en-dash → hyphen
    "\xE2\x80\x94" => '--',  // em-dash → double hyphen
    "\xE2\x80\x98" => "'",   // left single quote → apostrophe
    "\xE2\x80\x99" => "'",   // right single quote → apostrophe
    "\xE2\x80\x9C" => '"',   // left double quote → straight quote
    "\xE2\x80\x9D" => '"',   // right double quote → straight quote
];

// Count replacements
$total_replaced = 0;
foreach ($replacements as $utf8_char => $ascii_char) {
    $count = substr_count($content, $utf8_char);
    if ($count > 0) {
        $char_names = [
            "\xE2\x80\x93" => 'en-dash',
            "\xE2\x80\x94" => 'em-dash',
            "\xE2\x80\x98" => 'left single quote',
            "\xE2\x80\x99" => 'right single quote',
            "\xE2\x80\x9C" => 'left double quote',
            "\xE2\x80\x9D" => 'right double quote',
        ];
        echo "Replacing $count {$char_names[$utf8_char]} character(s)\n";
        $content = str_replace($utf8_char, $ascii_char, $content);
        $total_replaced += $count;
    }
}

if ($total_replaced == 0) {
    echo "No problematic UTF-8 characters found.\n";
    echo "File may have other issues.\n";
    exit(1);
}

echo "\nTotal replacements: $total_replaced\n";
echo "\nFixing PHP serialized data...\n";

// Now fix the serialized data by unserializing and re-serializing
// This will automatically fix all length declarations

$fixed_content = preg_replace_callback(
    '/<wp:meta_key><!\[CDATA\[(_ielts_cm_questions|_ielts_cm_reading_texts)\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s',
    function($matches) {
        $key = $matches[1];
        $serialized = $matches[2];
        
        // Try to unserialize
        $data = @unserialize($serialized);
        
        if ($data === false && $serialized !== 'b:0;') {
            echo "  ⚠ WARNING: Cannot unserialize $key - keeping original\n";
            return $matches[0];
        }
        
        // Re-serialize to fix all string lengths
        $fixed_serialized = serialize($data);
        
        echo "  ✓ Fixed $key (" . strlen($serialized) . " → " . strlen($fixed_serialized) . " bytes)\n";
        
        return '<wp:meta_key><![CDATA[' . $key . ']]></wp:meta_value><wp:meta_value><![CDATA[' . $fixed_serialized . ']]></wp:meta_value>';
    },
    $content
);

// Save the fixed file
file_put_contents($output_file, $fixed_content);

echo "\n✓ Fixed file saved to: $output_file\n";
echo "\nPlease validate the fixed file using:\n";
echo "  php TEMPLATES/validate-xml.php \"$output_file\"\n";

exit(0);
