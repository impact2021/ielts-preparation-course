#!/usr/bin/env php
<?php
$test_data = [
    'title' => 'table-completion-test - Demo Table Completion Questions (CBT)',
    'slug' => 'table-completion-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    'reading_texts' => [[
        'title' => 'Demo Reading Passage - World Population Growth',
        'content' => 'Global population patterns show interesting trends. In 1950, the world population was 2.5 billion. By 2000, it had reached 6 billion, and current estimates place it at approximately 8 billion. The fastest growth occurred in Asia, which houses 60% of the global population. Africa is experiencing rapid growth with a current population of 1.4 billion. Europe has a relatively stable population of around 750 million people.'
    ]],
    'questions' => [[
        'type' => 'table_completion',
        'instructions' => 'Complete the table below.

Choose NO MORE THAN TWO WORDS AND/OR A NUMBER from the passage for each answer.',
        'question' => 'World population in 1950: [field 1] Current population of Africa: [field 2]',
        'points' => 1,
        'no_answer_feedback' => '',
        'correct_feedback' => '',
        'incorrect_feedback' => '',
        'reading_text_id' => 0,
        'summary_fields' => [
            1 => [
                'answer' => '2.5 BILLION',
                'correct_feedback' => '',
                'incorrect_feedback' => '',
                'no_answer_feedback' => ''
            ],
            2 => [
                'answer' => '1.4 BILLION',
                'correct_feedback' => '',
                'incorrect_feedback' => '',
                'no_answer_feedback' => ''
            ]
        ]
    ]]
];
$post_id = 9999989;
$now = new DateTime();
$pub_date = $now->format('D, d M Y H:i:s') . ' +0000';
$post_date = $now->format('Y-m-d H:i:s');
$questions_serialized = serialize($test_data['questions']);
$reading_texts_serialized = serialize($test_data['reading_texts']);
$course_ids_serialized = serialize([]);
$lesson_ids_serialized = serialize([]);
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
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
file_put_contents('table-completion-test.xml', $xml);
echo "\n✓ Successfully generated: table-completion-test.xml\n✓ Reading passages: 1\n✓ Questions: 1 (with 2 fields)\n\n";
