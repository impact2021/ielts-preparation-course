#!/usr/bin/env php
<?php
/**
 * Creates TEMPLATE-headings.xml - Template with 4 heading questions across 2 passages
 * 
 * Usage:
 *   php create-template-headings.php
 */

$test_data = [
    'title' => 'TEMPLATE - Headings Question Type',
    'slug' => 'template-headings',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 10,
    
    'reading_texts' => [
        [
            'title' => 'Reading Passage 1 - [Your Title Here]',
            'content' => '<strong>A:</strong> [Content for paragraph A - This paragraph should focus on a specific main idea that matches one of the headings below]

<strong>B:</strong> [Content for paragraph B - This paragraph should focus on a different main idea that matches another heading]

<strong>C:</strong> [Content for paragraph C - Additional content for another paragraph if needed]

<strong>D:</strong> [Content for paragraph D - Additional content for another paragraph if needed]'
        ],
        [
            'title' => 'Reading Passage 2 - [Your Second Title Here]',
            'content' => '<strong>A:</strong> [Content for paragraph A - This paragraph should focus on a specific main idea]

<strong>B:</strong> [Content for paragraph B - This paragraph should focus on a different main idea]

<strong>C:</strong> [Content for paragraph C - Additional content]

<strong>D:</strong> [Content for paragraph D - Additional content]'
        ]
    ],
    
    'questions' => [
        // Questions 1-2 relate to Passage 1
        [
            'type' => 'headings',
            'instructions' => 'Reading Passage 1 has four paragraphs A-D.

Choose the most suitable heading from the list below for paragraphs A and B.

List of Headings:
I. [First heading option]
II. [Second heading option]
III. [Third heading option]
IV. [Fourth heading option]',
            'question' => 'Paragraph A',
            'points' => 1,
            'no_answer_feedback' => '[Provide guidance about what to look for in paragraph A]',
            'correct_feedback' => '[Explain why the correct heading matches paragraph A]',
            'incorrect_feedback' => '[Explain what paragraph A is actually about]',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'I. [First heading option]',
                    'is_correct' => true,
                    'feedback' => '[Explain why this is the correct heading for paragraph A]'
                ],
                [
                    'text' => 'II. [Second heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph A]'
                ],
                [
                    'text' => 'III. [Third heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph A]'
                ],
                [
                    'text' => 'IV. [Fourth heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph A]'
                ]
            ],
            'options' => 'I. [First heading option]
II. [Second heading option]
III. [Third heading option]
IV. [Fourth heading option]',
            'correct_answer' => '0',
            'option_feedback' => [
                '[Explain why this is the correct heading for paragraph A]',
                '[Explain why this heading does not match paragraph A]',
                '[Explain why this heading does not match paragraph A]',
                '[Explain why this heading does not match paragraph A]'
            ]
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph B',
            'points' => 1,
            'no_answer_feedback' => '[Provide guidance about what to look for in paragraph B]',
            'correct_feedback' => '[Explain why the correct heading matches paragraph B]',
            'incorrect_feedback' => '[Explain what paragraph B is actually about]',
            'reading_text_id' => 0,
            'mc_options' => [
                [
                    'text' => 'I. [First heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph B]'
                ],
                [
                    'text' => 'II. [Second heading option]',
                    'is_correct' => true,
                    'feedback' => '[Explain why this is the correct heading for paragraph B]'
                ],
                [
                    'text' => 'III. [Third heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph B]'
                ],
                [
                    'text' => 'IV. [Fourth heading option]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match paragraph B]'
                ]
            ],
            'options' => 'I. [First heading option]
II. [Second heading option]
III. [Third heading option]
IV. [Fourth heading option]',
            'correct_answer' => '1',
            'option_feedback' => [
                '[Explain why this heading does not match paragraph B]',
                '[Explain why this is the correct heading for paragraph B]',
                '[Explain why this heading does not match paragraph B]',
                '[Explain why this heading does not match paragraph B]'
            ]
        ],
        // Questions 3-4 relate to Passage 2
        [
            'type' => 'headings',
            'instructions' => 'Reading Passage 2 has four paragraphs A-D.

Choose the most suitable heading from the list below for paragraphs A and B.

List of Headings:
I. [First heading option for passage 2]
II. [Second heading option for passage 2]
III. [Third heading option for passage 2]
IV. [Fourth heading option for passage 2]',
            'question' => 'Paragraph A',
            'points' => 1,
            'no_answer_feedback' => '[Provide guidance about what to look for in paragraph A of passage 2]',
            'correct_feedback' => '[Explain why the correct heading matches this paragraph]',
            'incorrect_feedback' => '[Explain what this paragraph is actually about]',
            'reading_text_id' => 1,
            'mc_options' => [
                [
                    'text' => 'I. [First heading option for passage 2]',
                    'is_correct' => true,
                    'feedback' => '[Explain why this is the correct heading]'
                ],
                [
                    'text' => 'II. [Second heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ],
                [
                    'text' => 'III. [Third heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ],
                [
                    'text' => 'IV. [Fourth heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ]
            ],
            'options' => 'I. [First heading option for passage 2]
II. [Second heading option for passage 2]
III. [Third heading option for passage 2]
IV. [Fourth heading option for passage 2]',
            'correct_answer' => '0',
            'option_feedback' => [
                '[Explain why this is the correct heading]',
                '[Explain why this heading does not match]',
                '[Explain why this heading does not match]',
                '[Explain why this heading does not match]'
            ]
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph B',
            'points' => 1,
            'no_answer_feedback' => '[Provide guidance about what to look for in paragraph B of passage 2]',
            'correct_feedback' => '[Explain why the correct heading matches this paragraph]',
            'incorrect_feedback' => '[Explain what this paragraph is actually about]',
            'reading_text_id' => 1,
            'mc_options' => [
                [
                    'text' => 'I. [First heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ],
                [
                    'text' => 'II. [Second heading option for passage 2]',
                    'is_correct' => true,
                    'feedback' => '[Explain why this is the correct heading]'
                ],
                [
                    'text' => 'III. [Third heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ],
                [
                    'text' => 'IV. [Fourth heading option for passage 2]',
                    'is_correct' => false,
                    'feedback' => '[Explain why this heading does not match]'
                ]
            ],
            'options' => 'I. [First heading option for passage 2]
II. [Second heading option for passage 2]
III. [Third heading option for passage 2]
IV. [Fourth heading option for passage 2]',
            'correct_answer' => '1',
            'option_feedback' => [
                '[Explain why this heading does not match]',
                '[Explain why this is the correct heading]',
                '[Explain why this heading does not match]',
                '[Explain why this heading does not match]'
            ]
        ]
    ]
];

// Generate unique post ID
$post_id = 8000001;

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
<!-- TEMPLATE - Headings Question Type (2 passages, 4 questions) -->
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
$filename = 'TEMPLATES/TEMPLATE-headings.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nTemplate file created in TEMPLATES folder!\n\n";
