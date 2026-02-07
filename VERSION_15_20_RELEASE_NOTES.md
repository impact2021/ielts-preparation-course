# VERSION 15.20 RELEASE NOTES

## Summary
This release enhances the search functionality to include quiz content and updates the partnership dashboard to show overall band scores.

## Changes

### 1. Enhanced Search Functionality
**What Changed:**
- WordPress search now searches inside:
  - Transcripts (`_ielts_cm_transcript`)
  - Reading passages (`_ielts_cm_reading_passage`) 
  - Quiz questions (`_ielts_cm_questions`)
- Search continues to be limited to specific custom post types: `ielts_resource`, `ielts_lesson`, `ielts_course`, `ielts_quiz`

**Files Modified:**
- New file: `includes/class-search.php` - Contains search functionality
- Modified: `ielts-course-manager.php` - Added search class initialization

**How It Works:**
The search functionality uses WordPress filters to extend the default search:
- `pre_get_posts` - Limits search to specific post types
- `posts_join` - Joins the postmeta table for meta field access
- `posts_where` - Adds conditions to search meta fields
- `posts_groupby` - Groups results by post ID to prevent duplicates

**Performance Note:**
For large sites with many posts, LIKE queries on meta_value can be slow. Consider adding a database index on (meta_key, meta_value) columns in the postmeta table for optimal performance.

### 2. Partnership Dashboard Updates
**What Changed:**
- Removed "Last login" display from student list
- Added "Overall band score" in the same location under each student's expiry date

**Files Modified:**
- `includes/class-access-codes.php`:
  - Updated `render_students_table()` method to show band score instead of last login
  - Added `calculate_overall_band_score()` method to compute scores
  - Added `convert_percentage_to_band()` method for score conversion
  - Optimized to reuse gamification instance for all students in the loop

**How It Works:**
- Band score is calculated from user's skill scores (reading, listening, writing, speaking)
- Uses the existing gamification system to get skill scores
- Averages the band scores and rounds to nearest 0.5
- Shows 'N/A' if no scores are available

**Important Note:**
The band score calculation is an approximation for display purposes. Official IELTS band score calculation may use different rounding rules.

## Testing Guide

### Testing Search Functionality

1. **Create Test Content:**
   - Create a quiz with questions containing specific text
   - Add a transcript to a quiz with unique text
   - Add a reading passage with unique text

2. **Test Search:**
   - Go to site search
   - Search for text from quiz questions - should find the quiz
   - Search for text from transcript - should find the quiz
   - Search for text from reading passage - should find the quiz
   - Verify only IELTS custom post types appear in results

3. **Verify:**
   - Results should include posts with matching meta content
   - No duplicates should appear
   - Search should work with both exact and partial matches

### Testing Partnership Dashboard

1. **Access Dashboard:**
   - Log in as administrator or partner admin
   - Navigate to Partner Dashboard (using `[iw_partner_dashboard]` shortcode)

2. **Verify Display:**
   - Check "Managed Students" section
   - Under each student's expiry date, verify "Overall band score" is shown
   - Verify "Last login" is no longer displayed

3. **Test Band Score Calculation:**
   - For users with quiz scores, verify band score shows a number (e.g., "6.5")
   - For users without quiz scores, verify it shows "N/A"
   - Verify band scores are reasonable (between 0.5 and 9.0)

4. **Test Performance:**
   - With multiple students, verify page loads reasonably fast
   - Check browser console for any JavaScript errors

## Migration Notes

**No database changes required.**

The search functionality is automatically active when the plugin is updated. No configuration needed.

## Version Information

- **Previous Version:** 15.19
- **Current Version:** 15.20
- **Release Date:** February 7, 2026

## Files Changed

1. `ielts-course-manager.php` - Version update and search class inclusion
2. `includes/class-search.php` - New file with search functionality
3. `includes/class-access-codes.php` - Partnership dashboard updates

## Backward Compatibility

This update is fully backward compatible. No breaking changes.

## Support

For issues or questions, please contact support or create an issue in the repository.
