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
        var quizContainer = $('.ielts-single-quiz, .ielts-computer-based-quiz');
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
            var quizContainer = form.closest('.ielts-single-quiz, .ielts-computer-based-quiz');
            
            // If form is inside modal, get data from the original quiz container
            if (quizContainer.length === 0) {
                quizContainer = $('.ielts-computer-based-quiz');
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
                        
                        // Check if this is a CBT quiz
                        var isCBT = quizContainer.hasClass('ielts-computer-based-quiz');
                        
                        var html = '<div class="quiz-result ' + (isPassing ? 'pass' : 'fail') + '">';
                        html += '<h3>' + (isPassing ? 'Congratulations! You Passed!' : 'Quiz Completed') + '</h3>';
                        
                        // Display score based on type (band score or percentage)
                        if (result.display_type === 'band') {
                            html += '<p><strong>Your Score:</strong> ' + result.score + ' / ' + result.max_score + ' correct</p>';
                            html += '<p><strong>IELTS Band Score:</strong> ' + result.display_score + '</p>';
                        } else {
                            html += '<p><strong>Your Score:</strong> ' + result.score + ' / ' + result.max_score + ' (' + result.percentage + '%)</p>';
                        }
                        
                        // Show time information
                        if (timerMinutes && timerMinutes > 0) {
                            html += '<p><strong>Time Limit:</strong> ' + timerMinutes + ' minutes</p>';
                        }
                        html += '<p><strong>Time Taken:</strong> ' + timeTakenFormatted + '</p>';
                        
                        if (isPassing) {
                            html += '<p>Great job! You have passed this quiz.</p>';
                        } else {
                            html += '<p>Keep studying and try again to improve your score!</p>';
                        }
                        
                        // Show question-by-question feedback only for non-CBT quizzes
                        if (!isCBT && result.question_results && Object.keys(result.question_results).length > 0) {
                            html += '<div class="quiz-feedback-section">';
                            html += '<h4>Question Feedback</h4>';
                            html += '<div class="quiz-feedback-list">';
                            
                            $.each(result.question_results, function(index, questionResult) {
                                var questionNum = parseInt(index) + 1;
                                var statusClass = questionResult.correct ? 'correct' : 'incorrect';
                                var statusIcon = questionResult.correct ? '✓' : '✗';
                                var statusText = questionResult.correct ? 'Correct' : 'Incorrect';
                                
                                html += '<div class="feedback-item ' + statusClass + '">';
                                html += '<div class="feedback-header">';
                                html += '<span class="feedback-icon">' + statusIcon + '</span>';
                                html += '<strong>Question ' + questionNum + ':</strong> ';
                                html += '<span class="feedback-status">' + statusText + '</span>';
                                html += '</div>';
                                
                                // Show question text
                                if (questionResult.question_text) {
                                    html += '<div class="feedback-question">' + questionResult.question_text + '</div>';
                                }
                                
                                // Show user answer and correct answer
                                if (questionResult.question_type === 'multiple_choice' && questionResult.options) {
                                    var options = questionResult.options.split('\n').filter(function(opt) { return opt.trim(); });
                                    var userAnswerIndex = parseInt(questionResult.user_answer);
                                    var correctAnswerIndex = parseInt(questionResult.correct_answer);
                                    
                                    html += '<div class="feedback-answers">';
                                    html += '<p><strong>Your answer:</strong> ';
                                    if (!isNaN(userAnswerIndex) && options[userAnswerIndex]) {
                                        html += options[userAnswerIndex].trim();
                                    } else {
                                        html += '(No answer provided)';
                                    }
                                    html += '</p>';
                                    
                                    if (!questionResult.correct) {
                                        html += '<p><strong>Correct answer:</strong> ';
                                        if (!isNaN(correctAnswerIndex) && options[correctAnswerIndex]) {
                                            html += options[correctAnswerIndex].trim();
                                        }
                                        html += '</p>';
                                    }
                                    html += '</div>';
                                } else if (questionResult.question_type === 'true_false') {
                                    var tfLabels = {
                                        'true': 'True',
                                        'false': 'False',
                                        'not_given': 'Not Given'
                                    };
                                    html += '<div class="feedback-answers">';
                                    html += '<p><strong>Your answer:</strong> ' + (tfLabels[questionResult.user_answer] || '(No answer provided)') + '</p>';
                                    if (!questionResult.correct) {
                                        html += '<p><strong>Correct answer:</strong> ' + (tfLabels[questionResult.correct_answer] || questionResult.correct_answer) + '</p>';
                                    }
                                    html += '</div>';
                                } else if (questionResult.question_type === 'fill_blank' || questionResult.question_type === 'summary_completion') {
                                    html += '<div class="feedback-answers">';
                                    html += '<p><strong>Your answer:</strong> ' + (questionResult.user_answer || '(No answer provided)') + '</p>';
                                    if (!questionResult.correct) {
                                        html += '<p><strong>Correct answer:</strong> ' + questionResult.correct_answer + '</p>';
                                    }
                                    html += '</div>';
                                } else if (questionResult.question_type === 'essay') {
                                    html += '<div class="feedback-answers">';
                                    html += '<p><strong>Your answer:</strong></p>';
                                    html += '<div class="essay-answer">' + (questionResult.user_answer || '(No answer provided)') + '</div>';
                                    html += '</div>';
                                }
                                
                                if (questionResult.feedback) {
                                    html += '<div class="feedback-message">' + questionResult.feedback + '</div>';
                                }
                                
                                html += '</div>';
                            });
                            
                            html += '</div>';
                            html += '</div>';
                        }
                        
                        html += '<div class="quiz-actions">';
                        
                        // For CBT quizzes, add "Review my answers" button
                        if (isCBT) {
                            html += '<button class="button button-primary cbt-review-answers-btn">Review my answers</button>';
                            html += ' <button class="button quiz-retake-btn">Take Quiz Again</button>';
                        } else {
                            html += '<button class="button button-primary quiz-retake-btn">Take Quiz Again</button>';
                        }
                        
                        // Only add auto-redirect for non-CBT quizzes
                        if (!isCBT && result.next_url) {
                            html += ' <a href="' + result.next_url + '" class="button button-primary quiz-continue-btn">Continue</a>';
                            html += '<div id="quiz-auto-redirect" style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-left: 4px solid #72aee6;">';
                            html += '<p style="margin: 0;">Automatically continuing in <strong id="quiz-countdown">5</strong> seconds... <button type="button" id="quiz-cancel-redirect" class="button button-link" style="text-decoration: underline;">Cancel</button></p>';
                            html += '</div>';
                        } else if (isCBT && result.next_url) {
                            // For CBT, just add continue button without auto-redirect
                            html += ' <a href="' + result.next_url + '" class="button button-primary quiz-continue-btn">Continue</a>';
                        }
                        
                        html += '</div>';
                        html += '</div>';
                        
                        // Add visual feedback for correct/wrong answers in the form
                        $.each(result.question_results, function(index, questionResult) {
                            var questionNum = parseInt(index) + 1;
                            var questionElement = form.find('#question-' + index);
                            var navButton = $('.question-nav-btn[data-question="' + index + '"]');
                            
                            if (questionResult.correct) {
                                // Mark correct answers in green
                                questionElement.addClass('question-correct');
                                navButton.addClass('nav-correct').removeClass('answered');
                                
                                // Highlight the correct answer option
                                if (questionResult.question_type === 'multiple_choice' || questionResult.question_type === 'true_false') {
                                    questionElement.find('input[type="radio"]:checked').closest('.option-label').addClass('answer-correct');
                                } else {
                                    questionElement.find('input[type="text"], textarea').addClass('answer-correct');
                                }
                            } else {
                                // Mark incorrect answers in red
                                questionElement.addClass('question-incorrect');
                                navButton.addClass('nav-incorrect').removeClass('answered');
                                
                                // Highlight the user's wrong answer
                                if (questionResult.question_type === 'multiple_choice' || questionResult.question_type === 'true_false') {
                                    questionElement.find('input[type="radio"]:checked').closest('.option-label').addClass('answer-incorrect');
                                    
                                    // Also highlight the correct answer in green
                                    if (questionResult.question_type === 'multiple_choice') {
                                        var correctIndex = parseInt(questionResult.correct_answer);
                                        questionElement.find('input[type="radio"][value="' + correctIndex + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                    } else if (questionResult.question_type === 'true_false') {
                                        questionElement.find('input[type="radio"][value="' + questionResult.correct_answer + '"]').closest('.option-label').addClass('answer-correct-highlight');
                                    }
                                } else {
                                    questionElement.find('input[type="text"], textarea').addClass('answer-incorrect');
                                }
                            }
                            
                            // Add feedback message below the question if available (for CBT quizzes)
                            if (isCBT && questionResult.feedback) {
                                // Remove any existing feedback first
                                questionElement.find('.question-feedback-message').remove();
                                
                                // Create feedback element with proper CSS classes
                                var feedbackClass = questionResult.correct ? 'feedback-correct' : 'feedback-incorrect';
                                var feedbackDiv = $('<div>')
                                    .addClass('question-feedback-message')
                                    .addClass(feedbackClass)
                                    .html(questionResult.feedback); // Using .html() because feedback explicitly supports HTML formatting
                                                                     // Content is sanitized server-side with wp_kses_post() in class-quiz-handler.php
                                
                                questionElement.append(feedbackDiv);
                            }
                        });
                        
                        if (isCBT) {
                            // For CBT quizzes, show results in a modal and hide submit button
                            showCBTResultModal(html, result.next_url, result.course_url);
                            form.find('button[type="submit"]').hide();
                            
                            // Update timer display to show band score instead of time remaining
                            var timerElement = form.find('.quiz-timer-fullscreen');
                            if (timerElement.length > 0) {
                                // Preserve the return to course link
                                var returnLink = timerElement.find('.return-to-course-link').prop('outerHTML') || '';
                                
                                var scoreHtml = '';
                                if (result.display_type === 'band') {
                                    scoreHtml = '<div class="timer-content"><strong>Band Score:</strong> <span>' + result.display_score + '</span></div>';
                                } else {
                                    scoreHtml = '<div class="timer-content"><strong>Score:</strong> <span>' + result.percentage + '%</span></div>';
                                }
                                
                                timerElement.html(scoreHtml + returnLink);
                            }
                        } else {
                            // For regular quizzes, show inline
                            form.hide();
                            $('#quiz-result').html(html).show();
                            
                            // Scroll to result
                            $('html, body').animate({
                                scrollTop: $('#quiz-result').offset().top - 100
                            }, 500);
                        }
                        
                        // Auto-navigate to next item after 5 seconds if available (only for non-CBT)
                        if (!isCBT && result.next_url) {
                            var countdown = 5;
                            var redirectTimer = null;
                            var countdownInterval = setInterval(function() {
                                countdown--;
                                $('#quiz-countdown').text(countdown);
                                if (countdown <= 0) {
                                    clearInterval(countdownInterval);
                                    window.location.href = result.next_url;
                                }
                            }, 1000);
                            
                            // Allow user to cancel redirect
                            $(document).on('click', '#quiz-cancel-redirect, .quiz-retake-btn', function(e) {
                                clearInterval(countdownInterval);
                                $('#quiz-auto-redirect').fadeOut();
                            });
                            
                            // Also cancel on continue button click (let it navigate naturally)
                            $(document).on('click', '.quiz-continue-btn', function() {
                                clearInterval(countdownInterval);
                            });
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
            }
        });
        
        // Function to switch reading text based on question
        function switchReadingText(questionElement) {
            var readingTextId = questionElement.data('reading-text-id');
            
            // Only switch if the question has a linked reading text
            if (readingTextId !== '' && readingTextId !== undefined) {
                // Hide all reading texts
                $('.reading-text-section').hide();
                // Show the linked reading text
                $('#reading-text-' + readingTextId).fadeIn(300);
                
                // Scroll reading column to top
                $('.reading-column').animate({
                    scrollTop: 0
                }, 300);
            }
        }
        
        // Detect scroll position and switch reading text automatically
        if ($('.ielts-computer-based-quiz').length) {
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
                    }
                }, 150); // Debounce scroll events
            });
        }
        
        // Track answered questions in computer-based layout using event delegation
        $('.ielts-computer-based-quiz').on('change', 'input[type="radio"]', function() {
            var questionIndex = $(this).attr('name').replace('answer_', '');
            var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"]');
            navButton.addClass('answered');
        });
        
        $('.ielts-computer-based-quiz').on('input', 'input[type="text"], textarea', function() {
            var questionIndex = $(this).attr('name').replace('answer_', '');
            var navButton = $('.question-nav-btn[data-question="' + questionIndex + '"]');
            
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
        
        // Multi-select max selections enforcement
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
                } else if (checkedCount === maxSelections) {
                    // Disable unchecked boxes
                    $container.find('.multi-select-checkbox:not(:checked)').prop('disabled', true);
                    // Mark all nav buttons for this question as answered
                    navButtons.addClass('answered');
                } else {
                    // Enable all boxes
                    $container.find('.multi-select-checkbox').prop('disabled', false);
                    // Mark/unmark nav buttons based on whether any are checked
                    if (checkedCount > 0) {
                        navButtons.addClass('answered');
                    } else {
                        navButtons.removeClass('answered');
                    }
                }
            });
        });
        
        // Text Highlighting Feature for CBT Reading Texts
        if ($('.ielts-computer-based-quiz').length && $('.reading-text').length) {
            var quizId = $('.ielts-computer-based-quiz').data('quiz-id');
            var highlightStorageKey = 'ielts_cbt_highlights_' + quizId;
            var customMenu = null;
            
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
                        var $prev = $(this).prev();
                        var $next = $(this).next();
                        var contextBefore = $prev.length && $prev[0].nodeType === Node.TEXT_NODE 
                            ? $prev[0].nodeValue.slice(-20) : '';
                        var contextAfter = $next.length && $next[0].nodeType === Node.TEXT_NODE 
                            ? $next[0].nodeValue.slice(0, 20) : '';
                        
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
                var selection = window.getSelection();
                if (selection.rangeCount === 0) return;
                
                var range = selection.getRangeAt(0);
                var selectedText = selection.toString().trim();
                
                if (selectedText.length === 0) return;
                
                // Check if selection is within reading text
                var $container = $(range.commonAncestorContainer);
                if ($container.closest('.reading-text').length === 0) {
                    if ($container[0].nodeType !== Node.TEXT_NODE && $container.find('.reading-text').length === 0) {
                        return;
                    }
                }
                
                // Create highlighted span
                var span = document.createElement('span');
                span.className = 'highlighted';
                
                try {
                    range.surroundContents(span);
                    selection.removeAllRanges();
                    saveHighlights();
                } catch (e) {
                    // If surroundContents fails (e.g., selection spans multiple elements),
                    // extract contents and wrap them
                    try {
                        var contents = range.extractContents();
                        span.appendChild(contents);
                        range.insertNode(span);
                        selection.removeAllRanges();
                        saveHighlights();
                    } catch (err) {
                        console.error('Error highlighting text:', err);
                    }
                }
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
            
            // Warn when clicking "Return to course" link before submitting
            $(document).on('click', '#return-to-course-link', function(e) {
                if (!quizSubmitted) {
                    var confirmLeave = confirm('Are you sure you want to return to the course? Your progress will be lost if you have not submitted your test.');
                    if (!confirmLeave) {
                        e.preventDefault();
                        return false;
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
    });
    
})(jQuery);
