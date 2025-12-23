# Example: Converting Existing Test File to XML

This guide shows you step-by-step how to convert an existing text file (like `ielts-reading-test-temp.txt`) into XML format.

## The Input File Format

Looking at `ielts-reading-test-temp.txt`, we see:

```
[READING PASSAGE] Reading Passage 1 - Driverless cars

A: Could driving yourself soon be a thing of the past...
B: Google themselves have stated...
...

[END READING PASSAGE]

Questions 1 – 5 [HEADINGS]

1. Paragraph B
A) I. Early days
B) II. The main computer
...
E) V. Processing the data [CORRECT]
[INCORRECT] Explanation text...
```

## Step-by-Step Conversion

### Step 1: Copy the Reading Passages

From the text file, copy everything between `[READING PASSAGE]` and `[END READING PASSAGE]`:

```php
'reading_texts' => [
    [
        'title' => 'Reading Passage 1 - Driverless cars',
        'content' => 'A: Could driving yourself soon be a thing of the past? Internet giant Google certainly thinks so...'
    ]
],
```

### Step 2: Extract Questions

For each question type in your file:

#### Headings Questions (Example from file)

The file shows:
```
1. Paragraph B
E) V. Processing the data in a live environment [CORRECT]
[INCORRECT] Paragraph B refers to using data already collected...
```

Convert to:
```php
[
    'type' => 'headings',
    'instructions' => 'Reading Passage 1 has seven paragraphs A – G.
For paragraphs B – F, choose the most suitable heading from the list below.

List of Headings:
I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
    'question' => 'Paragraph B',
    'points' => 1,
    'incorrect_feedback' => 'Paragraph B refers to using data already collected as well as traffic lights and other vehicles to make decisions.',
    'reading_text_id' => 0,
    'mc_options' => [
        ['text' => 'I. Early days of development', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'V. Processing the data in a live environment', 'is_correct' => true, 'feedback' => ''],
        ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
        ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => false, 'feedback' => '']
    ],
    'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
    'correct_answer' => '4',  // Index 4 = option V (0-based)
    'option_feedback' => ['', '', '', '', '', '', '', '', '']
],
```

### Step 3: Identify the Correct Answer Index

Looking at the [CORRECT] marker in the file:
- Question 1: E) V. Processing... [CORRECT] → Index 4 (because it's the 5th option, 0-based)
- Question 2: G) VII. The benefits... [CORRECT] → Index 6
- Question 3: I) IX. Additional advantages... [CORRECT] → Index 8
- Question 4: A) I. Early days... [CORRECT] → Index 0
- Question 5: C) III. Support and opposition... [CORRECT] → Index 2

### Step 4: Complete PHP Script for This Test

Here's a complete conversion of the driverless cars test:

```php
#!/usr/bin/env php
<?php
$test_data = [
    'title' => 'IELTS Reading Test - Driverless Cars',
    'slug' => 'ielts-reading-test-driverless-cars',
    'pass_percentage' => 70,
    'layout_type' => 'computer_based',
    'exercise_label' => 'practice_test',
    'open_as_popup' => 1,
    'scoring_type' => 'ielts_academic_reading',
    'timer_minutes' => 60,
    
    'reading_texts' => [
        [
            'title' => 'Reading Passage 1 - Driverless cars',
            'content' => '<strong>A:</strong> Could driving yourself soon be a thing of the past? Internet giant Google certainly thinks so. It started work on a driverless car back in 2010, and now the US state of Nevada has granted it a licence to trial them on public roads, bringing self-driving vehicles one step closer to reality. The car is based on a Toyota Prius, but it\'s fitted with a wide range of technologies that allows it to drive itself. The most notable is the large, roof-mounted LIDAR (light detection and ranging) unit, which spins around at high speed to give the computers a constant 360-degree view of the car\'s immediate surroundings. This combines with another radar in the grille (which detects objects in front of the car) and positioning sensors that pinpoint the Toyota\'s exact location, while a computer works out whether the car should brake, speed up or steer to avoid obstacles.

<strong>B:</strong> Google themselves have stated that before any route is driven using automated technology, a manned vehicle is first driven along the roads to create a detailed digital map of all of the features on the way (much the same as Google Maps street view is recorded). By mapping things like lane markers and traffic signs, the software in the car becomes familiar with the environment and characteristics in advance. When the car later tackles the route without driver assistance, all previously recorded data is used, as well as current obstacles such as where other cars are and how fast they\'re moving. Acceleration and deceleration are controlled accordingly and can interpret traffic lights, sign and road signs. The challenge of how to get the right information to the cars at the right time has been made possible by Google\'s vase network of data centres, which can process enormous amounts of info gathered by these vehicles.

<strong>C:</strong> The potential benefits of the driverless car are significant. The majority of road incidents are related to driver error – perhaps a lack of concentration or slower reflexes. In fact, driver error is responsible for over 60% of all traffic accidents. The use of computers making calculated decisions in microseconds means that reaction times are greatly reduced and collisions avoided – clearly a step in the right direction given that traffic accidents involving human error claim over one million fatalities annually. With the concept of the driver becoming the passenger, there are also personal benefits and advantages – the daily commute could be used in a much more productive manner as the people previously spending time slowly moving through morning traffic jams are now able to work as they are driven to the office. With people in larger urban areas often spending an hour or more simply in trying to get to work, this would mean a significant boost in productivity as well as reduction in stress. Road rage, where one driver becomes irritated and acts aggressively towards (or because of) the actions of another driver, would be reduced if not eliminated.

<strong>D:</strong> Driverless cars would also mean a much greater level of mobility for people who previously were unable to operate a car. Those with disabilities or issues like epilepsy, as well as the visually impaired, would be independently mobile. Children would also benefit, as they could be driven to school without a parent or guardian having to drive them. But the gains aren\'t purely for the person being driven – there are also environmental gains. As driverless cars are controlled by highly sensitive computers, excess fuel that a human driver often wastes with over acceleration or heavy braking will be saved. The cars would be able to maintain a constant speed, again allowing for better fuel consumption and therefore fewer emissions.

<strong>E:</strong> The idea for a vehicle that no longer needs a human driver is the brainchild of Sebastian Thrun, a professor at Stanford University who was also part of the team that developed Google Street View. In the early days of this developing technology, Thrun led a Stanford team to victory in the 2005 DARPA Challenge, a race for driverless vehicles sponsored by the US Department of Defence. He has used the knowledge gained in that event to develop the Google driverless car. During its development the self-driving Prius has clocked up more than 200,000 miles, traversed the Golden Gate Bridge and driven down San Francisco\'s notoriously tricky Lombard Street one of the steepest, twistiest urban roads in the world.

<strong>F:</strong> There\'s been one accident, but Google claims the car was being driven manually at the time. It happened just outside company HQ in California, when another driver hit the back of the Prius at traffic lights. This shows the main impetus for driverless cars: taking humans out of the equation to improve safety. Thrun is naturally a strong supporter of driverless cars, but some people go even further – Google Chairman Eric Schmidt has commented that he thinks it\'s amazing humans were allowed to drive cars at all, and regards the need for a driver as a bug to be fixed. Driving enthusiasts may disagree with this view (the Hot Rod Association of Alberta, a group of car enthusiasts, have actually petitioned against further testing and development of the driverless car claiming that it would make car travel less interesting), but Tim Groesner, the director of the Nevada Department of Motor Vehicles, doesn\'t. He approved a driverless car licence for Google after riding in a Prius down the Las Vegas strip and seeing how much better it was than him at spotting hazards.

<strong>G:</strong> There is still considerable work to be done, but the potential advantages make this a concept well worth pursuing, even if some of us will miss being behind the wheel!'
        ]
    ],
    
    'questions' => [
        // Question 1: Paragraph B
        [
            'type' => 'headings',
            'instructions' => 'Reading Passage 1 has seven paragraphs A – G.

For paragraphs B – F, choose the most suitable heading from the list of headings below.

List of Headings:
I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere

Example: Paragraph A - Answer: VIII

You should spend about 20 minutes on Questions 1 – 13 which are based on Reading Passage 1.',
            'question' => 'Paragraph B',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph B refers to using data already collected as well as traffic lights and other vehicles to make decisions.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'I. Early days of development', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Processing the data in a live environment', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
            'correct_answer' => '4',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        
        // Question 2: Paragraph C
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph C',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph C refers to how commuting time can be used more productively, how people would be less stressed and the reduction of \'road rage\'.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'I. Early days of development', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Processing the data in a live environment', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
            'correct_answer' => '6',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        
        // Question 3: Paragraph D
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph D',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph D refers to mobility for people who would otherwise not be able to drive, as well as the lower emissions into the environment.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'I. Early days of development', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Processing the data in a live environment', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => true, 'feedback' => '']
            ],
            'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
            'correct_answer' => '8',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        
        // Question 4: Paragraph E
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph E',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph E refers to how Professor Thrun first developed the idea for a 2005 competition sponsored by the US Department of Defence.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'I. Early days of development', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Processing the data in a live environment', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
            'correct_answer' => '0',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        
        // Question 5: Paragraph F
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph F',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph F refers to support from Google and opposition from car enthusiasts.',
            'reading_text_id' => 0,
            'mc_options' => [
                ['text' => 'I. Early days of development', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The main computer controlling the driverless car', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Support and opposition for the driverless car', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'IV. Reacting to traffic lights', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. Processing the data in a live environment', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. Driverless car first developed by the Department of Defence', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. The benefits for the replaced drivers', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. Technology hardware allowing a complete view of risks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Additional advantages for transport and the atmosphere', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Early days of development
II. The main computer controlling the driverless car
III. Support and opposition for the driverless car
IV. Reacting to traffic lights
V. Processing the data in a live environment
VI. Driverless car first developed by the Department of Defence
VII. The benefits for the replaced drivers
VIII. Technology hardware allowing a complete view of risks
IX. Additional advantages for transport and the atmosphere',
            'correct_answer' => '2',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ]
    ]
];

// Include the XML generation code from create-test-xml.php here
// ... (same generate_test_xml function)
?>
```

## Summary

To convert ANY existing test:

1. **Copy reading passages** → Put in `'reading_texts'` array
2. **For each question:**
   - Identify question type
   - Find the [CORRECT] marker
   - Calculate the index (0-based)
   - Copy the feedback from [INCORRECT]
   - Build the question array
3. **Run the script** → `php your-script.php`
4. **Upload XML** to WordPress

The key is finding the correct answer index - just count from 0!

- A = 0
- B = 1
- C = 2
- D = 3
- E = 4
- etc.
