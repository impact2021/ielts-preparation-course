# Visual Guide: Enhanced Band Scores Table

## Before (Original Table)

```
┌─────────────────────────────────────────────────────────────────┐
│         Your Estimated IELTS Band Scores                        │
├──────────┬──────────┬──────────┬──────────┬──────────┐
│ Reading  │Listening │ Writing  │ Speaking │ Overall  │  ← Orange header
├──────────┼──────────┼──────────┼──────────┼──────────┤
│   7.5    │   8.0    │   7.0    │   7.5    │   7.5    │
│   Band   │   Band   │   Band   │   Band   │   Band   │
└──────────┴──────────┴──────────┴──────────┴──────────┘

Note: Band scores are estimates based on your test performance.
```

## After (Enhanced Table - Default)

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                    Your Estimated IELTS Band Scores                                     │
├──────────┬──────────┬──────────┬──────────┬──────────*┬──────────*┬──────────┬──────────┐
│ Reading  │Listening │ Writing  │ Speaking │ Grammar* │Vocabulary*│Skills    │ Overall  │
│          │          │          │          │          │           │ Total    │  Total   │
│  Orange  │  Orange  │  Orange  │  Orange  │  Lighter │  Lighter  │  Dark    │  Dark    │
│  Header  │  Header  │  Header  │  Header  │  Orange  │  Orange   │  Blue    │  Blue    │
├──────────┼──────────┼──────────┼──────────┼──────────┼───────────┼──────────┼──────────┤
│   7.5    │   8.0    │   7.0    │   7.5    │   8.0    │   7.5     │   7.5    │   7.6    │
│   Band   │   Band   │   Band   │   Band   │   Band   │   Band    │   Band   │   Band   │
│          │          │          │          │  Lighter │  Lighter  │   Blue   │  Orange  │
│          │          │          │          │    BG    │    BG     │    BG    │    BG    │
└──────────┴──────────┴──────────┴──────────┴──────────┴───────────┴──────────┴──────────┘

╔════════════════════════════════════════════════════════════════════════════════════╗
║ * Note: Grammar and Vocabulary are not rated as separate categories in the        ║
║   official IELTS test. These scores are provided to help you track your progress  ║
║   in these important language areas.                                              ║
╚════════════════════════════════════════════════════════════════════════════════════╝

Note: Band scores are estimates based on your test performance.
```

## Table Breakdown

### Column Types

1. **Official IELTS Skills** (4 columns)
   - Reading, Listening, Writing, Speaking
   - Standard orange header (#E56C0A)
   - Standard white/gray background

2. **Additional Skills** (2 columns)
   - Grammar*, Vocabulary*
   - Lighter orange header (30% lighter than main color)
   - Light gray background (#fafafa)
   - Asterisk (*) indicator

3. **Skills Total** (1 column)
   - Average of 4 official IELTS skills only
   - Dark blue header (#2c3e50)
   - Light blue background (#e3f2fd)
   - Blue text (#1976d2)
   - Only shown when additional skills are present

4. **Overall Total** (1 column)
   - Average of all 6 skills (4 official + 2 additional)
   - Dark blue header (#2c3e50)
   - Orange background (#fff3e0)
   - Orange text (#E46B0A)
   - Always shown (label changes based on context)

### Example Calculations

**Student has these scores:**
- Reading: 7.5
- Listening: 8.0
- Writing: 7.0
- Speaking: 7.5
- Grammar: 8.0
- Vocabulary: 7.5

**Skills Total** = (7.5 + 8.0 + 7.0 + 7.5) ÷ 4 = 30 ÷ 4 = 7.5
**Overall Total** = (7.5 + 8.0 + 7.0 + 7.5 + 8.0 + 7.5) ÷ 6 = 45.5 ÷ 6 = 7.58 → **7.5** (rounded to nearest 0.5)

### No Data State

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                    Your Estimated IELTS Band Scores                                     │
├──────────┬──────────┬──────────┬──────────┬──────────*┬──────────*┬──────────┬──────────┐
│ Reading  │Listening │ Writing  │ Speaking │ Grammar* │Vocabulary*│ Skills   │ Overall  │
│          │          │          │          │          │           │  Total   │  Total   │
├──────────┼──────────┼──────────┼──────────┼──────────┼───────────┼──────────┼──────────┤
│    —     │    —     │    —     │    —     │    —     │    —      │    —     │    —     │
│ No tests │ No tests │ No tests │ No tests │ No tests │ No tests  │ No tests │ No tests │
│   yet    │   yet    │   yet    │   yet    │   yet    │   yet     │   yet    │   yet    │
└──────────┴──────────┴──────────┴──────────┴──────────┴───────────┴──────────┴──────────┘

╔════════════════════════════════════════════════════════════════════════════════════╗
║ * Note: Grammar and Vocabulary are not rated as separate categories in the        ║
║   official IELTS test. These scores are provided to help you track your progress  ║
║   in these important language areas.                                              ║
╚════════════════════════════════════════════════════════════════════════════════════╝

Note: Band scores are estimates based on your test performance.
```

## Backward Compatibility Example

Using shortcode: `[ielts_band_scores skills="reading,listening"]`

```
┌────────────────────────────────────────────┐
│  Your Estimated IELTS Band Scores          │
├──────────┬──────────┬──────────┐
│ Reading  │Listening │ Overall  │  ← Only "Overall" (not "Overall Total")
├──────────┼──────────┼──────────┤
│   7.5    │   8.0    │   7.8    │
│   Band   │   Band   │   Band   │
└──────────┴──────────┴──────────┘

Note: Band scores are estimates based on your test performance.
```

No disclaimer shown (no additional skills present)

## Partner Dashboard Display

### Before
```
┌────────────────────────────────────────────────────────────┐
│ Username: john_smith                                       │
│ Full Name: John Smith                                      │
│ Email: john@example.com                                    │
├────────────────────────────────────────────────────────────┤
│ Membership: Academic IELTS Plus                            │
├────────────────────────────────────────────────────────────┤
│ Expiry: 15/02/2026                                         │
│ Overall band score: 7.5  ← Based on 4 skills              │
└────────────────────────────────────────────────────────────┘
```

### After
```
┌────────────────────────────────────────────────────────────┐
│ Username: john_smith                                       │
│ Full Name: John Smith                                      │
│ Email: john@example.com                                    │
├────────────────────────────────────────────────────────────┤
│ Membership: Academic IELTS Plus                            │
├────────────────────────────────────────────────────────────┤
│ Expiry: 15/02/2026                                         │
│ Overall band score: 7.6  ← Based on all 6 skills          │
└────────────────────────────────────────────────────────────┘
```

## Mobile View (< 768px)

```
┌────────────────────────────────────────┐
│ Your Estimated IELTS Band Scores       │
├────────────────────────────────────────┤
│     (Table scrolls horizontally)       │
│ ◄──────────────────────────────────► │
│                                        │
│ Reading │ Listening │ Writing │ ...   │
│   7.5   │    8.0    │   7.0   │ ...   │
│  Band   │   Band    │  Band   │ ...   │
└────────────────────────────────────────┘

* Note: Grammar and Vocabulary are not...
(Wraps to multiple lines on mobile)
```

## Color Reference

### Headers
- **Official Skills**: #E56C0A (Orange) - Uses site primary color
- **Additional Skills**: rgba(calculated, 0.8) - 30% lighter than orange
- **Total Columns**: #2c3e50 (Dark Blue)

### Cell Backgrounds
- **Standard Cells**: #f9f9f9 (Very Light Gray)
- **Additional Skills Cells**: #fafafa (Slightly Lighter Gray)
- **Skills Total Cell**: #e3f2fd (Light Blue)
- **Overall Total Cell**: #fff3e0 (Light Orange)

### Text Colors
- **Standard Text**: #2c3e50 (Dark Blue-Gray)
- **No Data Text**: #999 (Gray)
- **Skills Total Value**: #1976d2 (Blue)
- **Overall Total Value**: #E46B0A (Orange)

### Special Elements
- **Disclaimer Box**: #f0f7ff background, #1976d2 left border
- **Note Text**: #666 (Medium Gray), italic
