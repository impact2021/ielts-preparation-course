#!/usr/bin/env php
<?php
/**
 * Generator for Listening Test 6 - Rebuilt with Closed and Open Questions
 * This script converts the existing Listening Test 6 to use only the new question types
 */

$exercise_title = "Listening Test 6 Complete (All 40 Questions) - Rebuilt";
$exercise_content = "";
$starting_question_number = 1;

// Audio sections for all 4 parts
$audio_sections = array(
    array(
        'section_number' => 1,
        'audio_url' => 'https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0006-1.mp3',
        'title' => 'Section 1'
    ),
    array(
        'section_number' => 2,
        'audio_url' => 'https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0023-2.mp3',
        'title' => 'Section 2'
    ),
    array(
        'section_number' => 3,
        'audio_url' => 'https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0006-3.mp3',
        'title' => 'Section 3'
    ),
    array(
        'section_number' => 4,
        'audio_url' => 'https://www.ieltstestonline.com/wp-content/uploads/2018/12/L0023-4.mp3',
        'title' => 'Section 4'
    )
);

// Combined transcript from all sections
$transcript = '<strong>Section 1</strong>
<table border="1" width="90%" cellspacing="0" cellpadding="0" align="center">
<tbody>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Hello, Greenlee kennels here, how can I help you?</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Hi there, I\'m ringing to enquire about you advertisement I saw on the internet. Do you still have any puppies for sale?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, I do. I\'ve got two boys and one girl left.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">What colours do you have left? You had some black and some gold coloured dogs advertised on the website I saw.</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, I\'ve only got black puppies left now; the last of the golden ones was sold yesterday.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Well, I\'m interested in buying a male puppy. (Q1) I want it to be a companion for my daughter. Do you think the puppies are good with children?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, I raise them in the house and they\'re very good with other dogs and children. My grandson plays with them all the time. They\'re still very young remember, so they\'ll adapt really easily to living in a household with children around full-time. Just remember to tell your little girl to be very gentle, especially at first.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">How old are they now?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">They\'re seven weeks old. I let them go when they are 8 weeks or older, so they\'ll be available for their owners to take home in (Q2) one week.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Okay, that sounds great. Now can I arrange to come and see you and the puppies. When would be convenient?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Well, any day this week is fine, apart from (Q3) Friday afternoon, I\'ll be out at a dog show then. When would you like to come?</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">How about on Thursday – the day after tomorrow. Would it be okay if I come at around 10.30am?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Can you make it 11am instead, that would suit me better if it\'s okay for you?</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Yes, 11am is fine. Is your place easy to find?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, (Q4) I\'ve got a big sign outside, we\'re opposite the new leisure centre on Westlands Rd. I\'m at number 157.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">157 Westlands Rd - isn\'t Westland Rd near Barnett Drive?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, it is. (Q5/6) After the traffic lights, you come along Barnett Drive and you\'ll see a fruit and vegetable shop on the left hand side. Just after that you\'ll see a craft shop. Go past the craft shop and you\'ll see a cafe on your right. Take the second right after the cafe – that\'s Westlands Rd, it\'s the one after Banks Drive.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Okay, that\'s fine, thanks. I think I know here you mean.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">I have another question for you. I\'m thinking of getting the puppy micro-chipped, so that if it gets lost it could be easily traced and returned to us. Do you think that\'s a good idea?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, I do. There are many advantages to micro-chipping when you compare it to other forms of identification. Collars and tags can be convenient but they\'re also easily taken off or (Q7) lost. Micro-chipping is permanent. In very rare cases you might have a situation where the microchip moves in the dog\'s body so it can\'t be found and scanned, but you can always have the chip checked every now and then to make sure it\'s still working properly.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Does it hurt the dog? When they put the microchip in?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">No. The microchip is really small, roughly the size of a grain of (Q8) rice. Because it\'s so small it makes it easy for the vet to put in; it can be easily injected between the shoulder blades of even the smallest dog. The dog being injected rarely notices any discomfort for more than a few (Q9) seconds.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Are there any risks involved?</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">There\'s a very small risk of infection – but you can minimise this by just taking a bit of extra care of the area for a while – make sure it doesn\'t get (Q10) dirty – that sort of thing.</td>
</tr>
<tr>
<td valign="top" width="24%">Customer:</td>
<td valign="top" width="76%">Well thanks for that advice, it gives us something to think about. I\'ll see you soon then - we\'re looking forward to meeting you and of course the puppies!</td>
</tr>
<tr>
<td valign="top" width="24%">Breeder:</td>
<td valign="top" width="76%">Yes, looking forward to meeting you too. See you soon.</td>
</tr>
</tbody>
</table>

<strong>Section 2 </strong>

Good afternoon. I\'m James Harrison and welcome to "Your Hometown", the programme where we explore a place near you.

Today we\'re going to visit Quartztown, one of the busiest little towns in the country. Situated on the wild remote southwest coast, Quartztown is set amongst stunning scenery with a backdrop of towering mountains and lush forests.  Although it wasn\'t established before the eighteenth century, it\'s packed in a lot of action since then.

Quartstown was first founded by gold miners who discovered the precious metal in the Rift River in 1860. At the height of the gold rush Quartztown boasted a population of 26,000. In 1865 there were more than 100 pubs and just 1 library. Once the gold rush was over the town settled into a quieter but just as prosperous era. In 1873 the railway reached Quartztown connecting it to the capital city, Warrington. This development enabled the growth of agriculture in the gentle valleys of the coastal region in the twentieth century. The main economic activities became forestry, farming, fishing and coal mining. The Delmer coalmine is the largest opencast mine in the country and provides employment to 32% of Quartztown\'s working age population.

The town experienced a decline in population in the last couple of decades of the twentieth century owing to developments in the traditional primary industries. For example, dairy farming has become less labour intensive with larger herd sizes and the greater mechanisation of farming processes. This led to decline in the population numbers with many young people moving away to pursue employment opportunities. However, there has been a dramatic reversal of the town\'s fortunes in the last two decades with tourism and outdoor activities drawing huge numbers of tourists and thrill seekers into the area.

Since 1994 Quartztown has hosted a midwinter festival which brings thousands of visitors to the town. Celebrating the longest night of the year, the midwinter festival kicks off with a colourful parade of vividly costumed characters. Music is provided by bands from the town\'s high school, the army base and local musicians. Many local businesses sponsor floats in the parade which usually feature themes relating to the district and its history. Interactive light displays are staged at various locations, with vibrant projections screened onto local buildings and monuments. And the night ends with a magnificent fireworks display designed by the world famous pyrotechnician, Waldo Emerson.

The parade heralds two weeks of celebration.  A skating rink is installed in the town\'s main square and every night during the festival parties will be held at the rink with music mixed by the internationally renowned DJ, Soulbass. The Quartztown alpine ski and board competition is run at nearby Mt Davenport. Events include the giant slalom, downhill and freestyle skiing.  Also on the mountain is a "bikes on snow" event, where mountain bikers engage in a thrilling downhill race. The Long Neck marathon which attracts competitors from around the globe is staged in the stunning Long Neck Valley. All these competitions have been generously supported by international and local sponsors. For the less sports minded there is also a craft fair, a readers\' and writers\' event which is usually attended by the year\'s Man Booker prize winner and concerts by the Warrington Symphony Orchestra. Visitors can also take either walking or horse-back tours of the historical gold mining areas, and even try their hands at panning for gold. There\'s a lot going on.

We are going to go now to the town\'s mayor, Maisie Bates, for her view of this year\'s midwinter festival.';

$questions = array();

// ========== SECTION 1: Questions 1-10 ==========

// Q1 - Who is the customer buying the puppy for?
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Answer the questions below. Write NO MORE THAN TWO WORDS for each answer.',
    'question' => 'Who is the customer buying the puppy for?',
    'field_count' => 1,
    'field_labels' => array('Answer:'),
    'field_answers' => array('his daughter|daughter'),
    'correct_feedback' => '✓ Excellent! The answer is "HIS DAUGHTER" or "DAUGHTER". You listened carefully and identified the key information. Well done!',
    'incorrect_feedback' => '✗ Not quite. Acceptable answers: "HIS DAUGHTER" or "DAUGHTER". Listen to the audio again and check the transcript. Pay attention to keywords and signal words that indicate important information.',
    'no_answer_feedback' => 'No answer provided. Acceptable answers: "HIS DAUGHTER" or "DAUGHTER". In the IELTS Listening test, you should always attempt every question - there\'s no penalty for wrong answers.',
    'points' => 1
);

// Q2 - When will the puppies be available?
$questions[] = array(
    'type' => 'open_question',
    'instructions' => '',
    'question' => 'When will the puppies be available?',
    'field_count' => 1,
    'field_labels' => array('Answer:'),
    'field_answers' => array('in one week|in 1 week|one week|1 week|a week|in a week'),
    'correct_feedback' => '✓ Excellent! The answer is "IN ONE WEEK" (or variations). You listened carefully and identified the key information. Well done!',
    'incorrect_feedback' => '✗ Not quite. Acceptable answers: "IN ONE WEEK", "ONE WEEK", "1 WEEK", etc. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Acceptable answers: "IN ONE WEEK", "ONE WEEK", etc. Always attempt every question.',
    'points' => 1
);

// Q3 - When is the breeder not available to meet?
$questions[] = array(
    'type' => 'open_question',
    'instructions' => '',
    'question' => 'When is the breeder not available to meet?',
    'field_count' => 1,
    'field_labels' => array('Answer:'),
    'field_answers' => array('on friday afternoon|friday afternoon'),
    'correct_feedback' => '✓ Excellent! The answer is "ON FRIDAY AFTERNOON" or "FRIDAY AFTERNOON". Well done!',
    'incorrect_feedback' => '✗ Not quite. Acceptable answers: "ON FRIDAY AFTERNOON" or "FRIDAY AFTERNOON".',
    'no_answer_feedback' => 'No answer provided. Acceptable answers: "ON FRIDAY AFTERNOON" or "FRIDAY AFTERNOON".',
    'points' => 1
);

// Q4 - What is outside the kennels?
$questions[] = array(
    'type' => 'open_question',
    'instructions' => '',
    'question' => 'What is outside the kennels?',
    'field_count' => 1,
    'field_labels' => array('Answer:'),
    'field_answers' => array('a big sign|big sign|a sign|sign'),
    'correct_feedback' => '✓ Excellent! Acceptable answers include "A BIG SIGN", "BIG SIGN", "A SIGN", or "SIGN". Well done!',
    'incorrect_feedback' => '✗ Not quite. Acceptable answers: "A BIG SIGN", "BIG SIGN", "A SIGN", or "SIGN".',
    'no_answer_feedback' => 'No answer provided. Acceptable answers: "A BIG SIGN", "BIG SIGN", "A SIGN", or "SIGN".',
    'points' => 1
);

// Q5-6 - Map labelling (matching) - Converting to closed questions
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Label the map using letters A-F.',
    'question' => 'Question 5: Where is the craft shop?',
    'mc_options' => array(
        array('text' => 'A', 'is_correct' => false),
        array('text' => 'B', 'is_correct' => false),
        array('text' => 'C', 'is_correct' => false),
        array('text' => 'D', 'is_correct' => false),
        array('text' => 'E', 'is_correct' => true),
        array('text' => 'F', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '4',
    'correct_feedback' => '✓ Excellent! The answer is "E". You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. The answer is "E". Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. The answer is "E". Always attempt every question.',
    'points' => 1
);

$questions[] = array(
    'type' => 'closed_question',
    'instructions' => '',
    'question' => 'Question 6: Where is the cafe?',
    'mc_options' => array(
        array('text' => 'A', 'is_correct' => false),
        array('text' => 'B', 'is_correct' => false),
        array('text' => 'C', 'is_correct' => true),
        array('text' => 'D', 'is_correct' => false),
        array('text' => 'E', 'is_correct' => false),
        array('text' => 'F', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '2',
    'correct_feedback' => '✓ Excellent! The answer is "C". You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. The answer is "C". Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. The answer is "C". Always attempt every question.',
    'points' => 1
);

// Q7-10 - Summary/Sentence completion
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Complete the notes below. Write NO MORE THAN ONE WORD for each answer.',
    'question' => 'Micro-chipping pets

Questions 7-10:
Collars and tags can be removed or 7. ___
Microchips are about the size of a grain of 8. ___
Dogs feel discomfort for only a few 9. ___
Keep the area clean to prevent it becoming 10. ___',
    'field_count' => 4,
    'field_labels' => array('7.', '8.', '9.', '10.'),
    'field_answers' => array(
        'lost',
        'rice',
        'seconds',
        'dirty'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript for the correct answers.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 4
);

// ========== SECTION 2: Questions 11-20 ==========

// Q11-13 - Sentence completion
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Complete the sentences below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
    'question' => 'Questions 11-13:
11. Quartztown was established in ___
12. After the gold rush, Quartztown\'s economy relied on forestry, farming and ___
13. The Delmer coalmine provides employment to ___ of the working age population.',
    'field_count' => 3,
    'field_labels' => array('11.', '12.', '13.'),
    'field_answers' => array(
        '1860',
        'fishing',
        '32%|thirty two percent|32 percent|thirty two %'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 3
);

// Q14-15 - Multi-select (Choose TWO letters)
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose TWO letters A-E.',
    'question' => 'Questions 14-15: Which TWO factors have contributed to Quartztown\'s recent growth?',
    'mc_options' => array(
        array('text' => 'A. Increase in people working in agriculture', 'is_correct' => false),
        array('text' => 'B. Growth of tourism', 'is_correct' => true),
        array('text' => 'C. A boom in the coal mining industry', 'is_correct' => false),
        array('text' => 'D. Development of outdoor activities', 'is_correct' => true),
        array('text' => 'E. Decline in visitors', 'is_correct' => false)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '1|3',
    'correct_feedback' => '✓ Excellent! You selected both correct answers (B and D). You listened carefully and identified all the key information.',
    'incorrect_feedback' => '✗ Not quite. The correct answers are B and D. Make sure you select BOTH correct options.',
    'no_answer_feedback' => 'No answer provided. The correct answers are B and D. Always attempt every question.',
    'points' => 2
);

// Q16-20 - Summary completion
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Complete the summary below. Write NO MORE THAN TWO WORDS for each answer.',
    'question' => 'Quartztown Midwinter Festival

Questions 16-20:
Quartztown first staged the Midwinter Festival in 16. ___. The festival opens with a parade with costumed characters and floats displaying local and historical scenes. There are also sponsored by 17. ___. Interactive light spectacles are projected on local buildings and the night ends with an impressive 18. ___ show. The Long Neck marathon is held in 19. ___ Valley. Visitors can also take either walking or 20. ___ tours of historical gold mining areas.',
    'field_count' => 5,
    'field_labels' => array('16.', '17.', '18.', '19.', '20.'),
    'field_answers' => array(
        '1994',
        'local business|local businesses',
        'fireworks|firework',
        'longneck|long neck',
        'horse back|horseback|horse-back|a horse'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 5
);

// ========== SECTION 3: Questions 21-30 ==========

// Q21-24 - Short answer questions
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Answer the questions below. Write NO MORE THAN THREE WORDS for each answer.',
    'question' => 'Questions 21-24:
21. What was face reading originally used to diagnose?
22. What can cause the same skin problems as alcohol?
23. What type of behaviour might someone with square facial features favour?
24. In contrast to a squarer faced receptionist, how can we expect one with rounded features to be?',
    'field_count' => 4,
    'field_labels' => array('21.', '22.', '23.', '24.'),
    'field_answers' => array(
        'health related issues|health-related issues|health issues',
        'spicy food|spicy foods',
        'being efficient|efficiency|efficient|focused on efficiency|focus on efficiency',
        'friendlier|friendly|typically friendlier|typically friendly'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 4
);

// Q25-28 - True/False/Accurate/Inaccurate - Converting to open questions
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Do the following statements agree with the information in the talk? Write ACCURATE or INACCURATE for each statement.',
    'question' => 'Questions 25-28:
25. According to the research, sociable people are often not practical.
26. Bright eyes are good for communication.
27. Physical characteristics often correlate with behaviour.
28. People with long thin noses and people with downward sloping eyes share a common characteristic.',
    'field_count' => 4,
    'field_labels' => array('25.', '26.', '27.', '28.'),
    'field_answers' => array(
        'inaccurate',
        'accurate',
        'accurate',
        'accurate'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 4
);

// Q29-30 - Multi-select
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose TWO letters A-D.',
    'question' => 'Questions 29-30: Which TWO characteristics are associated with people who have high foreheads?',
    'mc_options' => array(
        array('text' => 'A. They learn best through reading', 'is_correct' => false),
        array('text' => 'B. They are highly analytical', 'is_correct' => true),
        array('text' => 'C. They prefer practical tasks', 'is_correct' => false),
        array('text' => 'D. They have a strong interest in studying', 'is_correct' => true)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '1|3',
    'correct_feedback' => '✓ Excellent! You selected both correct answers (B and D). Well done!',
    'incorrect_feedback' => '✗ Not quite. The correct answers are B and D. Listen to the audio again.',
    'no_answer_feedback' => 'No answer provided. The correct answers are B and D.',
    'points' => 2
);

// ========== SECTION 4: Questions 31-40 ==========

// Q31-32 - Multi-select
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose TWO letters A-F.',
    'question' => 'Questions 31-32: Which TWO cultural movements influenced the Bauhaus?',
    'mc_options' => array(
        array('text' => 'A. Arts and Crafts movement', 'is_correct' => true),
        array('text' => 'B. Socialism', 'is_correct' => false),
        array('text' => 'C. Art noveau', 'is_correct' => false),
        array('text' => 'D. Functionalism', 'is_correct' => false),
        array('text' => 'E. Classicism', 'is_correct' => false),
        array('text' => 'F. Modernism', 'is_correct' => true)
    ),
    'correct_answer_count' => 2,
    'correct_answer' => '0|5',
    'correct_feedback' => '✓ Excellent! You selected both correct answers (A and F). Well done!',
    'incorrect_feedback' => '✗ Not quite. The correct answers are A and F.',
    'no_answer_feedback' => 'No answer provided. The correct answers are A and F.',
    'points' => 2
);

// Q33-35 - Multiple choice questions
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Choose the correct letter A-D.',
    'question' => 'Question 33: What were the artistic occupations of the first faculty member at the Bauhaus in Weimar?',
    'mc_options' => array(
        array('text' => 'A. Painter', 'is_correct' => false),
        array('text' => 'B. Writer', 'is_correct' => false),
        array('text' => 'C. Architect', 'is_correct' => true),
        array('text' => 'D. Sculptor', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '2',
    'correct_feedback' => '✓ Correct! The answer is C (Architect).',
    'incorrect_feedback' => '✗ Not quite. The correct answer is C (Architect).',
    'no_answer_feedback' => 'No answer provided. The correct answer is C (Architect).',
    'points' => 1
);

$questions[] = array(
    'type' => 'closed_question',
    'instructions' => '',
    'question' => 'Question 34: What was another artistic occupation of the first faculty members?',
    'mc_options' => array(
        array('text' => 'A. Painter', 'is_correct' => false),
        array('text' => 'B. Writer', 'is_correct' => false),
        array('text' => 'C. Architect', 'is_correct' => false),
        array('text' => 'D. Sculptor', 'is_correct' => true)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '3',
    'correct_feedback' => '✓ Correct! The answer is D (Sculptor).',
    'incorrect_feedback' => '✗ Not quite. The correct answer is D (Sculptor).',
    'no_answer_feedback' => 'No answer provided. The correct answer is D (Sculptor).',
    'points' => 1
);

$questions[] = array(
    'type' => 'closed_question',
    'instructions' => '',
    'question' => 'Question 35: What was another artistic occupation among the first faculty?',
    'mc_options' => array(
        array('text' => 'A. Painter', 'is_correct' => true),
        array('text' => 'B. Writer', 'is_correct' => false),
        array('text' => 'C. Architect', 'is_correct' => false),
        array('text' => 'D. Sculptor', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '0',
    'correct_feedback' => '✓ Correct! The answer is A (Painter).',
    'incorrect_feedback' => '✗ Not quite. The correct answer is A (Painter).',
    'no_answer_feedback' => 'No answer provided. The correct answer is A (Painter).',
    'points' => 1
);

// Q36-38 - Sentence completion
$questions[] = array(
    'type' => 'open_question',
    'instructions' => 'Complete the sentences below. Write NO MORE THAN TWO WORDS for each answer.',
    'question' => 'Questions 36-38:
36. In the first year of the Bauhaus course, students learnt about colour theory and basic ___
37. Gropius\' aim was to create functional objects that were beautiful and that people could ___
38. Gropius designed a chair which was named the ___',
    'field_count' => 3,
    'field_labels' => array('36.', '37.', '38.'),
    'field_answers' => array(
        'principles of design|design principles',
        'afford',
        'f51 chair|f 51 chair|f51|f 51|f-51|f fifty one|f fifty-one'
    ),
    'correct_feedback' => '✓ Excellent! You listened carefully and identified the key information.',
    'incorrect_feedback' => '✗ Not quite. Listen to the audio again and check the transcript.',
    'no_answer_feedback' => 'No answer provided. Always attempt every question.',
    'points' => 3
);

// Q39-40 - Map/diagram labelling - Converting to closed questions
$questions[] = array(
    'type' => 'closed_question',
    'instructions' => 'Label the diagram using letters A-C.',
    'question' => 'Question 39: Which letter represents the typical Bauhaus building style?',
    'mc_options' => array(
        array('text' => 'A', 'is_correct' => true),
        array('text' => 'B', 'is_correct' => false),
        array('text' => 'C', 'is_correct' => false)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '0',
    'correct_feedback' => '✓ Correct! The answer is A.',
    'incorrect_feedback' => '✗ Not quite. The correct answer is A.',
    'no_answer_feedback' => 'No answer provided. The correct answer is A.',
    'points' => 1
);

$questions[] = array(
    'type' => 'closed_question',
    'instructions' => '',
    'question' => 'Question 40: Which letter represents the Dessau school building?',
    'mc_options' => array(
        array('text' => 'A', 'is_correct' => false),
        array('text' => 'B', 'is_correct' => false),
        array('text' => 'C', 'is_correct' => true)
    ),
    'correct_answer_count' => 1,
    'correct_answer' => '2',
    'correct_feedback' => '✓ Correct! The answer is C.',
    'incorrect_feedback' => '✗ Not quite. The correct answer is C.',
    'no_answer_feedback' => 'No answer provided. The correct answer is C.',
    'points' => 1
);

// Generate the XML
function generate_xml($title, $content, $questions, $starting_question_number, $audio_sections, $transcript) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- Listening Test 6 - Rebuilt with Closed and Open Questions -->' . "\n";
    $xml .= '<!-- Generated on ' . date('Y-m-d H:i:s') . ' -->' . "\n";
    $xml .= '<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= "\t<title>IELTStestONLINE</title>\n";
    $xml .= "\t<link>https://www.ieltstestonline.com/2026</link>\n";
    $xml .= "\t<description>Online IELTS preparation</description>\n";
    $xml .= "\t<pubDate>" . date('r') . "</pubDate>\n";
    $xml .= "\t<language>en-NZ</language>\n";
    $xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
    $xml .= "\t<wp:base_site_url>https://www.ieltstestonline.com/2026</wp:base_site_url>\n";
    $xml .= "\t<wp:base_blog_url>https://www.ieltstestonline.com/2026</wp:base_blog_url>\n";
    $xml .= "\t<wp:author>\n";
    $xml .= "\t\t<wp:author_id>1</wp:author_id>\n";
    $xml .= "\t\t<wp:author_login><![CDATA[impact]]></wp:author_login>\n";
    $xml .= "\t\t<wp:author_email><![CDATA[impact@ieltstestonline.com]]></wp:author_email>\n";
    $xml .= "\t\t<wp:author_display_name><![CDATA[impact]]></wp:author_display_name>\n";
    $xml .= "\t\t<wp:author_first_name><![CDATA[Patrick]]></wp:author_first_name>\n";
    $xml .= "\t\t<wp:author_last_name><![CDATA[Bourne]]></wp:author_last_name>\n";
    $xml .= "\t</wp:author>\n";
    $xml .= "\t<generator>IELTS Course Manager - Listening Test 6 Rebuilt Generator</generator>\n\n";
    
    $xml .= "\t<item>\n";
    $xml .= "\t\t<title><![CDATA[" . $title . "]]></title>\n";
    $xml .= "\t\t<link>https://www.ieltstestonline.com/2026/ielts-quiz/listening-test-6-complete-rebuilt/</link>\n";
    $xml .= "\t\t<pubDate>" . date('r') . "</pubDate>\n";
    $xml .= "\t\t<dc:creator><![CDATA[impact]]></dc:creator>\n";
    $xml .= "\t\t<guid isPermaLink=\"false\">https://www.ieltstestonline.com/2026/?post_type=ielts_quiz&amp;p=AUTO</guid>\n";
    $xml .= "\t\t<description/>\n";
    $xml .= "\t\t<content:encoded><![CDATA[" . $content . "]]></content:encoded>\n";
    $xml .= "\t\t<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n";
    $xml .= "\t\t<wp:post_id>AUTO</wp:post_id>\n";
    $xml .= "\t\t<wp:post_date><![CDATA[" . date('Y-m-d H:i:s') . "]]></wp:post_date>\n";
    $xml .= "\t\t<wp:post_date_gmt><![CDATA[" . gmdate('Y-m-d H:i:s') . "]]></wp:post_date_gmt>\n";
    $xml .= "\t\t<wp:post_modified><![CDATA[" . date('Y-m-d H:i:s') . "]]></wp:post_modified>\n";
    $xml .= "\t\t<wp:post_modified_gmt><![CDATA[" . gmdate('Y-m-d H:i:s') . "]]></wp:post_modified_gmt>\n";
    $xml .= "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n";
    $xml .= "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n";
    $xml .= "\t\t<wp:post_name><![CDATA[listening-test-6-complete-rebuilt]]></wp:post_name>\n";
    $xml .= "\t\t<wp:status><![CDATA[publish]]></wp:status>\n";
    $xml .= "\t\t<wp:post_parent>0</wp:post_parent>\n";
    $xml .= "\t\t<wp:menu_order>0</wp:menu_order>\n";
    $xml .= "\t\t<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>\n";
    $xml .= "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n";
    $xml .= "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
    
    // Add questions metadata
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . serialize($questions) . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add reading texts (empty array for listening tests)
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[a:0:{}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add pass percentage
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[70]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add layout type
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[listening_practice]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add exercise label
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[practice_test]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add open as popup
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add scoring type
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[ielts_listening]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add timer minutes (empty for no timer)
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add starting question number
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_starting_question_number]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . $starting_question_number . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add audio sections
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_audio_sections]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . serialize($audio_sections) . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    // Add transcript
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_transcript]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[" . htmlspecialchars($transcript, ENT_NOQUOTES) . "]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t</item>\n";
    $xml .= "</channel>\n";
    $xml .= "</rss>";
    
    return $xml;
}

// Generate and save the XML
$xml = generate_xml($exercise_title, $exercise_content, $questions, $starting_question_number, $audio_sections, $transcript);

$filename = 'Listening-Test-6-Complete-REBUILT.xml';
file_put_contents($filename, $xml);

echo "✓ XML file generated successfully: $filename\n";
echo "✓ Total questions in XML: " . count($questions) . "\n";

// Calculate total question numbers covered
$total_question_numbers = 0;
foreach ($questions as $q) {
    if ($q['type'] === 'closed_question') {
        $total_question_numbers += max(1, intval($q['correct_answer_count']));
    } elseif ($q['type'] === 'open_question') {
        $total_question_numbers += max(1, intval($q['field_count']));
    } else {
        $total_question_numbers += 1;
    }
}
echo "✓ Total question numbers covered: $total_question_numbers\n";
echo "✓ Question number range: Q" . $starting_question_number . " - Q" . ($starting_question_number + $total_question_numbers - 1) . "\n";
echo "\nQuestion type breakdown:\n";
$closed_count = 0;
$open_count = 0;
foreach ($questions as $q) {
    if ($q['type'] === 'closed_question') {
        $closed_count++;
    } elseif ($q['type'] === 'open_question') {
        $open_count++;
    }
}
echo "- Closed Questions: $closed_count\n";
echo "- Open Questions: $open_count\n";
