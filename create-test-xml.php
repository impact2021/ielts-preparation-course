#!/usr/bin/env php
<?php
/**
 * IELTS Reading Test XML Generator
 * 
 * Creates a complete WordPress WXR XML file for IELTS reading tests
 * that can be imported directly, bypassing the buggy text import interface.
 * 
 * Usage:
 *   php create-test-xml.php
 *   
 * This will create an example test. Edit the $test_data array to customize your test.
 * Or create a JSON file with your test data and modify the script to read from it.
 */

// Test configuration - EDIT THIS to create your own tests
$test_data = [
    'title' => 'Academic IELTS Reading Test 06',
    'slug' => 'academic-ielts-reading-test-06',
    'pass_percentage' => 70,
    'layout_type' => 'computer_based',  // or 'standard'
    'exercise_label' => 'practice_test',  // 'exercise', 'end_of_lesson_test', or 'practice_test'
    'open_as_popup' => 1,  // 1 or 0
    'scoring_type' => 'ielts_academic_reading',  // 'percentage', 'ielts_academic_reading', 'ielts_general_reading', 'ielts_listening'
    'timer_minutes' => 60,
    
    'reading_texts' => [
        [
            'title' => 'Reading Passage 1 - The Impact of Climate Change',
            'content' => 'Climate change is one of the most pressing issues facing humanity today. The Earth\'s average temperature has risen by approximately 1.1 degrees Celsius since the pre-industrial era, primarily due to human activities such as burning fossil fuels, deforestation, and industrial processes. This warming trend has led to a cascade of environmental changes including melting ice caps, rising sea levels, and more frequent extreme weather events.

Scientists have been studying climate patterns for decades, using sophisticated computer models to predict future scenarios. These models suggest that if current trends continue, global temperatures could rise by an additional 2-4 degrees Celsius by the end of this century. Such an increase would have catastrophic consequences for ecosystems, agriculture, and human settlements worldwide.

The effects of climate change are already being felt across the globe. Coastal cities are experiencing increased flooding due to rising sea levels. Agricultural regions are facing unpredictable weather patterns that threaten food security. Arctic ice is disappearing at an alarming rate, threatening the survival of polar bears and other wildlife that depend on these frozen habitats.

International efforts to address climate change have resulted in various agreements and protocols. The Paris Agreement, signed in 2015, committed nations to limiting global warming to well below 2 degrees Celsius above pre-industrial levels. However, achieving this goal requires unprecedented global cooperation and significant changes to energy production, transportation, and industrial practices.

Renewable energy sources such as solar, wind, and hydroelectric power offer promising alternatives to fossil fuels. Many countries have invested heavily in these technologies, with some nations now generating more than half their electricity from renewable sources. The cost of solar and wind energy has decreased dramatically in recent years, making them increasingly competitive with traditional energy sources.

Individual actions also play a crucial role in combating climate change. Reducing energy consumption, using public transportation, adopting plant-based diets, and supporting sustainable businesses are all ways that individuals can contribute to reducing greenhouse gas emissions. Education and awareness are essential in mobilizing collective action toward a sustainable future.'
        ],
        [
            'title' => 'Reading Passage 2 - The History of Renewable Energy',
            'content' => '<strong>A:</strong> Renewable energy has a longer history than many people realize. Windmills have been used for grinding grain and pumping water for over a thousand years, with the earliest known windmills appearing in Persia around 500-900 AD. Similarly, water wheels were utilized by ancient civilizations for various mechanical purposes. However, it wasn\'t until the 19th and 20th centuries that these traditional technologies began to be adapted for electricity generation.

<strong>B:</strong> The first windmill for electricity production was built in Scotland in 1887 by Professor James Blyth. He used the electricity to light his holiday cottage, making it the first house in the world to be powered by wind-generated electricity. Around the same time, across the Atlantic, Charles F. Brush built a large windmill in Ohio that generated electricity for his mansion. These early experiments demonstrated the potential of wind power, though it would take many decades before the technology became commercially viable.

<strong>C:</strong> Solar energy development followed a similar trajectory. In 1839, French physicist Alexandre Edmond Becquerel discovered the photovoltaic effect - the ability of certain materials to produce electricity when exposed to light. However, the first practical solar cell wasn\'t created until 1954, when researchers at Bell Laboratories developed a silicon-based cell capable of converting sunlight into electricity with an efficiency of about 6%.

<strong>D:</strong> The oil crises of the 1970s marked a turning point in renewable energy development. As oil prices skyrocketed, governments and researchers began investing seriously in alternative energy sources. This period saw significant advances in wind turbine technology, with the development of larger, more efficient designs. Denmark emerged as a leader in wind energy, establishing a strong domestic industry that continues to this day.

<strong>E:</strong> The 21st century has witnessed explosive growth in renewable energy adoption. China has become the world\'s largest producer of solar panels and wind turbines, while Germany has demonstrated that a major industrial economy can successfully transition to renewable energy. The German "Energiewende" (energy transition) policy aims to generate 80% of electricity from renewable sources by 2050. This ambitious goal has inspired similar initiatives in other countries.

<strong>F:</strong> Technological improvements have been crucial to the success of renewable energy. Modern wind turbines are hundreds of times more powerful than their 1980s predecessors, with offshore wind farms now capable of generating gigawatts of electricity. Solar panel efficiency has more than tripled since the 1950s, while costs have fallen by over 90% in the past decade. Energy storage technology, particularly lithium-ion batteries, has also advanced significantly, helping to address the intermittent nature of renewable energy sources.'
        ],
        [
            'title' => 'Reading Passage 3 - The Future of Energy Storage',
            'content' => 'Energy storage has emerged as a critical component in the transition to renewable energy. Unlike fossil fuel power plants that can generate electricity on demand, renewable sources like solar and wind are intermittent - they only produce power when the sun is shining or the wind is blowing. This variability presents a significant challenge for maintaining a stable electricity grid.

Battery technology has made remarkable progress in recent years. Lithium-ion batteries, which power everything from smartphones to electric vehicles, have become the dominant energy storage solution. Large-scale battery installations, sometimes called "battery farms," can store excess electricity generated during peak production periods and release it when demand is high or renewable generation is low.

Tesla\'s Hornsdale Power Reserve in South Australia, which began operating in 2017, demonstrated the potential of grid-scale battery storage. This 150-megawatt facility can store enough electricity to power approximately 30,000 homes for one hour. Since its installation, it has helped stabilize the local grid, prevent blackouts, and reduce electricity costs.

However, lithium-ion batteries are not without limitations. They degrade over time, losing capacity with each charge-discharge cycle. The mining of lithium and other materials required for battery production also raises environmental and ethical concerns. Researchers are exploring alternative battery technologies, including sodium-ion batteries, flow batteries, and solid-state batteries, each offering different advantages in terms of cost, safety, and performance.

Pumped hydroelectric storage represents another proven technology for large-scale energy storage. Water is pumped to an elevated reservoir during periods of excess electricity generation, then released through turbines to generate power when needed. This method accounts for over 90% of global energy storage capacity, though it requires specific geographical features and can have environmental impacts on local ecosystems.

Hydrogen is increasingly being considered as a long-term energy storage solution. Excess renewable electricity can be used to produce hydrogen through electrolysis - splitting water into hydrogen and oxygen. The hydrogen can then be stored and later used to generate electricity through fuel cells or combustion. Green hydrogen, produced entirely from renewable energy, could play a crucial role in decarbonizing sectors like heavy industry and long-distance transportation that are difficult to electrify directly.

Thermal energy storage offers another approach, particularly for solar power plants. Concentrated solar power facilities can store heat in molten salt, which retains thermal energy for hours or even days. This stored heat can then be used to generate electricity when the sun isn\'t shining, effectively turning solar power into a dispatchable resource similar to fossil fuel plants.

The development of a smart grid - an electricity network that uses digital technology to monitor and manage energy flow - is essential for integrating various storage technologies and renewable energy sources. Smart grids can automatically balance supply and demand, optimize energy distribution, and enable consumers to adjust their electricity usage based on real-time pricing signals. This intelligent infrastructure will be crucial for achieving a fully renewable energy system.'
        ]
    ],
    
    'questions' => [
        // Questions 1-6: True/False/Not Given (Passage 1)
        [
            'type' => 'true_false',
            'instructions' => 'Do the following statements agree with the information given in Reading Passage 1?

Select:
TRUE if the statement agrees with the information
FALSE if the statement contradicts the information
NOT GIVEN if there is no information on this in the passage

You should spend about 20 minutes on Questions 1 – 13 which are based on Reading Passage 1.',
            'question' => 'The Earth\'s temperature has increased by more than 1 degree Celsius since pre-industrial times.',
            'points' => 1,
            'no_answer_feedback' => 'The passage states that the Earth\'s average temperature has risen by approximately 1.1 degrees Celsius since the pre-industrial era, which is more than 1 degree. The answer is TRUE.',
            'correct_feedback' => 'Correct! The passage clearly states that temperatures have risen by approximately 1.1 degrees Celsius.',
            'incorrect_feedback' => 'The passage states that the Earth\'s average temperature has risen by approximately 1.1 degrees Celsius, which is more than 1 degree.',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'Computer models predict temperatures could rise by 2-4 degrees by 2100 if current trends continue.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'The Paris Agreement was signed in 2016.',
            'points' => 1,
            'no_answer_feedback' => 'The passage states the Paris Agreement was signed in 2015, not 2016, so the answer is FALSE.',
            'correct_feedback' => '',
            'incorrect_feedback' => 'The Paris Agreement was signed in 2015 according to the passage.',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'false'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'Some countries now generate over 50% of their electricity from renewable sources.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'Solar energy is now cheaper than all traditional energy sources.',
            'points' => 1,
            'no_answer_feedback' => 'The passage mentions that solar energy is "increasingly competitive" with traditional sources but doesn\'t say it is cheaper than ALL traditional sources. The answer is NOT GIVEN.',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'not_given'
        ],
        [
            'type' => 'true_false',
            'instructions' => '',
            'question' => 'Individual actions can help reduce greenhouse gas emissions.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 0,
            'options' => '',
            'correct_answer' => 'true'
        ],
        
        // Questions 7-13: Headings (Passage 2)
        [
            'type' => 'headings',
            'instructions' => 'Reading Passage 2 has six paragraphs A – F.
Choose the most suitable heading from the list below for each paragraph.
Write the appropriate numbers I - IX in boxes 7 – 12 on your answer sheet.

You should spend about 20 minutes on Questions 7 – 19 which are based on Reading Passage 2.

List of Headings:
I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'question' => 'Paragraph A',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'Paragraph A discusses windmills from over a thousand years ago and ancient civilizations using water wheels, making Heading I the correct choice.',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '0',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph B',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '2',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph C',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '4',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph D',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph E',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '7',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        [
            'type' => 'headings',
            'instructions' => '',
            'question' => 'Paragraph F',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 1,
            'mc_options' => [
                ['text' => 'I. Ancient origins of wind and water power', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'II. The oil crisis catalyst', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'III. Scotland\'s pioneering contribution', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IV. Modern efficiency gains', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'V. The photovoltaic breakthrough', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VI. China\'s renewable energy dominance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VII. Denmark\'s wind power leadership', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'VIII. 21st century expansion', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'IX. Solar panel cost reductions', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'I. Ancient origins of wind and water power
II. The oil crisis catalyst
III. Scotland\'s pioneering contribution
IV. Modern efficiency gains
V. The photovoltaic breakthrough
VI. China\'s renewable energy dominance
VII. Denmark\'s wind power leadership
VIII. 21st century expansion
IX. Solar panel cost reductions',
            'correct_answer' => '3',
            'option_feedback' => ['', '', '', '', '', '', '', '', '']
        ],
        
        // Questions 13-19: Short Answer (Passage 2)
        [
            'type' => 'short_answer',
            'instructions' => 'Complete the sentences below.
Choose NO MORE THAN THREE WORDS AND/OR A NUMBER from the passage for each answer.
Write your answers in boxes 13 – 19 on your answer sheet.',
            'question' => 'The earliest known windmills appeared in Persia around ___________.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => '500-900 AD'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'Professor James Blyth built the first windmill for electricity in ___________ in 1887.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => 'SCOTLAND'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'The photovoltaic effect was discovered in ___________.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => '1839'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'The first practical solar cell was created at ___________ in 1954.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => 'BELL LABORATORIES'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'Germany\'s "Energiewende" policy aims to generate 80% of electricity from renewable sources by ___________.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => '2050'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => 'Solar panel costs have fallen by over ___________ percent in the past decade.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => '90'
        ],
        [
            'type' => 'short_answer',
            'instructions' => '',
            'question' => '___________ batteries have become the dominant energy storage solution.',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => null,
            'options' => '',
            'correct_answer' => 'LITHIUM-ION|LITHIUM ION'
        ],
        
        // Questions 20-26: Multiple Choice (Passage 3)
        [
            'type' => 'multiple_choice',
            'instructions' => 'Choose the appropriate letters A – D and write them in boxes 20 – 26 on your answer sheet.

You should spend about 20 minutes on Questions 20 – 40 which are based on Reading Passage 3.',
            'question' => 'According to the passage, renewable energy sources are intermittent because',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => 'The passage states that solar and wind "only produce power when the sun is shining or the wind is blowing."',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: they are too expensive to run continuously', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: they only generate power under certain conditions', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: they require constant maintenance', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: they are not as efficient as fossil fuels', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: they are too expensive to run continuously
B: they only generate power under certain conditions
C: they require constant maintenance
D: they are not as efficient as fossil fuels',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'The Hornsdale Power Reserve in South Australia',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: was the world\'s first battery installation', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: can power 30,000 homes indefinitely', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'C: has helped stabilize the electricity grid', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'D: uses sodium-ion battery technology', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: was the world\'s first battery installation
B: can power 30,000 homes indefinitely
C: has helped stabilize the electricity grid
D: uses sodium-ion battery technology',
            'correct_answer' => '2',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'Pumped hydroelectric storage',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: is a new and experimental technology', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: accounts for most global energy storage capacity', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: can be built anywhere without geographical constraints', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: is more efficient than battery storage', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: is a new and experimental technology
B: accounts for most global energy storage capacity
C: can be built anywhere without geographical constraints
D: is more efficient than battery storage',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'Green hydrogen is produced by',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: burning natural gas', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: using electricity from renewable sources', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: extracting it from fossil fuels', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: processing biomass', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: burning natural gas
B: using electricity from renewable sources
C: extracting it from fossil fuels
D: processing biomass',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'Concentrated solar power facilities store energy in',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: lithium-ion batteries', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: hydrogen tanks', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'C: molten salt', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'D: water reservoirs', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: lithium-ion batteries
B: hydrogen tanks
C: molten salt
D: water reservoirs',
            'correct_answer' => '2',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'A smart grid can',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: generate renewable electricity', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: automatically balance supply and demand', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: eliminate the need for energy storage', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: prevent all power outages', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: generate renewable electricity
B: automatically balance supply and demand
C: eliminate the need for energy storage
D: prevent all power outages',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ],
        [
            'type' => 'multiple_choice',
            'instructions' => '',
            'question' => 'The main purpose of Reading Passage 3 is to',
            'points' => 1,
            'no_answer_feedback' => '',
            'correct_feedback' => '',
            'incorrect_feedback' => '',
            'reading_text_id' => 2,
            'mc_options' => [
                ['text' => 'A: explain why renewable energy is important', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'B: describe various energy storage technologies', 'is_correct' => true, 'feedback' => ''],
                ['text' => 'C: promote lithium-ion batteries', 'is_correct' => false, 'feedback' => ''],
                ['text' => 'D: criticize fossil fuel power plants', 'is_correct' => false, 'feedback' => '']
            ],
            'options' => 'A: explain why renewable energy is important
B: describe various energy storage technologies
C: promote lithium-ion batteries
D: criticize fossil fuel power plants',
            'correct_answer' => '1',
            'option_feedback' => ['', '', '', '']
        ]
    ]
];

/**
 * Generate WordPress WXR XML from test data
 */
function generate_test_xml($test_data) {
    $now = new DateTime();
    $date_str = $now->format('Y-m-d H:i:s');
    $date_gmt = $now->format('Y-m-d H:i:s');
    $pub_date = $now->format('D, d M Y H:i:s') . ' +0000';
    
    $title = $test_data['title'];
    $slug = $test_data['slug'];
    // Generate a unique post ID using SHA-256 hash to avoid collisions
    $post_id = abs(hexdec(substr(hash('sha256', $slug), 0, 8)));
    
    // Serialize questions and reading texts using PHP serialize
    $questions_serialized = serialize($test_data['questions']);
    $reading_texts_serialized = serialize($test_data['reading_texts']);
    $course_ids_serialized = serialize([]);
    $lesson_ids_serialized = serialize([]);
    
    // Build XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<!-- This is a WordPress eXtended RSS file for IELTS Course Manager exercise export -->' . "\n";
    $xml .= '<!-- Generated by IELTS Test XML Creator on ' . $date_str . ' -->' . "\n";
    $xml .= '<rss xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/" version="2.0">' . "\n";
    $xml .= '<channel>' . "\n";
    $xml .= "\t<title>IELTStestONLINE</title>\n";
    $xml .= "\t<link>https://www.ieltstestonline.com/2026</link>\n";
    $xml .= "\t<description>Online IELTS preparation</description>\n";
    $xml .= "\t<pubDate>$pub_date</pubDate>\n";
    $xml .= "\t<language>en-NZ</language>\n";
    $xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
    $xml .= "\t<wp:base_site_url>https://www.ieltstestonline.com/2026</wp:base_site_url>\n";
    $xml .= "\t<wp:base_blog_url>https://www.ieltstestonline.com/2026</wp:base_blog_url>\n\n";
    $xml .= "\t<wp:author><wp:author_id>1</wp:author_id><wp:author_login><![CDATA[impact]]></wp:author_login><wp:author_email><![CDATA[impact@ieltstestonline.com]]></wp:author_email><wp:author_display_name><![CDATA[impact]]></wp:author_display_name><wp:author_first_name><![CDATA[Patrick]]></wp:author_first_name><wp:author_last_name><![CDATA[Bourne]]></wp:author_last_name></wp:author>\n\n";
    $xml .= "\t<generator>IELTS Test XML Creator</generator>\n\n";
    $xml .= "\t<item>\n";
    $xml .= "\t\t<title><![CDATA[$title]]></title>\n";
    $xml .= "\t\t<link>https://www.ieltstestonline.com/2026/ielts-quiz/$slug/</link>\n";
    $xml .= "\t\t<pubDate>$pub_date</pubDate>\n";
    $xml .= "\t\t<dc:creator><![CDATA[impact]]></dc:creator>\n";
    $xml .= "\t\t<guid isPermaLink=\"false\">https://www.ieltstestonline.com/2026/?post_type=ielts_quiz&amp;p=$post_id</guid>\n";
    $xml .= "\t\t<description/>\n";
    $xml .= "\t\t<content:encoded><![CDATA[]]></content:encoded>\n";
    $xml .= "\t\t<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n";
    $xml .= "\t\t<wp:post_id>$post_id</wp:post_id>\n";
    $xml .= "\t\t<wp:post_date><![CDATA[$date_str]]></wp:post_date>\n";
    $xml .= "\t\t<wp:post_date_gmt><![CDATA[$date_gmt]]></wp:post_date_gmt>\n";
    $xml .= "\t\t<wp:post_modified><![CDATA[$date_str]]></wp:post_modified>\n";
    $xml .= "\t\t<wp:post_modified_gmt><![CDATA[$date_gmt]]></wp:post_modified_gmt>\n";
    $xml .= "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n";
    $xml .= "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n";
    $xml .= "\t\t<wp:post_name><![CDATA[$slug]]></wp:post_name>\n";
    $xml .= "\t\t<wp:status><![CDATA[publish]]></wp:status>\n";
    $xml .= "\t\t<wp:post_parent>0</wp:post_parent>\n";
    $xml .= "\t\t<wp:menu_order>0</wp:menu_order>\n";
    $xml .= "\t\t<wp:post_type><![CDATA[ielts_quiz]]></wp:post_type>\n";
    $xml .= "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n";
    $xml .= "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
    
    // Add all postmeta fields
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_questions]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$questions_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_reading_texts]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$reading_texts_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_pass_percentage]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['pass_percentage']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_layout_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['layout_type']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_exercise_label]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['exercise_label']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_open_as_popup]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['open_as_popup']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_scoring_type]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['scoring_type']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_timer_minutes]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[{$test_data['timer_minutes']}]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_course_ids]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$course_ids_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_lesson_ids]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[$lesson_ids_serialized]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_course_id]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t\t<wp:postmeta>\n";
    $xml .= "\t\t\t<wp:meta_key><![CDATA[_ielts_cm_lesson_id]]></wp:meta_key>\n";
    $xml .= "\t\t\t<wp:meta_value><![CDATA[]]></wp:meta_value>\n";
    $xml .= "\t\t</wp:postmeta>\n";
    
    $xml .= "\t</item>\n";
    $xml .= "</channel>\n";
    $xml .= "</rss>\n";
    
    return $xml;
}

// Generate and save the XML
$xml_content = generate_test_xml($test_data);
$output_filename = 'exercise-' . $test_data['slug'] . '-' . date('Y-m-d') . '.xml';

// Attempt to write file with error handling
if (file_put_contents($output_filename, $xml_content) === false) {
    echo "✗ Error: Failed to write file '$output_filename'\n";
    echo "  Check directory permissions and disk space.\n";
    exit(1);
}

echo "✓ Successfully generated: $output_filename\n";
echo "✓ Reading passages: " . count($test_data['reading_texts']) . "\n";
echo "✓ Questions: " . count($test_data['questions']) . "\n";
echo "\nYou can now upload this XML file to WordPress!\n";
echo "\nTo create your own test:\n";
echo "1. Edit the \$test_data array in this script\n";
echo "2. Run: php create-test-xml.php\n";
echo "3. Upload the generated XML file to WordPress\n";
?>
