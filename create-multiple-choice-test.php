#!/usr/bin/env php
<?php
/**
 * Creates multiple-choice-test.xml - A demo test with multiple choice questions (CBT Layout)
 * 
 * Usage:
 *   php create-multiple-choice-test.php
 */

$test_data = [
    'title' => 'multiple-choice-test - Demo Multiple Choice Questions (CBT)',
    'slug' => 'multiple-choice-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    
    'reading_texts' => [
        [
            'title' => 'Demo Reading Passage - Digital Photography',
            'content' => 'Digital photography has revolutionized how we capture and share images. Unlike traditional film cameras, digital cameras use electronic sensors to record images as digital data. This technology allows photographers to instantly preview their shots, delete unwanted images, and take thousands of photos without worrying about film costs.

The quality of digital photographs depends on several factors. The megapixel count indicates the resolution of the camera sensor, with higher numbers generally producing more detailed images. However, image quality also depends on the sensor size, lens quality, and the photographer\'s skill. Professional cameras often have larger sensors that perform better in low-light conditions.

Modern smartphones have made photography accessible to everyone. With built-in editing tools and instant sharing capabilities, billions of photos are uploaded to social media platforms every day. However, some photography enthusiasts argue that the convenience of smartphone cameras comes at the cost of creative control and image quality compared to dedicated digital cameras.'
        ]
    ],
    
    'questions' => [
        [
            'type' => 'multiple_choice',
            'instructions' => 'Choose the correct letter, A, B, C or D.

Questions 1-2 are based on the Demo Reading Passage.',
            'question' => 'According to the passage, what is an advantage of digital cameras over film cameras?',
            'points' => 1,
            'no_answer_feedback' => 'Look for information about the differences between digital and film cameras.',
            'correct_feedback' => 'Correct! The passage mentions photographers can instantly preview their shots with digital cameras.',
            'incorrect_feedback' => 'The passage states that digital cameras allow photographers to instantly preview their shots, which is an advantage over film cameras.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: They are less expensive to purchase', 'is_correct' => false, 'feedback' => 'The passage does not compare purchase prices.'],
                ['text' => 'B: They can instantly preview images', 'is_correct' => true, 'feedback' => 'Correct! This is mentioned as a key advantage.'],
                ['text' => 'C: They produce higher quality images', 'is_correct' => false, 'feedback' => 'The passage does not claim digital cameras always have higher quality.'],
                ['text' => 'D: They are easier to carry', 'is_correct' => false, 'feedback' => 'The passage does not discuss portability.']
            ],
            'options' => 'A: They are less expensive to purchase
B: They can instantly preview images
C: They produce higher quality images
D: They are easier to carry',
            'correct_answer' => '1',
            'option_feedback' => [
                'The passage does not compare purchase prices.',
                'Correct! This is mentioned as a key advantage.',
                'The passage does not claim digital cameras always have higher quality.',
                'The passage does not discuss portability.'
            ]
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'What does the passage suggest about smartphone photography?',
            'points' => 1,
            'no_answer_feedback' => 'Consider what the passage says about smartphone cameras in the final paragraph.',
            'correct_feedback' => 'Excellent! The passage mentions that some enthusiasts argue convenience comes at the cost of creative control and quality.',
            'incorrect_feedback' => 'Some photography enthusiasts argue that smartphone cameras sacrifice creative control and image quality for convenience.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A: It has replaced professional photography', 'is_correct' => false, 'feedback' => 'The passage does not claim this.'],
                ['text' => 'B: It offers the same quality as dedicated cameras', 'is_correct' => false, 'feedback' => 'The passage suggests it may have lower quality.'],
                ['text' => 'C: It trades creative control for convenience', 'is_correct' => true, 'feedback' => 'Correct! This is what some enthusiasts argue.'],
                ['text' => 'D: It requires professional training to use', 'is_correct' => false, 'feedback' => 'The passage says it has made photography accessible to everyone.']
            ],
            'options' => 'A: It has replaced professional photography
B: It offers the same quality as dedicated cameras
C: It trades creative control for convenience
D: It requires professional training to use',
            'correct_answer' => '2',
            'option_feedback' => [
                'The passage does not claim this.',
                'The passage suggests it may have lower quality.',
                'Correct! This is what some enthusiasts argue.',
                'The passage says it has made photography accessible to everyone.'
            ]
        ]
    ]
];

// Generate unique post ID
$post_id = 9999996;

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
<!-- multiple-choice-test Demo - Multiple Choice Questions (CBT Layout) -->
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
$filename = 'multiple-choice-test.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n\n";
