#!/usr/bin/env php
<?php
/**
 * Comprehensive Example IELTS Exercise XML Generator
 * 
 * Creates a WordPress WXR XML file with examples of all major IELTS question types.
 * This demonstrates the full range of question formats supported by the system.
 */

// Comprehensive Example test configuration - All question types
$test_data = [
    'title' => 'Comprehensive Example - All Question Types',
    'slug' => 'comprehensive-example-all-question-types',
    'pass_percentage' => 65,
    'layout_type' => 'standard',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 20,
    
    'reading_texts' => [
        [
            'title' => 'Reading Passage 1 - Urban Gardening',
            'content' => 'Urban gardening has become increasingly popular in cities around the world. From rooftop gardens to community plots, city dwellers are finding creative ways to grow their own food and connect with nature. Studies show that urban gardens can reduce temperatures in cities by up to 5 degrees Celsius, helping to combat the urban heat island effect.

In New York City, over 550 community gardens provide fresh produce to thousands of residents. These spaces also serve as important social hubs where neighbors meet, share gardening tips, and build stronger communities. Research conducted in 2020 found that participants in urban gardening programs reported 30% lower stress levels compared to non-participants.

The environmental benefits extend beyond temperature regulation. Urban gardens improve air quality by absorbing carbon dioxide and releasing oxygen. They also help manage stormwater runoff, reducing the burden on city drainage systems. In Berlin, rooftop gardens now cover approximately 15% of all suitable building surfaces, storing an estimated 10 million liters of rainwater annually.

Despite these benefits, urban gardening faces several challenges. Limited space, soil contamination from past industrial use, and lack of water access are common obstacles. However, innovative solutions such as vertical gardens, hydroponic systems, and community water-sharing programs are making urban agriculture more accessible and sustainable.'
        ],
        [
            'title' => 'Reading Passage 2 - The History of Coffee',
            'content' => '<strong>A:</strong> Coffee\'s journey from an Ethiopian discovery to a global phenomenon spans over a millennium. Legend has it that an Ethiopian goat herder named Kaldi first noticed the energizing effects of coffee beans in the 9th century when his goats became unusually lively after eating berries from a certain tree.

<strong>B:</strong> By the 15th century, coffee cultivation had spread to Yemen, where Sufi monks used it to stay alert during long prayer sessions. The port city of Mocha became the first major coffee trading hub, giving its name to a popular coffee variety still enjoyed today.

<strong>C:</strong> Coffee reached Europe in the 17th century, initially met with suspicion by some religious authorities who called it "the bitter invention of Satan." However, Pope Clement VIII reportedly tasted it and gave his blessing, declaring it acceptable for Christians to drink.

<strong>D:</strong> London\'s first coffeehouse opened in 1652, and within decades, coffeehouses became centers of social interaction, business dealings, and intellectual exchange. They were nicknamed "penny universities" because for the price of a penny, one could buy a cup of coffee and engage in stimulating conversation.

<strong>E:</strong> The 18th century saw coffee plantations established in tropical colonies around the world. The Dutch brought coffee to Java, while the French introduced it to the Caribbean. Coffee became a major commodity in global trade, transforming economies and landscapes.

<strong>F:</strong> Today, coffee is the world\'s second most traded commodity after oil. Over 2.25 billion cups are consumed globally every day, supporting the livelihoods of approximately 125 million people involved in coffee production, processing, and distribution.'
        ]
    ],
    
    'questions' => [
        // Question 1-3: True/False/Not Given
        [
            'type' => 'true_false',
            'instructions' => 'Do the following statements agree with the information given in Reading Passage 1?

Select:
TRUE if the statement agrees with the information
FALSE if the statement contradicts the information
NOT GIVEN if there is no information on this

Questions 1-3 are based on Reading Passage 1.',
            'question' => 'Urban gardens can reduce city temperatures by up to 5 degrees Celsius.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! The passage states this fact in the first paragraph.',
            'incorrect_feedback' => 'The passage clearly states that urban gardens can reduce temperatures by up to 5 degrees Celsius.',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'New York City has the most community gardens in the United States.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'not_given'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'Berlin\'s rooftop gardens store approximately 10 million liters of rainwater per year.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! The passage mentions this specific figure.',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        
        // Question 4-5: Multiple Choice
        [
            'type' => 'multiple_choice',
            'instructions' => 'Choose the correct letter, A, B, C or D.

Questions 4-5 are multiple choice.',
            'question' => 'According to the passage, urban gardening participants experienced',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! The passage states participants had 30% lower stress levels.',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: higher productivity at work', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: 30% lower stress levels', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: improved physical fitness', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: better sleep quality', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: higher productivity at work
B: 30% lower stress levels
C: improved physical fitness
D: better sleep quality',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'What is mentioned as a challenge for urban gardening?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'The passage mentions soil contamination as one of the challenges.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: excessive rainfall', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: lack of government support', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'C: soil contamination', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'D: high maintenance costs', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: excessive rainfall
B: lack of government support
C: soil contamination
D: high maintenance costs',
            'correct_answer' => '2',
            'option_feedback' => ['', '', '', '']
        ],
        
        // Question 6: Multi-select
        [
            'type' => 'multi_select',
            'instructions' => 'Choose TWO letters, A-E.

Which TWO benefits of urban gardens are mentioned in the passage?',
            'question' => 'Select TWO letters (A–E)',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! Both improving air quality and managing stormwater are mentioned.',
            'incorrect_feedback' => 'Check the passage for environmental benefits mentioned in paragraph 3.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: reducing noise pollution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: improving air quality', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: attracting wildlife', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: managing stormwater runoff', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'E: increasing property values', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: reducing noise pollution
B: improving air quality
C: attracting wildlife
D: managing stormwater runoff
E: increasing property values',
            'correct_answer' => '',
            'option_feedback' => ['', '', '', '', ''],
            'max_selections' => 2
        ],
        
        // Question 7: Short Answer
        [
            'type' => 'short_answer',
            'instructions' => 'Answer the question below.

Choose NO MORE THAN TWO WORDS from the passage for your answer.',
            'question' => 'In what city are there over 550 community gardens?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => 'Correct! New York City is mentioned in the passage.',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'NEW YORK|NEW YORK CITY'
        ],
        
        // Question 8-10: Headings (Passage 2)
        [
            'type' => 'headings',
            'instructions' => 'Reading Passage 2 has six paragraphs A – F.

Choose the most suitable heading from the list below for paragraphs A, D and F.

List of Headings:
I. The legend of coffee\'s discovery
II. European religious controversy
III. Coffee becomes a global commodity
IV. Modern coffee consumption statistics
V. Yemeni coffee cultivation
VI. Coffeehouses as social centers',
            'question' => 'Paragraph A',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph A discusses the Ethiopian legend of Kaldi and the goats.',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. The legend of coffee\'s discovery', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'II. European religious controversy', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Coffee becomes a global commodity', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern coffee consumption statistics', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Yemeni coffee cultivation', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Coffeehouses as social centers', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. The legend of coffee\'s discovery
II. European religious controversy
III. Coffee becomes a global commodity
IV. Modern coffee consumption statistics
V. Yemeni coffee cultivation
VI. Coffeehouses as social centers',
            'correct_answer' => '0',
            'option_feedback' => ['', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph D',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. The legend of coffee\'s discovery', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. European religious controversy', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Coffee becomes a global commodity', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern coffee consumption statistics', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Yemeni coffee cultivation', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Coffeehouses as social centers', 'is_correct' => true, 'feedback' => '']
            ],
            'options' => 'I. The legend of coffee\'s discovery
II. European religious controversy
III. Coffee becomes a global commodity
IV. Modern coffee consumption statistics
V. Yemeni coffee cultivation
VI. Coffeehouses as social centers',
            'correct_answer' => '5',
            'option_feedback' => ['', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph F',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. The legend of coffee\'s discovery', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. European religious controversy', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Coffee becomes a global commodity', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern coffee consumption statistics', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'V. Yemeni coffee cultivation', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Coffeehouses as social centers', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. The legend of coffee\'s discovery
II. European religious controversy
III. Coffee becomes a global commodity
IV. Modern coffee consumption statistics
V. Yemeni coffee cultivation
VI. Coffeehouses as social centers',
            'correct_answer' => '3',
            'option_feedback' => ['', '', '', '', '', '']
        ],
        
        // Question 11-12: Short Answer (Passage 2)
        [
            'type' => 'short_answer',
            'instructions' => 'Answer the questions below.

Choose NO MORE THAN THREE WORDS AND/OR A NUMBER from the passage for each answer.',
            'question' => 'What was the nickname given to London\'s coffeehouses?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'options' => '',
            'correct_answer' => 'PENNY UNIVERSITIES'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'How many cups of coffee are consumed globally each day?',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'options' => '',
            'correct_answer' => '2.25 BILLION|2.25 BILLION CUPS'
        ]
    ]
];

/**
 * Generate WordPress WXR XML for IELTS test
 */
function generate_test_xml($test_data) {
    $now = new DateTime('2024-12-23 12:00:00', new DateTimeZone('UTC'));
    $date_str = $now->format('Y-m-d H:i:s');
    $date_gmt = $now->format('Y-m-d H:i:s');
    $pub_date = $now->format('D, d M Y H:i:s') . ' +0000';
    
    $title = $test_data['title'];
    $slug = $test_data['slug'];
    // Use a fixed post ID for the comprehensive example
    $post_id = 9999002;
    
    // Serialize questions and reading texts using PHP serialize
    $questions_serialized = serialize($test_data['questions']);
    $reading_texts_serialized = serialize($test_data['reading_texts']);
    $course_ids_serialized = serialize([]);
    $lesson_ids_serialized = serialize([]);
    
    // Build XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- Comprehensive Example IELTS Exercise - All Question Types -->' . "\n";
    $xml .= '<!-- This example demonstrates all major IELTS question formats -->' . "\n";
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
echo "Generating Comprehensive Example IELTS Exercise XML...\n";
$xml_content = generate_test_xml($test_data);
$output_filename = 'comprehensive-example.xml';

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
        
        // Count question types
        $type_counts = [];
        foreach ($data as $q) {
            $type = $q['type'];
            $type_counts[$type] = ($type_counts[$type] ?? 0) + 1;
        }
        
        echo "\nQuestion type breakdown:\n";
        foreach ($type_counts as $type => $count) {
            echo "  - $type: $count\n";
        }
    }
} else {
    echo "✗ ERROR: Could not find questions meta field in XML\n";
    exit(1);
}

echo "\n✓ All checks passed! The comprehensive-example.xml file is ready to use.\n";
echo "\nFile details:\n";
echo "  - Size: " . number_format(filesize($output_filename)) . " bytes\n";
echo "  - Questions: 12 total\n";
echo "  - Timer: 20 minutes\n";
echo "  - Pass percentage: 65%\n";
echo "  - Reading passages: 2\n";
echo "\nThis file demonstrates all major IELTS question types:\n";
echo "  • True/False/Not Given\n";
echo "  • Multiple Choice (single answer)\n";
echo "  • Multi-select (multiple answers)\n";
echo "  • Matching Headings\n";
echo "  • Short Answer\n";
echo "\nYou can upload this file to WordPress using the standard WordPress importer.\n";
