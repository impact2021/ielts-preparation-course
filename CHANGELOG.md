# Changelog

## [3.2] - 2025-12-20

### Changed
- **Standard Layout Feedback Enhancement**: Standard layout now shows the same feedback style as Computer-based IELTS layout
  - Shows results in a popup modal with score
  - Includes "Review my answers" button to dismiss modal and view highlighted answers
  - Displays green/red highlighting on the quiz form for correct/incorrect answers
  - Provides consistent user experience across both layout types
  
### Technical Details
- **Files Modified**: `assets/js/frontend.js`
- Updated quiz submission handler to use CBT-style modal for both layout types
- Preserved existing question-by-question feedback display in the form through visual highlighting
- **Backward Compatible**: Yes, all existing functionality maintained

---

## [3.1] - 2025-12-20

### Fixed
- **Critical Bug Fix**: Headings questions now save and display correctly
  - Fixed issue where headings, matching_classifying, and matching questions were not saving their mc_options array
  - Radio button options now display properly for all affected question types
  - User answers now save and score correctly
  - **Root Cause**: Admin save logic only handled multiple_choice and multi_select types
  - **Solution**: Updated condition to include all mc_options-based question types (headings, matching_classifying, matching)
  
### Changed
- Updated plugin version to 3.1
- Enhanced save_meta_boxes() method to handle all mc_options question types consistently

### Technical Details
- **Files Modified**: `includes/admin/class-admin.php` (1 line changed at line 2196)
- **Backward Compatible**: Yes, existing questions continue to work
- **Security**: No security issues (passed CodeQL analysis and code review)

### Documentation
- Added comprehensive HEADINGS-FIX-SUMMARY.md with detailed explanation
- Full flow verification completed (parser → save → display → submit → score)

---

## [3.0] - Previous Release
- Full feature set for IELTS course management
- Support for multiple question types
- Computer-based test layout
- Reading passages and quiz functionality
