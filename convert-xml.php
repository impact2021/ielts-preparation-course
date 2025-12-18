<?php
/**
 * Convert LearnDash XML export to IELTS Course Manager format
 * 
 * This script converts sfwd-question post types to ielts_quiz post types
 * in the WordPress XML export file.
 * 
 * Usage: php convert-xml.php
 */

// File paths
$input_file = 'ieltstestonline.WordPress.2025-12-17.xml';
$output_file = 'ieltstestonline.WordPress.2025-12-17-converted.xml';

// Check if input file exists
if (!file_exists($input_file)) {
    die("Error: Input file '{$input_file}' not found.\n");
}

echo "Starting XML conversion...\n";
echo "Input file: {$input_file}\n";
echo "Output file: {$output_file}\n\n";

// Load the XML file
libxml_use_internal_errors(true);
$xml = simplexml_load_file($input_file);

if ($xml === false) {
    echo "Error loading XML:\n";
    foreach (libxml_get_errors() as $error) {
        echo "  - {$error->message}\n";
    }
    libxml_clear_errors();
    die("Failed to load XML file.\n");
}

echo "XML file loaded successfully.\n";

// Register namespaces
$namespaces = $xml->getNamespaces(true);
$xml->registerXPathNamespace('wp', $namespaces['wp']);
$xml->registerXPathNamespace('content', $namespaces['content']);

// Find all items with sfwd-question post type
$items = $xml->xpath('//item');
$total_items = count($items);
$converted_count = 0;
$skipped_count = 0;

echo "Total items found: {$total_items}\n";
echo "Processing items...\n\n";

foreach ($items as $item) {
    $wp_children = $item->children($namespaces['wp']);
    $post_type = (string)$wp_children->post_type;
    
    // Only process sfwd-question items
    if ($post_type !== 'sfwd-question') {
        $skipped_count++;
        continue;
    }
    
    $title = (string)$item->title;
    $post_id = (string)$wp_children->post_id;
    
    // Change the post type to ielts_quiz
    $wp_children->post_type = 'ielts_quiz';
    
    // Update the slug to match new format
    $wp_children->post_name = sanitize_title_for_slug($wp_children->post_name);
    
    // Update the link to use new slug pattern
    $old_link = (string)$item->link;
    $new_link = str_replace('/sfwd-question/', '/ielts-quiz/', $old_link);
    $item->link = $new_link;
    
    // Update GUID
    $old_guid = (string)$item->guid;
    $new_guid = str_replace('/sfwd-question/', '/ielts-quiz/', $old_guid);
    $item->guid = $new_guid;
    
    $converted_count++;
    
    if ($converted_count % 100 == 0) {
        echo "Converted {$converted_count} questions...\n";
    }
}

echo "\nConversion complete!\n";
echo "Converted: {$converted_count} sfwd-question items to ielts_quiz\n";
echo "Skipped: {$skipped_count} items (other post types)\n";

// Save the modified XML
$xml_string = $xml->asXML();

// Save to output file
if (file_put_contents($output_file, $xml_string)) {
    echo "\nOutput saved to: {$output_file}\n";
    $input_size = filesize($input_file);
    $output_size = filesize($output_file);
    echo "Input file size: " . format_bytes($input_size) . "\n";
    echo "Output file size: " . format_bytes($output_size) . "\n";
} else {
    die("Error: Failed to save output file.\n");
}

echo "\n✓ Conversion successful!\n";
echo "\nYou can now import '{$output_file}' into IELTS Course Manager.\n";

/**
 * Helper function to sanitize title for slug
 */
function sanitize_title_for_slug($title) {
    $title = strtolower($title);
    $title = preg_replace('/[^a-z0-9\-]/', '-', $title);
    $title = preg_replace('/-+/', '-', $title);
    $title = trim($title, '-');
    return $title;
}

/**
 * Helper function to format bytes
 */
function format_bytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
