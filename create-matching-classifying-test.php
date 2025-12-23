#!/usr/bin/env php
<?php
/**
 * Creates matching-classifying-test.xml - A demo test with matching/classifying questions (CBT Layout)
 * 
 * Usage:
 *   php create-matching-classifying-test.php
 */

$test_data = [
    'title' => 'matching-classifying-test - Demo Matching/Classifying Questions (CBT)',
    'slug' => 'matching-classifying-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    
    'reading_texts' => [
        [
            'title' => 'Demo Reading Passage - Types of Energy Sources',
            'content' => 'Energy sources can be broadly classified into renewable and non-renewable categories.

Renewable energy sources are naturally replenished on a human timescale. Solar power harnesses energy from the sun using photovoltaic panels or solar thermal systems. Wind energy captures the kinetic energy of moving air through turbines. Hydroelectric power uses the flow of water to generate electricity through dams or run-of-river systems.

Non-renewable energy sources are finite and cannot be easily replenished. Coal is a fossil fuel formed from ancient plant matter over millions of years. Natural gas, another fossil fuel, consists primarily of methane and is often found alongside oil deposits. Nuclear energy uses uranium or plutonium to generate heat through nuclear fission, though uranium itself is a limited resource.'
        ]
    ],
    
    'questions' => [
        [
            'type' => 'matching_classifying',
            'instructions' => 'Classify the following energy sources as:

A. Renewable
B. Non-renewable

Questions 1-2 refer to types of energy mentioned in the passage.',
            'question' => 'Wind energy',
            'points' => 1,
            'no_answer_feedback' => 'Check the passage to see if wind energy is renewable or non-renewable.',
            'correct_feedback' => 'Correct! Wind energy is classified as renewable in the passage.',
            'incorrect_feedback' => 'Wind energy is mentioned as a renewable energy source that captures kinetic energy from air.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A. Renewable', 'is_correct' => true, 'feedback' => 'Correct! Wind is naturally replenished.'],
                ['text' => 'B. Non-renewable', 'is_correct' => false, 'feedback' => 'Wind energy is renewable, not finite.']
            ],
            'options' => 'A. Renewable
B. Non-renewable',
            'correct_answer' => '0',
            'option_feedback' => [
                'Correct! Wind is naturally replenished.',
                'Wind energy is renewable, not finite.'
            ]
        ],
        [
            'type' => 'matching_classifying',
            'instructions' => '',
            'question' => 'Coal',
            'points' => 1,
            'no_answer_feedback' => 'Look at how coal is described in the passage.',
            'correct_feedback' => 'Excellent! Coal is a non-renewable fossil fuel.',
            'incorrect_feedback' => 'The passage clearly states that coal is a non-renewable fossil fuel formed over millions of years.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'A. Renewable', 'is_correct' => false, 'feedback' => 'Coal is finite and takes millions of years to form.'],
                ['text' => 'B. Non-renewable', 'is_correct' => true, 'feedback' => 'Correct! Coal is a finite resource.']
            ],
            'options' => 'A. Renewable
B. Non-renewable',
            'correct_answer' => '1',
            'option_feedback' => [
                'Coal is finite and takes millions of years to form.',
                'Correct! Coal is a finite resource.'
            ]
        ]
    ]
];

// Generate unique post ID
$post_id = 9999992;

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
<!-- matching-classifying-test Demo - Matching/Classifying Questions (CBT Layout) -->
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
$filename = 'matching-classifying-test.xml';

// Write the XML file
file_put_contents($filename, $xml);

echo "\n✓ Successfully generated: {$filename}\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n\n";
