/**
 * Membership-Specific User Tours for IELTS Course Manager
 * 
 * Provides guided tours for first-time users based on their membership type:
 * - Academic Module
 * - General Training Module
 * - English Only
 */
(function($) {
    'use strict';
    
    // Global function to manually start tour (for replay button)
    window.ieltsStartTour = function(forceReplay) {
        forceReplay = forceReplay || false;
        
        // Only run if Shepherd is loaded
        if (typeof Shepherd === 'undefined') {
            console.log('IELTS Tours: Shepherd.js not loaded');
            return;
        }
        
        // Check if tour data is available
        if (typeof ieltsTourData === 'undefined' || !ieltsTourData.tourType) {
            console.log('IELTS Tours: Tour data not available');
            return;
        }
        
        // Get membership/tour type from PHP
        const tourType = ieltsTourData.tourType; // 'academic', 'general', or 'english'
        
        // Check localStorage as quick cache (optional, but faster) - skip if force replay
        const localStorageKey = 'ielts_tour_completed_' + tourType;
        if (!forceReplay && localStorage.getItem(localStorageKey)) {
            console.log('IELTS Tours: Already completed (localStorage)');
            return; // Tour already completed
        }
        
        // Initialize tour
        const tour = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: { enabled: true },
                classes: 'ielts-tour-step',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        });
        
        // Load appropriate tour based on membership type
        if (tourType === 'academic') {
            loadAcademicTour(tour);
        } else if (tourType === 'general') {
            loadGeneralTrainingTour(tour);
        } else if (tourType === 'english') {
            loadEnglishOnlyTour(tour);
        } else {
            console.log('IELTS Tours: Unknown tour type:', tourType);
            return;
        }
        
        // When tour is completed, save to database (only if not forced replay)
        tour.on('complete', function() {
            console.log('IELTS Tours: Tour completed, saving to database');
            
            if (!forceReplay) {
                // Save to WordPress database (cross-device)
                $.ajax({
                    url: ieltsTourData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_complete_tour',
                        tour_type: tourType,
                        nonce: ieltsTourData.nonce,
                        user_id: ieltsTourData.userId
                    },
                    success: function(response) {
                        console.log('IELTS Tours: Completion saved:', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('IELTS Tours: Error saving completion:', error);
                    }
                });
                
                // Also save to localStorage for quick access
                localStorage.setItem(localStorageKey, 'true');
            }
        });
        
        // When tour is cancelled/skipped (only save if not forced replay)
        tour.on('cancel', function() {
            console.log('IELTS Tours: Tour cancelled/skipped, saving status');
            
            if (!forceReplay) {
                // Still mark as completed so it doesn't show again
                $.ajax({
                    url: ieltsTourData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ielts_complete_tour',
                        tour_type: tourType,
                        nonce: ieltsTourData.nonce,
                        user_id: ieltsTourData.userId
                    }
                });
                localStorage.setItem(localStorageKey, 'true');
            }
        });
        
        // Start tour after short delay to ensure page is fully loaded
        setTimeout(function() {
            console.log('IELTS Tours: Starting', tourType, 'tour');
            tour.start();
        }, 1000);
    };
    
    $(document).ready(function() {
        // Auto-start tour for first-time users
        ieltsStartTour(false);
    });
    
    /**
     * Helper function to add Band Scores step if element exists
     */
    function addBandScoresStep(tour) {
        if ($('.ielts-band-scores-container').length) {
            tour.addStep({
                id: 'band-scores',
                text: '<h3>Your Estimated IELTS Band Scores 📊</h3><p>This section shows your estimated band scores for Reading, Listening, Writing, Speaking, and Overall. Complete more tests for more accurate results!</p>',
                attachTo: { 
                    element: '.ielts-band-scores-container',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
    }
    
    /**
     * Helper function to add common course navigation steps (used by all tour types)
     */
    function addCourseNavigationSteps(tour) {
        // Courses section
        if ($('#courses').length) {
            tour.addStep({
                id: 'courses',
                text: '<h3>Your Course Units 📚</h3><p>After the tour, click Unit 1 below to start your course.</p>',
                attachTo: { 
                    element: '#courses',
                    on: 'top' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Practice tests section
        if ($('#practice-tests').length) {
            tour.addStep({
                id: 'practice-tests',
                text: '<h3>Practice Tests 📝</h3><p>Test yourself with full-length practice exams to track your progress and prepare for the real test.</p>',
                attachTo: { 
                    element: '#practice-tests',
                    on: 'top' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // General English section
        if ($('#general-english').length) {
            tour.addStep({
                id: 'general-english',
                text: '<h3>General English 🌍</h3><p>A good result in IELTS requires a good level of English, so don\'t ignore this section. Build your foundation here!</p>',
                attachTo: { 
                    element: '#general-english',
                    on: 'top' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Vocabulary course section (using the specific structure provided)
        if ($('.ielts-course-item a[href*="vocabulary-for-ielts"]').length) {
            tour.addStep({
                id: 'vocabulary-course',
                text: '<h3>Vocabulary for IELTS 📖</h3><p>Expand your vocabulary with our specialized IELTS vocabulary course. A strong vocabulary is essential for achieving a high band score!</p>',
                attachTo: { 
                    element: '.ielts-course-item a[href*="vocabulary-for-ielts"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
    }
    
    /**
     * Academic Module Tour
     */
    function loadAcademicTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to IELTS Academic! 🎓</h3><p>Let\'s show you around the Academic Module. This will take about 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // IELTS Band Scores - Show FIRST if present
        addBandScoresStep(tour);
        
        // Add course navigation steps (courses, practice tests, general english, vocabulary)
        addCourseNavigationSteps(tour);
        
        // Main Navigation
        if ($('.main-navigation, .primary-menu, .site-navigation, #site-navigation').length) {
            tour.addStep({
                id: 'navigation',
                text: '<h3>Main Menu 📚</h3><p>Access all your Academic courses and lessons here.</p>',
                attachTo: { 
                    element: '.main-navigation, .primary-menu, .site-navigation, #site-navigation',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Academic Practice Tests
        if ($('a[href*="academic"][href*="practice"], a[href*="practice"][href*="academic"]').length) {
            tour.addStep({
                id: 'academic-tests',
                text: '<h3>Academic Practice Tests 📝</h3><p>Take full-length IELTS Academic practice tests here to prepare for your exam.</p>',
                attachTo: { 
                    element: 'a[href*="academic"][href*="practice"], a[href*="practice"][href*="academic"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Trophy Room / Awards
        if ($('a[href*="trophy"], a[href*="award"]').length) {
            tour.addStep({
                id: 'trophies',
                text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you complete Academic modules! Earn badges, shields, and trophies.</p>',
                attachTo: { 
                    element: 'a[href*="trophy"], a[href*="award"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Progress
        if ($('a[href*="progress"], a[href*="account"]').length) {
            tour.addStep({
                id: 'progress',
                text: '<h3>Your Progress 📊</h3><p>View your scores and study statistics for the Academic module.</p>',
                attachTo: { 
                    element: 'a[href*="progress"], a[href*="account"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        } else {
            // If progress element not found, just add final step
            tour.addStep({
                id: 'finish',
                text: '<h3>You\'re Ready! 🎯</h3><p>Start your IELTS Academic preparation journey. Good luck!</p>',
                buttons: [
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        }
    }
    
    /**
     * General Training Module Tour
     */
    function loadGeneralTrainingTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to IELTS General Training! 🌍</h3><p>Let\'s show you around the General Training Module. This will take about 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // IELTS Band Scores - Show FIRST if present
        addBandScoresStep(tour);
        
        // Add course navigation steps (courses, practice tests, general english, vocabulary)
        addCourseNavigationSteps(tour);
        
        // Main Navigation
        if ($('.main-navigation, .primary-menu, .site-navigation, #site-navigation').length) {
            tour.addStep({
                id: 'navigation',
                text: '<h3>Main Menu 📚</h3><p>Access all your General Training courses and lessons here.</p>',
                attachTo: { 
                    element: '.main-navigation, .primary-menu, .site-navigation, #site-navigation',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // General Training Practice Tests
        if ($('a[href*="general"][href*="practice"], a[href*="practice"][href*="general"]').length) {
            tour.addStep({
                id: 'general-tests',
                text: '<h3>General Training Practice Tests 📝</h3><p>Take full-length IELTS General Training practice tests here.</p>',
                attachTo: { 
                    element: 'a[href*="general"][href*="practice"], a[href*="practice"][href*="general"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Trophy Room / Awards
        if ($('a[href*="trophy"], a[href*="award"]').length) {
            tour.addStep({
                id: 'trophies',
                text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you complete General Training modules!</p>',
                attachTo: { 
                    element: 'a[href*="trophy"], a[href*="award"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Progress
        if ($('a[href*="progress"], a[href*="account"]').length) {
            tour.addStep({
                id: 'progress',
                text: '<h3>Your Progress 📊</h3><p>View your scores and study statistics for the General Training module.</p>',
                attachTo: { 
                    element: 'a[href*="progress"], a[href*="account"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        } else {
            // If progress element not found, just add final step
            tour.addStep({
                id: 'finish',
                text: '<h3>You\'re Ready! 🎯</h3><p>Start your IELTS General Training preparation journey. Good luck!</p>',
                buttons: [
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        }
    }
    
    /**
     * English Only Module Tour
     */
    function loadEnglishOnlyTour(tour) {
        
        // Welcome
        tour.addStep({
            id: 'welcome',
            text: '<h3>Welcome to English Learning! 🌟</h3><p>Let\'s show you around the English-only content. This will take about 2 minutes.</p>',
            buttons: [
                { text: 'Skip Tour', classes: 'shepherd-button-secondary', action: tour.complete },
                { text: 'Start Tour', action: tour.next }
            ]
        });
        
        // IELTS Band Scores - Show FIRST if present
        addBandScoresStep(tour);
        
        // Add course navigation steps (courses, practice tests, general english, vocabulary)
        addCourseNavigationSteps(tour);
        
        // Main Navigation
        if ($('.main-navigation, .primary-menu, .site-navigation, #site-navigation').length) {
            tour.addStep({
                id: 'navigation',
                text: '<h3>Main Menu 📚</h3><p>Access all your English courses and lessons here.</p>',
                attachTo: { 
                    element: '.main-navigation, .primary-menu, .site-navigation, #site-navigation',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Trophy Room / Awards
        if ($('a[href*="trophy"], a[href*="award"]').length) {
            tour.addStep({
                id: 'trophies',
                text: '<h3>Trophy Room 🏆</h3><p>Track your achievements as you improve your English!</p>',
                attachTo: { 
                    element: 'a[href*="trophy"], a[href*="award"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Next', action: tour.next }
                ]
            });
        }
        
        // Progress
        if ($('a[href*="progress"], a[href*="account"]').length) {
            tour.addStep({
                id: 'progress',
                text: '<h3>Your Progress 📊</h3><p>View your learning progress and statistics.</p>',
                attachTo: { 
                    element: 'a[href*="progress"], a[href*="account"]',
                    on: 'bottom' 
                },
                buttons: [
                    { text: 'Back', classes: 'shepherd-button-secondary', action: tour.back },
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        } else {
            // If progress element not found, just add final step
            tour.addStep({
                id: 'finish',
                text: '<h3>You\'re Ready! 🎯</h3><p>Start your English learning journey. Good luck!</p>',
                buttons: [
                    { text: 'Finish Tour', action: tour.complete }
                ]
            });
        }
    }
    
})(jQuery);
