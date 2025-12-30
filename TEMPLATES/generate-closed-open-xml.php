#!/usr/bin/env php
<?php
/**
 * XML Generator for Closed and Open Question Types
 * 
 * This script generates WordPress WXR XML files for IELTS listening/reading exercises
 * using the simplified Closed and Open question types.
 * 
 * Usage: php generate-closed-open-xml.php
 */

// Configuration
$exercise_title = "Sample Exercise - Closed and Open Questions";
$exercise_content = "This is a sample exercise demonstrating the new question types.";
$starting_question_number = 1;
$audio_url = ""; // Optional: add audio URL for listening tests
$transcript = ""; // Optional: add transcript for listening tests

// Define questions using the new types
$questions = array();

// Example 1: Closed Question with 1 correct answer (single-select, covers 1 question number)
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose the correct answer.',
    'question' => 'What is the capital of France?',
    'mc_options' => array(
        array('text' => 'London', 'is_correct' => false),
        array('text' => 'Berlin', 'is_correct' => false),
        array('text' => 'Paris', 'is_correct' => true),
        array('text' => 'Madrid', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '2', // Index of correct option (0-based)
    'correct_feedback' => 'Well done! Paris is the capital of France.',
    'incorrect_feedback' => 'Not quite. Paris is the capital of France.',
    'no_answer_feedback' => 'Please select an answer. The correct answer is Paris.',
    'points' => 1
);

// Example 2: Closed Question with 2 correct answers (multi-select, covers 2 question numbers)
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose TWO letters A-E.',
    'question' => 'Which TWO of the following are European countries?',
    'mc_options' => array(
        array('text' => 'Japan', 'is_correct' => false),
        array('text' => 'Germany', 'is_correct' => true),
        array('text' => 'Brazil', 'is_correct' => false),
        array('text' => 'Italy', 'is_correct' => true),
        array('text' => 'Australia', 'is_correct' => false)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '1|3', // Pipe-separated indices of correct options
    'correct_feedback' => 'Excellent! Germany and Italy are both European countries.',
    'incorrect_feedback' => 'Not quite. The correct answers are Germany and Italy.',
    'no_answer_feedback' => 'Please select two answers. Germany and Italy are both European countries.',
    'points' => 2
);

// Example 3: Open Question with 3 input fields (covers 3 question numbers)
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Complete the sentences using NO MORE THAN TWO WORDS.',
    'question' => 'Fill in the blanks about the solar system.',
    'field_count' => 3,
    'field_labels' => array(
        'The largest planet in our solar system is ______.',
        'Earth has ______ moon(s).',
        'The Sun is a ______.'
    ),
    'field_answers' => array(
        'Jupiter',
        'one|1',
        'star'
    ),
    'correct_feedback' => 'Well done! Your answer is correct.',
    'incorrect_feedback' => 'Not quite. Please try again.',
    'no_answer_feedback' => 'Please provide an answer.',
    'points' => 3
);

// Example 4: Open Question with 1 input field (covers 1 question number)
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Write ONE WORD ONLY.',
    'question' => 'What is the opposite of hot?',
    'field_count' => 1,
    'field_labels' => array('Answer:'),
    'field_answers' => array('cold|cool'),
    'correct_feedback' => 'Correct! Cold is the opposite of hot.',
    'incorrect_feedback' => 'Not quite. The answer is "cold".',
    'no_answer_feedback' => 'Please provide an answer. The correct answer is "cold".',
    'points' => 1
);

// Generate the XML
function generate_xml($title, $content, $questions, $starting_question_number = 1, $audio_url = '', $transcript = '') {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- WordPress eXtended RSS file for IELTS Course Manager -->' . "\n";
    $xml .= '<!-- Generated on ' . date('Y-m-d H:i:s') . ' -->' . "\n";
    $xml .= '<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= "\t<title>IELTS Course</title>\n";
    $xml .= "\t<link>http://example.com</link>\n";
    $xml .= "\t<description>IELTS Preparation Course</description>\n";
    $xml .= "\t<pubDate>" . date('r') . "</pubDate>\n";
    $xml .= "\t<language>en</language>\n";
    $xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
    $xml .= "\t<wp:base_site_url>http://example.com</wp:base_site_url>\n";
    $xml .= "\t<wp:base_blog_url>http://example.com</wp:base_blog_url>\n\n";
    
    $xml .= "\t<generator>IELTS Course Manager - Closed/Open Question Generator</generator>\n\n";
    
    $xml .= "\t<item>\n";
    $xml .= "\t\t<title><![CDATA[" . $title . "]]></title>\n";
    $xml .= "\t\t<link>http://example.com/exercise</link>\n";
    $xml .= "\t\t<pubDate>" . date('r') . "</pubDate>\n";
    $xml .= "\t\t<dc:creator><![CDATA[admin]]></dc:creator>\n";
    $xml .= "\t\t<guid isPermaLink=\"false\">http://example.com/?post_type=ielts_quiz&#038;p=1</guid>\n";
    $xml .= "\t\t<description/>\n";
    $xml .= "\t\t<content:encoded><![CDATA[" . $content . "]]></content:encoded>\n";
    $xml .= "\t\t<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n";
    $xml .= "\t\t<wp:post_id>1</wp:post_id>\n";
    $xml .= "\t\t<wp:post_date><![CDATA[" . date('Y-m-d H:i:s') . "]]></wp:post_date>\n";
    $xml .= "\t\t<wp:post_date_gmt><![CDATA[" . gmdate('Y-m-d H:i:s') . "]]></wp:post_date_gmt>\n";
    $xml .= "\t\t<wp:post_modified><![CDATA[" . date('Y-m-d H:i:s') . "]]></wp:post_modified>\n";
    $xml .= "\t\t<wp:post_modified_gmt><![CDATA[" . gmdate('Y-m-d H:i:s') . "]]></wp:post_modified_gmt>\n";
    $xml .= "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n";
    $xml .= "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n";
    $xml .= "\t\t<wp:post_name><![CDATA[sample-exercise]]></wp:post_name>\n";
    $xml .= "\t\t<wp:status><![CDATA[publish]]></wp:status>\n";
    $xml .= "\t\t<wp:post_parent>0</wp:post_parent>\n";
    $xml .= "\t\t<wp:menu_order>0</wp:menu_order>\n";
    $xml .= "\t\t<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>\n";
    $xml .= "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n";
    $xml .= "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
    
    // Add questions metadata
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[ _ielts_cm_questions ]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . serialize($questions) . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add starting question number
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[ _ielts_cm_starting_question_number ]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . $starting_question_number . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add audio URL if provided
    if (!empty($audio_url)) {
        $xml .= "\t\t<wp:postmeta>\n";
        $xml .= "\t\t\t<wp:meta_key><![CDATA[ _ielts_cm_audio_url ]]></wp:meta_key>\n";
        $xml .= "\t\t\t<wp:meta_value><![CDATA[" . $audio_url . "]]></wp:meta_value>\n";
        $xml .= "\t\t</wp:postmeta>\n";
    }
    
    // Add transcript if provided
    if (!empty($transcript)) {
        $xml .= "\t\t<wp:postmeta>\n";
        $xml .= "\t\t\t<wp:meta_key><![CDATA[ _ielts_cm_transcript ]]></wp:meta_key>\n";
        $xml .= "\t\t\t<wp:meta_value><![CDATA[" . $transcript . "]]></wp:meta_value>\n";
        $xml .= "\t\t</wp:postmeta>\n";
    }
    
    $xml .= "\t</item>\n";
    $xml .= "</channel>\n";
    $xml .= "</rss>";
    
    return $xml;
}

// Generate and save the XML
$xml = generate_xml($exercise_title, $exercise_content, $questions, $starting_question_number, $audio_url, $transcript);

$filename = 'sample-closed-open-questions-' . date('Y-m-d') . '.xml';
file_put_contents($filename, $xml);

echo "XML file generated successfully: $filename\n";
echo "Total questions in XML: " . count($questions) . "\n";

// Calculate total question numbers covered
$total_question_numbers = 0;
foreach ($questions as $q) {
    if ($q['type'] === 'closed_question') {
        $total_question_numbers += max(1, intval($q['correct_answer_count']));
    } elseif ($q['type'] === 'open_question') {
        $total_question_numbers += max(1, intval($q['field_count']));
    } else {
        $total_question_numbers += 1;
    }
}
echo "Total question numbers covered: $total_question_numbers\n";
echo "Question number range: Q" . $starting_question_number . " - Q" . ($starting_question_number + $total_question_numbers - 1) . "\n";
