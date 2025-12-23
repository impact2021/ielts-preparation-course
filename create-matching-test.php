#!/usr/bin/env php
<?php
/**
 * Creates matching-test.xml - A demo test with matching questions (CBT Layout)
 * 
 * Usage:
 *   php create-matching-test.php
 */

$test_data = [
    'title' => 'matching-test - Demo Matching Questions (CBT)',
    'slug' => 'matching-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    
    'reading_texts' => [
        [
            'title' => 'Demo Reading Passage - Famous Inventors',
            'content' => 'Throughout history, inventors have transformed the world with their creations.

Thomas Edison (1847-1931) developed the practical electric light bulb, which revolutionized how people lived and worked. He held over 1,000 patents and established the first industrial research laboratory.

Marie Curie (1867-1934) conducted pioneering research on radioactivity and was the first woman to win a Nobel Prize. She discovered two new elements, polonium and radium, which advanced medical treatments.

Alexander Graham Bell (1847-1922) invented the telephone in 1876, fundamentally changing long-distance communication. He also worked extensively on technologies for the deaf.

The Wright Brothers, Orville (1871-1948) and Wilbur (1867-1912), achieved the first powered airplane flight in 1903, opening the age of aviation. Their engineering approach combined theoretical knowledge with practical experimentation.'
        ]
    ],
    
    'questions' => [
        [
            'type' => 'matching',
            'instructions' => 'Match each invention/achievement to the correct inventor.

Choose the appropriate letters A-E for questions 1-2.

Inventors:
A. Thomas Edison
B. Marie Curie
C. Alexander Graham Bell
D. The Wright Brothers',
            'question' => 'Discovered radioactivity',
            'points' => 1,
            'no_answer_feedback' => 'Find which inventor worked with radioactivity.',
            'correct_feedback' => 'Correct! Marie Curie conducted pioneering research on radioactivity.',
            'incorrect_feedback' => 'Marie Curie is known for her groundbreaking work on radioactivity.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A. Thomas Edison', 'is_correct' => false, 'feedback' => 'Edison worked on the electric light bulb.'],
                ['text' => 'B. Marie Curie', 'is_correct' => true, 'feedback' => 'Correct! Curie studied radioactivity.'],
                ['text' => 'C. Alexander Graham Bell', 'is_correct' => false, 'feedback' => 'Bell invented the telephone.'],
                ['text' => 'D. The Wright Brothers', 'is_correct' => false, 'feedback' => 'The Wright Brothers worked on aviation.']
            ],
            'options' => 'A. Thomas Edison
B. Marie Curie
C. Alexander Graham Bell
D. The Wright Brothers',
            'correct_answer' => '1',
            'option_feedback' => [
                'Edison worked on the electric light bulb.',
                'Correct! Curie studied radioactivity.',
                'Bell invented the telephone.',
                'The Wright Brothers worked on aviation.'
            ]
        ],
        [
            'type' => 'matching',
            'instructions' => '',
            'question' => 'Achieved the first powered flight',
            'points' => 1,
            'no_answer_feedback' => 'Look for information about aviation history.',
            'correct_feedback' => 'Excellent! The Wright Brothers made the first powered airplane flight.',
            'incorrect_feedback' => 'The Wright Brothers achieved the first powered airplane flight in 1903.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A. Thomas Edison', 'is_correct' => false, 'feedback' => 'Edison focused on electrical inventions.'],
                ['text' => 'B. Marie Curie', 'is_correct' => false, 'feedback' => 'Curie was a physicist and chemist.'],
                ['text' => 'C. Alexander Graham Bell', 'is_correct' => false, 'feedback' => 'Bell worked on communication devices.'],
                ['text' => 'D. The Wright Brothers', 'is_correct' => true, 'feedback' => 'Correct! They pioneered aviation.']
            ],
            'options' => 'A. Thomas Edison
B. Marie Curie
C. Alexander Graham Bell
D. The Wright Brothers',
            'correct_answer' => '3',
            'option_feedback' => [
                'Edison focused on electrical inventions.',
                'Curie was a physicist and chemist.',
                'Bell worked on communication devices.',
                'Correct! They pioneered aviation.'
            ]
        ]
    ]
];

// Generate unique post ID
$post_id = 9999993;

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
<!-- matching-test Demo - Matching Questions (CBT Layout) -->
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
$filename = 'matching-test.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n\n";
