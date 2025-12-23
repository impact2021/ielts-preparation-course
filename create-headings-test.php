#!/usr/bin/env php
<?php
/**
 * Creates headings-test.xml - A demo test with heading matching questions (CBT Layout)
 * 
 * This script demonstrates proper feedback field usage for heading matching questions.
 * For heading questions, feedback should be provided for each mc_option and in option_feedback array.
 * 
 * Usage:
 *   php create-headings-test.php
 */

$test_data = [
    'title' => 'headings-test - Demo Heading Questions (CBT)',
    'slug' => 'headings-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    
    'reading_texts' => [
        [
            'title' => 'Demo Reading Passage - The Science of Sleep',
            'content' => '<strong>A:</strong> Sleep plays a crucial role in learning and memory consolidation. During sleep, the brain processes and stores information gathered throughout the day. Research has shown that students who get adequate sleep before an exam perform significantly better than those who stay up late studying. The brain uses sleep time to strengthen neural connections related to newly learned information, making it easier to recall later.

<strong>B:</strong> The use of electronic devices before bedtime has become a growing concern among sleep researchers. The blue light emitted by smartphones, tablets, and computers can interfere with the production of melatonin, a hormone that regulates sleep cycles. Studies indicate that people who use electronic devices within an hour of bedtime take longer to fall asleep and experience poorer sleep quality overall.

<strong>C:</strong> Experts recommend several strategies for improving sleep quality. Maintaining a consistent sleep schedule, even on weekends, helps regulate the body\'s internal clock. Creating a cool, dark, and quiet sleeping environment can also enhance sleep quality. Additionally, avoiding caffeine and heavy meals in the evening can make it easier to fall asleep and stay asleep throughout the night.

<strong>D:</strong> Sleep requirements vary significantly across different life stages. Newborns typically need 14-17 hours of sleep per day, while teenagers require 8-10 hours. Adults generally function best with 7-9 hours of sleep, though individual needs may vary. As people age, they often experience changes in sleep patterns, including difficulty falling asleep and more frequent nighttime awakenings.'
        ]
    ],
    
    'questions' => [
        [
            'type' => 'headings',
            'instructions' => 'The reading passage has four paragraphs A – D.

Choose the most suitable heading from the list below for paragraphs A and C.

List of Headings:
I. The importance of sleep for learning
II. How technology affects sleep
III. Sleep patterns across different ages
IV. Tips for better sleep quality',
            'question' => 'Paragraph A',
            'points' => 1,
            'no_answer_feedback' => 'Look at the main topic of paragraph A. What is the paragraph primarily discussing?',
            'correct_feedback' => 'Correct! Paragraph A focuses on how sleep is crucial for learning and memory consolidation.',
            'incorrect_feedback' => 'Paragraph A discusses why sleep is essential for memory consolidation.',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'I. The importance of sleep for learning',
                    'is_correct' => true,
                    'feedback' => 'Correct! This heading matches the main idea - sleep\'s role in learning and memory.'
                ],
                [
                    'text' => 'II. How technology affects sleep',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph A is about learning and memory, not technology.'
                ],
                [
                    'text' => 'III. Sleep patterns across different ages',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph A focuses on learning and memory, not age-related sleep patterns.'
                ],
                [
                    'text' => 'IV. Tips for better sleep quality',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph A is about sleep\'s importance for learning, not sleep tips.'
                ]
            ],
            'options' => 'I. The importance of sleep for learning
II. How technology affects sleep
III. Sleep patterns across different ages
IV. Tips for better sleep quality',
            'correct_answer' => '0',
            'option_feedback' => [
                'Correct! This heading matches the main idea - sleep\'s role in learning and memory.',
                'Incorrect. Paragraph A is about learning and memory, not technology.',
                'Incorrect. Paragraph A focuses on learning and memory, not age-related sleep patterns.',
                'Incorrect. Paragraph A is about sleep\'s importance for learning, not sleep tips.'
            ]
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph C',
            'points' => 1,
            'no_answer_feedback' => 'Review paragraph C carefully. What is the main focus of this paragraph?',
            'correct_feedback' => 'Excellent! Paragraph C provides practical advice for better sleep.',
            'incorrect_feedback' => 'Paragraph C discusses practical advice for improving sleep habits.',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'I. The importance of sleep for learning',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph C provides practical tips, not information about learning.'
                ],
                [
                    'text' => 'II. How technology affects sleep',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph C is about sleep strategies, not technology.'
                ],
                [
                    'text' => 'III. Sleep patterns across different ages',
                    'is_correct' => false,
                    'feedback' => 'Incorrect. Paragraph C focuses on sleep improvement strategies, not age patterns.'
                ],
                [
                    'text' => 'IV. Tips for better sleep quality',
                    'is_correct' => true,
                    'feedback' => 'Correct! This paragraph provides specific recommendations for improving sleep quality.'
                ]
            ],
            'options' => 'I. The importance of sleep for learning
II. How technology affects sleep
III. Sleep patterns across different ages
IV. Tips for better sleep quality',
            'correct_answer' => '3',
            'option_feedback' => [
                'Incorrect. Paragraph C provides practical tips, not information about learning.',
                'Incorrect. Paragraph C is about sleep strategies, not technology.',
                'Incorrect. Paragraph C focuses on sleep improvement strategies, not age patterns.',
                'Correct! This paragraph provides specific recommendations for improving sleep quality.'
            ]
        ]
    ]
];

// Generate unique post ID
$post_id = 9999997;

// Current date/time
$now = new DateTime();
$pub_date = $now->format('D, d M Y H:i:s') . ' +0000';
$post_date = $now->format('Y-m-d H:i:s');

// Serialize the data
$questions_serialized = serialize($test_data['questions']);
$reading_texts_serialized = serialize($test_data['reading_texts']);
$course_ids_serialized = serialize([]);
$lesson_ids_serialized = serialize([]);

// Create the XML
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!-- headings-test Demo - Heading Matching Questions (CBT Layout) -->
<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">
<channel>
	<title>IELTStestONLINE</title>
	<link>https://www.ieltstestonline.com</link>
	<description>Online IELTS preparation</description>
	<pubDate>{$pub_date}</pubDate>
	<language>en-NZ</language>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:base_site_url>https://www.ieltstestonline.com</wp:base_site_url>
	<wp:base_blog_url>https://www.ieltstestonline.com</wp:base_blog_url>

	<wp:author><wp:author_id>1</wp:author_id><wp:author_login><![CDATA[impact]]></wp:author_login><wp:author_email><![CDATA[impact@ieltstestonline.com]]></wp:author_email><wp:author_display_name><![CDATA[impact]]></wp:author_display_name><wp:author_first_name><![CDATA[Patrick]]></wp:author_first_name><wp:author_last_name><![CDATA[Bourne]]></wp:author_last_name></wp:author>

	<generator>IELTS Test XML Creator</generator>

	<item>
		<title><![CDATA[{$test_data['title']}]]></title>
		<link>https://www.ieltstestonline.com/ielts-quiz/{$test_data['slug']}/</link>
		<pubDate>{$pub_date}</pubDate>
		<dc:creator><![CDATA[impact]]></dc:creator>
		<guid isPermaLink="false">https://www.ieltstestonline.com/?post_type=ielts_quiz&amp;p={$post_id}</guid>
		<description/>
		<content:encoded><![CDATA[]]></content:encoded>
		<excerpt:encoded><![CDATA[]]></excerpt:encoded>
		<wp:post_id>{$post_id}</wp:post_id>
		<wp:post_date><![CDATA[{$post_date}]]></wp:post_date>
		<wp:post_date_gmt><![CDATA[{$post_date}]]></wp:post_date_gmt>
		<wp:post_modified><![CDATA[{$post_date}]]></wp:post_modified>
		<wp:post_modified_gmt><![CDATA[{$post_date}]]></wp:post_modified_gmt>
		<wp:comment_status><![CDATA[closed]]></wp:comment_status>
		<wp:ping_status><![CDATA[closed]]></wp:ping_status>
		<wp:post_name><![CDATA[{$test_data['slug']}]]></wp:post_name>
		<wp:status><![CDATA[publish]]></wp:status>
		<wp:post_parent>0</wp:post_parent>
		<wp:menu_order>0</wp:menu_order>
		<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>
		<wp:post_password><![CDATA[]]></wp:post_password>
		<wp:is_sticky>0</wp:is_sticky>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$questions_serialized}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$reading_texts_serialized}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['pass_percentage']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['layout_type']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['exercise_label']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['open_as_popup']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['scoring_type']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$test_data['timer_minutes']}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_course_ids]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$course_ids_serialized}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_lesson_ids]]></wp:meta_key>
			<wp:meta_value><![CDATA[{$lesson_ids_serialized}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_course_id]]></wp:meta_key>
			<wp:meta_value><![CDATA[]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_ielts_cm_lesson_id]]></wp:meta_key>
			<wp:meta_value><![CDATA[]]></wp:meta_value>
		</wp:postmeta>
	</item>
</channel>
</rss>
XML;

// Output filename
$filename = 'headings-test.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n\n";
