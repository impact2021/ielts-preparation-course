#!/usr/bin/env php
<?php
/**
 * Generator for Listening Test 6 - Following Test 8 Format
 * This generates using open_question and closed_question with proper field structure
 */

// Q1-5: Open question with 5 fields covering Q1-5
$q1_5 = array(
    'type' => 'open_question',
    'instructions' => 'Listen to the recordings and type your answers in the spaces provided.

Questions 1 – 10

Questions 1 – 5

Complete the notes below USING NO MORE THAN TWO WORDS AND / OR A NUMBER',
    'question' => 'Questions 1-5',
    'field_count' => 5,
    'field_labels' => array(
        '1. Who is the customer buying the puppy for?',
        '2. When will the puppies be available?',
        '3. When is the breeder not available to meet?',
        '4. What is outside the kennels?',
        '5. The garden is slightly longer than ___'
    ),
    'field_answers' => array(
        'his daughter|daughter',
        'in one week|in 1 week|one week|1 week|a week|in a week',
        'on friday afternoon|friday afternoon',
        'a big sign|big sign|a sign|sign',
        '4 metres|four metres|4 m|4m|4 meters|four meters'
    ),
    'field_feedback' => array(
        array(
            'correct' => '✓ Excellent! Acceptable answers: "HIS DAUGHTER" or "DAUGHTER". You listened carefully and identified the key information. Well done!',
            'incorrect' => '✗ Not quite. Acceptable answers: "HIS DAUGHTER" or "DAUGHTER". Listen carefully to the conversation where the customer explains who the puppy is for.',
            'no_answer' => 'No answer provided. The customer mentions who the puppy is for early in the conversation. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The puppies will be available in ONE WEEK. You listened carefully and identified the key information. Well done!',
            'incorrect' => '✗ Not quite. The breeder states when the puppies will be ready. Listen for the specific timeframe mentioned.',
            'no_answer' => 'No answer provided. The breeder explains when the puppies will be old enough. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The breeder is not available on FRIDAY AFTERNOON. You listened carefully and identified the key information. Well done!',
            'incorrect' => '✗ Not quite. The breeder mentions one specific time when she cannot meet. Listen for when she says she\'ll be at a dog show.',
            'no_answer' => 'No answer provided. The breeder explains when she is unavailable. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! There is A BIG SIGN outside the kennels. You listened carefully and identified the key information. Well done!',
            'incorrect' => '✗ Not quite. The breeder describes what is outside to help the customer find the location. Listen for what makes the place easy to find.',
            'no_answer' => 'No answer provided. The breeder mentions something visible outside. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The garden is longer than 4 METRES. You listened carefully and identified the key information. Well done!',
            'incorrect' => '✗ Not quite. Listen for the measurement mentioned about the garden length.',
            'no_answer' => 'No answer provided. A specific measurement is given for the garden. Always attempt every question.'
        )
    ),
    'points' => 5
);

// Q6-10: Open question with 5 fields for map labelling
$q6_10 = array(
    'type' => 'open_question',
    'instructions' => 'Questions 6 – 10

Label the map below using NO MORE THAN TWO WORDS. Write your answers in boxes 6 – 10 in the answer sheet.

Image URL is https://www.ieltstestonline.com/wp-content/uploads/2018/12/TEST-6.jpg',
    'question' => 'Questions 6-10: Complete the micro-chipping notes',
    'field_count' => 5,
    'field_labels' => array(
        '6. Collars and tags can be removed or ___',
        '7. Microchips are about the size of a grain of ___',
        '8. Dogs feel discomfort for only a few ___',
        '9. The chip should be checked occasionally to ensure it is still ___',
        '10. Keep the area clean to prevent it becoming ___'
    ),
    'field_answers' => array(
        'lost',
        'rice',
        'seconds',
        'working|working properly',
        'dirty'
    ),
    'field_feedback' => array(
        array(
            'correct' => '✓ Excellent! Collars and tags can be LOST. You listened carefully to the discussion about microchipping benefits.',
            'incorrect' => '✗ Not quite. The breeder explains a disadvantage of collars and tags compared to microchips. Listen for what can happen to them.',
            'no_answer' => 'No answer provided. The breeder discusses why microchips are better than collars. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The microchip is the size of a grain of RICE. You listened carefully and identified the key information.',
            'incorrect' => '✗ Not quite. The breeder uses a comparison to describe the tiny size of the microchip. Listen for what grain it is compared to.',
            'no_answer' => 'No answer provided. A specific grain is mentioned to show how small the chip is. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! Dogs feel discomfort for only a few SECONDS. You listened carefully and identified the key information.',
            'incorrect' => '✗ Not quite. The breeder reassures about how brief any discomfort is. Listen for the time unit mentioned.',
            'no_answer' => 'No answer provided. A very short time period is mentioned. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The chip should be checked to ensure it is WORKING properly. You listened carefully and identified the key information.',
            'incorrect' => '✗ Not quite. The breeder mentions occasional checks to make sure the chip hasn\'t moved. What should you verify?',
            'no_answer' => 'No answer provided. Regular checks ensure the chip is functioning correctly. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! You should prevent the area from becoming DIRTY. You listened carefully and identified the key information.',
            'incorrect' => '✗ Not quite. The breeder gives advice about preventing infection. What should you avoid the area becoming?',
            'no_answer' => 'No answer provided. Basic hygiene prevents infection. Always attempt every question.'
        )
    ),
    'points' => 5
);

// Q11-12: Closed question with 2 correct answers (multi-select)
$q11_12 = array(
    'type' => 'closed_question',
    'instructions' => 'Section 2

Questions 11-12

Which TWO of the following factors have contributed to Quartztown\'s recent growth?',
    'question' => 'Choose TWO letters A-E',
    'mc_options' => array(
        array('text' => 'A. Increase in people working in agriculture', 'is_correct' => false),
        array('text' => 'B. Growth of tourism', 'is_correct' => true),
        array('text' => 'C. A boom in the coal mining industry', 'is_correct' => false),
        array('text' => 'D. Development of outdoor activities', 'is_correct' => true),
        array('text' => 'E. Decline in visitors', 'is_correct' => false)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '1|3',  // B and D
    'option_feedback' => array(
        'This is not one of the correct answers. The correct options are B and D. Agriculture actually declined.',
        '✓ Correct! Tourism growth is one of the key factors mentioned.',
        'This is not one of the correct answers. The correct options are B and D. Coal mining is mentioned but not as a recent growth factor.',
        '✓ Correct! Development of outdoor activities is one of the key factors mentioned.',
        'This is not one of the correct answers. The correct options are B and D. Visitors have increased, not declined.'
    ),
    'correct_feedback' => '✓ Excellent! You selected both correct answers (B and D). Tourism and outdoor activities have driven recent growth.',
    'incorrect_feedback' => '✗ Not quite. The correct answers are B and D. Listen to the section about the town\'s reversal of fortunes in the last two decades.',
    'no_answer_feedback' => 'No answer provided. The correct answers are B and D. The speaker describes what brought people back to the town.',
    'points' => 2
);

// Generate questions array
$questions = array();
$questions[] = $q1_5;
$questions[] = $q6_10;
$questions[] = $q11_12;

// Now let's add  more questions to complete to 40...
// For now, just create a basic structure

// Calculate total covered
$total_covered = 5 + 5 + 2; // = 12 so far

echo "Generator created with " . count($questions) . " question objects\n";
echo "Total question numbers covered: $total_covered\n";
echo "Note: This is a simplified example. Full implementation would include all 40 questions.\n";

// To see the structure:
echo "\nExample structure of Q1-5:\n";
print_r($q1_5);
