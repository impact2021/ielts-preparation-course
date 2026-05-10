# Version 16.9 Release Notes

**Release Date**: February 2026
**Type**: Bug Fix Release

## Overview
This release fixes the bottom navigation links on sublesson (resource) pages so that General Training users are no longer shown "Access Restricted" when navigating to the next or previous sublesson via the NEXT/PREV buttons.

## Issue

### "NEXT/PREV" links at the bottom of a sublesson ignored the lesson context

Version 16.8 (PR #777) fixed the table of contents in `single-lesson.php`: resource links were updated to include `?lesson_id=` so that the resource page could resolve access against the correct lesson and course (rather than defaulting to the primary/Academic lesson stored in `_ielts_cm_lesson_id`).

However, the **bottom navigation links** inside the sublesson page itself (`templates/single-resource-page.php`) and inside exercise pages (`templates/single-quiz.php`) still used bare `get_permalink()` calls — without the `?lesson_id=` parameter. This meant:

1. A General Training user opens Sublesson A via the lesson TOC. The URL carries `?lesson_id=GT_Lesson_ID`, so access is checked correctly. ✓
2. The user clicks **NEXT** to go to Sublesson B. The generated link has **no** `?lesson_id=`.
3. Sublesson B resolves access against its primary lesson (`_ielts_cm_lesson_id` = Academic lesson). The user is not enrolled in the Academic course → **Access Restricted**. ✗

The same problem occurred in reverse: navigating **PREV** from any sublesson, and navigating from an exercise (quiz) page to a sublesson.

## Fix

### `templates/single-resource-page.php`

When building the previous and next navigation URLs for resource items, pass `?lesson_id=` so the destination resource page inherits the correct lesson context:

```php
// Before
$prev_url = get_permalink($prev_item['post']->ID);
$next_url = get_permalink($next_item['post']->ID);

// After
$prev_url = $lesson_id
    ? add_query_arg('lesson_id', $lesson_id, get_permalink($prev_item['post']->ID))
    : get_permalink($prev_item['post']->ID);
$next_url = $lesson_id
    ? add_query_arg('lesson_id', $lesson_id, get_permalink($next_item['post']->ID))
    : get_permalink($next_item['post']->ID);
```

Quiz URLs (exercises) are unchanged — quiz pages do not perform the same `?lesson_id=` validation.

### `templates/single-quiz.php`

When a quiz's previous or next navigation item is a resource (`post_type === 'ielts_resource'`), pass `?lesson_id=` so the resource page inherits the correct lesson context. Quiz-to-quiz links remain unchanged.

Pre-compute `$prev_url` / `$next_url` alongside the existing `$prev_label` / `$next_label` variables, then use them in the navigation HTML.

```php
// Before — always a bare permalink
<a href="<?php echo get_permalink($prev_item->ID); ?>" ...>
<a href="<?php echo get_permalink($next_item->ID); ?>" ...>

// After — resource links carry ?lesson_id=
$prev_url = ($prev_item->post_type === 'ielts_resource' && $lesson_id)
    ? add_query_arg('lesson_id', $lesson_id, get_permalink($prev_item->ID))
    : get_permalink($prev_item->ID);
$next_url = ($next_item->post_type === 'ielts_resource' && $lesson_id)
    ? add_query_arg('lesson_id', $lesson_id, get_permalink($next_item->ID))
    : get_permalink($next_item->ID);

<a href="<?php echo esc_url($prev_url); ?>" ...>
<a href="<?php echo esc_url($next_url); ?>" ...>
```

## Files Modified

| File | Change |
|---|---|
| `templates/single-resource-page.php` | PREV/NEXT resource links now include `?lesson_id=` |
| `templates/single-quiz.php` | PREV/NEXT links to resources now include `?lesson_id=`; URLs pre-computed and properly escaped with `esc_url()` |
| `ielts-course-manager.php` | Version bumped `16.8 → 16.9` |

## Security

- No new security vulnerabilities introduced.
- The `lesson_id` value passed in query args originates from the already-validated `$lesson_id` PHP variable on the current page — it is not taken from raw user input.
- Destination pages validate the `?lesson_id=` parameter with `filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT)` and cross-check it against the resource's stored lesson IDs before trusting it (existing validation from v16.8).
- Output is escaped with `esc_url()` (the quiz nav links were previously missing this — now fixed).

## Testing

1. **Resource → Resource navigation (same lesson)**
   - Log in as a General Training user.
   - Navigate to a sublesson that is shared between Academic and General Training lessons via the lesson TOC (URL should include `?lesson_id=GT_lesson_ID`).
   - Click NEXT (or PREV). Confirm the next/previous resource loads without "Access Restricted".
   - Confirm the URL still contains `?lesson_id=GT_lesson_ID`.

2. **Exercise → Resource navigation**
   - Navigate to an exercise within a General Training lesson.
   - Click PREV where the previous item is a resource. Confirm access is granted.

3. **Academic users unaffected**
   - Repeat the above as an Academic user. Confirm no regressions.

4. **Admins unaffected**
   - Repeat the above as an admin. Confirm no regressions.
