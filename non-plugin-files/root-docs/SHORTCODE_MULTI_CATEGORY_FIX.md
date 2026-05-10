# [ielts_courses] Shortcode - Multi-Category Support

## Issue Fixed
The `category` parameter now supports **multiple comma-separated values** to filter courses by multiple categories simultaneously.

## Syntax

```
[ielts_courses category="category1,category2,category3" columns="5" orderby="title" order="ASC"]
```

## Examples

### Single Category
```
[ielts_courses category="academic"]
```

### Multiple Categories (NEW - Fixed!)
```
[ielts_courses category="academic,general"]
```

### Multiple Categories with Spaces (also works)
```
[ielts_courses category="academic, general, reading"]
```

### Complete Example
```
[ielts_courses category="academic,general" columns="5" orderby="title" order="ASC"]
```

## All Available Parameters

| Parameter | Default | Description | Example Values |
|-----------|---------|-------------|----------------|
| `category` | empty | Filter by category slug(s) | `"academic"`, `"general"`, `"academic,general"` |
| `columns` | `5` | Number of columns (1-6) | `"3"`, `"4"`, `"5"` |
| `limit` | `-1` | Number of courses to display (-1 = all) | `"10"`, `"20"`, `"-1"` |
| `orderby` | `date` | Sort field | `"date"`, `"title"`, `"menu_order"`, `"rand"` |
| `order` | `DESC` | Sort direction | `"ASC"`, `"DESC"` |

## Implementation Details

**File:** `includes/class-shortcodes.php` (lines 99-110)

The fix splits the category parameter by commas and trims whitespace:

```php
if (!empty($atts['category'])) {
    // Support comma-separated categories (e.g., "academic,general")
    $categories = array_map('trim', explode(',', $atts['category']));
    
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'ielts_course_category',
            'field' => 'slug',
            'terms' => $categories
        )
    );
}
```

## How It Works

1. Takes the category parameter value (e.g., `"academic,general"`)
2. Splits by comma: `explode(',', $atts['category'])` → `["academic", "general"]`
3. Trims whitespace from each: `array_map('trim', ...)` → `["academic", "general"]`
4. Passes the array to WordPress tax_query
5. WordPress returns courses that match **any** of the specified categories (OR relationship)

## Common Use Cases

### Show Both Academic and General Training Courses
```
[ielts_courses category="academic,general" columns="5" orderby="title" order="ASC"]
```

### Show Only Academic Courses
```
[ielts_courses category="academic" columns="5" orderby="title" order="ASC"]
```

### Show Only General Training Courses
```
[ielts_courses category="general" columns="5" orderby="title" order="ASC"]
```

### Show Reading Courses from Multiple Modules
```
[ielts_courses category="academic-reading,general-reading" columns="4" orderby="title" order="ASC"]
```

### Show Latest 10 Courses (Any Category)
```
[ielts_courses limit="10" columns="5" orderby="date" order="DESC"]
```

## Backward Compatibility

✓ **Fully backward compatible**  
- Old single-category usage still works: `category="academic"`
- No breaking changes to existing shortcodes
- New multi-category feature is additive

## Testing

Tested scenarios:
- ✓ Single category: `category="academic"`
- ✓ Multiple categories: `category="academic,general"`
- ✓ Multiple categories with spaces: `category="academic, general"`
- ✓ Three or more categories: `category="academic,general,reading"`
- ✓ No category (show all): no `category` parameter

## Notes

- The relationship between categories is **OR** (courses matching ANY of the specified categories will be shown)
- Category slugs are case-sensitive (use lowercase)
- Spaces around category names are automatically trimmed
- If a category doesn't exist, WordPress will simply exclude it from the query

## Related Documentation

- WordPress Codex: [WP_Query Tax Query](https://developer.wordpress.org/reference/classes/wp_query/#taxonomy-parameters)
- IELTS Course Manager: Course Categories (admin taxonomy)
