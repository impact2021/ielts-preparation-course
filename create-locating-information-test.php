#!/usr/bin/env php
<?php
$test_data = [
    'title' => 'locating-information-test - Demo Locating Information Questions (CBT)',
    'slug' => 'locating-information-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    'reading_texts' => [[
        'title' => 'Demo Reading Passage - Benefits of Music',
        'content' => '<strong>A:</strong> Music has been shown to improve cognitive function in children. Studies demonstrate that learning to play an instrument enhances memory, attention span, and problem-solving skills. Children who receive music education often perform better academically.

<strong>B:</strong> The therapeutic effects of music are well-documented in medical literature. Hospitals use music therapy to help patients manage pain, reduce anxiety before surgery, and support recovery from strokes and brain injuries.

<strong>C:</strong> Music plays a vital role in cultural preservation and identity. Traditional songs and instruments carry historical narratives and values across generations, maintaining connections to ancestral heritage.

<strong>D:</strong> The music industry contributes significantly to the global economy. It generates billions of dollars annually through live performances, streaming services, and merchandise sales, while creating employment opportunities for millions of people worldwide.'
    ]],
    'questions' => [[
        'type' => 'locating_information',
        'instructions' => 'The reading passage has four paragraphs A-D.

Which paragraph contains the following information?

Questions 1-2 refer to paragraphs in the passage.',
        'question' => 'Information about music\'s economic impact',
        'points' => 1,
        'no_answer_feedback' => 'Look for which paragraph discusses economic contributions.',
        'correct_feedback' => 'Correct! Paragraph D discusses the music industry\'s economic contribution.',
        'incorrect_feedback' => 'Paragraph D mentions billions of dollars and employment opportunities.',
        'reading_text_id' => 0,
        'mc_options' => [
            ['text' => 'A', 'is_correct' => false, 'feedback' => 'This paragraph is about cognitive benefits.'],
            ['text' => 'B', 'is_correct' => false, 'feedback' => 'This paragraph covers therapeutic effects.'],
            ['text' => 'C', 'is_correct' => false, 'feedback' => 'This paragraph discusses cultural preservation.'],
            ['text' => 'D', 'is_correct' => true, 'feedback' => 'Correct! This discusses economic impact.']
        ],
        'options' => 'A
B
C
D',
        'correct_answer' => '3',
        'option_feedback' => ['This paragraph is about cognitive benefits.', 'This paragraph covers therapeutic effects.', 'This paragraph discusses cultural preservation.', 'Correct! This discusses economic impact.']
    ], [
        'type' => 'locating_information',
        'instructions' => '',
        'question' => 'Information about music therapy in healthcare settings',
        'points' => 1,
        'no_answer_feedback' => 'Find the paragraph mentioning hospitals and medical uses.',
        'correct_feedback' => 'Excellent! Paragraph B describes medical applications.',
        'incorrect_feedback' => 'Paragraph B specifically mentions hospitals using music therapy.',
        'reading_text_id' => 0,
        'mc_options' => [
            ['text' => 'A', 'is_correct' => false, 'feedback' => 'This is about children\'s education.'],
            ['text' => 'B', 'is_correct' => true, 'feedback' => 'Correct! This covers medical therapy.'],
            ['text' => 'C', 'is_correct' => false, 'feedback' => 'This is about culture and heritage.'],
            ['text' => 'D', 'is_correct' => false, 'feedback' => 'This is about economics.']
        ],
        'options' => 'A
B
C
D',
        'correct_answer' => '1',
        'option_feedback' => ['This is about children\'s education.', 'Correct! This covers medical therapy.', 'This is about culture and heritage.', 'This is about economics.']
    ]]
];
$post_id = 9999991;
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
file_put_contents('locating-information-test.xml', $xml);
echo "\n✓ Successfully generated: locating-information-test.xml\n✓ Reading passages: 1\n✓ Questions: 2\n\n";
