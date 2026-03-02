# Version 16.10 Release Notes

**Release Date**: March 2026
**Type**: Bug Fix Release

## Overview

This release fixes two issues introduced after v16.9:

1. The NEXT/PREV navigation buttons on **exercise (quiz) pages** were still navigating to the wrong
   (Academic) lesson context, even though v16.9 had fixed the same problem for resource pages.
2. Users whose course was switched from Academic to General Training could still access
   Academic content (exercises and resources), rather than seeing the "Access Restricted" page.

---

## Issue 1 — NEXT/PREV on exercises navigated to Academic Module pages

### Root cause

v16.9 propagated the `?lesson_id=` query parameter from resource pages to their neighbours, but
quiz pages (exercises) were not updated in the same way:

- **`templates/single-quiz-page.php`** (the WordPress template loaded when a user visits a quiz
  URL directly) read `$lesson_id` and `$course_id` purely from post meta
  (`_ielts_cm_lesson_id` / `_ielts_cm_course_id`).  The stored meta typically points to the
  primary (Academic) lesson, so even though the URL carried `?lesson_id=GT_lesson_id`, the page
  ignored it.

- **`templates/single-quiz.php`** added `?lesson_id=` to navigation URLs that lead to
  `ielts_resource` posts, but **not** to navigation URLs that lead to other `ielts_quiz` posts.
  A resource → quiz transition therefore dropped the lesson context, and subsequent quiz → quiz
  transitions never had it.

- **`templates/single-quiz-computer-based.php`** used bare `get_permalink()` calls for both
  PREV and NEXT links — no `?lesson_id=` was added for any destination type.

- **`includes/class-shortcodes.php`** (`display_quiz()`) set `$lesson_id` and `$course_id` from
  post meta only, ignoring any `?lesson_id=` URL parameter.

### Fix

| File | Change |
|---|---|
| `templates/single-quiz-page.php` | Read and validate `?lesson_id=` URL param; if valid, override `$lesson_id` and derive `$course_id` from the lesson (same pattern as resource pages). |
| `templates/single-quiz.php` | Pass `?lesson_id=` to **all** PREV/NEXT navigation links, not just resource links. |
| `templates/single-quiz-computer-based.php` | Pass `?lesson_id=` to both PREV and NEXT navigation links. |
| `includes/class-shortcodes.php` | Read and validate `?lesson_id=` URL param in `display_quiz()` and derive `$course_id` from it for the shortcode rendering path. |

The `?lesson_id=` parameter is validated against `_ielts_cm_lesson_ids` (plural) and
`_ielts_cm_lesson_id` (singular) meta before being trusted, identical to the v16.8/16.9
validation in resource pages.

---

## Issue 2 — Switched users could still access Academic content

### Root cause

`IELTS_CM_Enrollment::is_enrolled()` checked `user_has_course_access()` (which correctly
returns `false` for an access-code user whose `iw_course_group` no longer includes the target
course), but only used that result to **allow** access (`return true`).  When
`user_has_course_access()` returned `false`, execution fell through to a secondary check:

```
1. enrollment table status === 'active'?   ← yes (not yet deactivated)
2. user has a valid membership role?       ← yes (access-code role still present)
→ return true  ← WRONG
```

Because access-code users retain their role and their enrollment record may still be `active`
after an admin switches their course group, the fallback logic granted access to courses the user
should no longer be allowed to reach.

### Fix

For access-code users, `user_has_course_access()` is the authoritative gating function (it checks
`iw_course_group` against the course's category taxonomy).  A new early-exit in `is_enrolled()`
now returns `false` immediately for access-code users when `user_has_course_access()` has already
said no, bypassing the enrollment-table + role check:

```php
// For access-code users, user_has_course_access() is the authoritative check.
// If it returned false above, deny without checking the enrollment table.
if (class_exists('IELTS_CM_Access_Codes')) {
    $user_for_role_check = get_userdata($user_id);
    if ($user_for_role_check) {
        $access_code_roles = array_keys(IELTS_CM_Access_Codes::ACCESS_CODE_MEMBERSHIP_TYPES);
        foreach ($user_for_role_check->roles as $role) {
            if (in_array($role, $access_code_roles)) {
                return false;
            }
        }
    }
}
```

Paid-membership users are unaffected — they don't carry access-code roles, so they continue
through the unchanged enrollment-table + role logic.

---

## Files Modified

| File | Change |
|---|---|
| `templates/single-quiz-page.php` | Read & validate `?lesson_id=` URL param; derive correct `$course_id` from it |
| `templates/single-quiz.php` | Pass `?lesson_id=` to all PREV/NEXT nav links |
| `templates/single-quiz-computer-based.php` | Pass `?lesson_id=` to PREV/NEXT nav links |
| `includes/class-shortcodes.php` | Read & validate `?lesson_id=` URL param in `display_quiz()` |
| `includes/class-enrollment.php` | Early-exit for access-code users when `user_has_course_access()` returns `false` |
| `ielts-course-manager.php` | Version bumped `16.9 → 16.10` |

## Security

- No new security vulnerabilities introduced.
- `lesson_id` values from GET params are validated with `FILTER_VALIDATE_INT` and cross-checked
  against the quiz's stored `_ielts_cm_lesson_ids` / `_ielts_cm_lesson_id` meta before being
  trusted (same approach as v16.8/16.9).
- The enrollment change only tightens access; it does not relax it.
