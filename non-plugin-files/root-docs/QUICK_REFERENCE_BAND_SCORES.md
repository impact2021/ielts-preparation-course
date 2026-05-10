# Quick Reference: Band Scores Enhancement

## What Changed?

The `[ielts_band_scores]` shortcode now shows **6 skills** instead of 4:
- Reading, Listening, Writing, Speaking (official IELTS skills)
- Grammar*, Vocabulary* (additional tracking skills)

## New Table Columns

### Before
| Reading | Listening | Writing | Speaking | Overall |

### After
| Reading | Listening | Writing | Speaking | Grammar* | Vocabulary* | Skills Total | Overall Total |

## What Do the Columns Mean?

### Skills Total
- Average of the **4 official IELTS skills** (Reading, Listening, Writing, Speaking)
- This is what the official IELTS test would average
- Blue background to distinguish from overall

### Overall Total  
- Average of **all 6 skills** (including Grammar and Vocabulary)
- Gives you a complete picture of your English level
- Orange background (matches previous "Overall" column)

## Important Note

**Grammar and Vocabulary are marked with an asterisk (*)** because they are not separate scores in the official IELTS test. However, they are important components of your overall English proficiency and are tracked to help you improve.

## Shortcode Usage

### Default (Shows all 6 skills)
```
[ielts_band_scores]
```

### Custom (Choose which skills to show)
```
[ielts_band_scores skills="reading,listening,writing,speaking"]
```
*This will show only the 4 official skills (backward compatible)*

### With Custom Title
```
[ielts_band_scores title="My Current Progress"]
```

## Partner Dashboard Change

**What Changed**: The "Overall band score" shown in the partner dashboard now includes all 6 skills (was previously 4 skills).

**Where to See It**: Partner Dashboard → Managed Students table → "Expiry" column

**Example**:
```
Expiry: 15/02/2026
Overall band score: 7.5
```

This score now reflects:
- Reading
- Listening  
- Writing
- Speaking
- Grammar
- Vocabulary

## Calculation Example

**If a student has:**
- Reading: 7.5
- Listening: 8.0
- Writing: 7.0
- Speaking: 7.5
- Grammar: 8.0
- Vocabulary: 7.5

**Skills Total** = (7.5 + 8.0 + 7.0 + 7.5) ÷ 4 = **7.5**  
**Overall Total** = (7.5 + 8.0 + 7.0 + 7.5 + 8.0 + 7.5) ÷ 6 = **7.5** (rounded to nearest 0.5)

## Mobile View

The table is responsive and will scroll horizontally on smaller screens while maintaining readability.

## No Data State

If a student hasn't completed any tests yet, all columns will show:
```
—
No tests yet
```

## Color Guide

- **Orange headers** = Official IELTS skills
- **Light orange headers** = Additional skills (Grammar, Vocabulary)
- **Dark blue headers** = Total columns
- **Blue background cell** = Skills Total
- **Orange background cell** = Overall Total

## Backward Compatibility

✅ **Fully backward compatible**

If you have existing pages using the shortcode, they will automatically show the new columns. If you want to keep the old behavior, just specify which skills to show:

```
[ielts_band_scores skills="reading,listening,writing,speaking"]
```

## FAQ

**Q: Will this affect existing users' scores?**  
A: No, scores are recalculated in real-time based on their test data. Nothing is permanently changed.

**Q: What if grammar and vocabulary data isn't available?**  
A: Those columns will show "—" and "No tests yet" just like any other skill without data.

**Q: Can I hide the Grammar and Vocabulary columns?**  
A: Yes, use: `[ielts_band_scores skills="reading,listening,writing,speaking"]`

**Q: Will the Skills Total ever differ from Overall Total?**  
A: Yes, if the student has different scores for Grammar and Vocabulary compared to the official skills.

**Q: Is this change automatic?**  
A: Yes, all pages with the shortcode will automatically show the enhanced table.

## Technical Details

- **File modified**: `includes/class-shortcodes.php`
- **Partner dashboard file**: `includes/class-access-codes.php`
- **Default skills**: Changed from 4 to 6
- **Calculation**: Same band score conversion (percentage to 0.5-9.0 scale)
- **Rounding**: Same as before (nearest 0.5)

## Support

If you need to customize the display or have questions about the implementation, refer to:
- `IMPLEMENTATION_SUMMARY_NAVIGATION_AND_BAND_SCORES.md` - Technical details
- `VISUAL_GUIDE_BAND_SCORES_ENHANCEMENT.md` - Visual examples
