# Changes in Version 1.1

## Summary
This release addresses several feature requests and improvements to make the plugin more suitable for IELTS preparation courses.

## Plugin Metadata Updates
- **Version**: Updated from 1.0.0 to 1.1
- **Author**: Changed to "Impact Websites"
- **Plugin URI**: Updated to https://www.impactwebsites.co.nz/
- **Author URI**: Updated to https://www.impactwebsites.co.nz/

## Post Type Changes
### Resources → Lesson pages
The "Resources" post type has been renamed to "Lesson pages" to better reflect its purpose:
- All labels updated throughout the admin interface
- Menu name changed from "Resources" to "Lesson pages"
- Slug updated from 'ielts-resource' to 'ielts-lesson-page'
- **Note**: The internal post type name `ielts_resource` remains unchanged for backward compatibility

### Resource Type Dropdown Removed
- The resource type dropdown (Document/Video/Audio/Link) has been removed from the Lesson page meta box
- The Resource URL field remains available for optional external resource links
- This simplifies the interface while maintaining flexibility

## Quiz System Improvements

### True/False/Not Given Question Type
The True/False question type has been enhanced to support the standard IELTS format:
- **New label**: "True/False/Not Given"
- **Frontend**: Students now see three radio button options:
  - True
  - False
  - Not Given
- **Admin**: Updated help text to indicate correct answer format (true/false/not_given)

### Improved Documentation for Question Types
Added comprehensive documentation in multiple locations:

#### Admin Documentation Page
Enhanced the Documentation page with detailed information for each question type:
- **Multiple Choice**: How to enter options and specify correct answers
- **True/False/Not Given**: IELTS-specific format explanation
- **Fill in the Blank**: Detailed explanation of flexible matching (case-insensitive, ignores punctuation/extra spaces)
- **Essay**: Manual grading requirement

#### Inline Help in Quiz Meta Box
Added a helpful reference box directly in the quiz editing interface showing:
- Quick guidelines for each question type
- Correct answer format requirements
- Tips for Fill in the Blank questions

#### Documentation Files Updated
- **README.md**: Updated feature descriptions
- **USAGE_GUIDE.md**: Enhanced quiz creation instructions with detailed question type guidelines
- **PLUGIN_README.md**: Updated feature list and instructions

## Bug Fixes

### Quiz Save Logic
Fixed potential issues with quiz question persistence:
- Questions are now always saved, even if the array is empty
- Added validation to skip empty questions during save
- Improved robustness of the save process to prevent data loss

## Technical Changes

### Modified Files
1. `ielts-course-manager.php` - Plugin metadata and version constant
2. `includes/class-post-types.php` - Post type labels and slug
3. `includes/admin/class-admin.php` - Meta boxes, save logic, documentation, and column labels
4. `includes/class-quiz-handler.php` - Quiz type labels
5. `templates/single-quiz.php` - Frontend quiz display with 3 options for True/False/Not Given
6. `README.md` - Main readme updates
7. `USAGE_GUIDE.md` - Usage instructions updates
8. `PLUGIN_README.md` - Feature documentation updates

### Backward Compatibility
- Internal post type names remain unchanged (`ielts_resource`)
- Old meta keys are preserved for compatibility
- Existing resource type data is maintained but hidden from the UI
- All existing functionality continues to work

## Migration Notes
No migration is required for existing installations. All changes are backward compatible:
- Existing "Resources" will automatically display as "Lesson pages"
- Existing True/False questions will continue to work (users can still answer true or false)
- New quizzes can use the "Not Given" option
- Resource type data is preserved but no longer displayed in the admin UI

## For Users

### What's New
1. **Better terminology**: "Lesson pages" more accurately describes the content type
2. **IELTS-appropriate quiz format**: True/False/Not Given matches IELTS Reading test format
3. **Clearer documentation**: Better guidance on using Fill in the Blank and other question types
4. **Simplified interface**: Removed unnecessary resource type dropdown

### Action Required
None. All changes are automatic upon plugin update.

### Tips for Using New Features
1. When creating True/False/Not Given questions, enter correct answer as:
   - `true` for True
   - `false` for False
   - `not_given` for Not Given (all lowercase)

2. For Fill in the Blank questions:
   - The system is forgiving - it ignores case, extra spaces, and punctuation
   - Example: "Paris", "paris", "PARIS ", and "paris." are all treated as correct answers

3. Check the new inline help text in the quiz editor for quick reference

## Support
For issues or questions about these changes, please visit:
https://www.impactwebsites.co.nz/
