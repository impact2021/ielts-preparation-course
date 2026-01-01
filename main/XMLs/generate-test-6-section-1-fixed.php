#!/usr/bin/env php
<?php
/**
 * Generator for Listening Test 6 Section 1 - FIXED VERSION
 * Using proper open_question and closed_question format with field arrays
 */

// Questions 1-4: Open question with 4 fields
$q1_4 = array(
    'type' => 'open_question',
    'instructions' => 'Listen to the recordings and type your answers in the spaces provided.

Questions 1 – 10

Questions 1 – 4

Complete the notes below USING NO MORE THAN TWO WORDS for each answer',
    'question' => 'Buying a Puppy - Customer Enquiry',
    'field_count' => 4,
    'field_labels' => array(
        '1. Who is the customer buying the puppy for? ________',
        '2. When will the puppies be available? ________',
        '3. When is the breeder not available to meet? ________',
        '4. What is outside the kennels? ________'
    ),
    'field_answers' => array(
        'his daughter|daughter',
        'in one week|in 1 week|one week|1 week|a week|in a week',
        'on friday afternoon|friday afternoon',
        'a big sign|big sign|a sign|sign'
    ),
    'field_feedback' => array(
        array(
            'correct' => '✓ Excellent! The customer is buying the puppy for HIS DAUGHTER. You listened carefully when the customer explained "I want it to be a companion for my daughter."',
            'incorrect' => '✗ Not quite. The customer clearly states who the puppy is for. Listen for when the customer explains the reason for buying the puppy.',
            'no_answer' => 'No answer provided. The customer mentions who the puppy is for early in the conversation. In IELTS Listening, you should always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The puppies will be available IN ONE WEEK (or variations). The breeder says they are 7 weeks old and can go home at 8 weeks.',
            'incorrect' => '✗ Not quite. The breeder states "they\'ll be available for their owners to take home in one week." Listen for the specific timeframe.',
            'no_answer' => 'No answer provided. The breeder explains when the puppies will be old enough to go to new homes. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The breeder is not available on FRIDAY AFTERNOON. She mentions she\'ll be at a dog show then.',
            'incorrect' => '✗ Not quite. The breeder says "any day this week is fine, apart from Friday afternoon." Listen for the day and time she\'s unavailable.',
            'no_answer' => 'No answer provided. The breeder specifies one time when she cannot meet. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! There is A BIG SIGN outside the kennels. The breeder says "I\'ve got a big sign outside" to help the customer find the location.',
            'incorrect' => '✗ Not quite. The breeder describes what is outside to help with directions. Listen for what makes the place easy to find.',
            'no_answer' => 'No answer provided. The breeder mentions something visible outside to help identify the location. Always attempt every question.'
        )
    ),
    'correct_feedback' => '✓ Well done! You identified all the key information correctly.',
    'incorrect_feedback' => '✗ Not quite. Review the conversation between the customer and breeder carefully.',
    'no_answer_feedback' => 'No answers provided. Always attempt every question in the IELTS test.',
    'points' => 4
);

// Questions 5-6: Closed question (map labelling - selecting letters)
$q5_6 = array(
    'type' => 'closed_question',
    'instructions' => 'Questions 5 – 6

Label the map below using letters A-F. Write your answers in boxes 5 – 6 in the answer sheet.

<img src="https://www.ieltstestonline.com/wp-content/uploads/2018/12/TEST-6.jpg" alt="Map" />

Listen to the directions and identify where the craft shop and cafe are located.',
    'question' => 'Choose TWO letters A-F',
    'mc_options' => array(
        array('text' => 'A', 'is_correct' => false),
        array('text' => 'B', 'is_correct' => false),
        array('text' => 'C - Cafe', 'is_correct' => true),
        array('text' => 'D', 'is_correct' => false),
        array('text' => 'E - Craft Shop', 'is_correct' => true),
        array('text' => 'F', 'is_correct' => false)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '2|4',  // C and E
    'option_feedback' => array(
        'Location A is not correct. Listen to the directions about the fruit shop, craft shop, and cafe.',
        'Location B is not correct. Listen to the directions about the fruit shop, craft shop, and cafe.',
        '✓ Correct! C is the cafe location. The breeder says "you\'ll see a cafe on your right."',
        'Location D is not correct. Listen to the directions about the fruit shop, craft shop, and cafe.',
        '✓ Correct! E is the craft shop location. The breeder says "you\'ll see a craft shop. Go past the craft shop."',
        'Location F is not correct. Listen to the directions about the fruit shop, craft shop, and cafe.'
    ),
    'correct_feedback' => '✓ Excellent! You correctly identified both locations (C for cafe and E for craft shop). You followed the directions accurately.',
    'incorrect_feedback' => '✗ Not quite. The correct locations are C (cafe) and E (craft shop). Listen carefully to the sequence: fruit shop, then craft shop, then cafe on your right.',
    'no_answer_feedback' => 'No answer provided. The correct locations are C and E. Listen to the breeder\'s detailed directions along Barnett Drive.',
    'points' => 2
);

// Questions 7-10: Open question with 4 fields
$q7_10 = array(
    'type' => 'open_question',
    'instructions' => 'Questions 7 – 10

Complete the notes below about MICROCHIPPING PETS

Write NO MORE THAN ONE WORD for each answer',
    'question' => 'Microchipping Information',
    'field_count' => 4,
    'field_labels' => array(
        '7. Collars and tags can be removed or ________',
        '8. Microchips are about the size of a grain of ________',
        '9. Dogs feel discomfort for only a few ________',
        '10. Keep the area clean to prevent it becoming ________'
    ),
    'field_answers' => array(
        'lost',
        'rice',
        'seconds',
        'dirty'
    ),
    'field_feedback' => array(
        array(
            'correct' => '✓ Excellent! Collars and tags can be LOST. The breeder explains this is a disadvantage compared to permanent microchipping.',
            'incorrect' => '✗ Not quite. The breeder explains what can happen to collars and tags that makes them less reliable than microchips. Listen for the word after "removed or..."',
            'no_answer' => 'No answer provided. The breeder discusses why microchips are better than collars. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! The microchip is the size of a grain of RICE. This shows how tiny and minimally invasive the chip is.',
            'incorrect' => '✗ Not quite. The breeder uses a comparison to describe the tiny size. Listen for what specific grain the chip is compared to.',
            'no_answer' => 'No answer provided. A specific grain is mentioned to show the small size. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! Dogs feel discomfort for only a few SECONDS. The breeder reassures that any discomfort is very brief.',
            'incorrect' => '✗ Not quite. The breeder talks about how brief the discomfort is. Listen for the unit of time mentioned.',
            'no_answer' => 'No answer provided. A very short time period is mentioned. Always attempt every question.'
        ),
        array(
            'correct' => '✓ Excellent! Keep the area from becoming DIRTY to minimize infection risk. Basic hygiene is important after the procedure.',
            'incorrect' => '✗ Not quite. The breeder gives advice about preventing infection. What condition should you avoid?',
            'no_answer' => 'No answer provided. The breeder mentions keeping the injection area clean. Always attempt every question.'
        )
    ),
    'correct_feedback' => '✓ Well done! You understood all the microchipping information correctly.',
    'incorrect_feedback' => '✗ Not quite. Listen to the breeder\'s explanation about microchipping carefully.',
    'no_answer_feedback' => 'No answers provided. Always attempt every question in the IELTS test.',
    'points' => 4
);

// Generate the questions array
$questions = array($q1_4, $q5_6, $q7_10);

// Generate the XML
function generate_xml($questions) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">' . "\n";
    $xml .= '  <channel>' . "\n";
    $xml .= '    <item>' . "\n";
    $xml .= '      <title><![CDATA[Listening Test 6 Section 1 - FIXED]]></title>' . "\n";
    $xml .= '      <wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>' . "\n";
    $xml .= '      <wp:status><![CDATA[publish]]></wp:status>' . "\n";
    
    // Questions metadata
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[' . serialize($questions) . ']]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    // Other metadata
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[i:60;]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[listening_practice]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[i:40;]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[i:1;]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[ielts_listening]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    // Audio URL
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_audio_url]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0006-1.mp3]]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    // Transcript
    $transcript = '<strong>Section 1</strong>
<table border="1" width="90%" cellspacing="0" cellpadding="0" align="center">
<tbody>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Hello, Greenlee kennels here, how can I help you?</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Hi there, I\'m ringing to enquire about you advertisement I saw on the internet. Do you still have any puppies for sale?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, I do. I\'ve got two boys and one girl left.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">What colours do you have left? You had some black and some gold coloured dogs advertised on the website I saw.</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, I\'ve only got black puppies left now; the last of the golden ones was sold yesterday.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Well, I\'m interested in buying a male puppy. (Q1) I want it to be a companion for my daughter. Do you think the puppies are good with children?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, I raise them in the house and they\'re very good with other dogs and children. My grandson plays with them all the time. They\'re still very young remember, so they\'ll adapt really easily to living in a household with children around full-time. Just remember to tell your little girl to be very gentle, especially at first.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">How old are they now?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">They\'re seven weeks old. I let them go when they are 8 weeks or older, so they\'ll be available for their owners to take home in (Q2) one week.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Okay, that sounds great. Now can I arrange to come and see you and the puppies. When would be convenient?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Well, any day this week is fine, apart from (Q3) Friday afternoon, I\'ll be out at a dog show then. When would you like to come?</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">How about on Thursday – the day after tomorrow. Would it be okay if I come at around 10.30am?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Can you make it 11am instead, that would suit me better if it\'s okay for you?</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Yes, 11am is fine. Is your place easy to find?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, (Q4) I\'ve got a big sign outside, we\'re opposite the new leisure centre on Westlands Rd. I\'m at number 157.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">157 Westlands Rd - isn\'t Westland Rd near Barnett Drive?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, it is. (Q5/6) After the traffic lights, you come along Barnett Drive and you\'ll see a fruit and vegetable shop on the left hand side. Just after that you\'ll see a craft shop. Go past the craft shop and you\'ll see a cafe on your right. Take the second right after the cafe – that\'s Westlands Rd, it\'s the one after Banks Drive.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Okay, that\'s fine, thanks. I think I know here you mean.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">I have another question for you. I\'m thinking of getting the puppy micro-chipped, so that if it gets lost it could be easily traced and returned to us. Do you think that\'s a good idea?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, I do. There are many advantages to micro-chipping when you compare it to other forms of identification. Collars and tags can be convenient but they\'re also easily taken off or (Q7) lost. Micro-chipping is permanent. In very rare cases you might have a situation where the microchip moves in the dog\'s body so it can\'t be found and scanned, but you can always have the chip checked every now and then to make sure it\'s still working properly.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Does it hurt the dog? When they put the microchip in?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">No. The microchip is really small, roughly the size of a grain of (Q8) rice. Because it\'s so small it makes it easy for the vet to put in; it can be easily injected between the shoulder blades of even the smallest dog. The dog being injected rarely notices any discomfort for more than a few (Q9) seconds.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Are there any risks involved?</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">There\'s a very small risk of infection – but you can minimise this by just taking a bit of extra care of the area for a while – make sure it doesn\'t get (Q10) dirty – that sort of thing.</td></tr>
<tr><td valign="top" width="24%">Customer:</td><td valign="top" width="76%">Well thanks for that advice, it gives us something to think about. I\'ll see you soon then - we\'re looking forward to meeting you and of course the puppies!</td></tr>
<tr><td valign="top" width="24%">Breeder:</td><td valign="top" width="76%">Yes, looking forward to meeting you too. See you soon.</td></tr>
</tbody>
</table>';
    
    $xml .= '      <wp:postmeta>' . "\n";
    $xml .= '        <wp:meta_key><![CDATA[_ielts_cm_transcript]]></wp:meta_key>' . "\n";
    $xml .= '        <wp:meta_value><![CDATA[' . htmlspecialchars($transcript, ENT_NOQUOTES) . ']]></wp:meta_value>' . "\n";
    $xml .= '      </wp:postmeta>' . "\n";
    
    $xml .= '    </item>' . "\n";
    $xml .= '  </channel>' . "\n";
    $xml .= '</rss>';
    
    return $xml;
}

// Generate and save
$xml = generate_xml($questions);
$filename = 'Listening-Test-6-Section-1-FIXED.xml';
file_put_contents($filename, $xml);

echo "✓ XML file generated: $filename\n";
echo "✓ Total question objects: " . count($questions) . "\n";
$total_questions = 0;
foreach ($questions as $q) {
    if ($q['type'] === 'open_question') {
        $total_questions += $q['field_count'];
    } elseif ($q['type'] === 'closed_question') {
        $total_questions += $q['correct_answer_count'];
    }
}
echo "✓ Total question numbers covered: $total_questions (Q1-Q10)\n";
echo "\nStructure:\n";
echo "- Q1-4: Open question with 4 fields (short answer)\n";
echo "- Q5-6: Closed question with 2 correct answers (map labelling)\n";
echo "- Q7-10: Open question with 4 fields (note completion)\n";
