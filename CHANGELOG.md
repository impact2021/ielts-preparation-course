# Changelog

All notable changes to the IELTS Course Manager plugin will be documented in this file.

## [2.42] - 2025-12-20

### Fixed
- **Dropdown Paragraph Display Bug**: Fixed critical bug where dropdown paragraph questions displayed duplicate/malformed text on the frontend
  - Question text was being displayed twice: once as raw text and once with embedded dropdowns
  - This caused confusing display like seeing both `1.[A: great B: good]` and `-A: greatB: good`
  - Solution: Added conditional logic to skip the general question text display for dropdown_paragraph type since it renders its own formatted version
  - Affected both standard and computer-based test (CBT) layouts

### Added
- **Flexible Placeholder Support**: Dropdown paragraph questions now support both `__N__` (double underscores) and `___N___` (triple underscores) as placeholder formats
  - Previous version only supported `___N___` (triple underscores)
  - Users were confused and tried using `__N__` which didn't work
  - New pattern matching uses regex alternation `(___N___|__N__)` to efficiently support both formats
  - Updated admin instructions to clarify both formats are supported
  - Backward compatible: existing questions with `___N___` continue to work perfectly

### Changed
- **Version Update**: Updated plugin version from 2.41 to 2.42
- **Pattern Matching Optimization**: Improved regex pattern for placeholder replacement
  - Uses single alternation pattern instead of multiple checks
  - More efficient with no performance degradation
  - Enforces consistent formats (won't match mixed formats like `__1___`)

### Documentation
- **V2.42_IMPLEMENTATION_SUMMARY.md**: Comprehensive technical documentation of all changes
- **V2.42_SECURITY_SUMMARY.md**: Complete security analysis confirming no vulnerabilities introduced
- **V2.42_TESTING_GUIDE.md**: Detailed testing scenarios covering all use cases

### Technical Details
- Modified `templates/single-quiz.php` and `templates/single-quiz-computer-based.php`
  - Added conditional check: `if ($question['type'] !== 'dropdown_paragraph')` before displaying question text
  - Prevents duplicate rendering of dropdown paragraph questions
- Modified `includes/admin/class-admin.php`
  - Updated placeholder replacement pattern from `/___N___/` to `/(___N___|__N__)/`
  - Updated admin instructions to mention both formats
- Modified `includes/admin/class-exercise-import-export.php`
  - Updated JSON format documentation to include both placeholder formats

### Security
- ✅ No security vulnerabilities introduced
- ✅ All existing sanitization and escaping maintained
- ✅ CodeQL analysis: Clean (no issues detected)
- ✅ Code review: Passed with no comments

### Backward Compatibility
- ✅ 100% backward compatible
- ✅ All existing dropdown questions continue to work
- ✅ No database migration required
- ✅ No breaking changes

## [2.41] - 2025-12-20

### Added
- **Improved Dropdown Paragraph Admin Interface**: Completely redesigned the admin interface for creating dropdown paragraph questions
  - Admins can now use simple ___1___, ___2___, ___3___ placeholders in the question text
  - Visual interface to add dropdown options for each numbered position
  - Radio buttons to select the correct answer for each dropdown
  - Much more intuitive than the previous format requiring manual `1.[A: option1 B: option2]` syntax
  - Automatic conversion from admin format (___N___) to internal format (N.[A: ... B: ...]) on save
  - Backward compatible: existing dropdown paragraph questions continue to work

- **Enhanced JSON Documentation**: Updated the Import Exercise page documentation
  - Added explanation of admin interface format (___N___ placeholders)
  - Documented the structured `dropdown_options` field in JSON format
  - Clarified the relationship between admin UI and exported JSON format

### Changed
- **Version Update**: Updated plugin version to 2.41
- **Dropdown Paragraph Storage**: Questions now store both `dropdown_options` (structured) and the converted question text format

### Technical Details
- Modified `includes/admin/class-admin.php` to add new dropdown-paragraph admin UI
  - Added `.dropdown-paragraph-field` section with visual dropdown builder
  - JavaScript handlers for dynamically adding/removing dropdown groups and options
  - Radio button selection for correct answers
  - Conversion logic in save_meta_boxes() to transform ___N___ format to N.[A: ... B: ...] format
- Updated `includes/admin/class-exercise-import-export.php`
  - Added sanitization for `dropdown_options` field during import
  - Updated JSON documentation to explain both formats
  - Added complete example with `dropdown_options` structure

## [2.40] - 2025-12-20

### Added
- **Dropdown Paragraph Question Type**: Added `dropdown_paragraph` to the available question types in the admin interface
  - Teachers can now select "Dropdown Paragraph Questions" from the question type dropdown
  - Previously the functionality existed but was not available in the UI
  - Allows creating questions with inline dropdown selections within paragraph text
  
- **Comprehensive JSON Format Documentation**: Added extensive documentation on the Import Exercise admin page
  - Complete examples of all 14 supported question types in JSON format
  - Detailed explanations for each question type including format requirements
  - Expandable/collapsible section with full JSON example showing all question types
  - Guidelines for alternative answers, case sensitivity, and special formats
  - Helps teachers understand how to create and modify exercise JSON files
  - Covers: Multiple Choice, Multi Select, True/False, Headings, Matching/Classifying, Short Answer, Sentence Completion, Summary Completion, Dropdown Paragraph, Table Completion, Labelling, Locating Information, Fill in Blank, and Essay

### Changed
- **Version Update**: Updated plugin version to 2.40

### Technical Details
- Modified `includes/class-quiz-handler.php` to add 'dropdown_paragraph' to `get_quiz_types()` array
- Enhanced `includes/admin/class-exercise-import-export.php` with comprehensive JSON documentation section
- Added styled documentation section with syntax highlighting and expandable code examples
- Documentation includes format specifications, correct answer patterns, and usage examples for all question types

## [2.38] - 2025-12-20

### Added
- **New Question Type: Dropdown Paragraph**: New question type that allows inline dropdown selections within paragraph text
  - Teachers can use placeholders like `1.[A: option1 B: option2]`, `2.[A: option3 B: option4]`, etc. in the question text
  - Placeholders are automatically replaced with inline dropdown select elements
  - Example: "1.[A: Sincere apologies B: Really sorry], unfortunately I am writing to 2.[A: let you know B: inform you] that I will be unable to meet."
  - Students select from dropdown options (A, B, C, etc.) inline with the text
  - Provides an authentic IELTS-style testing experience for option selection in context
  - Works in both standard and computer-based test layouts

### Changed
- **Version Update**: Updated plugin version to 2.38

### Technical Details
- Added `dropdown_paragraph` case in `templates/single-quiz.php` to parse `N.[A: option1 B: option2]` placeholders
- Added `dropdown_paragraph` case in `templates/single-quiz-computer-based.php` with same parsing logic
- Added CSS styling for `.answer-select-inline` class for inline dropdown display
- Added `.dropdown-paragraph-text` wrapper with increased line-height for better readability
- JavaScript answer collection (in `assets/js/frontend.js`) already handles inline inputs with format `answer_X_N`
- Added `dropdown_paragraph` case in `includes/class-quiz-handler.php` to check answers with format `1:A|2:B|3:C`
- Correct answer format uses numbers and letters (e.g., "1:A|2:B" means dropdown 1 should be A, dropdown 2 should be B)
- Case-insensitive letter matching for dropdown selections

## [2.37] - 2025-12-20

### Added
- **Inline Input Fields for Summary Completion Questions**: Summary completion questions now support inline input fields within the text
  - Teachers can use placeholders like `[ANSWER 1]`, `[ANSWER 2]`, etc. in the question text
  - Placeholders are automatically replaced with inline text input fields
  - Example: "Many people believe that [ANSWER 1] because of the importance of [ANSWER 2]"
  - Students type answers directly into the text where the blanks appear
  - Provides a more authentic IELTS testing experience
  - Backward compatible - existing summary completion questions with single input still work
- **JSON Text Paste for Exercise Import**: Import meta box now includes a "Paste JSON" tab
  - Teachers can directly paste JSON content instead of uploading a file
  - Useful for quickly copying and modifying exercises
  - Tab interface allows choosing between file upload or text paste
  - Same validation and security checks as file upload

### Changed
- **Version Update**: Updated plugin version to 2.37
- **Enhanced Import Meta Box**: Added tabbed interface for import methods

### Technical Details
- Modified `templates/single-quiz.php` to parse `[ANSWER N]` placeholders using regex and replace with inline input fields
- Modified `templates/single-quiz-computer-based.php` with same placeholder parsing logic
- Added CSS styling for `.answer-input-inline` class for proper inline display
- Added `.summary-completion-text` wrapper with increased line-height for better readability
- Updated `assets/js/frontend.js` answer collection to handle inline inputs (name format: `answer_X_N`)
- Enhanced `includes/class-quiz-handler.php` to check inline answers with format `N:answer1|alt1|M:answer2|alt2`
- Supports multiple alternative answers per blank (e.g., `1:students|learners|2:education|learning`)
- All existing flexible matching features preserved (case-insensitive, punctuation-agnostic)
- Modified `includes/admin/class-exercise-import-export.php` to add tabbed import interface
- Added new AJAX handler `handle_import_json_text()` for processing pasted JSON
- Updated `assets/js/exercise-import.js` with tab switching and JSON paste functionality
- Added validation for pasted JSON with helpful error messages

## [2.36] - 2025-12-20

### Changed
- **Version Update**: Updated plugin version to 2.36
- **Improved Lesson Table Layout**: Fixed text wrapping issues in lesson content table
  - Increased "Type" column width from 150px to 180px to prevent "End of lesson test" from wrapping
  - Increased "Action" column width from 120px to 180px to prevent "Retake (Fullscreen)" from wrapping
  - Improves readability and professional appearance of lesson tables
- **Enhanced Exercise Navigation**: "Next page >" button now appears BEFORE submitting quiz
  - Next URL is calculated on page load and displayed immediately
  - Button changes from "< Return to course" to "Next page >" when there's a next item in the lesson
  - No confirmation warning when clicking "Next page >" (only warns when returning to course mid-quiz)
  - Improves learning flow by making next item navigation more discoverable
  - Students no longer need to complete quiz to see if there's a next item

### Technical Details
- Modified `templates/single-quiz-computer-based.php` to calculate next_url on page load (reuses same logic as quiz-handler)
- Added `data-next-url` attribute to quiz container for JavaScript access
- Updated return-to-course link text and URL based on next_url availability
- Modified `assets/js/frontend.js` to skip warning dialog when link text contains "Next page"
- Enhanced `templates/single-lesson.php` with wider column widths for better text display

## [2.35] - 2025-12-20

### Changed
- **Version Update**: Updated plugin version to 2.35
- **Enhanced Sublesson Navigation**: Bottom navigation on sublesson pages now includes exercises
  - Navigation arrows at the bottom of sublesson (resource) pages now navigate through all lesson content in menu order
  - Includes both sublessons and exercises in the navigation sequence
  - Navigation labels dynamically change to indicate item type ("Previous/Next Sub Lesson" or "Previous/Next Exercise")
  - Exercises with CBT fullscreen mode automatically link to fullscreen view
  - Improves learning flow by allowing students to navigate directly from sublessons to exercises and vice versa
  - Respects menu_order for proper sequencing of all content items

### Technical Details
- Modified `templates/single-resource-page.php` to combine resources and quizzes in navigation logic
- Navigation now uses same approach as quiz-handler's `get_next_item_url()` method
- Properly sorts all items by menu_order before determining previous/next items
- Handles CBT quiz fullscreen parameter for seamless navigation

## [2.34] - 2025-12-20

### Added
- **Lesson Connection Display for Sub Lessons and Exercises**: Added "Lesson" column to admin list views for both Sub lessons and Exercises
  - Sub lessons now show which lesson(s) they are connected to in the admin interface
  - Exercises now show which lesson(s) they are connected to in the admin interface
  - Displays clickable links to parent lessons for easy navigation
  - Shows multiple lessons when content is connected to more than one lesson
  - Displays "—" when no lesson connection exists
  - Matches existing functionality where lessons show their connected courses

### Changed
- **Version Update**: Updated plugin version to 2.34

## [2.33] - 2025-12-20

### Fixed
- **Correct Answer Feedback in CBT Quizzes**: Fixed issue where correct answer highlighting was not consistently showing in computer-based test layout for multiple choice questions
  - Enhanced highlighting logic to use dual approach: highlight by both checked state AND correct answer value
  - Ensures correct answers are visible in all scenarios: correct answers, wrong answers, and no answers
  - Added validation for parseInt() to handle invalid inputs gracefully
  - Refactored code to eliminate duplication and improve maintainability
  - When user gets answer CORRECT: Both solid green and light green highlights applied
  - When user gets answer WRONG: Wrong answer shown in red, correct answer in light green
  - When user provides NO answer: Correct answer shown in light green

### Changed
- **Smart "Next Page" Navigation for Exercises**: "Return to course" button now changes to "Next page >" when there are more items in the lesson
  - Button dynamically uses `next_url` from backend to navigate to next sublesson or exercise
  - Text changes to "Next page >" when there's a next item in the lesson
  - Reverts to "< Return to course" when at the end of lesson/course
  - Improves learning flow by guiding students through lesson content sequentially
  - Backend already determined correct next URL; frontend now uses it properly
- **Version Update**: Updated plugin version to 2.33

### Technical Improvements
- Added radix parameter (10) to parseInt() calls for consistent base-10 parsing
- Added NaN validation before using parsed values
- Improved variable naming for better code clarity
- Enhanced security with proper input validation and output escaping

## [2.32] - 2025-12-20

### Changed
- **Version Update**: Updated plugin version to 2.32
- **Auto-Continue Removed**: Removed auto-continue countdown from ALL exercises
  - Previously, standard exercises would automatically redirect to the next exercise after 5 seconds
  - Users must now manually click the "Continue" button to proceed
  - CBT exercises already had this behavior and remain unchanged
  - Improves user control and prevents accidental navigation
- **Quiz Feedback Styling**: Updated standard quiz feedback box to match CBT feedback style
  - Removed colored background borders from pass/fail states
  - Changed to clean, minimal design consistent with CBT modal feedback
  - Maintains all functionality while improving visual consistency

## [2.31] - 2025-12-20

### Fixed
- **Lesson Display in Multiple Courses**: Fixed issue where lessons added to multiple courses would only show in one course
  - Root cause: Database queries were looking for string-serialized course IDs but arrays stored integer values
  - When lesson is added to courses [123, 456], it's stored as `a:2:{i:0;i:123;i:1;i:456;}` (integers)
  - Previous queries only looked for `s:3:"123";` pattern (strings)
  - Updated all queries to check for both integer pattern `i:123;` and string pattern `s:3:"123";`
  - Fixed in: shortcodes, templates (single-course-page, single-lesson), admin, progress tracker, and quiz handler
  - Lessons now properly appear in all assigned courses

### Changed
- **Course Cloning Behavior**: Modified course cloning to reuse lessons instead of duplicating them
  - Cloned course now links to the same lessons, sub-lessons, and exercises as the original
  - Preserves course structure and lesson order without creating duplicate content
  - Lessons are added to the cloned course's `_ielts_cm_course_ids` array
  - Perfect for creating course variations that share the same content
  - Significantly reduces database bloat and simplifies content management
- **Version Update**: Updated plugin version to 2.31

## [2.30] - 2025-12-20

### Added
- **Course Cloning Feature**: New functionality to clone courses with all their content
  - Added "Clone Course" meta box in the sidebar of course edit pages
  - Clones the entire course including all lessons, sub-lessons (resources), and exercises
  - Cloned courses are created as drafts with "(Copy)" suffix in the title
  - All course meta data, taxonomies, and content are preserved in the clone
  - Provides direct link to edit the newly cloned course
  - Perfect for creating variations of existing courses

### Changed
- **Question Type Renaming**: Renamed "Classifying and Matching Questions" question type
  - Changed from 'classifying_matching' to 'matching_classifying' for consistency
  - Works exactly like multiple choice questions (radio button selection)
  - Maintained for statistical tracking purposes
  - Updated in quiz handler, admin interface, and both quiz templates
- **Version Update**: Updated plugin version to 2.30

## [2.29] - 2025-12-20

### Changed
- **Matching Question Type Behavior**: Updated matching questions to work like multiple choice questions
  - Matching questions now use radio button selection interface (same as multiple choice)
  - Previously used text input fields for answers
  - Simplified scoring: 1 point per question (not per match item)
  - Updated admin interface to show multiple choice options for matching questions
  - Applied to both standard quiz layout and computer-based layout
  - Aligns matching questions with classifying_matching question type behavior
- **Version Update**: Updated plugin version to 2.29

## [2.28] - 2025-12-20

### Fixed
- **Matching Questions Display**: Fixed display of matching questions to show individual question numbers
  - Each match in a matching question now displays its own sequential question number (e.g., Question 7, Question 8, Question 9, etc.)
  - Previously, all matches were grouped under a single question number without individual numbering
  - Applied to both standard quiz layout and computer-based layout
  - Improves clarity when exporting/importing exercises with matching questions

### Changed
- **Version Update**: Updated plugin version to 2.28

## [2.27] - 2025-12-20

### Added
- **Extended IELTS Question Types**: Expanded question type options to better match authentic IELTS exam formats
  - Added "Headings Questions" type for matching headings to paragraphs
  - Added "Short Answer Questions" type for concise text answers
  - Added "Sentence Completion Questions" type for completing sentences
  - Added "Table Completion Questions" type for filling in table cells
  - Added "Labelling Style Questions" type for diagram/image labelling
  - Added "Classifying and Matching Questions" type for categorization tasks
  - Added "Locating Information Questions" type for finding specific information
  - Kept existing types: Multiple Choice, Multi Select, True/False/Not Given, Summary Completion, Essay
  - "Fill in the Blank" retained as legacy option for backward compatibility

### Changed
- **Version Update**: Updated plugin version to 2.27
- **Question Type Handling**: Updated backend and frontend to support all new question types
  - Text-based questions (short answer, sentence completion, etc.) use flexible text matching
  - Selection-based questions (headings, classifying) use multiple choice format
  - All question types fully functional in both standard and computer-based layouts

## [2.26] - 2025-12-19

### Fixed
- **Export Download Issue**: Fixed bug where XML export wasn't downloading and instead redirected back to the previous page
  - Added output buffer clearing before sending headers to prevent interference
  - Added `nocache_headers()` to ensure proper download behavior
  - Added Content-Length header for better browser compatibility
  - Export now properly initiates file download in all browsers

### Changed
- **Version Update**: Updated plugin version to 2.26

## [2.25] - 2025-12-19

### Added
- **Exercise Import/Export Functionality**: New feature to export and import individual exercises
  - Export exercises to JSON format from exercise edit pages
  - New "Export Exercise" meta box in sidebar of exercise edit pages
  - Import JSON files into existing or new exercises
  - New "Import Exercise" admin page under IELTS Courses menu
  - Comprehensive step-by-step documentation included in import page
  - Exports include all questions, settings, reading texts, and feedback
  - Perfect for creating practice test variations by modifying exported JSON
  - Saves hours when creating similar exercises

### Fixed
- **Lesson Save Issue**: Fixed bug where adding lessons to courses from course edit page didn't properly save
  - Added backward compatibility field update in AJAX handler
  - Lessons added via course page now properly appear in lesson lists
  - Removed need to manually open lesson and add it to course
  - Both `_ielts_cm_course_ids` and `_ielts_cm_course_id` meta fields now updated correctly
  - Also fixed lesson removal to properly update backward compatibility field

### Changed
- **Version Update**: Updated plugin version to 2.25

## [2.19] - 2025-12-19

### Added
- **Unsaved Progress Warnings**: Added two-layer protection against accidental data loss
  - Warning dialog when clicking "Return to course" before submitting quiz
  - Browser warning when trying to close tab or navigate away without submitting
  - Warnings only appear before submission, not after
  - Prevents accidental loss of quiz answers and student work

- **Multiple Text Highlights Support**: Enhanced text highlighting to support multiple independent highlight blocks
  - Users can now highlight multiple separate sections of reading text
  - All highlights persist during the quiz session
  - Fixed issue where only one highlight block could be added at a time
  - Improved highlight restoration logic to properly handle multiple highlights

### Changed
- **Submit Quiz Button Repositioned**: Moved "Submit Quiz" button from bottom navigation to top timer bar
  - Button now appears on the left side of the top bar
  - Same size and styling as "Return to course" button
  - Always visible without scrolling
  - Bottom navigation now only contains question number buttons
  - Cleaner, more professional interface design

- **Improved Question Navigation Scroll**: Enhanced scroll position when clicking question numbers
  - Increased scroll offset from 20px to 50px above question
  - Ensures full question text and title are visible
  - Reduces need for manual scrolling adjustments
  - Better reading experience when reviewing questions

- **Version Update**: Updated plugin version to 2.19

## [2.18] - 2025-12-19

### Fixed
- **Text Highlighting Background Color**: Fixed highlight background color not applying properly
  - Added `!important` to background-color CSS to ensure highlighting is always visible
  - Resolves issue where highlighted text didn't show yellow background

### Changed
- **Compact Navigation Buttons**: Reduced navigation button size to fit more questions on one line
  - Button size reduced from 40px to 32px (height and width)
  - Padding reduced from 8px to 4px
  - Font size set to 13px for better readability at smaller size
  - Gap between buttons reduced from 8px to 5px
  - Can now fit 40+ question buttons on one line
  - Improves navigation for quizzes with many questions

- **Simplified Navigation Separators**: Replaced "Reading Passage X" labels with simple "|" separators
  - Reading passage sections now separated by a simple vertical bar "|"
  - Saves significant horizontal space in navigation bar
  - Cleaner, more compact visual design
  - Only shows separator between passages, not before first passage

- **Version Update**: Updated plugin version to 2.18

## [2.17] - 2025-12-19

### Added
- **Text Highlighting Feature for CBT Reading Texts**: Reading texts in computer-based tests now support text highlighting
  - Select text, right-click, and choose "Highlight" to add yellow background highlighting
  - "Clear" button appears when highlights exist to remove all highlighting
  - Highlights persist during the quiz session using browser storage
  - Highlights automatically clear when quiz is submitted
  - Mimics the actual IELTS computer-delivered test highlighting feature
  - Only available in CBT (Computer-Based Test) layout exercises

### Changed
- **Version Update**: Updated plugin version to 2.17

## [2.16] - 2025-12-19

### Changed
- **Return to Course Link Location**: Moved "Return to course" link to top right corner of timer bar
  - Link now appears in the same row as the timer and band score
  - Shows during the test AND after submission
  - Replaces previous bottom-centered link that only showed in fullscreen mode
  - Better UX with always-visible access to course navigation
  
- **Question Navigation Simplification**: Removed "Jump to Question:" label from navigation bar
  - Cleaner, more streamlined navigation interface
  - Question buttons are now more prominent
  - Reduces visual clutter in the bottom navigation area

### Added
- **Collapsible Reading Texts**: Reading texts in exercise editor now collapse by default with expandable caret icons
  - Reading texts start collapsed for easier navigation in the admin panel
  - Click on reading text header to expand/collapse individual texts
  - Smooth animation for better user experience
  - Matches existing question collapse/expand behavior
  - New reading texts added are shown expanded by default for immediate editing

## [2.15] - 2025-12-19

### Changed
- **Return to Course Link Location**: Moved "Return to course" link from quiz completion modal to the main fullscreen page
  - Link now appears below the quiz result area on the fullscreen page
  - Provides better UX as it's always visible without needing to close the modal
  - Only appears when quiz is in fullscreen mode and linked to a course

### Fixed
- **Feedback for Unanswered Questions**: Question feedback now displays even when no answer is provided
  - Students see incorrect_feedback for questions they skipped
  - Helps with learning by showing what they missed
  - Unanswered questions still marked as incorrect as expected
  
- **Question Text Paragraph Breaks**: Fixed paragraph breaks in question text not displaying
  - Applied wpautop() function to properly format line breaks
  - Double line breaks now create visual paragraph spacing
  - Applies to both CBT and regular quiz layouts
  - Improves readability of multi-paragraph questions

## [2.14] - 2025-12-19

### Added
- **Return to Course Button**: Added "Return to course" button in top right of CBT results modal
  - Button appears next to the close button when quiz is linked to a course
  - Allows students to quickly navigate back to the course page after completing a quiz
  - Styled consistently with WordPress button design
  
- **Reading Passage Labels**: Enhanced bottom navigation with reading passage labels
  - Passage labels (e.g., "Reading Passage 1") now appear before question number groups
  - Questions are visually grouped by their linked reading passage
  - Uses custom passage titles when configured, or falls back to "Reading Passage N"
  - Improves navigation clarity for multi-passage reading exercises
  
- **True/False/Not Given Dropdown**: Changed correct answer field to dropdown for better UX
  - Replaces text input with a dropdown containing three options: True, False, Not Given
  - Prevents typos and ensures consistent answer format
  - Dynamically converts between input types when question type changes
  - Preserves existing values when editing saved questions

### Changed
- **Quiz Submission Response**: Extended AJAX response to include course URL
  - Enables "Return to course" functionality
  - No impact on existing functionality

### Security
- **XSS Prevention**: Improved output escaping in dynamic content
  - Course URLs are properly escaped using jQuery .attr() method
  - Reading passage labels use esc_html() for safe output
  - All user inputs properly sanitized and validated

## [2.13] - 2025-12-18

### Changed
- **CBT Fullscreen Mode Improvements**: Enhanced fullscreen mode for computer-based tests
  - Quiz header with breadcrumb navigation is now hidden in fullscreen mode for distraction-free testing
  - Provides a cleaner, more focused test-taking experience
  
### Added
- **Answer Review Feedback**: Enhanced answer review functionality in CBT quizzes
  - Feedback from correct_feedback and incorrect_feedback fields now displays below each question when reviewing answers
  - Feedback appears after quiz submission when students click "Review my answers"
  - Visual styling matches the correctness of the answer (green border for correct, red for incorrect)

## [2.12] - 2025-12-18

### Fixed
- **CBT Fullscreen Button**: Verified and maintained working fullscreen button functionality from v2.10
  - Button opens CBT exercise in fullscreen modal correctly
  - Text hyperlink opens quiz normally without modal
  
### Changed
- **CBT Results Modal Simplified**: Streamlined results display for computer-based tests
  - Removed detailed question-by-question feedback from results modal
  - Removed automatic 5-second redirect countdown
  - Added "Review my answers" button to view highlighted answers in the form
  - Band score now displays prominently after submission
  - Submit button is hidden after submission during answer review
  - Visual highlighting (green/red) in quiz form remains for answer review

### Added
- **Review Answers Functionality**: New button to review answers after quiz submission
  - Button appears in results modal under time taken section
  - Closes modal and allows students to review their highlighted answers
  - Students can see correct (green) and incorrect (red) answers directly in the form

## [2.11] - 2025-12-18

### Added
- **Collapsible Questions**: Exercise questions now collapse by default with expandable caret icons
  - Questions in exercise editor start collapsed for easier navigation
  - Click on question header to expand/collapse individual questions
  - Smooth animation for better user experience
  
- **Course-Based Lesson Filtering**: Dynamic lesson filtering in exercise editor
  - "Assign to Lessons" dropdown now filters based on selected courses
  - Only shows lessons that belong to the selected course(s)
  - Maintains selected lessons when filtering
  - AJAX-powered for instant updates
  
- **Remove Buttons**: Added remove functionality for better content management
  - Remove lessons from courses directly in the Course Lessons meta box
  - Remove sublessons and exercises from lessons in the Lesson Content meta box
  - Confirmation dialogs prevent accidental deletions
  - Removed content can be re-added using search functionality
  
- **Search Functionality**: Enhanced content assignment with search capabilities
  - Search for lessons when adding to courses
  - Search for sublessons when adding to lessons
  - Search for exercises when adding to lessons
  - Real-time filtering as you type
  - Separate selectors for different content types

### Changed
- **Version Update**: Updated plugin version to 2.11
- **Course Lessons UI**: Improved UI with add/remove/search section above lesson list
- **Lesson Content UI**: Enhanced UI with content type selector and search functionality
- **Content Management**: More intuitive workflow for managing course and lesson relationships

### Technical
- Added AJAX handlers for dynamic lesson filtering
- Added AJAX handlers for adding/removing content relationships
- Enhanced JavaScript for search and content management
- Improved nonce security for all new AJAX operations

## [2.10] - 2025-12-18

### Fixed
- **CBT Fullscreen Navigation**: Removed broken `?fullscreen=1` parameter navigation
  - Clicking quiz title link now works the same as "Start CBT Exercise" button
  - Always shows proper quiz header and content
  - Popup/modal only triggers via "Open in Fullscreen" button
  
- **Scoring System**: Fixed quiz submission for computer-based tests
  - Quiz form submission now properly detects CBT layout container
  - Score calculation works correctly for both percentage and band score modes
  - Fixed 0/0 scoring issue where answers weren't being registered
  
- **Submit Button State**: Button no longer stuck showing "Submitting..."
  - Button is properly disabled during submission
  - Form is hidden after successful submission (for inline results)
  - Modal displays results without form visibility issues

### Added
- **CBT Score Display Modal**: Results now show in popup for computer-based tests
  - Results display in centered modal overlay for CBT exercises
  - Modal includes close button and retake functionality
  - Prevents page navigation while reviewing results
  
- **Timer Information in Results**: Enhanced result display with timing details
  - Shows time limit (if set) in quiz results
  - Shows actual time taken to complete the quiz
  - Time formatted as MM:SS for easy reading
  
- **Visual Answer Feedback**: Correct and wrong answers now highlighted
  - Correct answers: Green background in navigation and answer areas
  - Wrong answers: Red background in navigation and answer areas
  - Correct answer also highlighted in green when user answer is wrong
  - Applies to both navigation buttons and question/answer sections
  
- **Partial Quiz Submission**: Removed requirement to answer all questions sitewide
  - Removed `required` attribute from all question input fields
  - Students can now submit quizzes with partial answers
  - Applies to all question types: multiple choice, true/false, fill-in-blank, essay
  - Affects both standard quiz layout and computer-based test layout

### Changed
- **Version Update**: Updated plugin version to 2.10
- **Quiz Submission Handler**: Enhanced to use event delegation
  - Works with both static forms and dynamically created modal forms
  - Properly tracks quiz start time for duration calculation
  - Better detection of quiz container type (standard vs CBT)

## [2.9] - 2025-12-18

### Added
- **CBT Popup Control**: New checkbox in exercise settings for computer-based tests
  - "Open as Popup/Fullscreen Modal" option in Quiz Settings
  - When checked: Opens CBT exercise in fullscreen popup modal (previous default behavior)
  - When unchecked: Opens CBT exercise in the same window without modal
  - Provides flexibility for different testing scenarios
  - Only visible when "Computer-Based IELTS Layout" is selected

### Fixed
- **CBT Fullscreen Modal Improvements**: Enhanced fullscreen modal for computer-based tests
  - Radio buttons now properly display vertically (stacked) in fullscreen mode
  - Reading text remains visible and doesn't disappear on scroll
  - Timer displays correctly in fullscreen mode
  - Question navigation numbers properly highlight when answered (green background)
  - All CSS styling properly applied within modal
  - Improved modal styling for better user experience

### Changed
- **Multi-Site Content Sync Order Preservation**: Course push now maintains correct order
  - Lessons sync in correct menu_order
  - Sublessons (resources) sync in correct menu_order
  - Exercises (quizzes) sync in correct menu_order
  - Ensures consistent content structure across primary and subsites
- Updated plugin version to 2.9

## [2.8] - 2025-12-18

### Changed
- **Version Update**: Updated plugin version to 2.8
- **CBT Modal Improvements**: Fixed computer-based test modal to properly hide content when not in fullscreen mode
  - Quiz form is now completely hidden when viewing the fullscreen notice
  - Prevents content from showing below the "must be viewed in fullscreen" section
- **CBT Navigation Enhancement**: Moved submit button to navigation row for better accessibility
  - Submit button now appears in the question navigation bar at the bottom
  - Improved layout and user experience for computer-based tests
- **Answered Question Indication**: Enhanced visual feedback for answered questions
  - Answered questions now show with green background in navigation
  - Makes it easier to track progress through the test

### Fixed
- **Timer Visibility**: Ensured timer is properly visible for exercises with time limits
  - Timer displays correctly in both standard and computer-based layouts
  - Timer remains visible in fullscreen mode
- **CBT Fullscreen Formatting**: Fixed formatting issues in fullscreen mode
  - Reading text on left no longer disappears on scroll
  - Proper CSS styling maintained in fullscreen modal
  - Two-column layout properly maintained

## [2.7] - 2025-12-18

### Changed
- **Version Update**: Updated plugin version to 2.7
- **Fullscreen Mode for CBT Tests**: Computer-based tests now require fullscreen mode
  - Quiz content is hidden when not in fullscreen mode
  - Users must click "Open in Fullscreen" button to access the test
  - Improved fullscreen layout with better space utilization
  - Removed unnecessary spacing below computer-based-container
  - Question navigation now properly contained within viewport (no scrolling required)
  - Optimized layout uses full viewport height efficiently

### Fixed
- **Grammar Issue**: Fixed "points" text to correctly show "point" (singular) when a question is worth 1 point
  - Applied to both standard quiz layout and computer-based test layout
  - Dynamic pluralization based on point value

## [2.6] - 2025-12-18

### Added
- **IELTS Band Score Conversion**: Exercises can now display results as IELTS band scores instead of percentages
  - New "Scoring Type" field in exercise settings with options for:
    - Percentage (Standard) - default behavior
    - IELTS General Training Reading - converts correct answers to band scores (0-9)
    - IELTS Academic Reading - converts correct answers to band scores (0-9)
    - IELTS Listening - converts correct answers to band scores (0-9)
  - Band score conversion tables match official IELTS scoring rubrics
  - Results displayed as "Band X.X" instead of percentage where applicable
  - Progress tables show band scores for IELTS-type exercises
  - Lesson tables show band scores for best quiz results
  - Quiz submission displays band score prominently

### Fixed
- **Fullscreen CSS Issue**: Fixed styling loss when opening CBT exercises in fullscreen mode
  - Fullscreen mode now properly loads all WordPress styles and scripts
  - Complete HTML document structure created for fullscreen mode
  - All formatting, fonts, and styles now preserved in fullscreen
  - Maintains consistent appearance between regular and fullscreen modes

### Changed
- Updated plugin version to 2.6
- Progress table column header changed from "Percentage" to "Result" to accommodate both percentages and band scores
- Quiz result displays adapt based on scoring type (band score or percentage)

## [2.5] - 2025-12-18

### Added
- **Question-to-Passage Linking for CBT Layout**: Questions can now be linked to specific reading texts in computer-based exercises
  - New "Linked Reading Text" dropdown in question editor for computer-based layout exercises
  - Automatically shows corresponding reading text when user scrolls to linked questions
  - Smooth transitions when switching between reading passages
  - Example: Questions 1-12 linked to Passage 1, Questions 13-23 linked to Passage 2
  - Reading text automatically switches when scrolling through questions
  - Click navigation also triggers reading text switching
  - Initially only first reading text is visible; others shown on demand
  
### Changed
- Updated plugin version to 2.5
- Enhanced computer-based quiz layout with intelligent reading text display
- Reading texts now initially hidden except the first one for cleaner interface
- Improved scroll detection to automatically show relevant reading passages

### Technical
- Added `reading_text_id` field to question data structure
- Dynamic admin UI updates reading text selectors when texts are added/removed
- Debounced scroll event handling for smooth performance
- Fade transitions for reading text switching

## [2.4] - 2025-12-18

### Added
- **Multiple Accepted Answers for Fill in the Blank**: Fill in the blank questions now support multiple correct answers
  - Use pipe character `|` to separate multiple accepted answers (e.g., "The British Council|British Council")
  - Flexible matching still applies (case-insensitive, punctuation removed)
  - Works with both standard and computer-based quiz layouts
  
- **Summary Completion Question Type**: New question type for embedding blanks within paragraphs
  - Similar to fill in the blank but designed for summary/paragraph completion
  - Supports multiple accepted answers using pipe separator
  - Available in quiz creation admin interface
  - Works with both standard and computer-based quiz layouts
  
- **Feedback Button Feature**: Added minimizable feedback button on all course content pages
  - Appears on course, lesson, resource (sublesson), and quiz pages
  - Also appears on LearnDash pages (sfwd-courses, sfwd-lessons, sfwd-topic)
  - Button minimizes to icon instead of closing completely
  - State persists across page loads using localStorage
  - Integrates with Contact Form 7 for feedback submission
  - Auto-fills page title, URL, and user information
  - Only visible to logged-in users

### Changed
- Updated plugin version to 2.4
- Enhanced fill in the blank answer checking to support multiple answers

## [2.3] - 2025-12-18

### Added
- **Recursive Content Sync**: When pushing a course, all associated content is now automatically synced
  - Pushing a course now syncs all its lessons
  - All sublessons (resources) within those lessons are synced
  - All exercises (quizzes) within those lessons are synced
  - Detailed sync statistics shown after push (lesson count, sublesson count, exercise count)
  - Eliminates need to manually push each piece of content individually

- **Fullscreen Mode for Computer-Based Tests**: CBT exercises now open in fullscreen mode
  - New "Start CBT Exercise" button in lesson view opens exercise in fullscreen window
  - Fullscreen mode removes WordPress header and footer for distraction-free testing
  - "Open Fullscreen" button available within CBT exercise for manual fullscreen launch
  - Optimized viewport heights for true fullscreen experience
  - Mimics actual IELTS computer-delivered test environment

### Fixed
- **Lesson Meta Box Error**: Fixed critical error "Uncaught TypeError: in_array(): Argument #2 ($haystack) must be of type array, string given"
  - Issue occurred when retrieving lesson course assignments that were stored as serialized strings
  - Added proper unserialization handling for `_ielts_cm_course_ids`, `_ielts_cm_lesson_ids` metadata
  - Applied fix to lesson_meta_box, resource_meta_box, and quiz_meta_box functions
  - Maintains backward compatibility with both array and serialized string formats

### Changed
- Updated plugin version to 2.3

## [2.2] - 2025-12-18

### Added
- **Question Duplication**: Added duplicate button for questions in exercises
  - One-click duplication of any question type
  - Duplicates all question data including options, feedback, and points
  - Automatically assigns new unique index to duplicated question
  - Works with multiple choice, true/false, fill-in-blank, and essay questions

- **Question Drag-and-Drop**: Implemented drag-and-drop reordering for questions
  - Drag handle icon on each question for easy reordering
  - Visual feedback during drag operation
  - Automatic reindexing of question names after reordering
  - Preserves question content and settings during reorder
  - Improves exercise organization workflow

- **WYSIWYG Editor for Reading Texts**: Enhanced reading text editor in computer-based layout
  - Rich text editor (wp_editor) for reading passages
  - Full formatting toolbar with bold, italic, lists, links, etc.
  - Support for images and media embedding
  - Preserves HTML formatting in reading passages
  - Better content authoring experience for CBT exercises

### Changed
- **Pass Percentage Field**: Hidden pass percentage field from admin UI
  - Field still saves to database for future features
  - Reduces UI clutter in quiz settings
  - Pass percentage remains configurable but not prominently displayed

### Fixed
- **Database Table Creation**: Fixed issue where `wp_ielts_cm_site_connections` table would not exist on sites that installed the plugin before version 2.0
  - Added automatic database table creation check on plugin version update
  - All required tables are now created or verified when version changes
  - Ensures multi-site sync functionality works on all installations

- **Computer-Based Layout Heights**: Fixed viewport height issue for computer-based IELTS test layout
  - Changed from fixed `max-height: 700px` to viewport-relative heights using `calc(100vh - Xpx)`
  - Desktop layout now uses `calc(100vh - 300px)` to properly account for headers and page elements
  - Tablet layout uses `calc(100vh - 400px)` for stacked columns
  - Mobile layout uses `calc(100vh - 450px)` for optimal mobile viewing
  - Layout now properly fills the screen regardless of header size

### Technical
- Updated plugin version to 2.2
- Database upgrade routine now runs on version update to ensure all tables exist
- Enhanced JavaScript for question management with jQuery sortable
- Improved handling of TinyMCE editor instances in dynamic content
- Added null checking for regex matches to prevent errors

## [2.1] - 2025-12-18

### Added - Computer-Based IELTS Test Layout
- **New Layout Type**: Added computer-based IELTS test layout option for exercises
  - Two-column full-width design mimicking actual IELTS computer test
  - Left column displays reading passages with scrollable content
  - Right column shows questions with answer inputs
  - Bottom navigation bar for quick question jumping
  - Smooth scrolling and question highlighting
  
- **Reading Text Management**: New reading text fields for exercises
  - Support for multiple reading passages per exercise
  - Optional titles for each reading text (e.g., "Passage 1")
  - Rich text content support with proper formatting
  - Reading texts displayed in left column of computer-based layout
  
- **Question Navigation**: Interactive question navigation system
  - Navigation buttons for all questions at bottom of page
  - Click any button to jump directly to that question
  - Visual indication when questions are answered (green highlight)
  - Smooth scrolling animation to selected question
  - Question highlight animation on navigation
  
- **Layout Selection**: Exercise can use either standard or computer-based layout
  - Default layout remains unchanged (backward compatible)
  - Computer-based layout selected via dropdown in exercise settings
  - Separate templates for each layout type
  
- **Responsive Design**: Computer-based layout adapts to different screen sizes
  - Desktop: Side-by-side columns with fixed heights and scrollbars
  - Tablet: Vertical stacking with separate scroll areas
  - Mobile: Optimized column heights and navigation layout
  
### Changed
- Updated plugin version to 2.1
- Enhanced quiz meta box with layout type selection
- Modified single quiz page template to support multiple layouts

### Technical Details
- New template: `templates/single-quiz-computer-based.php`
- New CSS styles for two-column layout and navigation
- JavaScript handlers for question navigation and answer tracking
- New meta fields: `_ielts_cm_layout_type` and `_ielts_cm_reading_texts`

## [2.0] - 2025-12-18

### Added - Multi-Site Content Sync
- **Primary/Subsite Architecture**: New multi-site content synchronization system
  - Configure sites as primary (content source) or subsite (content receiver)
  - Primary sites can push content updates to multiple subsites
  - Standalone mode available for sites not using sync features

- **Site Connection Management**: New admin page for managing site connections
  - Navigate to "IELTS Courses > Multi-Site Sync" to configure
  - Add/remove subsite connections with authentication tokens
  - Test connections to verify subsite availability
  - View last sync time and status for each connected subsite

- **Content Push Functionality**: Push content from primary to subsites
  - "Push to Subsites" button on course, lesson, lesson page, and exercise edit pages
  - One-click push to all connected subsites
  - Real-time sync status display with per-subsite results
  - Automatic content change detection using hash comparison

- **Progress Preservation**: Student progress protected during content updates
  - Completed items remain marked as complete after content updates
  - Completion percentages automatically recalculated when new content added
  - Quiz results preserved and linked to updated content
  - User enrollment data maintained across syncs

- **REST API for Sync**: New REST API endpoints for inter-site communication
  - `/wp-json/ielts-cm/v1/sync-content` - Receive content from primary site
  - `/wp-json/ielts-cm/v1/test-connection` - Test connectivity and authentication
  - `/wp-json/ielts-cm/v1/site-info` - Get site information and configuration
  - Token-based authentication for secure communication

- **Database Tables**: New tables for sync management
  - `ielts_cm_site_connections` - Store connected subsite information
  - `ielts_cm_content_sync` - Track sync history and content hashes

- **Sync Logging and History**: Track all sync operations
  - View sync history for each content item
  - Per-subsite sync status (success/failed)
  - Content hash tracking for change detection
  - Last sync timestamp for each subsite

### Changed
- Updated plugin version to 2.0
- Enhanced database schema with multi-site sync tables

### Use Cases Supported
1. **Update Existing Content**: When you update a course, lesson, or exercise on the primary site, push changes to subsites. Student completion status is preserved.

2. **Add New Content**: When you add new lessons or exercises to an existing course, push updates to subsites. Completion percentages automatically adjust for both master and subsites.

3. **Centralized Content Management**: Manage all course content from a single primary site and distribute to multiple learning sites.

## [1.18] - 2025-12-18

### Added
- **Text-Based Exercise Import**: New admin page for creating exercises from pasted text
  - Navigate to "IELTS Courses > Create Exercises from Text"
  - Paste specially formatted text with questions, options, and feedback
  - Automatically parses True/False questions with correct/incorrect indicators
  - Supports multi-question exercises in a single paste
  - Extracts question text, answer options, correct answers, and feedback
  - Creates exercises as drafts or published posts
  - Ideal for quickly importing exercises from formatted documents
  - Example format supported:
    ```
    Exercise Title/Instructions
    
    Question 1 text
    This is TRUE
    Correct answer
    This is FALSE
    Incorrect
    
    Optional feedback text
    
    Question 2 text
    This is TRUE
    This is FALSE
    Correct answer
    ```

### Changed
- Updated plugin version to 1.18

## [1.17] - 2025-12-18

### Enhanced
- **Exercise Results Display**: Complete question feedback now shown after submission
  - Shows full question text for each question
  - Displays user's answer alongside the question
  - Shows correct answer when user answered incorrectly
  - Displays configured feedback for each question/option
  - Supports all question types: multiple choice, true/false, fill-in-blank, essay

- **Auto-Navigation**: Automatic progression to next content after exercise completion
  - 5-second countdown with visual indicator
  - "Continue" button for immediate navigation
  - "Cancel" button to stop auto-redirect and review results
  - Intelligently determines next item: quiz, resource page, lesson, or course
  - Improves learning flow and user experience

- **Multiple Choice Backend Redesign**: New structured interface for creating options
  - Individual input field for each option text
  - Checkbox to mark correct answer for each option
  - Dedicated feedback field for each option
  - Add/Remove option buttons with minimum 2 options enforced
  - More intuitive than previous textarea-based approach
  - Maintains backward compatibility with existing questions

### Changed
- **Passing Score**: Removed passing score display from exercise frontend
  - Exercises no longer show "Passing Score: XX%" to students
  - Focus on learning and feedback rather than pass/fail
  - Passing percentage still configurable in admin for future features

### Fixed
- Improved operator precedence in multiple choice field visibility logic
- Fixed JavaScript string concatenation in dynamic option generation
- Enhanced auto-redirect accessibility with cancel option

### Removed
- **Documentation Cleanup**: Removed 37 unnecessary .md documentation files
  - Kept only essential files: README.md, CHANGELOG.md, SECURITY_SUMMARY.md
  - Cleaner repository structure

## [1.16] - 2025-12-18

### Enhanced
- **XML Exercises Creator - Question Grouping**: Questions that belong to the same quiz are now grouped into multi-question exercises
  - Automatically detects quiz associations via `ld_quiz_*` metadata
  - Creates single exercises with multiple questions instead of separate exercises per question
  - Intelligently extracts base quiz titles (e.g., "Quiz Name Q1" → "Quiz Name")
  - Displays question count in results table
  - Significantly reduces number of exercise posts created

- **XML Exercises Creator - Placeholder Values**: Pre-fills options and correct answers with helpful examples
  - Multiple choice: Pre-fills with "Option A", "Option B", "Option C", "Option D" and correct answer "0"
  - True/False: Pre-fills correct answer with "true" (placeholder to be updated)
  - Fill in the blank: Pre-fills with "[Enter the expected answer here]"
  - Makes it clear what format is expected for each question type

- **Question Editor - WYSIWYG Support**: Question text now uses WordPress visual editor
  - HTML content including images is properly preserved from XML
  - Full visual editing capabilities with formatting toolbar
  - Media buttons for adding/editing images
  - Existing questions display in rich text editor
  - New questions show helper text about HTML support

- **Data Sanitization**: Improved security for question content
  - Uses `wp_kses_post()` instead of `sanitize_textarea_field()` for question text
  - Allows safe HTML while preventing XSS attacks
  - Preserves formatting, images, and other HTML elements

### Updated
- Documentation updated to reflect new features
- XML_EXERCISES_CREATOR_GUIDE.md updated with placeholder information
- README.md highlights v1.16 improvements

## [1.15] - 2025-12-18

### Added
- **LearnDash XML Conversion**: New XML conversion script for LearnDash exports
  - Converts `sfwd-question` post types to `ielts_quiz` format
  - Automatically updates URLs and GUIDs to match new structure
  - Processes 4,500+ questions in under 2 minutes
  - Preserves all metadata and relationships
  - Creates backup of original file automatically

### Documentation
- **New Guide**: Added `XML_CONVERSION_README.md`
  - Complete documentation of XML conversion process
  - Verification steps to ensure successful conversion
  - Import instructions for converted files
  - Troubleshooting guide for common issues
  - File structure and metadata preservation details

### Tools
- **Conversion Script**: Added `convert-xml.php`
  - Standalone PHP script for XML transformation
  - Can process large XML files (13+ MB)
  - Reusable for multiple LearnDash exports
  - Detailed progress reporting and statistics

## [1.14] - 2024-12-18

### Fixed
- **LearnDash XML Import - Question Import**: Enhanced quiz question import from LearnDash XML exports
  - Fixed question-to-quiz linking with multiple meta key fallbacks (`quiz_id`, `_quiz_id`)
  - Fixed options storage format (now stores as newline-separated string instead of array)
  - Added extraction of correct/incorrect answer feedback from question meta
  - Added comprehensive logging to track question import process with skip counters
  - Added warnings for quizzes that end up with no questions after import

- **LearnDash XML Import - Relationship Linking**: Fixed critical relationship linking issues
  - Fixed lessons not linking to courses automatically
  - Fixed lesson pages (topics) not linking to lessons automatically
  - Fixed quizzes not linking to courses and lessons automatically
  - Root cause: `map_meta_key()` was mapping `course_id` inconsistently across post types
  - Now all relationship IDs are stored with `_ld_original_` prefix for consistent lookup
  - Added multiple fallback meta key lookups in `update_relationships()`
  - No longer need to manually open and save items to establish relationships

### Enhanced
- **Import UI Feedback**: Improved user experience for LearnDash XML imports
  - Added detailed import results display showing counts for all imported items
  - Added question count to import summary
  - Added expandable log viewer with color-coded messages (info, warning, error)
  - Added helpful error messages for common import failures
  - Updated import instructions to emphasize exporting `sfwd-question` post types
  - Added XML verification step to guide users

- **Import Logging**: Comprehensive relationship logging
  - Shows count of lessons linked to courses
  - Shows count of lesson pages linked to lessons
  - Shows count of quizzes linked to courses and lessons
  - Individual warnings for items that couldn't be linked with reasons
  - Helps troubleshoot import issues quickly

### Documentation
- **New Guide**: Added `QUIZ_QUESTIONS_IMPORT_GUIDE.md`
  - Comprehensive troubleshooting guide for quiz question import issues
  - Step-by-step solutions for common problems
  - XML structure reference for advanced users
  - Import log interpretation guide
  - Success verification methods
  - Covers both question import and relationship linking issues

## [1.13] - 2024-12-17

### Fixed
- **Exercise Validation**: Added validation to prevent publishing exercises without questions
  - Exercises without questions are automatically saved as drafts instead of being published
  - Added admin notice explaining why an exercise was not published
  - Added inline warning in quiz meta box when no questions exist
  - Dynamic UI updates warning visibility when questions are added/removed
  - Prevents creation of empty exercises that display "No questions available" to students

### Enhanced
- **Course Listing Shortcode**: Enhanced [ielts_courses] shortcode with new parameters
  - Added `columns` parameter to control grid layout (1-6 columns, default 5)
  - Existing `category` parameter now filters by category slug
  - Existing `limit` parameter controls number of courses shown
  - Example: `[ielts_courses category="beginner" columns="3" limit="9"]`
  - Responsive design automatically adjusts columns on smaller screens
- **Admin Course List**: Added Category column to courses admin list
  - Displays course categories with clickable links to filter by category
  - Shows "—" when no category is assigned

## [1.11] - 2024-12-17

### Fixed
- **Sublesson Auto-Completion**: Fixed issue where sublessons were not showing as complete when viewed
  - Modified `auto_mark_lesson_on_view()` to only record lesson access, not mark as complete
  - Lessons are now only marked complete when ALL resources are viewed AND ALL quizzes are attempted
  - Resources (sublessons) continue to be marked complete automatically when viewed by enrolled users
  
### Changed
- **Courses Grid Layout**: Improved course listing display for better consistency
  - Desktop (>1200px): 5 courses per row
  - Tablet (900-1200px): 3 courses per row
  - Small tablet (768-900px): 2 courses per row
  - Mobile (<768px): 1 course per row
  - Removed auto-fill behavior for more predictable layout

## [1.7.0] - 2024-12-17

### Added
- **Manual Enrollment System**: New admin page for managing user enrollments
  - Create new users and enroll them in courses directly from admin panel
  - Enroll existing users in multiple courses at once
  - Default course duration of 1 year (365 days)
  - Support for enrolling users in all available courses
- **Course End Date Management**: Track and modify course access expiration
  - Added `course_end_date` field to enrollment database table
  - Admins can modify end dates for individual enrollments
  - Automatic calculation of 1-year access from enrollment date
- **My Account Page**: New user-facing account dashboard
  - New shortcode `[ielts_my_account]` displays user enrollment details
  - Shows course enrollment dates and access expiration dates
  - Displays course completion progress for each enrolled course
  - Visual indication of expired courses
  - Direct links to continue learning in active courses

### Changed
- **Lesson Completion Logic**: Improved accuracy of lesson completion tracking
  - Lessons now marked complete ONLY when ALL sublesson pages are viewed AND ALL exercises are attempted
  - More accurate progress tracking and course completion percentages
  - Prevents premature lesson completion status
- **Page Layout Improvements**: Enhanced consistency across all page types
  - Reduced top padding from 60px to 30px on all course/lesson/sublesson pages
  - Fixed sublesson page width to match course and lesson pages (100% width)
  - Improved visual consistency throughout the plugin
- **Course Display**: Removed featured image from individual course pages
  - Featured images now only display on course listing pages
  - Cleaner, more focused course detail pages

### Fixed
- Enrollment table now properly tracks course end dates
- Consistent width and padding across all IELTS custom post type pages
- Lesson completion status now accurately reflects actual progress

## [1.6.0] - 2024-12-17

### Added
- **Sub Lesson (Resource) Page Template**: New dedicated template for sub lesson pages with breadcrumb navigation
  - Breadcrumb navigation showing Course > Lesson > Sub Lesson hierarchy
  - Consistent padding and width matching course and lesson pages
  - "Mark as Complete" functionality for enrolled students
  - Support for external resource links
- **Quiz Question Conversion**: Automatic conversion of LearnDash quiz questions during import/conversion
  - Converts multiple choice questions
  - Converts true/false questions
  - Converts fill-in-the-blank questions
  - Converts essay questions
  - Maintains question points and correct answers

### Changed
- **Terminology Update**: Renamed "Lesson pages" to "Sub lessons" throughout the plugin for clarity
  - Updated post type labels in admin interface
  - Updated template display labels
  - Improved consistency across the UI
- **LearnDash Import Improvements**: Enhanced order preservation and relationship handling
  - Fixed menu_order preservation in XML importer
  - Fixed menu_order preservation in direct database converter
  - Quizzes now properly linked to lessons (not just courses)
  - Sub lessons now properly ordered within lessons

### Fixed
- Sub lesson pages now display with proper breadcrumb navigation
- Sub lesson pages now have consistent styling with course and lesson pages
- LearnDash import now preserves the original order of lessons, sub lessons, and quizzes
- Quizzes are now correctly added to the lessons table during LearnDash import
- Quiz questions are now automatically converted from LearnDash format

### Security
- Added proper escaping for JavaScript output in templates
- Improved SQL query preparation with parameterized queries
- Added input validation with intval() for all ID parameters

## [1.3.0] - 2024-12-16

### Added
- **Direct LearnDash Converter**: New one-click conversion tool for sites with both LearnDash and IELTS Course Manager installed
  - Convert LearnDash courses directly from the database without XML export/import
  - Real-time progress monitoring with modal window
  - Live conversion log showing each step
  - Automatically detects and skips already-converted courses
  - Converts courses, lessons, topics (to lesson pages), and quizzes
  - Preserves course structure and relationships
  - Safe to re-run - will not create duplicates
- Modal UI for conversion progress tracking
- Comprehensive error reporting during conversion
- New JavaScript asset for converter functionality
- New CSS styling for converter interface

### Changed
- Updated plugin version to 1.3.0
- Replaced XML import functionality with direct database conversion
- Improved user experience for LearnDash migration

### Removed
- XML import page and functionality (replaced by direct converter)
- XML-based LearnDash import process (no longer needed when both plugins are on same site)

### Documentation
- Added comprehensive [LEARNDASH_CONVERSION_GUIDE.md](LEARNDASH_CONVERSION_GUIDE.md) with detailed conversion instructions
- Updated [README.md](README.md) to highlight new direct conversion feature
- Includes troubleshooting, best practices, and FAQ for conversion process

## [1.0.0] - 2024-12-16

### Added
- Initial release of IELTS Course Manager plugin
- Custom post types for Courses, Lessons, Resources, and Quizzes
- Course management system supporting 25+ courses
- Lesson structure with hierarchical organization
- Learning resources system with multiple resource types:
  - Documents
  - Videos
  - Audio files
  - External links
- Comprehensive quiz system with four question types:
  - Multiple Choice
  - True/False
  - Fill in the Blank
  - Essay (manual grading)
- Progress tracking system:
  - Per-course progress tracking
  - Lesson completion status
  - Last accessed timestamps
  - Completion percentage calculation
- Quiz results tracking:
  - Store all quiz attempts
  - Display best scores
  - Show score history
  - Automatic grading for objective questions
- Student enrollment system:
  - Enroll/unenroll functionality
  - Track enrolled students per course
  - Active/inactive status
- Admin interface:
  - Course settings meta box
  - Lesson assignment interface
  - Resource management
  - Quiz builder with dynamic question addition
  - Progress reports dashboard
  - Custom admin columns
- Frontend interface:
  - Course listing
  - Single course view with lessons
  - Lesson view with resources and quizzes
  - Quiz taking interface
  - Progress dashboard
- Shortcode system:
  - [ielts_courses] - Display all courses
  - [ielts_course id="X"] - Display single course
  - [ielts_lesson id="X"] - Display lesson
  - [ielts_quiz id="X"] - Display quiz
  - [ielts_progress] - Display progress page
- AJAX functionality:
  - Course enrollment
  - Lesson completion marking
  - Quiz submission
  - Progress updates
- Responsive CSS styling for all components
- JavaScript for interactive features
- Database tables for storing:
  - Progress data
  - Quiz results
  - Enrollment information
- Plugin activation/deactivation hooks
- Uninstall script for clean removal
- Course categories taxonomy
- Documentation:
  - README.md
  - PLUGIN_README.md
  - USAGE_GUIDE.md

### Features Overview
- Flexible alternative to LearnDash
- Support for unlimited courses, lessons, and quizzes
- Progress tracking per course
- Comprehensive student dashboard
- Multiple quiz types similar to LearnDash
- Easy content management through WordPress admin
- Shortcode support for flexible display
- Clean, modern UI design
- Mobile-responsive interface

### Technical Details
- WordPress 5.0+ compatible
- PHP 7.2+ required
- MySQL 5.6+ required
- Custom database tables for efficient data storage
- Object-oriented PHP code structure
- Follows WordPress coding standards
- Uses WordPress security best practices (nonces, sanitization, escaping)
- AJAX-powered interactive features
- Modular plugin architecture

## Future Enhancements (Planned)

### Version 1.1.0
- Certificate generation upon course completion
- Email notifications for quiz results
- Advanced progress reports with charts
- Export progress data to CSV
- Bulk enrollment functionality
- Course prerequisites

### Version 1.2.0
- Discussion forums per course
- Live video integration
- Assignment submission system
- Drip content scheduling
- Student groups and cohorts
- Instructor role and permissions

### Version 1.3.0
- Gamification features (badges, points)
- Course reviews and ratings
- Advanced quiz analytics
- AI-powered content recommendations
- Multi-language support
- Mobile app integration

---

## Legend

- **Added** - New features
- **Changed** - Changes to existing functionality
- **Deprecated** - Features marked for removal
- **Removed** - Features removed
- **Fixed** - Bug fixes
- **Security** - Security improvements
