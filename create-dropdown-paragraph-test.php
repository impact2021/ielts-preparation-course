#!/usr/bin/env php
<?php
$test_data = [
    'title' => 'dropdown-paragraph-test - Demo Dropdown Paragraph Questions (CBT)',
    'slug' => 'dropdown-paragraph-test-demo-cbt',
    'pass_percentage' => 60,
    'layout_type' => 'computer_based',
    'exercise_label' => 'exercise',
    'open_as_popup' => 0,
    'scoring_type' => 'percentage',
    'timer_minutes' => 5,
    'reading_texts' => [[
        'title' => 'Demo Reading Passage - The Water Cycle',
        'content' => 'The water cycle is a continuous process that circulates water through the Earth\'s atmosphere, land, and oceans. Water evaporates from the ocean surface due to solar heat, forming water vapor. This vapor rises into the atmosphere where it cools and condenses into clouds. When clouds become saturated, precipitation occurs in the form of rain or snow. Water that falls on land either flows into rivers and streams or seeps into the ground as groundwater.'
    ]],
    'questions' => [[
        'type' => 'dropdown_paragraph',
        'instructions' => 'Complete the paragraph below by selecting the correct word from the dropdown for each gap.

The water cycle involves water (1) ___________ from oceans into the atmosphere.',
        'question' => 'The water cycle involves water ___1___ from oceans into the atmosphere. Vapor cools and ___2___ into clouds.',
        'points' => 1,
        'no_answer_feedback' => 'Please select an answer for all dropdown questions.',
        'correct_feedback' => 'Well done! You have correctly completed the paragraph.',
        'incorrect_feedback' => 'Some answers are incorrect. Review the water cycle process.',
        'reading_text_id' => 0,
        'dropdown_options' => [
            1 => [
                'position' => 1,
                'options' => [
                    ['text' => 'evaporating', 'is_correct' => true],
                    ['text' => 'freezing', 'is_correct' => false],
                    ['text' => 'flowing', 'is_correct' => false]
                ],
                'correct_feedback' => 'Correct! Water evaporates from the ocean.',
                'incorrect_feedback' => 'Incorrect. Water evaporates, not freezes or flows at this stage.',
                'no_answer_feedback' => 'Please select an option for gap 1.'
            ],
            2 => [
                'position' => 2,
                'options' => [
                    ['text' => 'evaporates', 'is_correct' => false],
                    ['text' => 'condenses', 'is_correct' => true],
                    ['text' => 'precipitates', 'is_correct' => false]
                ],
                'correct_feedback' => 'Correct! Water vapor condenses to form clouds.',
                'incorrect_feedback' => 'Incorrect. Water vapor condenses when it cools.',
                'no_answer_feedback' => 'Please select an option for gap 2.'
            ]
        ],
        'correct_answer' => '1:A|2:B'
    ]]
];

// Convert dropdown_options format to inline format in question text for proper rendering
foreach ($test_data['questions'] as &$question) {
    if ($question['type'] === 'dropdown_paragraph' && isset($question['dropdown_options'])) {
        $question_text = $question['question'];
        
        foreach ($question['dropdown_options'] as $position => $dropdown_data) {
            if (!isset($dropdown_data['options']) || !is_array($dropdown_data['options'])) {
                continue;
            }
            
            // Build inline format: N.[A: option1 B: option2 C: option3]
            $options_text_parts = [];
            foreach ($dropdown_data['options'] as $opt_idx => $opt_data) {
                // Only process first 26 options (A-Z)
                if ($opt_idx > 25) {
                    break;
                }
                $opt_letter = chr(ord('A') + $opt_idx);
                $options_text_parts[] = $opt_letter . ': ' . $opt_data['text'];
            }
            $options_string = implode(' ', $options_text_parts);
            $replacement = $position . '.[' . $options_string . ']';
            
            // Replace ___N___ or __N__ placeholders with inline format
            $placeholder_pattern = '/(___' . preg_quote($position, '/') . '___|__' . preg_quote($position, '/') . '__)/';
            $question_text = preg_replace($placeholder_pattern, $replacement, $question_text);
        }
        
        $question['question'] = $question_text;
    }
}
unset($question);
$post_id = 9999988;
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
file_put_contents('dropdown-paragraph-test.xml', $xml);
echo "\n✓ Successfully generated: dropdown-paragraph-test.xml\n✓ Reading passages: 1\n✓ Questions: 1 (with 2 dropdown gaps)\n\n";
