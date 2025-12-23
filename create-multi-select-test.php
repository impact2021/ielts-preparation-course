#!/usr/bin/env php
<?php
/**
 * Creates multi-select-test.xml - A demo test with multi-select questions (CBT Layout)
 * 
 * Usage:
 *   php create-multi-select-test.php
 */

$test_data = [
    'title' => 'multi-select-test - Demo Multi-Select Questions (CBT)',
    'slug' => 'multi-select-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    
    'reading_texts' => [
        [
            'title' => 'Demo Reading Passage - Sustainable Agriculture',
            'content' => 'Sustainable agriculture focuses on meeting society\'s food needs while preserving environmental quality for future generations. This approach incorporates several key practices. Crop rotation helps maintain soil fertility by alternating different crops in the same field across seasons. Cover crops prevent soil erosion and improve soil structure when planted between harvest and planting seasons.

Integrated pest management reduces reliance on chemical pesticides by using biological controls and crop diversity. Water conservation techniques, such as drip irrigation, minimize water waste while maintaining crop productivity. Farmers also use precision agriculture technology to optimize fertilizer application, reducing environmental impact.

The benefits of sustainable agriculture extend beyond environmental protection. Studies show that sustainable farming can improve farm profitability through reduced input costs. It also enhances biodiversity by creating habitats for beneficial insects and wildlife. Additionally, sustainable practices help build resilience against climate change impacts such as droughts and floods.'
        ]
    ],
    
    'questions' => [
        [
            'type' => 'multi_select',
            'instructions' => 'Choose TWO letters, A-E.

Which TWO practices are mentioned as part of sustainable agriculture?',
            'question' => 'Select TWO letters (A–E)',
            'points' => 1,
            'no_answer_feedback' => 'Look for specific farming practices mentioned in the passage.',
            'correct_feedback' => 'Correct! Both crop rotation and drip irrigation are mentioned as sustainable practices.',
            'incorrect_feedback' => 'The passage specifically mentions crop rotation and drip irrigation as sustainable agriculture practices.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: Crop rotation', 'is_correct' => true, 'feedback' => 'Correct! This is mentioned in the first paragraph.'],
                ['text' => 'B: Genetic modification', 'is_correct' => false, 'feedback' => 'This is not mentioned in the passage.'],
                ['text' => 'C: Drip irrigation', 'is_correct' => true, 'feedback' => 'Correct! This water conservation technique is mentioned.'],
                ['text' => 'D: Chemical fertilizers', 'is_correct' => false, 'feedback' => 'The passage mentions reducing chemical inputs, not using them.'],
                ['text' => 'E: Monoculture farming', 'is_correct' => false, 'feedback' => 'This contradicts the crop diversity mentioned in the passage.']
            ],
            'options' => 'A: Crop rotation
B: Genetic modification
C: Drip irrigation
D: Chemical fertilizers
E: Monoculture farming',
            'correct_answer' => '',
            'option_feedback' => [
                'Correct! This is mentioned in the first paragraph.',
                'This is not mentioned in the passage.',
                'Correct! This water conservation technique is mentioned.',
                'The passage mentions reducing chemical inputs, not using them.',
                'This contradicts the crop diversity mentioned in the passage.'
            ],
            'max_selections' => 2
        ],
        [
            'type' => 'multi_select',
            'instructions' => '',
            'question' => 'Which TWO benefits of sustainable agriculture are mentioned in the passage?

Select TWO letters (A–E)',
            'points' => 1,
            'no_answer_feedback' => 'Check the final paragraph for benefits of sustainable agriculture.',
            'correct_feedback' => 'Excellent! The passage mentions both improved profitability and enhanced biodiversity.',
            'incorrect_feedback' => 'The passage states that sustainable farming can improve profitability and enhance biodiversity.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: Higher crop yields', 'is_correct' => false, 'feedback' => 'The passage does not claim higher yields.'],
                ['text' => 'B: Improved profitability', 'is_correct' => true, 'feedback' => 'Correct! This is mentioned due to reduced input costs.'],
                ['text' => 'C: Enhanced biodiversity', 'is_correct' => true, 'feedback' => 'Correct! This is mentioned in the final paragraph.'],
                ['text' => 'D: Faster crop growth', 'is_correct' => false, 'feedback' => 'This is not mentioned in the passage.'],
                ['text' => 'E: Reduced labor costs', 'is_correct' => false, 'feedback' => 'Labor costs are not discussed in the passage.']
            ],
            'options' => 'A: Higher crop yields
B: Improved profitability
C: Enhanced biodiversity
D: Faster crop growth
E: Reduced labor costs',
            'correct_answer' => '',
            'option_feedback' => [
                'The passage does not claim higher yields.',
                'Correct! This is mentioned due to reduced input costs.',
                'Correct! This is mentioned in the final paragraph.',
                'This is not mentioned in the passage.',
                'Labor costs are not discussed in the passage.'
            ],
            'max_selections' => 2
        ]
    ]
];

// Generate unique post ID
$post_id = 9999995;

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
<!-- multi-select-test Demo - Multi-Select Questions (CBT Layout) -->
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
$filename = 'multi-select-test.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n\n";
