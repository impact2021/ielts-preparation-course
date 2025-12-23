#!/usr/bin/env php
<?php
/**
 * Quick Example IELTS Exercise XML Generator
 * 
 * Creates a minimal WordPress WXR XML file with just 5 questions for quick testing.
 * This script fixes the corrupted serialization in the original quick-example.xml.
 */

// Quick Example test configuration - Minimal 5-question test
$test_data = [
    'title' => 'Quick Example IELTS Reading Exercise',
    'slug' => 'quick-example-exercise',
    'pass_percentage' => 60,
    'layout_type' => 'standard',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 10,
    
    'reading_texts' => [
        [
            'title' => 'Reading Passage - The IELTS Test',
            'content' => 'The International English Language Testing System (IELTS) is one of the world\'s most popular English language proficiency tests. It is recognized by thousands of universities, employers, and immigration authorities around the globe.

The IELTS test consists of four modules: Listening, Reading, Writing, and Speaking. Each module is designed to assess different aspects of English language ability. The Reading module, which is the focus of this exercise, contains three passages with a variety of question types.

The Academic Reading test takes 60 minutes and includes three long texts which range from descriptive and factual to discursive and analytical. These are taken from books, journals, magazines, and newspapers. The texts are on topics of general interest.

Test-takers must answer 40 questions in total. Question types include multiple choice, identifying information (True/False/Not Given), matching headings, sentence completion, and short answer questions. The variety of question types ensures a comprehensive assessment of reading skills.'
        ]
    ],
    
    'questions' => [
        // Questions 1-3: True/False/Not Given
        [
            'type' => 'true_false',
            'instructions' => 'Do the following statements agree with the information given in the reading passage?

Select TRUE, FALSE, or NOT GIVEN.

You should spend about 5 minutes on Questions 1-3.',
            'question' => 'The IELTS test is recognized by universities worldwide.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! IELTS is indeed recognized by thousands of universities around the world.',
            'incorrect_feedback' => 'The passage confirms that IELTS is internationally recognized.',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'There are four sections in the IELTS reading module.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'The IELTS reading module has three passages, not four sections. False.',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'false'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'The reading test takes exactly one hour to complete.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! The IELTS reading test is exactly 60 minutes long.',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        // Questions 4-5: Multiple Choice
        [
            'type' => 'multiple_choice',
            'instructions' => 'Choose the correct letter A, B, C, or D.

Questions 4-5 are multiple choice.',
            'question' => 'What is the primary purpose of the IELTS test?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! IELTS measures English language proficiency.',
            'incorrect_feedback' => 'IELTS is designed to assess English language proficiency for various purposes.',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'A: To test knowledge of English grammar',
                    'is_correct' => false,
                    'feedback' => ''
                ],
                [
                    'text' => 'B: To measure English language proficiency',
                    'is_correct' => true,
                    'feedback' => ''
                ],
                [
                    'text' => 'C: To evaluate writing skills only',
                    'is_correct' => false,
                    'feedback' => ''
                ],
                [
                    'text' => 'D: To certify teaching qualifications',
                    'is_correct' => false,
                    'feedback' => ''
                ]
            ],
            'options' => 'A: To test knowledge of English grammar
B: To measure English language proficiency
C: To evaluate writing skills only
D: To certify teaching qualifications',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'How many passages are in the IELTS reading test?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! There are three reading passages.',
            'incorrect_feedback' => 'The reading test consists of three passages.',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'A: Two',
                    'is_correct' => false,
                    'feedback' => ''
                ],
                [
                    'text' => 'B: Three',
                    'is_correct' => true,
                    'feedback' => ''
                ],
                [
                    'text' => 'C: Four',
                    'is_correct' => false,
                    'feedback' => ''
                ],
                [
                    'text' => 'D: Five',
                    'is_correct' => false,
                    'feedback' => ''
                ]
            ],
            'options' => 'A: Two
B: Three
C: Four
D: Five',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ]
    ]
];

/**
 * Generate WordPress WXR XML for IELTS test
 */
function generate_test_xml($test_data) {
    $now = new DateTime('2024-12-23 10:00:00', new DateTimeZone('UTC'));
    $date_str = $now->format('Y-m-d H:i:s');
    $date_gmt = $now->format('Y-m-d H:i:s');
    $pub_date = $now->format('D, d M Y H:i:s') . ' +0000';
    
    $title = $test_data['title'];
    $slug = $test_data['slug'];
    // Use a fixed post ID for the quick example
    $post_id = 9999001;
    
    // Serialize questions and reading texts using PHP serialize
    $questions_serialized = serialize($test_data['questions']);
    $reading_texts_serialized = serialize($test_data['reading_texts']);
    $course_ids_serialized = serialize([]);
    $lesson_ids_serialized = serialize([]);
    
    // Build XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- Quick Example IELTS Exercise for Upload -->' . "\n";
    $xml .= '<!-- This is a minimal example with just 5 questions for quick testing -->' . "\n";
    $xml .= '<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= "\t<title>IELTStestONLINE</title>\n";
    $xml .= "\t<link>https://www.ieltstestonline.com</link>\n";
    $xml .= "\t<description>Online IELTS preparation</description>\n";
    $xml .= "\t<pubDate>$pub_date</pubDate>\n";
    $xml .= "\t<language>en-NZ</language>\n";
    $xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
    $xml .= "\t<wp:base_site_url>https://www.ieltstestonline.com</wp:base_site_url>\n";
    $xml .= "\t<wp:base_blog_url>https://www.ieltstestonline.com</wp:base_blog_url>\n\n";
    $xml .= "\t<wp:author><wp:author_id>1</wp:author_id><wp:author_login><![CDATA[impact]]></wp:author_login><wp:author_email><![CDATA[impact@ieltstestonline.com]]></wp:author_email><wp:author_display_name><![CDATA[impact]]></wp:author_display_name><wp:author_first_name><![CDATA[Patrick]]></wp:author_first_name><wp:author_last_name><![CDATA[Bourne]]></wp:author_last_name></wp:author>\n\n";
    $xml .= "\t<generator>IELTS Test XML Creator</generator>\n\n";
    $xml .= "\t<item>\n";
    $xml .= "\t\t<title><![CDATA[$title]]></title>\n";
    $xml .= "\t\t<link>https://www.ieltstestonline.com/ielts-quiz/$slug/</link>\n";
    $xml .= "\t\t<pubDate>$pub_date</pubDate>\n";
    $xml .= "\t\t<dc:creator><![CDATA[impact]]></dc:creator>\n";
    $xml .= "\t\t<guid isPermaLink=\"false\">https://www.ieltstestonline.com/?post_type=ielts_quiz&amp;p=$post_id</guid>\n";
    $xml .= "\t\t<description/>\n";
    $xml .= "\t\t<content:encoded><![CDATA[]]></content:encoded>\n";
    $xml .= "\t\t<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n";
    $xml .= "\t\t<wp:post_id>$post_id</wp:post_id>\n";
    $xml .= "\t\t<wp:post_date><![CDATA[$date_str]]></wp:post_date>\n";
    $xml .= "\t\t<wp:post_date_gmt><![CDATA[$date_gmt]]></wp:post_date_gmt>\n";
    $xml .= "\t\t<wp:post_modified><![CDATA[$date_str]]></wp:post_modified>\n";
    $xml .= "\t\t<wp:post_modified_gmt><![CDATA[$date_gmt]]></wp:post_modified_gmt>\n";
    $xml .= "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n";
    $xml .= "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n";
    $xml .= "\t\t<wp:post_name><![CDATA[$slug]]></wp:post_name>\n";
    $xml .= "\t\t<wp:status><![CDATA[publish]]></wp:status>\n";
    $xml .= "\t\t<wp:post_parent>0</wp:post_parent>\n";
    $xml .= "\t\t<wp:menu_order>0</wp:menu_order>\n";
    $xml .= "\t\t<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>\n";
    $xml .= "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n";
    $xml .= "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
    
    // Add all postmeta fields
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$questions_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$reading_texts_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['pass_percentage']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['layout_type']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['exercise_label']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['open_as_popup']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['scoring_type']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['timer_minutes']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_course_ids]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$course_ids_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_lesson_ids]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$lesson_ids_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_course_id]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_lesson_id]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t</item>\n";
    $xml .= "</channel>\n";
    $xml .= "</rss>\n";
    
    return $xml;
}

// Generate and save the XML
echo "Generating Quick Example IELTS Exercise XML...\n";
$xml_content = generate_test_xml($test_data);
$output_filename = 'quick-example.xml';

// Attempt to write file with error handling
if (file_put_contents($output_filename, $xml_content) === false) {
    echo "✗ Error: Failed to write file '$output_filename'\n";
    echo "  Check directory permissions and disk space.\n";
    exit(1);
}

echo "✓ Successfully created: $output_filename\n";

// Verify the serialized data can be unserialized
echo "\nVerifying XML structure...\n";
$xml = file_get_contents($output_filename);
if (preg_match('/<wp:meta_key><!\[CDATA\[_ielts_cm_questions\]\]><\/wp:meta_key>.*?<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $xml, $matches)) {
    $serialized = $matches[1];
    $data = @unserialize($serialized);
    if ($data === false) {
        echo "✗ ERROR: Questions data cannot be unserialized!\n";
        exit(1);
    } else {
        echo "✓ Questions data is valid\n";
        echo "✓ Number of questions: " . count($data) . "\n";
        foreach ($data as $i => $q) {
            echo "  Question " . ($i+1) . " (" . $q['type'] . "): " . substr($q['question'], 0, 50) . "...\n";
        }
    }
} else {
    echo "✗ ERROR: Could not find questions meta field in XML\n";
    exit(1);
}

echo "\n✓ All checks passed! The quick-example.xml file is ready to use.\n";
echo "\nFile details:\n";
echo "  - Size: " . number_format(filesize($output_filename)) . " bytes\n";
echo "  - Questions: 5 (3 True/False/Not Given + 2 Multiple Choice)\n";
echo "  - Timer: 10 minutes\n";
echo "  - Pass percentage: 60%\n";
echo "\nYou can now upload this file to WordPress using the standard WordPress importer.\n";
echo "See QUICK-EXAMPLE-README.md for detailed upload instructions.\n";
