#!/usr/bin/env php
<?php
/**
 * Combine multiple IELTS section XML files into one complete test
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

function extractQuestionsFromXML($xmlContent) {
    if (preg_match('/<wp:meta_key><!\[CDATA\[_ielts_cm_questions\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $xmlContent, $match)) {
        $serialized = $match[1];
        $data = @unserialize($serialized);
        if ($data !== false) {
            return $data;
        }
    }
    return [];
}

function extractTranscriptFromXML($xmlContent) {
    if (preg_match('/<wp:meta_key><!\[CDATA\[_ielts_cm_transcript\]\]><\/wp:meta_value>/s', $xmlContent, $match)) {
        return $match[1];
    }
    return "";
}

// Section files
$sections = [
    __DIR__ . "/Listening Test 6 Section 1.xml",
    __DIR__ . "/Listening Test 6 Section 2.xml",
    __DIR__ . "/Listening Test 6 Section 3.xml",
    __DIR__ . "/Listening Test 6 Section 4.xml",
];

$allQuestions = [];
$allTranscripts = [];

echo "Combining sections...\n";

foreach ($sections as $i => $file) {
    $sectionNum = $i + 1;
    echo "Processing Section $sectionNum... ";
    
    if (!file_exists($file)) {
        echo "NOT FOUND\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $questions = extractQuestionsFromXML($content);
    
    if (!empty($questions)) {
        $allQuestions = array_merge($allQuestions, array_values($questions));
        echo count($questions) . " questions\n";
    } else {
        echo "NO QUESTIONS\n";
    }
    
    // Extract transcript
    if (preg_match('/<wp:meta_key><!\[CDATA\[_ielts_cm_transcript\]\]><\/wp:meta_key>\s*<wp:meta_value><!\[CDATA\[(.*?)\]\]><\/wp:meta_value>/s', $content, $match)) {
        $allTranscripts[] = "<strong>SECTION $sectionNum</strong>\n" . $match[1];
    }
}

// Reindex from 0
$reindexed = array_values($allQuestions);

echo "\nTotal questions: " . count($reindexed) . "\n";

// Serialize
$serialized = serialize($reindexed);
$combinedTranscript = implode("\n\n<hr />\n\n", $allTranscripts);

// Create XML
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <item>
      <title><![CDATA[Listening Test 6 - Complete (All 40 Questions)]]></title>
      <wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>
      <wp:status><![CDATA[publish]]></wp:status>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:60;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>
        <wp:meta_value><![CDATA[listening_practice]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:40;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>
        <wp:meta_value><![CDATA[i:1;]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>
        <wp:meta_value><![CDATA[$serialized]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_transcript]]></wp:meta_key>
        <wp:meta_value><![CDATA[$combinedTranscript]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>
        <wp:meta_value><![CDATA[a:0:{}]]></wp:meta_value>
      </wp:postmeta>
    </item>
  </channel>
</rss>
XML;

$outputFile = __DIR__ . "/Listening-Test-6-Complete-FIXED.xml";
file_put_contents($outputFile, $xml);

echo "\n✓ Combined XML written to: $outputFile\n";
echo "  Question elements: " . count($reindexed) . "\n";

// Validate it
echo "\nValidating with comprehensive validator...\n";
system("php " . __DIR__ . "/../TEMPLATES/validate-xml-comprehensive.php '$outputFile'");
