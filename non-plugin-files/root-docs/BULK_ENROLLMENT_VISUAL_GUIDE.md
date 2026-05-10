# Bulk Enrollment Feature - Visual Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                    WordPress Admin Dashboard                        │
│                      /wp-admin/users.php                            │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Administrator navigates to Users page
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ Bulk Actions: [Enroll in IELTS Course (30 days)] [Apply] ▼    │ │
│  └────────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ ☑ All                                                          │ │
│  ├────────────────────────────────────────────────────────────────┤ │
│  │ ☑ John Doe       john@example.com      Subscriber             │ │
│  │ ☑ Jane Smith     jane@example.com      Subscriber             │ │
│  │ ☑ Bob Wilson     bob@example.com       Subscriber             │ │
│  │ ☐ Admin User     admin@example.com     Administrator          │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Admin selects users and clicks Apply
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│           IELTS_CM_Bulk_Enrollment::handle_bulk_action()            │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                    ┌────────────┴────────────┐
                    │                         │
                    ▼                         ▼
          Check for courses          Calculate expiry date
       ┌─────────────────┐           (30 days from today)
       │ ielts_course    │                   │
       │ post_type       │                   │
       │ (published)     │                   │
       └─────────────────┘                   │
                    │                         │
                    └────────────┬────────────┘
                                 │
                                 ▼
        ┌────────────────────────────────────────────────┐
        │  For each selected user:                       │
        │  1. Get user_id                                │
        │  2. Get course_id (first published course)     │
        │  3. Call enrollment->enroll()                  │
        │     - user_id: [ID]                           │
        │     - course_id: [Course ID]                  │
        │     - status: 'active'                        │
        │     - course_end_date: [Today + 30 days]      │
        └────────────────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                Database: wp_ielts_cm_enrollment                     │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ id │ user_id │ course_id │ status │ enrolled_date │ end_date  │ │
│  ├────┼─────────┼───────────┼────────┼───────────────┼───────────┤ │
│  │ 1  │   42    │    100    │ active │ 2026-02-06    │2026-03-08 │ │
│  │ 2  │   43    │    100    │ active │ 2026-02-06    │2026-03-08 │ │
│  │ 3  │   44    │    100    │ active │ 2026-02-06    │2026-03-08 │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                 │
                                 │ Redirect back to users page
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ╔═════════════════════════════════════════════════════════════╗   │
│  ║ ✓ 3 users enrolled in IELTS Preparation Course with        ║   │
│  ║   expiry date: March 8, 2026                      [Dismiss] ║   │
│  ╚═════════════════════════════════════════════════════════════╝   │
│                                                                     │
│  Users list continues here...                                      │
└─────────────────────────────────────────────────────────────────────┘

```

## Key Points

1. **Single Click Operation**: Select users, choose bulk action, click Apply
2. **Automatic Course Selection**: Uses first published IELTS course
3. **Fixed 30-Day Period**: All enrollments get same expiry (30 days from today)
4. **Immediate Feedback**: Success/error message shown immediately
5. **Database Integration**: Uses existing enrollment system
6. **Safe Updates**: If user already enrolled, updates their enrollment

## Error Handling

If no courses exist:
```
┌─────────────────────────────────────────────────────────────────────┐
│  ╔═════════════════════════════════════════════════════════════╗   │
│  ║ ✗ No IELTS courses found. Please create a course   [Dismiss]║   │
│  ║   first.                                                     ║   │
│  ╚═════════════════════════════════════════════════════════════╝   │
└─────────────────────────────────────────────────────────────────────┘
```
