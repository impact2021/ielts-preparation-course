/**
 * Frontend JavaScript for IELTS Course Manager
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Helper function to force reload from server, bypassing cache
        function forceReload() {
            var url = new URL(window.location);
            url.searchParams.set('refresh', Date.now());
            window.location.href = url.toString();
        }
        
        // Initialize quiz timer if present
        var quizContainer = $('.ielts-single-quiz, .ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz');
        var timerMinutes = quizContainer.data('timer-minutes');
        var quizTimerInterval = null;
        
        // Check for both standard and computer-based quiz timers
        var timerElement = $('#quiz-timer').length ? $('#quiz-timer') : $('#quiz-timer-fullscreen');
        var timerDisplay = $('#timer-display').length ? $('#timer-display') : $('#timer-display-fullscreen');
        
        if (timerMinutes && timerMinutes > 0 && timerElement.length && timerDisplay.length) {
            var totalSeconds = timerMinutes * 60;
            
            quizTimerInterval = setInterval(function() {
                totalSeconds--;
                
                var mins = Math.floor(totalSeconds / 60);
                var secs = totalSeconds % 60;
                timerDisplay.text(mins + ':' + (secs < 10 ? '0' : '') + secs);
                
                // Warning at 5 minutes
                if (totalSeconds === 300) {
                    timerElement.css('color', 'orange');
                    timerDisplay.css('color', 'orange');
                }
                
                // Critical at 1 minute
                if (totalSeconds === 60) {
                    timerElement.css('color', 'red');
                    timerDisplay.css('color', 'red');
                }
                
                if (totalSeconds <= 0) {
                    clearInterval(quizTimerInterval);
                    quizTimerInterval = null;
                    timerDisplay.text('0:00');
                    timerElement.css('color', 'red');
                    timerDisplay.css('color', 'red');
                    
                    // Auto-submit the form
                    alert('Time is up! The exercise will be submitted automatically.');
                    $('#ielts-quiz-form').submit();
                }
            }, 1000);
        }
        
        // Cleanup timer on page unload
        $(window).on('beforeunload', function() {
            if (quizTimerInterval) {
                clearInterval(quizTimerInterval);
                quizTimerInterval = null;
            }
        });
        
        // Enrollment
        $('.enroll-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var courseId = button.data('course-id');
            
            button.prop('disabled', true).text('Enrolling...');
            
            $.ajax({
                url: ieltsCM.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_enroll',
                    nonce: ieltsCM.nonce,
                    course_id: courseId
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', 'You have been enrolled successfully!');
                        setTimeout(function() {
                            forceReload();
                        }, 1500);
                    } else {
                        showMessage('error', response.data.message || 'Failed to enroll');
                        button.prop('disabled', false).text('Enroll Now');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred. Please try again.');
                    button.prop('disabled', false).text('Enroll Now');
                }
            });
        });
        
        // Mark lesson complete
        $('.mark-complete-button').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var courseId = button.data('course-id');
            var lessonId = button.data('lesson-id');
            
            button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: ieltsCM.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_mark_complete',
                    nonce: ieltsCM.nonce,
                    course_id: courseId,
                    lesson_id: lessonId
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', 'Lesson marked as complete!');
                        setTimeout(function() {
                            forceReload();
                        }, 1500);
                    } else {
                        showMessage('error', response.data.message || 'Failed to save progress');
                        button.prop('disabled', false).text('Mark as Complete');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred. Please try again.');
                    button.prop('disabled', false).text('Mark as Complete');
                }
            });
        });
        
        // Store quiz start time (set when quiz form is first shown)
        var quizStartTime = null;
        
        // Initialize quiz start time when form is first visible (not hidden by fullscreen notice)
        if ($('#ielts-quiz-form:visible').length > 0) {
            quizStartTime = Date.now();
        }
        
        // Quiz submission (using event delegation to handle both static and modal forms)
        $(document).on('submit', '#ielts-quiz-form', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var quizContainer = form.closest('.ielts-single-quiz, .ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz');
            
            // If form is inside modal, get data from the original quiz container
            if (quizContainer.length === 0) {
                quizContainer = $('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').first();
            }
            
            // Stop audio playback if this is a listening quiz
            var isListeningQuiz = quizContainer.hasClass('ielts-listening-practice-quiz') || 
                                 quizContainer.hasClass('ielts-listening-exercise-quiz') ||
                                 quizContainer.data('test-type') === 'listening';
            if (isListeningQuiz) {
                var audioElement = document.getElementById('listening-audio') || document.getElementById('cbt-audio-element');
                if (audioElement) {
                    audioElement.pause();
                }
            }
            
            var quizId = quizContainer.data('quiz-id');
            var courseId = quizContainer.data('course-id');
            var lessonId = quizContainer.data('lesson-id');
            var timerMinutes = quizContainer.data('timer-minutes');
            
            // Calculate time taken
            var timeTakenFormatted = 'N/A';
            if (quizStartTime) {
                var timeTakenMs = Date.now() - quizStartTime;
                var timeTakenSeconds = Math.floor(timeTakenMs / 1000);
                var timeTakenMinutes = Math.floor(timeTakenSeconds / 60);
                var timeTakenSecondsRemainder = timeTakenSeconds % 60;
                timeTakenFormatted = timeTakenMinutes + ':' + (timeTakenSecondsRemainder < 10 ? '0' : '') + timeTakenSecondsRemainder;
            }
            
            // Collect answers
            var answers = {};
            form.find('[name^="answer_"]').each(function() {
                var name = $(this).attr('name');
                
                // Check if this is an inline summary completion input with new format (e.g., answer_0_field_1, answer_0_field_2)
                var fieldMatch = name.match(/^answer_(\d+)_field_(\d+)$/);
                if (fieldMatch) {
                    var questionIndex = fieldMatch[1];
                    var fieldNum = fieldMatch[2];
                    
                    if (!answers[questionIndex]) {
                        answers[questionIndex] = {};
                    }
                    answers[questionIndex][fieldNum] = $(this).val();
                    return; // Continue to next iteration
                }
                
                // Check if this is an inline summary completion input with legacy format (e.g., answer_0_1, answer_0_2)
                var inlineMatch = name.match(/^answer_(\d+)_(\d+)$/);
                if (inlineMatch) {
                    var questionIndex = inlineMatch[1];
                    var answerNum = inlineMatch[2];
                    
                    if (!answers[questionIndex]) {
                        answers[questionIndex] = {};
                    }
                    answers[questionIndex][answerNum] = $(this).val();
                    return; // Continue to next iteration
                }
                
                // Regular answer handling
                var index = name.replace('answer_', '').replace('[]', '');
                
                if ($(this).attr('type') === 'radio') {
                    if ($(this).is(':checked')) {
                        answers[index] = $(this).val();
                    }
                } else if ($(this).attr('type') === 'checkbox') {
                    // Handle multi-select checkboxes
                    if ($(this).is(':checked')) {
                        if (!answers[index]) {
                            answers[index] = [];
                        }
                        answers[index].push($(this).val());
                    }
                } else {
                    answers[index] = $(this).val();
                }
            });
            
            // Disable form
            form.find('button[type="submit"]').prop('disabled', true).text('Submitting...');
            
            $.ajax({
                url: ieltsCM.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ielts_cm_submit_quiz',
                    nonce: ieltsCM.nonce,
                    quiz_id: quizId,
                    course_id: courseId,
                    lesson_id: lessonId,
                    answers: JSON.stringify(answers)
                },
                success: function(response) {
                    if (response.success) {
                        var result = response.data;
                        var isPassing = result.percentage >= 70;
                        
                        // Mark the quiz container as submitted to reveal question markers and highlights
                        quizContainer.addClass('quiz-submitted');
                        
                        // Check if this is a CBT quiz or listening quiz (they share the same layout features)
                        var isCBT = quizContainer.hasClass('ielts-computer-based-quiz') || 
                                    quizContainer.hasClass('ielts-listening-practice-quiz') || 
                                    quizContainer.hasClass('ielts-listening-exercise-quiz');
                        
                        // Get exercise label from result, default to 'Exercise'
                        var exerciseLabel = result.exercise_label || 'Exercise';
                        
                        var html = '<div class="quiz-result ' + (isPassing ? 'pass' : 'fail') + '">';
                        html += '<h3>' + (isPassing ? 'Congratulations! You Passed!' : exerciseLabel + ' completed') + '</h3>';
                        
                        // Display score based on type (band score or percentage) with larger font
                        if (result.display_type === 'band') {
                            html += '<div class="quiz-score-display">';
                            html += '<p><strong>Your Score:</strong> ' + result.score + ' / ' + result.max_score + ' correct</p>';
                            html += '<p class="score-large"><strong>IELTS Band Score:</strong> ' + result.display_score + '</p>';
                            html += '</div>';
                        } else {
                            html += '<div class="quiz-score-display">';
                            html += '<p class="score-large">' + result.score + ' / ' + result.max_score + ' (' + result.percentage + '%)</p>';
                            html += '</div>';
                        }
                        
                        // Show time information only if there was a timer
                        if (timerMinutes && timerMinutes > 0) {
                            html += '<p><strong>Time Limit:</strong> ' + timerMinutes + ' minutes</p>';
                            html += '<p><strong>Time Taken:</strong> ' + timeTakenFormatted + '</p>';
                        }
                        
                        if (isPassing) {
                            html += '<p class="quiz-comment">Great job! You have passed this quiz.</p>';
                        } else {
                            html += '<p class="quiz-comment">Keep studying and try again to improve your score!</p>';
                        }
                        
                        // Question-by-question feedback is now shown via visual highlighting (green/red colors) in the form
                        // for both standard and CBT layouts. This replaces the detailed inline feedback that was previously
                        // displayed on the page (below the modal score) for standard layout only.
                        
                        html += '<div class="quiz-actions">';
                        
                        // Add "Review my answers" button for all quiz types (both standard and CBT)
                        html += '<button class="button button-primary cbt-review-answers-btn">Review my answers</button>';
                        html += ' <button class="button quiz-retake-btn">Take Quiz Again</button>';
                        
                        // Add continue button without auto-redirect for both CBT and non-CBT
                        if (result.next_url) {
                            html += ' <a href="' + result.next_url + '" class="button button-primary quiz-continue-btn">Continue</a>';
                        }
                        
                        html += '</div>';
                        html += '</div>';
                        
                        // Add visual feedback for correct/wrong answers in the form
                        $.each(result.question_results, function(index, questionResult) {
                            var questionNum = parseInt(index) + 1;
                            var questionElement = form.find('#question-' + index);
                            var navButtons = $('.question-nav-btn[data-question="' + index + '"]');
                            
                            if (questionResult.correct) {
                                // Mark correct answers in green
                                questionElement.addClass('question-correct');
                                
                                // For dropdown paragraph, mark each nav button individually
                                if (questionResult.question_type === 'dropdown_paragraph') {
                                    var correctAnswerStr = '';
                                    
                                    // Handle both object format and string format for correct_answer
                                    if (questionResult.correct_answer && typeof questionResult.correct_answer === 'object' && questionResult.correct_answer.correct_answer) {
                                        correctAnswerStr = questionResult.correct_answer.correct_answer;
                                    } else if (typeof questionResult.correct_answer === 'string') {
                                        correctAnswerStr = questionResult.correct_answer;
                                    }
                                    
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Parse correct answers to count dropdowns and mark each nav button
                                    var parts = correctAnswerStr.split('|');
                                    $.each(parts, function(i, part) {
                                        var subparts = part.trim().split(':');
                                        if (subparts.length === 2) {
                                            var dropdownNum = subparts[0].trim();
                                            var displayNumber = displayStart + parseInt(dropdownNum, 10) - 1;
                                            var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                            navButton.addClass('nav-correct').removeClass('answered');
                                        }
                                    });
                                } else if (questionResult.question_type === 'summary_completion' || questionResult.question_type === 'table_completion') {
                                    // For summary/table completion with multiple fields, mark each nav button individually
                                    var fieldResults = questionResult.correct_answer && questionResult.correct_answer.field_results 
                                        ? questionResult.correct_answer.field_results 
                                        : {};
                                    
                                    // Get display start number for this question
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Mark each field's nav button as correct
                                    $.each(fieldResults, function(fieldNum, fieldResult) {
                                        var displayNumber = displayStart + parseInt(fieldNum, 10) - 1;
                                        var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                        navButton.addClass('nav-correct').removeClass('answered');
                                    });
                                } else if (questionResult.question_type === 'open_question') {
                                    // For open_question with multiple fields, mark each nav button individually
                                    var fieldResults = questionResult.correct_answer && questionResult.correct_answer.field_results 
                                        ? questionResult.correct_answer.field_results 
                                        : {};
                                    
                                    // Get display start number for this question
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Mark each field's nav button as correct
                                    $.each(fieldResults, function(fieldNum, fieldResult) {
                                        var displayNumber = displayStart + parseInt(fieldNum, 10) - 1;
                                        var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                        navButton.addClass('nav-correct').removeClass('answered');
                                    });
                                } else {
                                    navButtons.addClass('nav-correct').removeClass('answered');
                                }
                                
                                // Highlight the correct answer option
                                if (questionResult.question_type === 'multiple_choice' || 
                                    questionResult.question_type === 'headings' || 
                                    questionResult.question_type === 'matching' || 
                                    questionResult.question_type === 'matching_classifying' ||
                                    questionResult.question_type === 'locating_information') {
                                    // Highlight by both checked state AND correct answer value to ensure visibility
                                    var correctIndex = parseInt(questionResult.correct_answer, 10);
                                    if (!isNaN(correctIndex)) {
                                        questionElement.find('input[type="radio"][value="' + correctIndex + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                    }
                                } else if (questionResult.question_type === 'true_false') {
                                    if (questionResult.correct_answer) {
                                        questionElement.find('input[type="radio"][value="' + questionResult.correct_answer + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                    }
                                } else if (questionResult.question_type === 'dropdown_paragraph') {
                                    // For dropdown paragraph, all dropdowns are correct - mark them all with green borders
                                    questionElement.find('select.answer-select-inline').addClass('answer-correct');
                                } else if (questionResult.question_type === 'summary_completion' || questionResult.question_type === 'table_completion') {
                                    // For summary/table completion with multiple fields, all fields are correct - mark them all with green borders
                                    questionElement.find('input[type="text"].answer-input-inline').addClass('answer-correct');
                                } else if (questionResult.question_type === 'open_question') {
                                    // For open_question with multiple fields, all fields are correct - mark them all with green borders
                                    questionElement.find('input[type="text"].answer-input-inline').addClass('answer-correct');
                                }
                                
                                // Common highlighting for multiple choice variants and true_false: mark the checked answer
                                if (questionResult.question_type === 'multiple_choice' || 
                                    questionResult.question_type === 'headings' || 
                                    questionResult.question_type === 'matching' || 
                                    questionResult.question_type === 'matching_classifying' || 
                                    questionResult.question_type === 'locating_information' ||
                                    questionResult.question_type === 'true_false') {
                                    questionElement.find('input[type="radio"]:checked').closest('.option-label').addClass('answer-correct');
                                } else if (questionResult.question_type === 'multi_select') {
                                    // For multi-select, highlight all checked options in green (they're all correct if question is correct)
                                    questionElement.find('input[type="checkbox"]:checked').closest('.option-label').addClass('answer-correct');
                                } else if (questionResult.question_type === 'closed_question') {
                                    // For closed_question, determine if single or multi-select based on correct_answer type
                                    var correctAnswer = questionResult.correct_answer;
                                    if (correctAnswer && correctAnswer.correct_indices) {
                                        // Multi-select mode - highlight all checked options in green (they're all correct if question is correct)
                                        questionElement.find('input[type="checkbox"]:checked').closest('.option-label').addClass('answer-correct');
                                    } else {
                                        // Single-select mode - mark the checked answer in green
                                        questionElement.find('input[type="radio"]:checked').closest('.option-label').addClass('answer-correct');
                                    }
                                } else if (questionResult.question_type !== 'dropdown_paragraph' && 
                                           questionResult.question_type !== 'summary_completion' && 
                                           questionResult.question_type !== 'table_completion' &&
                                           questionResult.question_type !== 'open_question') {
                                    questionElement.find('input[type="text"], textarea').addClass('answer-correct');
                                }
                            } else {
                                // Mark incorrect answers in red
                                questionElement.addClass('question-incorrect');
                                
                                // For multi-select, we need to mark nav buttons progressively based on correct selections
                                if (questionResult.question_type === 'multi_select') {
                                    var userAnswers = questionResult.user_answer || [];
                                    var correctAnswers = questionResult.correct_answer || [];
                                    
                                    // Handle both old array format and new object format
                                    if (correctAnswers.correct_indices) {
                                        correctAnswers = correctAnswers.correct_indices;
                                    }
                                    
                                    // Convert to arrays if needed
                                    if (!Array.isArray(userAnswers)) {
                                        userAnswers = [userAnswers];
                                    }
                                    if (!Array.isArray(correctAnswers)) {
                                        correctAnswers = [correctAnswers];
                                    }
                                    
                                    // Count how many correct answers were selected
                                    // Use Set for O(1) lookup performance
                                    var correctAnswersSet = new Set(correctAnswers.map(function(idx) { return parseInt(idx); }));
                                    var correctSelectionsCount = 0;
                                    $.each(userAnswers, function(i, answerIndex) {
                                        if (correctAnswersSet.has(parseInt(answerIndex))) {
                                            correctSelectionsCount++;
                                        }
                                    });
                                    
                                    // Mark nav buttons: first N as correct, rest as incorrect
                                    navButtons.removeClass('answered').each(function(btnIndex) {
                                        if (btnIndex < correctSelectionsCount) {
                                            $(this).addClass('nav-correct');
                                        } else {
                                            $(this).addClass('nav-incorrect');
                                        }
                                    });
                                } else if (questionResult.question_type === 'closed_question') {
                                    // For closed_question multi-select mode, mark nav buttons individually (Issue 6)
                                    var correctAnswer = questionResult.correct_answer;
                                    
                                    if (correctAnswer && correctAnswer.correct_indices) {
                                        // Multi-select mode
                                        var userAnswers = questionResult.user_answer || [];
                                        var correctAnswers = correctAnswer.correct_indices || [];
                                        
                                        // Convert to arrays if needed
                                        if (!Array.isArray(userAnswers)) {
                                            userAnswers = [userAnswers];
                                        }
                                        if (!Array.isArray(correctAnswers)) {
                                            correctAnswers = [correctAnswers];
                                        }
                                        
                                        // Count how many correct answers were selected
                                        var correctAnswersSet = new Set(correctAnswers.map(function(idx) { return parseInt(idx); }));
                                        var correctSelectionsCount = 0;
                                        $.each(userAnswers, function(i, answerIndex) {
                                            if (correctAnswersSet.has(parseInt(answerIndex))) {
                                                correctSelectionsCount++;
                                            }
                                        });
                                        
                                        // Mark nav buttons: first N as correct (green), rest as incorrect (red)
                                        navButtons.removeClass('answered').each(function(btnIndex) {
                                            if (btnIndex < correctSelectionsCount) {
                                                $(this).addClass('nav-correct');
                                            } else {
                                                $(this).addClass('nav-incorrect');
                                            }
                                        });
                                    } else {
                                        // Single-select mode - mark as incorrect
                                        navButtons.addClass('nav-incorrect').removeClass('answered');
                                    }
                                } else if (questionResult.question_type === 'dropdown_paragraph') {
                                    // For dropdown paragraph, mark each dropdown and nav button individually
                                    var userAnswers = questionResult.user_answer || {};
                                    var correctAnswerStr = '';
                                    
                                    // Handle both object format and string format for correct_answer
                                    if (questionResult.correct_answer && typeof questionResult.correct_answer === 'object' && questionResult.correct_answer.correct_answer) {
                                        correctAnswerStr = questionResult.correct_answer.correct_answer;
                                    } else if (typeof questionResult.correct_answer === 'string') {
                                        correctAnswerStr = questionResult.correct_answer;
                                    }
                                    
                                    // Parse correct answers from format "1:A|2:B|3:C"
                                    var correctAnswersMap = {};
                                    var parts = correctAnswerStr.split('|');
                                    $.each(parts, function(i, part) {
                                        var subparts = part.trim().split(':');
                                        if (subparts.length === 2) {
                                            var dropdownNum = subparts[0].trim();
                                            var correctLetter = subparts[1].trim().toUpperCase();
                                            correctAnswersMap[dropdownNum] = correctLetter;
                                        }
                                    });
                                    
                                    // Get display start number for this question
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Check each dropdown and mark it and its nav button
                                    $.each(correctAnswersMap, function(dropdownNum, correctLetter) {
                                        var dropdown = questionElement.find('select[name="answer_' + index + '_' + dropdownNum + '"]');
                                        var userAnswer = userAnswers[dropdownNum] ? userAnswers[dropdownNum].toString().trim().toUpperCase() : '';
                                        
                                        // Calculate which nav button this corresponds to
                                        var displayNumber = displayStart + parseInt(dropdownNum, 10) - 1;
                                        var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                        
                                        if (userAnswer === correctLetter) {
                                            // Correct answer
                                            dropdown.addClass('answer-correct');
                                            navButton.removeClass('answered').addClass('nav-correct');
                                        } else {
                                            // Incorrect or not answered
                                            dropdown.addClass('answer-incorrect');
                                            navButton.removeClass('answered').addClass('nav-incorrect');
                                        }
                                    });
                                } else if (questionResult.question_type === 'summary_completion' || questionResult.question_type === 'table_completion') {
                                    // For summary/table completion with multiple fields, mark each field and nav button individually
                                    var fieldResults = questionResult.correct_answer && questionResult.correct_answer.field_results 
                                        ? questionResult.correct_answer.field_results 
                                        : {};
                                    
                                    // Get display start number for this question
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Check each field and mark it and its nav button
                                    $.each(fieldResults, function(fieldNum, fieldResult) {
                                        var inputField = questionElement.find('input[name="answer_' + index + '_field_' + fieldNum + '"]');
                                        
                                        // Calculate which nav button this corresponds to
                                        var displayNumber = displayStart + parseInt(fieldNum, 10) - 1;
                                        var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                        
                                        if (fieldResult.correct) {
                                            // Correct answer
                                            inputField.addClass('answer-correct');
                                            navButton.removeClass('answered').addClass('nav-correct');
                                        } else {
                                            // Incorrect or not answered
                                            inputField.addClass('answer-incorrect');
                                            navButton.removeClass('answered').addClass('nav-incorrect');
                                        }
                                    });
                                } else if (questionResult.question_type === 'open_question') {
                                    // For open_question with multiple fields, mark each field and nav button individually (Issue 3)
                                    var fieldResults = questionResult.correct_answer && questionResult.correct_answer.field_results 
                                        ? questionResult.correct_answer.field_results 
                                        : {};
                                    
                                    // Get display start number for this question
                                    var displayStart = parseInt(questionElement.data('display-start'), 10);
                                    
                                    // Check each field and mark it and its nav button
                                    $.each(fieldResults, function(fieldNum, fieldResult) {
                                        var inputField = questionElement.find('input[name="answer_' + index + '_field_' + fieldNum + '"]');
                                        
                                        // Calculate which nav button this corresponds to
                                        var displayNumber = displayStart + parseInt(fieldNum, 10) - 1;
                                        var navButton = $('.question-nav-btn[data-question="' + index + '"][data-display-number="' + displayNumber + '"]');
                                        
                                        // Get or create the parent wrapper for styling
                                        var fieldWrapper = inputField.closest('.open-question-field');
                                        if (!fieldWrapper.length) {
                                            // If no wrapper exists, wrap the input
                                            inputField.wrap('<div class="open-question-field"></div>');
                                            fieldWrapper = inputField.closest('.open-question-field');
                                        }
                                        
                                        if (fieldResult.correct) {
                                            // Correct answer - green background with check icon
                                            inputField.addClass('answer-correct');
                                            fieldWrapper.addClass('field-correct');
                                            navButton.removeClass('answered').addClass('nav-correct');
                                        } else if (fieldResult.user_answer && fieldResult.user_answer.trim() !== '') {
                                            // Incorrect answer (user provided an answer) - red background with cross icon
                                            inputField.addClass('answer-incorrect');
                                            fieldWrapper.addClass('field-incorrect');
                                            navButton.removeClass('answered').addClass('nav-incorrect');
                                        } else {
                                            // No answer provided - just red nav button, no fill color
                                            navButton.removeClass('answered').addClass('nav-incorrect');
                                        }
                                    });
                                } else {
                                    navButtons.addClass('nav-incorrect').removeClass('answered');
                                }
                                
                                // Highlight the user's wrong answer (if they selected one)
                                if (questionResult.question_type === 'multiple_choice' || 
                                    questionResult.question_type === 'headings' || 
                                    questionResult.question_type === 'matching' || 
                                    questionResult.question_type === 'matching_classifying' || 
                                    questionResult.question_type === 'locating_information' ||
                                    questionResult.question_type === 'true_false') {
                                    var checkedRadio = questionElement.find('input[type="radio"]:checked');
                                    if (checkedRadio.length > 0) {
                                        checkedRadio.closest('.option-label').addClass('answer-incorrect');
                                    }
                                    
                                    // Always highlight the correct answer in green (even when no answer was selected)
                                    if (questionResult.question_type === 'multiple_choice' || 
                                        questionResult.question_type === 'headings' || 
                                        questionResult.question_type === 'matching' || 
                                        questionResult.question_type === 'matching_classifying' ||
                                        questionResult.question_type === 'locating_information') {
                                        var correctIndex = parseInt(questionResult.correct_answer);
                                        if (!isNaN(correctIndex)) {
                                            questionElement.find('input[type="radio"][value="' + correctIndex + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                        }
                                    } else if (questionResult.question_type === 'true_false') {
                                        if (questionResult.correct_answer) {
                                            questionElement.find('input[type="radio"][value="' + questionResult.correct_answer + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                        }
                                    }
                                } else if (questionResult.question_type === 'multi_select') {
                                    // For multi-select questions:
                                    // 1. Highlight user's selections (some may be correct, some incorrect)
                                    // 2. Highlight all correct answers in green
                                    var userAnswers = questionResult.user_answer || [];
                                    var correctAnswers = questionResult.correct_answer || [];
                                    
                                    // Handle both old array format and new object format
                                    if (correctAnswers.correct_indices) {
                                        correctAnswers = correctAnswers.correct_indices;
                                    }
                                    
                                    // Convert to arrays if needed
                                    if (!Array.isArray(userAnswers)) {
                                        userAnswers = [userAnswers];
                                    }
                                    if (!Array.isArray(correctAnswers)) {
                                        correctAnswers = [correctAnswers];
                                    }
                                    
                                    // Convert correctAnswers to a Set for O(1) lookup performance
                                    var correctAnswersSet = new Set(correctAnswers.map(function(idx) { return parseInt(idx); }));
                                    
                                    // First, mark user's incorrect selections in red
                                    $.each(userAnswers, function(i, answerIndex) {
                                        if (!correctAnswersSet.has(parseInt(answerIndex))) {
                                            questionElement.find('input[type="checkbox"][value="' + answerIndex + '"]').closest('.option-label').addClass('answer-incorrect');
                                        }
                                    });
                                    
                                    // Then, mark all correct answers in green (highlight style)
                                    $.each(correctAnswers, function(i, correctIndex) {
                                        var checkbox = questionElement.find('input[type="checkbox"][value="' + correctIndex + '"]');
                                        if (checkbox.is(':checked')) {
                                            // User selected this correct answer - use solid green
                                            checkbox.closest('.option-label').addClass('answer-correct');
                                        } else {
                                            // User missed this correct answer - use highlight green
                                            checkbox.closest('.option-label').addClass('answer-correct-highlight');
                                        }
                                    });
                                } else if (questionResult.question_type === 'closed_question') {
                                    // For closed_question, handle both single and multi-select modes
                                    var correctAnswer = questionResult.correct_answer;
                                    
                                    if (correctAnswer && correctAnswer.correct_indices) {
                                        // Multi-select mode - similar to multi_select
                                        var userAnswers = questionResult.user_answer || [];
                                        var correctAnswers = correctAnswer.correct_indices || [];
                                        
                                        // Convert to arrays if needed
                                        if (!Array.isArray(userAnswers)) {
                                            userAnswers = [userAnswers];
                                        }
                                        if (!Array.isArray(correctAnswers)) {
                                            correctAnswers = [correctAnswers];
                                        }
                                        
                                        // Convert correctAnswers to a Set for O(1) lookup performance
                                        var correctAnswersSet = new Set(correctAnswers.map(function(idx) { return parseInt(idx); }));
                                        
                                        // First, mark user's incorrect selections in red
                                        $.each(userAnswers, function(i, answerIndex) {
                                            if (!correctAnswersSet.has(parseInt(answerIndex))) {
                                                questionElement.find('input[type="checkbox"][value="' + answerIndex + '"]').closest('.option-label').addClass('answer-incorrect');
                                            }
                                        });
                                        
                                        // Then, mark all correct answers in green
                                        $.each(correctAnswers, function(i, correctIndex) {
                                            var checkbox = questionElement.find('input[type="checkbox"][value="' + correctIndex + '"]');
                                            if (checkbox.is(':checked')) {
                                                // User selected this correct answer - use solid green with tick
                                                checkbox.closest('.option-label').addClass('answer-correct');
                                            } else {
                                                // User missed this correct answer - use highlight green (border only)
                                                checkbox.closest('.option-label').addClass('answer-correct-highlight');
                                            }
                                        });
                                    } else {
                                        // Single-select mode - similar to multiple_choice
                                        var userAnswer = questionResult.user_answer;
                                        var correctIndex = correctAnswer && correctAnswer.correct_idx !== undefined ? correctAnswer.correct_idx : correctAnswer;
                                        
                                        // Mark user's wrong answer in red if they selected one
                                        if (userAnswer !== null && userAnswer !== '' && userAnswer != correctIndex) {
                                            questionElement.find('input[type="radio"][value="' + userAnswer + '"]').closest('.option-label').addClass('answer-incorrect');
                                        }
                                        
                                        // Always highlight the correct answer in green
                                        if (correctIndex !== null && correctIndex !== undefined && !isNaN(parseInt(correctIndex))) {
                                            questionElement.find('input[type="radio"][value="' + correctIndex + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                        }
                                    }
                                } else if (questionResult.question_type !== 'dropdown_paragraph' && 
                                           questionResult.question_type !== 'summary_completion' && 
                                           questionResult.question_type !== 'table_completion' &&
                                           questionResult.question_type !== 'open_question') {
                                    // Only mark text fields as incorrect if it's not a dropdown paragraph, summary completion, table completion, or open_question
                                    // (those types are already handled above with field-level marking)
                                    questionElement.find('input[type="text"], textarea').addClass('answer-incorrect');
                                }
                            }
                            
                            // Add per-question text feedback messages for all layouts
                            if (questionResult.question_type === 'multi_select') {
                                // For multi_select, show individual feedback only under selected options
                                // Remove any existing feedback first
                                questionElement.find('.option-feedback-message').remove();
                                questionElement.find('.question-feedback-message').remove();
                                
                                // Get option feedback from correct_answer object
                                var optionFeedback = questionResult.correct_answer && questionResult.correct_answer.option_feedback 
                                    ? questionResult.correct_answer.option_feedback 
                                    : {};
                                
                                // Display feedback only under selected options that have feedback
                                $.each(optionFeedback, function(optionIndex, feedbackText) {
                                    if (feedbackText) {
                                        var checkbox = questionElement.find('input[type="checkbox"][value="' + optionIndex + '"]');
                                        // Only show feedback if this option was selected by the user
                                        if (checkbox.is(':checked')) {
                                            var optionLabel = checkbox.closest('.option-label');
                                            
                                            // Create feedback element for this option
                                            var feedbackDiv = $('<div>')
                                                .addClass('option-feedback-message')
                                                .html(feedbackText); // Using .html() because feedback explicitly supports HTML formatting
                                                                     // Content is sanitized server-side with wp_kses_post() in class-quiz-handler.php
                                            
                                            // Append feedback inside the option label
                                            optionLabel.append(feedbackDiv);
                                        }
                                    }
                                });
                                
                                // Also show general no-answer feedback if provided and no options were selected
                                if (questionResult.feedback && (!questionResult.user_answer || questionResult.user_answer.length === 0)) {
                                    var feedbackDiv = $('<div>')
                                        .addClass('question-feedback-message')
                                        .addClass('feedback-incorrect')
                                        .html(questionResult.feedback);
                                    questionElement.append(feedbackDiv);
                                }
                            } else if (questionResult.question_type === 'closed_question') {
                                // For closed_question, show feedback only for selected options (both multi-select and single-select)
                                // Remove any existing feedback first
                                questionElement.find('.option-feedback-message').remove();
                                questionElement.find('.question-feedback-message').remove();
                                
                                // Check if this is a multi-select or single-select closed question
                                var questionOptions = questionElement.find('.question-options');
                                var correctAnswerCount = questionOptions.length ? (questionOptions.data('correct-count') || 1) : 1;
                                
                                if (questionResult.correct_answer && questionResult.correct_answer.option_feedback) {
                                    // Both multi-select and single-select: show option-specific feedback only for selected options
                                    var optionFeedback = questionResult.correct_answer.option_feedback;
                                    
                                    // Display feedback only under selected options that have feedback
                                    $.each(optionFeedback, function(optionIndex, feedbackText) {
                                        if (feedbackText) {
                                            // For multi-select, look for checkboxes; for single-select, look for radio buttons
                                            var inputSelector = correctAnswerCount > 1 
                                                ? 'input[type="checkbox"][value="' + optionIndex + '"]'
                                                : 'input[type="radio"][value="' + optionIndex + '"]';
                                            var input = questionElement.find(inputSelector);
                                            // Only show feedback if this option was selected by the user
                                            if (input.length > 0 && input.is(':checked')) {
                                                var optionLabel = input.closest('.option-label');
                                                if (optionLabel.length > 0) {
                                                    // Create feedback element for this option
                                                    var feedbackDiv = $('<div>')
                                                        .addClass('option-feedback-message')
                                                        .html(feedbackText);
                                                    
                                                    // Append feedback inside the option label
                                                    optionLabel.append(feedbackDiv);
                                                }
                                            }
                                        }
                                    });
                                    
                                    // Also show general no-answer feedback if provided and no options were selected
                                    if (questionResult.feedback && (!questionResult.user_answer || 
                                        (Array.isArray(questionResult.user_answer) && questionResult.user_answer.length === 0) ||
                                        questionResult.user_answer === null || questionResult.user_answer === '')) {
                                        var feedbackDiv = $('<div>')
                                            .addClass('question-feedback-message')
                                            .addClass('feedback-incorrect')
                                            .html(questionResult.feedback);
                                        questionElement.append(feedbackDiv);
                                    }
                                } else if (questionResult.feedback) {
                                    // Fallback: show feedback at question level if option_feedback not available
                                    var feedbackClass = questionResult.correct ? 'feedback-correct' : 'feedback-incorrect';
                                    var feedbackDiv = $('<div>')
                                        .addClass('question-feedback-message')
                                        .addClass(feedbackClass)
                                        .html(questionResult.feedback);
                                    questionElement.append(feedbackDiv);
                                }
                            } else if (questionResult.question_type === 'multiple_choice' || 
                                       questionResult.question_type === 'matching' || 
                                       questionResult.question_type === 'matching_classifying' ||
                                       questionResult.question_type === 'headings' ||
                                       questionResult.question_type === 'locating_information') {
                                // For single-select question types with options (including legacy types), show feedback strategically:
                                // - Under correct answer option (always)
                                // - Under incorrect selected option (if user selected it)
                                // - At bottom (only if nothing was selected)
                                // Note: These legacy types are still supported for existing XML files
                                // Remove any existing feedback first
                                questionElement.find('.option-feedback-message').remove();
                                questionElement.find('.question-feedback-message').remove();
                                
                                if (questionResult.feedback) {
                                    var selectedRadio = questionElement.find('input[type="radio"]:checked');
                                    var correctIndex = parseInt(questionResult.correct_answer, 10);
                                    var userAnswer = questionResult.user_answer;
                                    
                                    if (selectedRadio.length > 0) {
                                        // User selected an answer
                                        var selectedValue = parseInt(selectedRadio.val(), 10);
                                        
                                        if (questionResult.correct) {
                                            // User selected correct answer - show feedback inside the correct option
                                            var correctLabel = selectedRadio.closest('.option-label');
                                            var feedbackDiv = $('<div>')
                                                .addClass('option-feedback-message')
                                                .html(questionResult.feedback);
                                            correctLabel.append(feedbackDiv);
                                        } else {
                                            // User selected wrong answer
                                            // Show feedback inside the incorrect selected option
                                            var incorrectLabel = selectedRadio.closest('.option-label');
                                            var feedbackDiv = $('<div>')
                                                .addClass('option-feedback-message')
                                                .html(questionResult.feedback);
                                            incorrectLabel.append(feedbackDiv);
                                            
                                            // Also show feedback inside the correct answer option
                                            if (!isNaN(correctIndex)) {
                                                var correctRadio = questionElement.find('input[type="radio"][value="' + correctIndex + '"]');
                                                if (correctRadio.length > 0) {
                                                    var correctLabel = correctRadio.closest('.option-label');
                                                    // Show what the correct answer explanation is
                                                    var correctFeedbackDiv = $('<div>')
                                                        .addClass('option-feedback-message')
                                                        .html('<strong>This is the correct answer.</strong>');
                                                    correctLabel.append(correctFeedbackDiv);
                                                }
                                            }
                                        }
                                    } else {
                                        // No option selected - show general no-answer feedback at question level
                                        // and clearly indicate the correct answer
                                        var feedbackText = questionResult.feedback;
                                        
                                        // Add correct answer indication if we have the correct answer index and options
                                        if (!isNaN(correctIndex) && questionResult.options) {
                                            var options = [];
                                            
                                            // Parse options - can be array of objects or newline-separated string
                                            if (Array.isArray(questionResult.options)) {
                                                options = questionResult.options;
                                            } else if (typeof questionResult.options === 'string' && questionResult.options.trim()) {
                                                var optionLines = questionResult.options.split('\n').filter(function(line) {
                                                    return line.trim().length > 0;
                                                });
                                                options = optionLines.map(function(line) {
                                                    return { text: line.trim() };
                                                });
                                            }
                                            
                                            // Add correct answer text if available
                                            if (correctIndex >= 0 && correctIndex < options.length) {
                                                var correctOptionText = typeof options[correctIndex] === 'object' 
                                                    ? options[correctIndex].text 
                                                    : options[correctIndex];
                                                if (correctOptionText && typeof correctOptionText === 'string') {
                                                    feedbackText += '<br><br><strong>The correct answer is:</strong> ' + 
                                                                   $('<div>').text(correctOptionText).html(); // Escape HTML
                                                }
                                            }
                                        }
                                        
                                        var feedbackDiv = $('<div>')
                                            .addClass('question-feedback-message')
                                            .addClass('feedback-incorrect')
                                            .html(feedbackText);
                                        questionElement.append(feedbackDiv);
                                    }
                                }
                            } else if (questionResult.question_type === 'dropdown_paragraph') {
                                // For dropdown paragraph, show feedback at question level
                                // Remove any existing feedback first
                                questionElement.find('.question-feedback-message').remove();
                                
                                if (questionResult.feedback) {
                                    // Create feedback element with proper CSS classes
                                    var feedbackClass = questionResult.correct ? 'feedback-correct' : 'feedback-incorrect';
                                    var feedbackDiv = $('<div>')
                                        .addClass('question-feedback-message')
                                        .addClass(feedbackClass)
                                        .html(questionResult.feedback); // Using .html() because feedback explicitly supports HTML formatting
                                                                         // Content is sanitized server-side with wp_kses_post() in class-quiz-handler.php
                                    
                                    questionElement.append(feedbackDiv);
                                }
                            } else if (questionResult.feedback) {
                                // For other question types (text input, etc.), show general feedback
                                // Remove any existing feedback first
                                questionElement.find('.question-feedback-message').remove();
                                
                                // Create feedback element with proper CSS classes
                                var feedbackClass = questionResult.correct ? 'feedback-correct' : 'feedback-incorrect';
                                var feedbackDiv = $('<div>')
                                    .addClass('question-feedback-message')
                                    .addClass(feedbackClass)
                                    .html(questionResult.feedback); // Using .html() because feedback explicitly supports HTML formatting
                                                                     // Content is sanitized server-side with wp_kses_post() in class-quiz-handler.php
                                
                                // Add special class for open questions to show icon beside feedback
                                if (questionResult.question_type === 'open_question') {
                                    feedbackDiv.addClass('open-question-feedback');
                                }
                                
                                // Add "Show in transcript" link if audio_section_id is available (for listening tests)
                                // Note: "Show me the section of the reading passage" links are added in a unified manner
                                // after all question processing (see lines 1032+) to handle all question types consistently
                                // Skip for open_question type since links are added per-field in PHP
                                if (questionResult.question_type !== 'open_question') {
                                    if (questionResult.audio_section_id !== null && 
                                        questionResult.audio_section_id !== undefined) {
                                        var transcriptLink = $('<a>')
                                            .attr('href', '#')
                                            .addClass('show-in-transcript-link')
                                            .attr('data-section', questionResult.audio_section_id)
                                            .attr('data-question', questionNum)
                                            .text('Show in transcript');
                                        
                                        feedbackDiv.append('<br>').append(transcriptLink);
                                        
                                        // Add "Listen to this answer" button if audio timing is available
                                        if (questionResult.audio_start_time !== null && 
                                            questionResult.audio_start_time !== undefined &&
                                            questionResult.audio_end_time !== null && 
                                            questionResult.audio_end_time !== undefined) {
                                            var listenLink = $('<a>')
                                                .attr('href', '#')
                                                .addClass('listen-to-answer-link')
                                                .attr('data-start-time', questionResult.audio_start_time)
                                                .attr('data-end-time', questionResult.audio_end_time)
                                                .attr('data-question', questionNum)
                                                .text('Listen to this answer')
                                                .css('margin-left', '10px');
                                            
                                            feedbackDiv.append(listenLink);
                                        }
                                    }
                                }
                                
                                questionElement.append(feedbackDiv);
                            }
                        });
                        
                        // Add "Show me the section of the reading passage" buttons for reading tests
                        // This applies to ALL question types that have a reading_text_id
                        $.each(result.question_results, function(index, questionResult) {
                            var questionNum = parseInt(index, 10) + 1;
                            var questionElement = $('#question-' + index);
                            
                            // Only add button if reading_text_id is present and this is not an open_question
                            // (open_question has the buttons added per-field in PHP)
                            if (questionResult.reading_text_id !== null && 
                                questionResult.reading_text_id !== undefined &&
                                questionResult.question_type !== 'open_question') {
                                
                                // Check if button already exists to avoid duplicates
                                if (questionElement.find('.show-in-reading-passage-link').length === 0) {
                                    var readingLink = $('<a>')
                                        .attr('href', '#')
                                        .addClass('show-in-reading-passage-link')
                                        .attr('data-reading-text', questionResult.reading_text_id)
                                        .attr('data-question', questionNum)
                                        .text('Show me the section of the reading passage');
                                    
                                    // Append button to the question element
                                    questionElement.append($('<br>')).append(readingLink);
                                }
                            }
                        });
                        
                        // Show results in a modal for all quiz types (both standard and CBT)
                        showCBTResultModal(html, result.next_url, result.course_url);
                        form.find('button[type="submit"]').hide();
                        
                        if (isCBT) {
                            // Update the fullscreen timer UI component (CBT-specific feature - standard layout doesn't have this element)
                            var timerElement = form.find('.quiz-timer-fullscreen');
                            if (timerElement.length > 0) {
                                // Preserve and update the navigation links
                                var nextLinkElement = timerElement.find('.next-page-link, .return-course-link');
                                
                                // Update link URL to next_url if available, otherwise use course_url
                                var linkUrl = result.next_url || result.course_url;
                                if (nextLinkElement.length && linkUrl) {
                                    nextLinkElement.attr('href', linkUrl);
                                    // The text is already set correctly in the template
                                }
                                
                                var navLinksHtml = timerElement.find('.timer-left-section').html() || '';
                                
                                // Remove submit button from right section before preserving HTML
                                var rightSection = timerElement.find('.timer-right-section').clone();
                                rightSection.find('button[type="submit"]').remove();
                                var rightSectionHtml = rightSection.html() || '';
                                
                                var scoreHtml = '';
                                if (result.display_type === 'band') {
                                    scoreHtml = '<div class="timer-content"><strong>Band Score:</strong> <span>' + result.display_score + '</span></div>';
                                } else {
                                    scoreHtml = '<div class="timer-content"><strong>Score:</strong> <span>' + result.percentage + '%</span></div>';
                                }
                                
                                timerElement.html('<div class="timer-left-section">' + navLinksHtml + '</div>' + scoreHtml + '<div class="timer-right-section">' + rightSectionHtml + '</div>');
                                
                                // Make sure the bottom bar is visible (it will be shown when "Review my answers" is clicked)
                                timerElement.addClass('quiz-completed-bar');
                            }
                            
                            // For listening quizzes, show transcript after results are displayed
                            var isListeningQuiz = quizContainer.hasClass('ielts-listening-practice-quiz') || 
                                                 quizContainer.hasClass('ielts-listening-exercise-quiz') ||
                                                 quizContainer.data('test-type') === 'listening';
                            if (isListeningQuiz) {
                                var audioPlayerContainer = quizContainer.find('#listening-audio-player');
                                var transcriptContainer = quizContainer.find('#listening-transcript, #listening-transcripts');
                                
                                if (audioPlayerContainer.length && transcriptContainer.length) {
                                    // Hide audio player and show transcript(s)
                                    audioPlayerContainer.fadeOut(300, function() {
                                        transcriptContainer.fadeIn(300);
                                    });
                                }
                            }
                        }
                    } else {
                        showMessage('error', response.data.message || 'Failed to submit quiz');
                        form.find('button[type="submit"]').prop('disabled', false).text('Submit Quiz');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred. Please try again.');
                    form.find('button[type="submit"]').prop('disabled', false).text('Submit Quiz');
                }
            });
        });
        
        // Event delegation for dynamically created quiz retake button
        $(document).on('click', '.quiz-retake-btn', function(e) {
            e.preventDefault();
            forceReload();
        });
        
        // Helper function to show messages
        function showMessage(type, message) {
            var messageHtml = '<div class="ielts-message ' + type + '">' + message + '</div>';
            
            // Remove existing messages
            $('.ielts-message').remove();
            
            // Add new message at the top of the page
            $('body').prepend(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: 0
            }, 300);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $('.ielts-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Computer-Based Quiz Layout: Question Navigation
        $('.question-nav-btn').on('click', function(e) {
            e.preventDefault();
            var questionIndex = $(this).data('question');
            var questionElement = $('#question-' + questionIndex);
            
            if (questionElement.length) {
                // Scroll to the question in the right column
                var questionsColumn = $('.questions-column');
                var questionOffset = questionElement.position().top;
                var columnScrollTop = questionsColumn.scrollTop();
                
                questionsColumn.animate({
                    scrollTop: columnScrollTop + questionOffset - 50
                }, 300);
                
                // Highlight the question briefly
                questionElement.addClass('highlight-question');
                setTimeout(function() {
                    questionElement.removeClass('highlight-question');
                }, 1000);
                
                // Switch reading text if needed
                switchReadingText(questionElement);
                // Switch audio section if needed
                switchAudioSection(questionElement);
                // Switch transcript section if needed
                switchTranscriptSection(questionElement);
            }
        });
        
        // Track currently visible reading text to avoid unnecessary scrolling
        var currentReadingTextId = null;
        
        // Function to switch reading text based on question
        function switchReadingText(questionElement) {
            var readingTextId = questionElement.data('reading-text-id');
            
            // Only switch if the question has a linked reading text
            // Note: readingTextId can be 0, which is valid, so we check !== null and !== undefined
            if (readingTextId !== null && readingTextId !== undefined && readingTextId !== '') {
                // Check if we're switching to a different passage
                var isDifferentPassage = (currentReadingTextId !== readingTextId);
                
                // Only hide/show and scroll when actually switching to a different passage
                if (isDifferentPassage) {
                    // Hide all reading texts
                    $('.reading-text-section').hide();
                    // Show the linked reading text
                    $('#reading-text-' + readingTextId).fadeIn(300);
                    
                    // Scroll to top when switching to a different passage
                    $('.reading-column').animate({
                        scrollTop: 0
                    }, 300);
                    
                    // Update the current reading text ID
                    currentReadingTextId = readingTextId;
                }
            } else {
                // If no valid reading text, reset the tracking variable
                currentReadingTextId = null;
            }
        }
        
        // Function to switch audio section based on question
        var currentAudioSectionId = null;
        function switchAudioSection(questionElement) {
            var audioSectionId = questionElement.data('audio-section-id');
            
            // Only switch if the question has a linked audio section
            if (audioSectionId && audioSectionId !== '') {
                // Check if we're switching to a different section
                var isDifferentSection = (currentAudioSectionId !== audioSectionId);
                
                // Only hide/show when actually switching to a different section
                if (isDifferentSection) {
                    // Hide all audio sections
                    $('.audio-section-content').hide();
                    // Show the linked audio section
                    $('#audio-section-' + audioSectionId).fadeIn(300);
                    
                    // Update tab buttons
                    $('.audio-section-tab').removeClass('active');
                    $('.audio-section-tab[data-section="' + audioSectionId + '"]').addClass('active');
                    
                    // Update the current audio section ID
                    currentAudioSectionId = audioSectionId;
                }
            } else {
                // If no valid audio section, reset the tracking variable
                currentAudioSectionId = null;
            }
        }
        
        // Function to switch transcript section based on question
        var currentTranscriptSectionId = null;
        function switchTranscriptSection(questionElement) {
            var audioSectionId = questionElement.data('audio-section-id');
            
            // Only switch if the question has a linked audio section
            if (audioSectionId && audioSectionId !== '') {
                // Check if we're switching to a different section
                var isDifferentSection = (currentTranscriptSectionId !== audioSectionId);
                
                // Only hide/show when actually switching to a different section
                if (isDifferentSection) {
                    // Hide all transcript sections
                    $('.transcript-section-content').hide();
                    // Show the linked transcript section
                    $('#transcript-section-' + audioSectionId).fadeIn(300);
                    
                    // Update tab buttons
                    $('.transcript-section-tab').removeClass('active');
                    $('.transcript-section-tab[data-section="' + audioSectionId + '"]').addClass('active');
                    
                    // Update the current transcript section ID
                    currentTranscriptSectionId = audioSectionId;
                }
            } else {
                // If no valid audio section, reset the tracking variable
                currentTranscriptSectionId = null;
            }
        }
        
        // Detect scroll position and switch reading text/audio section automatically
        if ($('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').length) {
            var questionsColumn = $('.questions-column');
            var scrollTimeout;
            
            questionsColumn.on('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    // Find the question most visible in viewport
                    var columnScrollTop = questionsColumn.scrollTop();
                    var columnHeight = questionsColumn.height();
                    var viewportCenter = columnScrollTop + (columnHeight / 2);
                    
                    var closestQuestion = null;
                    var closestDistance = Infinity;
                    var columnOffset = questionsColumn.offset().top;
                    
                    $('.quiz-question').each(function() {
                        var $question = $(this);
                        var questionTop = $question.offset().top - columnOffset + columnScrollTop;
                        var questionCenter = questionTop + ($question.height() / 2);
                        var distance = Math.abs(questionCenter - viewportCenter);
                        
                        if (distance < closestDistance) {
                            closestDistance = distance;
                            closestQuestion = $question;
                        }
                    });
                    
                    if (closestQuestion) {
                        switchReadingText(closestQuestion);
                        switchAudioSection(closestQuestion);
                        switchTranscriptSection(closestQuestion);
                    }
                }, 150); // Debounce scroll events
            });
        }
        
        // Manual audio section tab switching
        $(document).on('click', '.audio-section-tab', function(e) {
            e.preventDefault();
            var sectionId = $(this).data('section');
            var audioContainer = $(this).closest('.audio-section-container, .listening-audio-content');
            audioContainer.find('.audio-section-content').hide();
            audioContainer.find('#audio-section-' + sectionId).fadeIn(300);
            audioContainer.find('.audio-section-tab').removeClass('active');
            $(this).addClass('active');
            currentAudioSectionId = sectionId;
        });
        
        // Manual transcript section tab switching
        $(document).on('click', '.transcript-section-tab', function(e) {
            e.preventDefault();
            var sectionId = $(this).data('section');
            var transcriptContainer = $(this).closest('.listening-transcripts, .listening-transcript');
            transcriptContainer.find('.transcript-section-content').hide();
            transcriptContainer.find('#transcript-section-' + sectionId).fadeIn(300);
            transcriptContainer.find('.transcript-section-tab').removeClass('active');
            $(this).addClass('active');
        });
        
        // Delegated event handler for "Show in transcript" links (including PHP-generated links in feedback)
        $(document).on('click', '.show-in-transcript-link', function(e) {
            e.preventDefault();
            var sectionId = $(this).data('section');
            var questionNumber = $(this).data('question');
            
            // Hide all transcript sections
            $('.transcript-section-content').hide();
            
            // Show the linked transcript section
            var $targetSection = $('#transcript-section-' + sectionId);
            $targetSection.fadeIn(300);
            
            // Update tab active states
            $('.transcript-section-tab').removeClass('active');
            $('.transcript-section-tab[data-section="' + sectionId + '"]').addClass('active');
            
            // Find and highlight the question marker in the transcript
            var $questionMarker = $('#transcript-q' + questionNumber);
            
            // Remove any previous highlighting
            $('.transcript-content .transcript-highlight').removeClass('transcript-highlight');
            
            if ($questionMarker.length) {
                // Highlight the specific sentence or phrase containing the marker
                // For table-formatted transcripts (most common), highlight the table cell
                var $tableCell = $questionMarker.closest('td');
                if ($tableCell.length) {
                    // For table cells, highlight the cell containing the answer
                    $tableCell.addClass('transcript-highlight');
                } else {
                    // For non-table content, highlight the containing paragraph
                    var $paragraph = $questionMarker.closest('p');
                    if ($paragraph.length) {
                        $paragraph.addClass('transcript-highlight');
                    }
                }
                
                // Scroll to the question marker
                setTimeout(function() {
                    // For CBT layout, scroll within the reading column
                    var $readingColumn = $questionMarker.closest('.reading-column');
                    if ($readingColumn.length) {
                        // CBT layout - scroll within the column
                        var markerOffset = $questionMarker.position().top;
                        var columnScrollTop = $readingColumn.scrollTop();
                        $readingColumn.animate({
                            scrollTop: columnScrollTop + markerOffset - 100
                        }, 500);
                    } else {
                        // Standard layout - scroll the whole page
                        $('html, body').animate({
                            scrollTop: $questionMarker.offset().top - 100
                        }, 500);
                    }
                }, 350); // Wait for section fade-in
            } else {
                // If no marker found, just scroll to transcript container
                var transcriptContainer = $('#listening-transcript, #listening-transcripts');
                if (transcriptContainer.length) {
                    $('html, body').animate({
                        scrollTop: transcriptContainer.offset().top - 100
                    }, 500);
                }
            }
        });
        
        // Delegated event handler for "Listen to this answer" links
        $(document).on('click', '.listen-to-answer-link', function(e) {
            e.preventDefault();
            var $button = $(this);
            
            // Prevent multiple clicks while loading
            if ($button.hasClass('loading')) {
                return;
            }
            
            var startTime = parseFloat($button.data('start-time'));
            var endTime = parseFloat($button.data('end-time'));
            
            // Find the audio player element
            var $audioPlayer = $('#listening-audio-player audio').first();
            
            if ($audioPlayer.length && !isNaN(startTime) && !isNaN(endTime)) {
                var audioElement = $audioPlayer[0];
                
                // Show loading state
                $button.addClass('loading');
                
                // Define handler functions before using them
                // seekedHandler is a one-time handler that removes itself after execution
                var seekedHandler = function() {
                    $button.removeClass('loading');
                    audioElement.removeEventListener('seeked', seekedHandler);
                };
                
                // Set the current time to start time
                audioElement.currentTime = startTime;
                
                // Remove loading state when seeking is complete
                audioElement.addEventListener('seeked', seekedHandler);
                
                // Play the audio
                var playPromise = audioElement.play();
                
                // Handle play promise
                if (playPromise !== undefined) {
                    playPromise.catch(function(error) {
                        // Remove loading state on error
                        $button.removeClass('loading');
                        audioElement.removeEventListener('seeked', seekedHandler);
                        console.log('Audio playback failed:', error);
                    });
                }
                
                // Set up an event listener to stop at end time
                var timeUpdateHandler = function() {
                    if (audioElement.currentTime >= endTime) {
                        audioElement.pause();
                        audioElement.removeEventListener('timeupdate', timeUpdateHandler);
                    }
                };
                
                audioElement.addEventListener('timeupdate', timeUpdateHandler);
                
                // Also ensure we remove the listener if user stops/pauses manually
                var pauseHandler = function() {
                    audioElement.removeEventListener('timeupdate', timeUpdateHandler);
                    audioElement.removeEventListener('pause', pauseHandler);
                };
                audioElement.addEventListener('pause', pauseHandler);
            }
        });
        
        // Delegated event handler for "Show me the section of the reading passage" links (for reading tests)
        $(document).on('click', '.show-in-reading-passage-link', function(e) {
            e.preventDefault();
            var readingTextId = $(this).data('reading-text');
            var questionNumber = $(this).data('question');
            
            // Hide all reading text sections
            $('.reading-text-section').hide();
            
            // Show the linked reading text section
            var $targetText = $('#reading-text-' + readingTextId);
            $targetText.fadeIn(300);
            
            // Find the question marker in the reading passage
            // Try passage-q# first (new format), then transcript-q# (old format for backward compatibility)
            var $questionMarker = $('#passage-q' + questionNumber);
            if (!$questionMarker.length) {
                $questionMarker = $('#transcript-q' + questionNumber);
            }
            
            // Remove any previous highlighting
            $('.reading-text .reading-passage-highlight').removeClass('reading-passage-highlight');
            
            if ($questionMarker.length) {
                // Highlight the answer text associated with this question
                // Look for reading-answer-marker (new manual format) or reading-answer-highlight (auto-generated)
                var $container = $questionMarker.closest('.reading-text');
                var $answerHighlight = $container.find('.reading-answer-highlight[data-question="' + questionNumber + '"], .reading-answer-marker[data-question="' + questionNumber + '"]').first();
                
                // If no data-question attribute, try finding the next sibling .reading-answer-marker (manual format without data-question)
                if (!$answerHighlight.length) {
                    $answerHighlight = $questionMarker.next('.reading-answer-marker');
                }
                
                if ($answerHighlight.length) {
                    $answerHighlight.addClass('reading-passage-highlight');
                } else {
                    // Fallback: highlight the containing paragraph
                    var $paragraph = $questionMarker.closest('p');
                    if ($paragraph.length) {
                        $paragraph.addClass('reading-passage-highlight');
                    }
                }
                
                // Scroll to the question marker, centering the highlighted section
                setTimeout(function() {
                    // For CBT layout, scroll within the reading column
                    var $readingColumn = $targetText.closest('.reading-column');
                    if ($readingColumn.length) {
                        // CBT layout - scroll to center the marker/highlight within the column viewport
                        var elementToCenter = $answerHighlight.length ? $answerHighlight : $questionMarker;
                        var markerOffset = elementToCenter.position().top;
                        var columnScrollTop = $readingColumn.scrollTop();
                        var columnHeight = $readingColumn.height();
                        var elementHeight = elementToCenter.outerHeight();
                        // Center the element: scroll so element center aligns with viewport center
                        var targetScrollTop = columnScrollTop + markerOffset - (columnHeight / 2) + (elementHeight / 2);
                        $readingColumn.animate({
                            scrollTop: targetScrollTop
                        }, 500);
                    } else {
                        // Standard layout - scroll the whole page to center the marker/highlight
                        var elementToCenter = $answerHighlight.length ? $answerHighlight : $questionMarker;
                        var windowHeight = $(window).height();
                        var elementHeight = elementToCenter.outerHeight();
                        // Center the element: scroll so element center aligns with viewport center
                        var targetScrollTop = elementToCenter.offset().top - (windowHeight / 2) + (elementHeight / 2);
                        $('html, body').animate({
                            scrollTop: targetScrollTop
                        }, 500);
                    }
                }, 350); // Wait for section fade-in
            } else {
                // If no marker found, just scroll to the reading text
                setTimeout(function() {
                    // For CBT layout, scroll within the reading column
                    var $readingColumn = $targetText.closest('.reading-column');
                    if ($readingColumn.length) {
                        // CBT layout - scroll to top of the column to show the reading text
                        $readingColumn.animate({
                            scrollTop: 0
                        }, 500);
                    } else {
                        // Standard layout - scroll the whole page to the reading text
                        $('html, body').animate({
                            scrollTop: $targetText.offset().top - 100
                        }, 500);
                    }
                }, 350); // Wait for section fade-in
            }
        });
        
        // Track answered questions in computer-based layout using event delegation
        $('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').on('change', 'input[type="radio"]', function() {
            var questionIndex = $(this).attr('name').replace('answer_', '');
            var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"]');
            navButton.addClass('answered');
        });
        
        $('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').on('input', 'input[type="text"], textarea', function() {
            var name = $(this).attr('name');
            
            // Check if this is a summary completion or table completion field (format: answer_X_field_Y)
            var fieldMatch = name.match(/^answer_(\d+)_field_(\d+)$/);
            if (fieldMatch) {
                var questionIndex = fieldMatch[1];
                var fieldNum = parseInt(fieldMatch[2]);
                
                // Find the question element to get its display number range
                var questionElement = $(this).closest('.quiz-question');
                var displayStart = parseInt(questionElement.data('display-start'), 10);
                
                // Calculate which display number corresponds to this field
                // Field numbers start from 1, so field 1 = first button, field 2 = second button, etc.
                var displayNumber = displayStart + fieldNum - 1;
                
                // Find the specific nav button for this field
                var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"][data-display-number="' + displayNumber + '"]');
                
                if ($(this).val().trim().length > 0) {
                    navButton.addClass('answered');
                } else {
                    navButton.removeClass('answered');
                }
            } else {
                // Regular text input or legacy format - mark all nav buttons for the question
                var questionIndex = name.replace('answer_', '').split('_')[0];
                var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"]');
                
                if ($(this).val().trim().length > 0) {
                    navButton.addClass('answered');
                } else {
                    navButton.removeClass('answered');
                }
            }
        });
        
        // Track dropdown selections in computer-based layout
        $('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').on('change', 'select.answer-select-inline', function() {
            var name = $(this).attr('name');
            // Extract question index and dropdown number from name (format: answer_X_Y where X is question index, Y is dropdown number)
            var parts = name.replace('answer_', '').split('_');
            var questionIndex = parts[0];
            var dropdownNum = parts[1];
            
            // Find the question element to get its display number range
            var questionElement = $(this).closest('.quiz-question');
            var displayStart = parseInt(questionElement.data('display-start'), 10);
            var displayEnd = parseInt(questionElement.data('display-end'), 10);
            
            // Calculate which nav button corresponds to this dropdown
            // Dropdown numbers start from 1, so dropdown 1 = first button, dropdown 2 = second button, etc.
            var displayNumber = displayStart + parseInt(dropdownNum, 10) - 1;
            
            // Find the specific nav button for this dropdown
            var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"][data-display-number="' + displayNumber + '"]');
            
            if ($(this).val().trim().length > 0) {
                navButton.addClass('answered');
            } else {
                navButton.removeClass('answered');
            }
        });
        
        // Function to show CBT result modal
        function showCBTResultModal(resultHtml, nextUrl, courseUrl) {
            // Create modal if it doesn't exist
            if ($('#cbt-result-modal').length === 0) {
                var modalHtml = '<div id="cbt-result-modal" class="cbt-result-modal">';
                modalHtml += '<div class="cbt-result-modal-overlay"></div>';
                modalHtml += '<div class="cbt-result-modal-content">';
                modalHtml += '<button type="button" class="cbt-result-modal-close">&times;</button>';
                modalHtml += '<div class="cbt-result-modal-body"></div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                $('body').append(modalHtml);
            }
            
            // Remove any old "Return to course" button from modal (no longer used in v2.15)
            $('#cbt-result-modal .cbt-result-modal-content').find('.cbt-return-to-course-btn').remove();
            
            // Show the modal with results
            $('#cbt-result-modal .cbt-result-modal-body').html(resultHtml);
            $('#cbt-result-modal').fadeIn(300);
            $('body').css('overflow', 'hidden');
            
            // Handle modal close (use event delegation)
            $(document).on('click', '.cbt-result-modal-close, .cbt-result-modal-overlay', function() {
                $('#cbt-result-modal').fadeOut(300);
                $('body').css('overflow', '');
            });
            
            // Handle retake button (use event delegation)
            $(document).on('click', '#cbt-result-modal .quiz-retake-btn', function(e) {
                e.preventDefault();
                $('#cbt-result-modal').fadeOut(300);
                $('body').css('overflow', '');
                forceReload();
            });
            
            // Handle "Review my answers" button (use event delegation)
            $(document).on('click', '.cbt-review-answers-btn', function(e) {
                e.preventDefault();
                $('#cbt-result-modal').fadeOut(300);
                $('body').css('overflow', '');
                // Modal closes and user can see the highlighted answers in the form
            });
        }
        
        // Multi-select max selections enforcement and progressive nav button marking
        $('.multi-select-options').each(function() {
            var $container = $(this);
            var maxSelections = parseInt($container.data('max-selections')) || 2;
            
            $container.find('.multi-select-checkbox').on('change', function() {
                var checkedCount = $container.find('.multi-select-checkbox:checked').length;
                
                // Get question index and mark navigation buttons
                var questionIndex = $(this).attr('name').replace('answer_', '').replace('[]', '');
                var navButtons = $('.question-nav-btn[data-question="' + questionIndex + '"]');
                
                if (checkedCount > maxSelections) {
                    // Uncheck this box and show warning
                    $(this).prop('checked', false);
                    showMessage('error', 'You can only select up to ' + maxSelections + ' options.');
                } else {
                    // Progressive marking: mark nav buttons based on number of selections
                    navButtons.removeClass('answered answered-partial');
                    
                    if (checkedCount > 0) {
                        // Mark the first N buttons as answered (blue) where N = checkedCount
                        navButtons.each(function(index) {
                            if (index < checkedCount) {
                                $(this).addClass('answered');
                            }
                        });
                    }
                    
                    // Disable/enable checkboxes based on max selections
                    if (checkedCount === maxSelections) {
                        $container.find('.multi-select-checkbox:not(:checked)').prop('disabled', true);
                    } else {
                        $container.find('.multi-select-checkbox').prop('disabled', false);
                    }
                }
            });
        });
        
        // Closed question selection enforcement and progressive nav button marking (Issue 2, 7)
        $('.closed-question-options').each(function() {
            var $container = $(this);
            var correctCount = parseInt($container.data('correct-count')) || 1;
            
            // For single-select (radio buttons)
            if (correctCount === 1) {
                $container.find('.closed-question-radio').on('change', function() {
                    // Get question index and mark navigation button
                    var questionIndex = $(this).attr('name').replace('answer_', '');
                    var navButtons = $('.question-nav-btn[data-question="' + questionIndex + '"]');
                    
                    // Mark nav button as answered (blue)
                    navButtons.removeClass('answered-partial').addClass('answered');
                });
            } else {
                // For multi-select (checkboxes)
                $container.find('.closed-question-checkbox').on('change', function() {
                    var checkedCount = $container.find('.closed-question-checkbox:checked').length;
                    
                    // Get question index and mark navigation buttons
                    var questionIndex = $(this).attr('name').replace('answer_', '').replace('[]', '');
                    var navButtons = $('.question-nav-btn[data-question="' + questionIndex + '"]');
                    
                    if (checkedCount > correctCount) {
                        // Uncheck this box and show warning
                        $(this).prop('checked', false);
                        showMessage('error', 'You can only select up to ' + correctCount + ' options.');
                    } else {
                        // Progressive marking: mark nav buttons based on number of selections (blue)
                        navButtons.removeClass('answered answered-partial');
                        
                        if (checkedCount > 0) {
                            // Mark the first N buttons as answered (blue) where N = checkedCount
                            navButtons.each(function(index) {
                                if (index < checkedCount) {
                                    $(this).addClass('answered');
                                }
                            });
                        }
                        
                        // Disable/enable checkboxes based on correct answer count
                        if (checkedCount === correctCount) {
                            $container.find('.closed-question-checkbox:not(:checked)').prop('disabled', true);
                        } else {
                            $container.find('.closed-question-checkbox').prop('disabled', false);
                        }
                    }
                });
            }
        });
        
        // Font size controls for CBT quizzes
        if ($('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').length) {
            var $quizContent = $('.computer-based-container');
            var baseFontSize = 16; // Default base font size in pixels
            var currentFontSize = baseFontSize;
            var minFontSize = 12;
            var maxFontSize = 24;
            var fontSizeStep = 2;
            
            // Load saved font size from localStorage
            var savedFontSize = localStorage.getItem('ielts_quiz_font_size');
            if (savedFontSize) {
                var parsedSize = parseInt(savedFontSize);
                // Validate the parsed value is within bounds
                if (!isNaN(parsedSize) && parsedSize >= minFontSize && parsedSize <= maxFontSize) {
                    currentFontSize = parsedSize;
                    applyFontSize(currentFontSize);
                }
            }
            
            function applyFontSize(size) {
                $quizContent.css('font-size', size + 'px');
                currentFontSize = size;
                localStorage.setItem('ielts_quiz_font_size', size);
            }
            
            $('.font-decrease').on('click', function(e) {
                e.preventDefault();
                var newSize = Math.max(minFontSize, currentFontSize - fontSizeStep);
                if (newSize !== currentFontSize) {
                    applyFontSize(newSize);
                }
            });
            
            $('.font-increase').on('click', function(e) {
                e.preventDefault();
                var newSize = Math.min(maxFontSize, currentFontSize + fontSizeStep);
                if (newSize !== currentFontSize) {
                    applyFontSize(newSize);
                }
            });
            
            $('.font-reset').on('click', function(e) {
                e.preventDefault();
                applyFontSize(baseFontSize);
            });
        }
        
        // Text Highlighting Feature for CBT Reading Texts
        // Note: This is CBT-specific as listening layouts don't have reading text components (they have audio instead)
        if ($('.ielts-computer-based-quiz').length && $('.reading-text').length) {
            var quizId = $('.ielts-computer-based-quiz').data('quiz-id');
            var highlightStorageKey = 'ielts_cbt_highlights_' + quizId;
            var customMenu = null;
            var savedRange = null; // Save the selection range before menu interaction
            
            // Load highlights from sessionStorage
            function loadHighlights() {
                try {
                    var savedHighlights = sessionStorage.getItem(highlightStorageKey);
                    if (savedHighlights) {
                        var highlights = JSON.parse(savedHighlights);
                        highlights.forEach(function(highlight) {
                            highlightTextNode(highlight.textContent, highlight.parentIndex, 
                                highlight.contextBefore, highlight.contextAfter);
                        });
                        updateClearButtonVisibility();
                    }
                } catch (e) {
                    console.error('Error loading highlights:', e);
                }
            }
            
            // Save highlights to sessionStorage
            function saveHighlights() {
                try {
                    var highlights = [];
                    $('.reading-text .highlighted').each(function(index) {
                        var $parent = $(this).closest('.reading-text');
                        var parentIndex = $('.reading-text').index($parent);
                        
                        // Get context before and after for better restoration accuracy
                        // Walk siblings to find text nodes (skip other highlighted spans)
                        var contextBefore = '';
                        var contextAfter = '';
                        
                        var prevSibling = this.previousSibling;
                        while (prevSibling && contextBefore.length < 20) {
                            if (prevSibling.nodeType === Node.TEXT_NODE) {
                                contextBefore = prevSibling.nodeValue.slice(-20) + contextBefore;
                                break;
                            } else if (prevSibling.nodeType === Node.ELEMENT_NODE && !$(prevSibling).hasClass('highlighted')) {
                                // Get text from element
                                var text = $(prevSibling).text();
                                contextBefore = text.slice(-20) + contextBefore;
                                break;
                            }
                            prevSibling = prevSibling.previousSibling;
                        }
                        
                        var nextSibling = this.nextSibling;
                        while (nextSibling && contextAfter.length < 20) {
                            if (nextSibling.nodeType === Node.TEXT_NODE) {
                                contextAfter += nextSibling.nodeValue.slice(0, 20);
                                break;
                            } else if (nextSibling.nodeType === Node.ELEMENT_NODE && !$(nextSibling).hasClass('highlighted')) {
                                // Get text from element
                                var text = $(nextSibling).text();
                                contextAfter += text.slice(0, 20);
                                break;
                            }
                            nextSibling = nextSibling.nextSibling;
                        }
                        
                        highlights.push({
                            textContent: $(this).text(),
                            parentIndex: parentIndex,
                            contextBefore: contextBefore,
                            contextAfter: contextAfter
                        });
                    });
                    sessionStorage.setItem(highlightStorageKey, JSON.stringify(highlights));
                    updateClearButtonVisibility();
                } catch (e) {
                    console.error('Error saving highlights:', e);
                }
            }
            
            // Function to highlight text in a specific parent
            // Supports multiple highlights by being called once per highlight to restore
            // Each call highlights the first matching occurrence with context validation
            function highlightTextNode(textToHighlight, parentIndex, contextBefore, contextAfter) {
                var $targetParent = $('.reading-text').eq(parentIndex);
                if ($targetParent.length === 0) return;
                
                // Walk through text nodes and find matching text with context
                // Skip text nodes that are already inside a highlighted span
                var found = false;
                $targetParent.find('*').addBack().contents().each(function() {
                    if (found) return false; // Stop after first match for this highlight restoration
                    
                    // Skip if this is inside a highlighted span
                    if ($(this).closest('.highlighted').length > 0) {
                        return; // continue to next node
                    }
                    
                    if (this.nodeType === Node.TEXT_NODE) { // Text node
                        var text = this.nodeValue;
                        var index = text.indexOf(textToHighlight);
                        if (index !== -1) {
                            // Verify context if provided
                            var validContext = true;
                            if (contextBefore || contextAfter) {
                                var before = text.substring(Math.max(0, index - 20), index);
                                var after = text.substring(index + textToHighlight.length, 
                                    index + textToHighlight.length + 20);
                                validContext = (!contextBefore || before.includes(contextBefore)) &&
                                              (!contextAfter || after.includes(contextAfter));
                            }
                            
                            if (validContext) {
                                var before = text.substring(0, index);
                                var highlighted = text.substring(index, index + textToHighlight.length);
                                var after = text.substring(index + textToHighlight.length);
                                
                                var span = document.createElement('span');
                                span.className = 'highlighted';
                                span.textContent = highlighted;
                                
                                var parent = this.parentNode;
                                parent.insertBefore(document.createTextNode(before), this);
                                parent.insertBefore(span, this);
                                parent.insertBefore(document.createTextNode(after), this);
                                parent.removeChild(this);
                                
                                found = true;
                                return false;
                            }
                        }
                    }
                });
            }
            
            // Update Clear button visibility
            function updateClearButtonVisibility() {
                var hasHighlights = $('.reading-text .highlighted').length > 0;
                if (hasHighlights) {
                    $('.clear-highlights-btn').show();
                } else {
                    $('.clear-highlights-btn').hide();
                }
            }
            
            // Show custom context menu
            function showContextMenu(e) {
                e.preventDefault();
                
                var selection = window.getSelection();
                var selectedText = selection.toString().trim();
                
                if (selectedText.length === 0) {
                    return;
                }
                
                // Save the range before showing the menu (critical: selection may be lost on menu interaction)
                if (selection.rangeCount > 0) {
                    savedRange = selection.getRangeAt(0).cloneRange();
                }
                
                // Remove existing menu if any
                if (customMenu) {
                    $(customMenu).remove();
                }
                
                // Create menu
                customMenu = $('<div class="text-highlight-menu"></div>');
                var highlightItem = $('<div class="text-highlight-menu-item highlight-option">Highlight</div>');
                
                highlightItem.on('click', function() {
                    highlightSelection();
                    $(customMenu).remove();
                    customMenu = null;
                    savedRange = null;
                });
                
                customMenu.append(highlightItem);
                $('body').append(customMenu);
                
                // Position menu
                customMenu.css({
                    top: e.pageY + 'px',
                    left: e.pageX + 'px'
                });
            }
            
            // Highlight selected text
            function highlightSelection() {
                // Use the saved range instead of current selection (which may be cleared)
                var range = savedRange;
                if (!range) {
                    // Fallback: try to get current selection if savedRange is not available
                    var selection = window.getSelection();
                    if (selection.rangeCount === 0) return;
                    range = selection.getRangeAt(0);
                }
                
                var selectedText = range.toString().trim();
                
                if (selectedText.length === 0) return;
                
                // Check if selection is within reading text
                var $container = $(range.commonAncestorContainer);
                if ($container.closest('.reading-text').length === 0) {
                    if ($container[0].nodeType !== Node.TEXT_NODE && $container.find('.reading-text').length === 0) {
                        return;
                    }
                }
                
                // Use a more robust highlighting approach that works with complex selections
                try {
                    // Check if we're trying to highlight already highlighted text
                    var startContainer = range.startContainer;
                    var endContainer = range.endContainer;
                    
                    // Don't allow highlighting within already highlighted text
                    if ($(startContainer).closest('.highlighted').length > 0 || 
                        $(endContainer).closest('.highlighted').length > 0) {
                        return;
                    }
                    
                    // Try simple surroundContents first (works for simple selections)
                    var span = document.createElement('span');
                    span.className = 'highlighted';
                    
                    try {
                        range.surroundContents(span);
                    } catch (e) {
                        // If surroundContents fails, use a more complex approach
                        // This handles selections that span multiple elements
                        highlightComplexSelection(range);
                        return;
                    }
                    
                    saveHighlights();
                } catch (err) {
                    console.error('Error highlighting text:', err);
                }
            }
            
            // Handle complex selections that span multiple elements
            function highlightComplexSelection(range) {
                // Walk through all text nodes in the selection
                var walker = document.createTreeWalker(
                    range.commonAncestorContainer,
                    NodeFilter.SHOW_TEXT,
                    {
                        acceptNode: function(node) {
                            // Accept nodes that intersect with our range
                            // A node intersects if: range.end >= node.start AND range.start <= node.end
                            var nodeRange = document.createRange();
                            nodeRange.selectNodeContents(node);
                            
                            // Check if range ends at or after node starts
                            var endsAfterStart = range.compareBoundaryPoints(Range.END_TO_START, nodeRange) >= 0;
                            // Check if range starts before or at node ends
                            var startsBeforeEnd = range.compareBoundaryPoints(Range.START_TO_END, nodeRange) <= 0;
                            
                            if (endsAfterStart && startsBeforeEnd) {
                                return NodeFilter.FILTER_ACCEPT;
                            }
                            return NodeFilter.FILTER_REJECT;
                        }
                    }
                );
                
                var nodesToHighlight = [];
                var node;
                
                while ((node = walker.nextNode()) !== null) {
                    // Skip if already highlighted
                    if ($(node).closest('.highlighted').length > 0) {
                        continue;
                    }
                    
                    nodesToHighlight.push(node);
                }
                
                // Highlight each text node
                nodesToHighlight.forEach(function(textNode) {
                    var nodeRange = document.createRange();
                    nodeRange.selectNodeContents(textNode);
                    
                    // Determine the start and end positions for this node
                    var startOffset = 0;
                    var endOffset = textNode.nodeValue.length;
                    
                    // If this is the start node, use the range's start offset
                    if (textNode === range.startContainer) {
                        startOffset = range.startOffset;
                    }
                    
                    // If this is the end node, use the range's end offset
                    if (textNode === range.endContainer) {
                        endOffset = range.endOffset;
                    }
                    
                    // Extract the text to highlight
                    var textBefore = textNode.nodeValue.substring(0, startOffset);
                    var textToHighlight = textNode.nodeValue.substring(startOffset, endOffset);
                    var textAfter = textNode.nodeValue.substring(endOffset);
                    
                    if (textToHighlight.trim().length === 0) {
                        return; // Skip empty/whitespace-only nodes
                    }
                    
                    // Create the highlighted span
                    var span = document.createElement('span');
                    span.className = 'highlighted';
                    span.textContent = textToHighlight;
                    
                    // Replace the text node with our highlighted version
                    var parent = textNode.parentNode;
                    
                    if (textBefore) {
                        parent.insertBefore(document.createTextNode(textBefore), textNode);
                    }
                    parent.insertBefore(span, textNode);
                    if (textAfter) {
                        parent.insertBefore(document.createTextNode(textAfter), textNode);
                    }
                    parent.removeChild(textNode);
                });
                
                saveHighlights();
            }
            
            // Clear all highlights
            $('.clear-highlights-btn').on('click', function() {
                $('.reading-text .highlighted').each(function() {
                    var text = $(this).text();
                    $(this).replaceWith(text);
                });
                sessionStorage.removeItem(highlightStorageKey);
                updateClearButtonVisibility();
            });
            
            // Handle right-click on reading text
            $('.reading-text').on('contextmenu', function(e) {
                var selection = window.getSelection();
                if (selection.toString().trim().length > 0) {
                    showContextMenu(e);
                    return false;
                }
            });
            
            // Close context menu when clicking elsewhere
            $(document).on('click', function(e) {
                if (customMenu && !$(e.target).closest('.text-highlight-menu').length) {
                    $(customMenu).remove();
                    customMenu = null;
                    savedRange = null; // Clear saved range when menu is closed
                }
            });
            
            // Load highlights on page load
            loadHighlights();
            
            // Clear highlights when quiz is submitted
            $(document).on('submit', '#ielts-quiz-form', function() {
                sessionStorage.removeItem(highlightStorageKey);
            });
        }
        
        // Warning when leaving quiz without submitting
        var quizSubmitted = false;
        var quizFormExists = $('#ielts-quiz-form').length > 0;
        
        if (quizFormExists) {
            // Mark quiz as submitted when form is submitted
            $(document).on('submit', '#ielts-quiz-form', function() {
                quizSubmitted = true;
            });
            
            // Warn when clicking navigation links before submitting
            $(document).on('click', '.nav-link-clickable', function(e) {
                if (!quizSubmitted) {
                    // Don't show warning if clicking next-page-link (user is progressing forward)
                    var isNextPageLink = $(this).hasClass('next-page-link');
                    
                    if (!isNextPageLink) {
                        var confirmLeave = confirm('Are you sure you want to leave? Your progress will be lost if you have not submitted your test.');
                        if (!confirmLeave) {
                            e.preventDefault();
                            return false;
                        }
                    }
                }
            });
            
            // Warn when leaving page/closing tab before submitting
            $(window).on('beforeunload', function(e) {
                if (!quizSubmitted) {
                    var message = 'You have not submitted your test yet. Are you sure you want to leave?';
                    e.returnValue = message; // For older browsers
                    return message;
                }
            });
        }
        
        // ========================================
        // Listening Test Functionality
        // ========================================
        
        // Check if this is a listening practice or exercise quiz
        var listeningPracticeQuiz = $('.ielts-listening-practice-quiz');
        var listeningExerciseQuiz = $('.ielts-listening-exercise-quiz');
        
        if (listeningPracticeQuiz.length || listeningExerciseQuiz.length) {
            var isListeningPractice = listeningPracticeQuiz.length > 0;
            var listeningContainer = isListeningPractice ? listeningPracticeQuiz : listeningExerciseQuiz;
            var audioUrl = listeningContainer.data('audio-url');
            
            var audioPlayerContainer = $('#listening-audio-player');
            var audioElement = document.getElementById('listening-audio');
            var transcriptContainer = $('#listening-transcript, #listening-transcripts');
            
            if (audioElement) {
                if (isListeningPractice) {
                    // Listening Practice: Use Enable Audio button (no countdown)
                    var enableAudioContainer = $('#listening-enable-audio');
                    var enableAudioBtn = $('#enable-audio-btn');
                    
                    if (enableAudioBtn.length) {
                        enableAudioBtn.on('click', function() {
                            var $btn = $(this);
                            var originalText = $btn.text();
                            
                            // Disable button to prevent multiple clicks
                            $btn.prop('disabled', true).text('Loading...');
                            
                            // Hide enable audio container and show audio player
                            enableAudioContainer.fadeOut(300, function() {
                                audioPlayerContainer.fadeIn(300, function() {
                                    // Start playing audio (muted first for browser compatibility)
                                    audioElement.load();
                                    var playPromise = audioElement.play();
                                    
                                    if (playPromise !== undefined) {
                                        playPromise.then(function() {
                                            console.log('Audio playback started successfully');
                                            // Unmute immediately after successful playback start
                                            audioElement.muted = false;
                                            // Update progress bar as audio plays
                                            updateAudioProgress();
                                        }).catch(function(error) {
                                            console.log('Audio playback failed:', error);
                                            $('.audio-status-text').text('Unable to play audio. Please check your browser settings.');
                                            // Re-enable button on error
                                            enableAudioContainer.fadeIn(300);
                                            audioPlayerContainer.fadeOut(300);
                                            $btn.prop('disabled', false).text(originalText);
                                        });
                                    }
                                });
                            });
                        });
                    }
                    
                    // Function to update progress bar
                    function updateAudioProgress() {
                        var progressBar = $('#audio-progress-fill');
                        var currentTimeDisplay = $('#audio-current-time');
                        var durationDisplay = $('#audio-duration');
                        
                        // Update duration when metadata is loaded
                        audioElement.addEventListener('loadedmetadata', function() {
                            var duration = audioElement.duration;
                            if (isFinite(duration) && duration > 0) {
                                durationDisplay.text(formatTime(duration));
                            }
                        });
                        
                        // Update progress bar as audio plays
                        audioElement.addEventListener('timeupdate', function() {
                            var currentTime = audioElement.currentTime;
                            var duration = audioElement.duration;
                            
                            if (isFinite(duration) && duration > 0) {
                                var progress = (currentTime / duration) * 100;
                                progressBar.css('width', progress + '%');
                                currentTimeDisplay.text(formatTime(currentTime));
                            }
                        });
                        
                        // Update status when audio ends
                        audioElement.addEventListener('ended', function() {
                            $('.audio-status-icon').text('■');
                            $('.audio-status-text').text('Audio Finished');
                        });
                    }
                    
                    // Helper function to format time as MM:SS
                    function formatTime(seconds) {
                        var mins = Math.floor(seconds / 60);
                        var secs = Math.floor(seconds % 60);
                        return mins + ':' + (secs < 10 ? '0' : '') + secs;
                    }
                    
                } else {
                    // Listening Exercise: Use countdown (existing behavior)
                    var countdownElement = $('#countdown-number');
                    var countdownContainer = $('#listening-countdown');
                    var countdownSeconds = 1;
                    
                    if (countdownElement.length) {
                        // Start countdown when page loads
                        var countdownTimer = countdownSeconds;
                        countdownElement.text(countdownTimer);
                        
                        var countdownInterval = setInterval(function() {
                            countdownTimer--;
                            
                            if (countdownTimer <= 0) {
                                clearInterval(countdownInterval);
                                startAudioPlayback();
                            } else {
                                countdownElement.text(countdownTimer);
                            }
                        }, 1000);
                        
                        function startAudioPlayback() {
                            // Hide countdown and show audio player
                            countdownContainer.fadeOut(300, function() {
                                audioPlayerContainer.fadeIn(300, function() {
                                    // Start playing audio after fade-in completes
                                    audioElement.load();
                                    
                                    // For listening exercise, audio starts unmuted with controls
                                    var playPromise = audioElement.play();
                                    if (playPromise !== undefined) {
                                        playPromise.catch(function(error) {
                                            console.log('Audio autoplay failed:', error);
                                            // Audio controls are visible, user can manually start
                                        });
                                    }
                                });
                            });
                        }
                    }
                }
                
                // Note: Transcript display is now handled in the main quiz submission success callback
                // to ensure proper timing and avoid race conditions with the results modal
            }
        }
        
        // Previous Attempts functionality
        if ($('.quiz-attempts-section').length) {
            var attemptsLoaded = false;
            
            // Toggle attempts section
            $('.attempts-toggle').on('click', function() {
                var $content = $('.attempts-content');
                var $icon = $('.toggle-icon');
                
                if ($content.is(':visible')) {
                    $content.slideUp();
                    $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                } else {
                    $content.slideDown();
                    $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                    
                    // Load attempts if not already loaded
                    if (!attemptsLoaded) {
                        loadQuizAttempts();
                    }
                }
            });
            
            // Load quiz attempts
            function loadQuizAttempts() {
                var quizId = $('.ielts-single-quiz, .ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').data('quiz-id');
                
                if (!quizId) {
                    $('.attempts-loading').hide();
                    $('.attempts-list').html('<p>' + 'No quiz ID found.' + '</p>');
                    return;
                }
                
                $.ajax({
                    url: ieltsCM.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_get_quiz_attempts',
                        nonce: ieltsCM.nonce,
                        quiz_id: quizId
                    },
                    success: function(response) {
                        $('.attempts-loading').hide();
                        
                        if (response.success && response.data.attempts && response.data.attempts.length > 0) {
                            displayAttempts(response.data.attempts, response.data.is_admin);
                            attemptsLoaded = true;
                        } else {
                            $('.attempts-list').html('<p>' + 'You have not attempted this exercise yet.' + '</p>');
                        }
                    },
                    error: function() {
                        $('.attempts-loading').hide();
                        $('.attempts-list').html('<p>' + 'Failed to load attempts. Please try again.' + '</p>');
                    }
                });
            }
            
            // Display attempts in a table
            function displayAttempts(attempts, isAdmin) {
                var html = '<table class="attempts-table">';
                html += '<thead><tr>';
                html += '<th>Attempt</th>';
                html += '<th>Score</th>';
                html += '<th>Percentage</th>';
                html += '<th>Date</th>';
                if (isAdmin) {
                    html += '<th>Actions</th>';
                }
                html += '</tr></thead>';
                html += '<tbody>';
                
                attempts.forEach(function(attempt, index) {
                    var attemptNum = attempts.length - index;
                    // Using default 70% pass threshold - ideally this would come from quiz settings
                    var passClass = attempt.percentage >= 70 ? 'pass' : 'fail';
                    
                    html += '<tr data-attempt-id="' + attempt.id + '">';
                    html += '<td>' + attemptNum + '</td>';
                    html += '<td>' + attempt.score + ' / ' + attempt.max_score + '</td>';
                    html += '<td><span class="percentage-badge percentage-' + passClass + '">' + attempt.percentage + '%</span></td>';
                    html += '<td>' + attempt.submitted_date + '</td>';
                    if (isAdmin) {
                        html += '<td><button class="delete-attempt-btn button button-small" data-attempt-id="' + attempt.id + '">Delete</button></td>';
                    }
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('.attempts-list').html(html);
                
                // Add delete functionality for admin
                if (isAdmin) {
                    $('.delete-attempt-btn').on('click', function() {
                        var attemptId = $(this).data('attempt-id');
                        if (confirm('Are you sure you want to delete this attempt? This action cannot be undone.')) {
                            deleteAttempt(attemptId, $(this).closest('tr'));
                        }
                    });
                }
            }
            
            // Delete attempt function
            function deleteAttempt(attemptId, row) {
                $.ajax({
                    url: ieltsCM.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_delete_quiz_attempt',
                        nonce: ieltsCM.nonce,
                        attempt_id: attemptId
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(300, function() {
                                $(this).remove();
                                // Check if there are any attempts left
                                if ($('.attempts-table tbody tr').length === 0) {
                                    $('.attempts-list').html('<p>No attempts remaining.</p>');
                                }
                            });
                        } else {
                            alert('Failed to delete attempt: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Failed to delete attempt. Please try again.');
                    }
                });
            }
        }
        
        // Lesson page: Previous Attempts modal functionality
        if ($('.view-attempts-btn').length) {
            // Open modal when clicking "View Attempts" button
            $('.view-attempts-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var quizId = $btn.data('quiz-id');
                var quizTitle = $btn.data('quiz-title');
                
                // Set modal title
                $('#attempts-modal-title').text('Previous Attempts - ' + quizTitle);
                
                // Show modal
                $('#attempts-modal').fadeIn(200);
                
                // Load attempts for this quiz
                loadQuizAttemptsForLesson(quizId);
            });
            
            // Close modal functionality
            $('.attempts-modal-close').on('click', function() {
                $('#attempts-modal').fadeOut(200);
            });
            
            // Close modal when clicking outside
            $(window).on('click', function(e) {
                if ($(e.target).is('#attempts-modal')) {
                    $('#attempts-modal').fadeOut(200);
                }
            });
            
            // Load quiz attempts function for lesson page
            function loadQuizAttemptsForLesson(quizId) {
                $('#attempts-modal .attempts-loading').show();
                $('#attempts-modal .attempts-list').html('');
                
                $.ajax({
                    url: ieltsCM.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_get_quiz_attempts',
                        nonce: ieltsCM.nonce,
                        quiz_id: quizId
                    },
                    success: function(response) {
                        $('#attempts-modal .attempts-loading').hide();
                        
                        if (response.success && response.data.attempts && response.data.attempts.length > 0) {
                            displayAttemptsInModal(response.data.attempts, response.data.is_admin, quizId);
                        } else {
                            $('#attempts-modal .attempts-list').html('<p style="text-align: center; color: #666;">No attempts found for this exercise.</p>');
                        }
                    },
                    error: function() {
                        $('#attempts-modal .attempts-loading').hide();
                        $('#attempts-modal .attempts-list').html('<p style="text-align: center; color: #c00;">Failed to load attempts. Please try again.</p>');
                    }
                });
            }
            
            // Display attempts in modal
            function displayAttemptsInModal(attempts, isAdmin, quizId) {
                var html = '<table class="attempts-table">';
                html += '<thead><tr>';
                html += '<th>Attempt</th>';
                html += '<th>Score</th>';
                html += '<th>Percentage</th>';
                html += '<th>Date</th>';
                if (isAdmin) {
                    html += '<th>Actions</th>';
                }
                html += '</tr></thead>';
                html += '<tbody>';
                
                attempts.forEach(function(attempt, index) {
                    var attemptNum = attempts.length - index;
                    // Using default 70% pass threshold - ideally this would come from quiz settings
                    var passClass = attempt.percentage >= 70 ? 'pass' : 'fail';
                    
                    html += '<tr data-attempt-id="' + attempt.id + '">';
                    html += '<td>' + attemptNum + '</td>';
                    html += '<td>' + attempt.score + ' / ' + attempt.max_score + '</td>';
                    html += '<td><span class="percentage-badge percentage-' + passClass + '">' + attempt.percentage + '%</span></td>';
                    html += '<td>' + attempt.submitted_date + '</td>';
                    if (isAdmin) {
                        html += '<td><button class="delete-attempt-modal-btn button button-small" data-attempt-id="' + attempt.id + '" data-quiz-id="' + quizId + '">Delete</button></td>';
                    }
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#attempts-modal .attempts-list').html(html);
                
                // Add delete functionality for admin
                if (isAdmin) {
                    $('#attempts-modal .delete-attempt-modal-btn').on('click', function() {
                        var attemptId = $(this).data('attempt-id');
                        var quizId = $(this).data('quiz-id');
                        if (confirm('Are you sure you want to delete this attempt? This action cannot be undone.')) {
                            deleteAttemptFromModal(attemptId, quizId, $(this).closest('tr'));
                        }
                    });
                }
            }
            
            // Delete attempt from modal
            function deleteAttemptFromModal(attemptId, quizId, row) {
                $.ajax({
                    url: ieltsCM.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_cm_delete_quiz_attempt',
                        nonce: ieltsCM.nonce,
                        attempt_id: attemptId
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(300, function() {
                                $(this).remove();
                                // Check if there are any attempts left
                                if ($('#attempts-modal .attempts-table tbody tr').length === 0) {
                                    $('#attempts-modal .attempts-list').html('<p style="text-align: center; color: #666;">No attempts remaining.</p>');
                                }
                            });
                            // Optionally reload the page to update the score display
                            // You could also update the score in the table directly
                        } else {
                            alert('Failed to delete attempt: ' + (response.data.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Failed to delete attempt. Please try again.');
                    }
                });
            }
        }
        
        // Focus mode for exercises (auto-enabled for all CBT quizzes)
        // Check if we're on an exercise page
        if ($('.ielts-computer-based-quiz, .ielts-listening-practice-quiz, .ielts-listening-exercise-quiz').length) {
            // Auto-enable focus mode for all CBT quizzes
            $('body').addClass('ielts-quiz-focus-mode');
            $('html').addClass('ielts-quiz-focus-mode');
            
            // Header toggle functionality
            $('#header-toggle-btn').on('click', function(e) {
                e.preventDefault();
                var $header = $('.quiz-header');
                var $wpHeader = $('.et-l--header'); // WordPress/Divi header
                var $adminBar = $('#wpadminbar'); // WordPress admin bar
                var $toggleBtn = $(this);
                var $toggleIcon = $toggleBtn.find('.toggle-icon');
                
                if ($header.hasClass('header-visible')) {
                    // Hide header
                    $header.removeClass('header-visible');
                    $wpHeader.removeClass('header-visible');
                    $adminBar.removeClass('header-visible');
                    $('html').removeClass('admin-bar-visible');
                    $toggleIcon.text('▼');
                    $toggleBtn.attr('title', 'Show header');
                } else {
                    // Show header
                    $header.addClass('header-visible');
                    $wpHeader.addClass('header-visible');
                    $adminBar.addClass('header-visible');
                    // Only add admin-bar-visible if admin bar exists
                    if ($adminBar.length > 0) {
                        $('html').addClass('admin-bar-visible');
                    }
                    $toggleIcon.text('▲');
                    $toggleBtn.attr('title', 'Hide header');
                }
            });
        }
    });
    
})(jQuery);
