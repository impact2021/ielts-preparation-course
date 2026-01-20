# Unified Reading/Listening Template Implementation - Version 12.7

## Summary

Implemented a **single unified template** for both Reading and Listening tests, controlled by a simple checkbox. This eliminates duplicate code and ensures both test types work identically.

## The Problem

The "Show in transcript" buttons for Listening Test 1 (Questions 1-5) were visible but did nothing when clicked. Investigation revealed:

1. **Different templates**: Listening and Reading used separate templates with duplicate logic
2. **Inconsistent implementations**: Listening template had its own marker processing function
3. **Link format mismatch**: Links were using `href="#"` instead of native anchor links like reading tests

## The Solution

### ONE Template for All Tests

**Before:**
- `single-quiz-listening-practice.php` - Listening tests only
- `single-quiz-listening-exercise.php` - Listening exercises only  
- `single-quiz-computer-based.php` - Reading tests only

**After:**
- `single-quiz-computer-based.php` - **BOTH reading AND listening tests**

### Simple Checkbox Control

**Admin Interface:**
```
☐ This is for a listening exercise
```

**When UNCHECKED (Reading Test):**
- Shows "Reading Texts" admin section
- Displays reading passages during test
- Feedback says: "Show me the section of the reading passage"
- Uses `#passage-q{N}` markers

**When CHECKED (Listening Test):**
- Shows "Listening Audio & Transcripts" admin section  
- Displays audio player during test
- Hides transcripts until AFTER submission
- Feedback says: "Show in transcript"
- Uses `#transcript-q{N}` markers

## Technical Changes

### 1. Admin Interface (`includes/admin/class-admin.php`)

```php
// Simplified layout selection
<select name="ielts_cm_layout_type">
    <option value="two_column_reading">2 Column Test (Reading or Listening)</option>
</select>

// New checkbox
<input type="checkbox" name="ielts_cm_is_listening_exercise" value="1">
This is for a listening exercise
```

**JavaScript toggles sections:**
```javascript
$('#ielts_cm_is_listening_exercise').on('change', function() {
    if ($(this).is(':checked')) {
        $('#listening-audio-section').show();
        $('#reading-texts-section').hide();
    } else {
        $('#listening-audio-section').hide();
        $('#reading-texts-section').show();
    }
});
```

### 2. Template Logic (`templates/single-quiz-computer-based.php`)

```php
// Determine test type from checkbox
$is_listening_exercise = get_post_meta($quiz->ID, '_ielts_cm_is_listening_exercise', true);

// Backward compatibility
if ($layout_type === 'two_column_listening') {
    $is_listening_exercise = '1';
}

$test_type = ($is_listening_exercise === '1') ? 'listening' : 'reading';
```

### 3. Feedback Links (`includes/class-quiz-handler.php`)

**Links now use anchor hrefs** (like reading tests always did):

```php
// Listening test links
<a href="#transcript-q1" 
   class="show-in-transcript-link" 
   data-section="0" 
   data-question="1">
   Show in transcript
</a>

// Reading test links  
<a href="#passage-q1" 
   class="show-in-reading-passage-link" 
   data-reading-text="0" 
   data-question="1">
   Show me the section of the reading passage
</a>
```

**Browser handles navigation automatically** via anchor links, then JavaScript adds highlighting.

### 4. Backward Compatibility

All existing quizzes continue to work:
- Old `two_column_listening` → automatically treated as checked checkbox
- Old `two_column_reading` → automatically treated as unchecked checkbox
- Old templates still exist but are no longer used

## How It Works

### Reading Test Flow

1. Admin unchecks "This is for a listening exercise"
2. Admin adds reading passages with `[Q1]`, `[Q2]` markers
3. Student sees passage on left, questions on right
4. After submission, clicks "Show me the section of the reading passage"
5. Browser scrolls to `#passage-q1` anchor
6. JavaScript highlights answer text

### Listening Test Flow

1. Admin checks "This is for a listening exercise"  
2. Admin adds audio file and transcripts with `[Q1]`, `[Q2]` markers
3. Student sees audio player on left (transcripts HIDDEN), questions on right
4. After submission, transcripts appear
5. Student clicks "Show in transcript"
6. Browser scrolls to `#transcript-q1` anchor
7. JavaScript highlights answer text with visible "Q1" badge

## Files Modified

1. **ielts-course-manager.php** - Version bumped to 12.7
2. **includes/admin/class-admin.php**:
   - Removed duplicate layout options
   - Added checkbox for listening/reading toggle
   - Added JavaScript to show/hide sections
   - Added save logic for new meta field

3. **includes/class-quiz-handler.php**:
   - Added check for `_ielts_cm_is_listening_exercise` meta
   - Updated link href to use `#transcript-q{N}` (was `#`)
   - Maintained conditional link text

4. **templates/single-quiz-computer-based.php**:
   - Added logic to check listening checkbox
   - Maintained backward compatibility with old layout_type

## Benefits

✅ **Single source of truth** - One template handles both test types  
✅ **Consistent behavior** - Reading and listening work identically  
✅ **Native browser navigation** - Anchor links work automatically  
✅ **Simpler admin** - Just one checkbox to toggle  
✅ **Backward compatible** - Existing tests continue working  
✅ **Easier maintenance** - Fix bugs in one place, not three  

## Testing Checklist

- [ ] Create new reading test - verify passages show
- [ ] Create new listening test - verify audio/transcripts show
- [ ] Check listening test - verify transcripts hidden until submission
- [ ] Uncheck listening test - verify it switches to reading mode
- [ ] Test "Show in transcript" links click and scroll
- [ ] Test "Show me the section" links click and scroll
- [ ] Verify old listening tests still work
- [ ] Verify old reading tests still work
- [ ] Test checkbox toggle in admin (show/hide sections)

## Version History

**12.7** - Unified template implementation with checkbox control  
**12.6** - Previous version before unification
