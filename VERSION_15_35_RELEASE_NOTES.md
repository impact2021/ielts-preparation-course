# Version 15.35 Release Notes

## Partner Dashboard - Managed Students Search Functionality

### Feature Addition
Added a search function to the "Managed Students" table in the partner dashboard, allowing partners to quickly find specific users.

### Problem
Partner sites with many managed students needed a way to quickly locate specific users without manually scrolling through the entire table.

### Solution
Implemented a real-time search input that filters the managed students table as the user types.

**Search Features:**
- **Real-time filtering**: Table updates immediately as you type
- **Multi-field search**: Searches across username, full name, and email address
- **Case-insensitive**: Works regardless of letter case
- **Works with filters**: Maintains Active/Expired tab filtering while searching
- **Clear feedback**: Shows "No students found matching..." message when no results

### How to Use

1. Navigate to the partner dashboard (page with `[iw_partner_dashboard]` shortcode)
2. Scroll to the "Managed Students" section
3. Enter text in the search box at the top of the students table
4. The table will automatically filter to show only matching students
5. Use Active/Expired tabs to further refine results
6. Clear the search box to see all students again

### Search Behavior

**Searches in:**
- Username (displayed in bold)
- Full name (displayed below username)
- Email address (displayed below name)

**Examples:**
- Search "john" - finds users with "john" in username, name, or email
- Search "@gmail" - finds all users with Gmail addresses
- Search "smith" - finds users with "Smith" in their name

### Technical Implementation

**Files Modified:**
1. **includes/class-access-codes.php**
   - Added search input HTML (line ~1254-1256)
   - Updated `filterStudents()` JavaScript function to handle search filtering
   - Added search event handler for real-time filtering
   - Enhanced empty state messaging for search results

2. **ielts-course-manager.php**
   - Updated version from 15.34 to 15.35

### Code Changes

#### 1. Search Input HTML
```html
<input type="text" id="iw-student-search" 
       placeholder="Search by name, username, or email..." 
       style="margin-bottom: 10px; max-width: 400px;">
```

#### 2. Enhanced JavaScript Filtering
The `filterStudents()` function now:
- Retrieves the search term from the input
- Filters rows by both status (active/expired) AND search term
- Shows custom "No students found" message when search yields no results
- Maintains compatibility with existing Active/Expired tab filtering

#### 3. Search Event Handler
```javascript
$('#iw-student-search').on('keyup', function() {
    var currentFilter = $('.iw-filter-btn[data-filter-students].active').data('filter-students');
    IWDashboard.filterStudents(currentFilter);
});
```

### User Interface

**Location:** Partner Dashboard → Managed Students card

**Layout:**
```
┌─────────────────────────────────────────┐
│ Managed Students                         │
├─────────────────────────────────────────┤
│ [Search by name, username, or email...] │
│                                          │
│ [Active (25)] [Expired (5)]             │
│                                          │
│ ┌────────────────────────────────────┐  │
│ │ Table with filtered students       │  │
│ └────────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

### Performance

- **Client-side filtering**: No server requests required
- **Instant response**: Filters as you type with no delay
- **Lightweight**: Uses existing jQuery already loaded in dashboard
- **Efficient**: Only searches visible text content, not metadata

### Compatibility

- **Browser Compatibility**: Works in all modern browsers (Chrome, Firefox, Safari, Edge)
- **jQuery Version**: Compatible with WordPress bundled jQuery
- **WordPress**: No WordPress version requirements
- **Backward Compatible**: No breaking changes to existing functionality

### Testing Recommendations

1. **Basic Search**
   - Type partial username and verify it appears
   - Type partial email and verify it appears
   - Type partial name and verify it appears

2. **Case Sensitivity**
   - Search with different cases (JOHN, john, John)
   - Verify all variants work

3. **Combined Filtering**
   - Switch between Active and Expired tabs while search is active
   - Verify search persists across tab changes
   - Clear search and verify tabs still work

4. **Empty States**
   - Search for non-existent term
   - Verify "No students found matching..." message appears
   - Clear search and verify original state returns

5. **Edge Cases**
   - Empty search (shows all students for current tab)
   - Special characters in search
   - Very long search terms

### Impact

**User Benefits:**
- ✅ Faster user lookup in large student lists
- ✅ Improved partner dashboard usability
- ✅ Better user experience for partner sites
- ✅ No learning curve - intuitive search interface

**Technical Benefits:**
- ✅ No server load - client-side filtering
- ✅ Minimal code changes
- ✅ Leverages existing JavaScript architecture
- ✅ No new dependencies

### Related Features

This search function complements existing partner dashboard features:
- Active/Expired student filtering (existing)
- Student management actions (Edit, Resend Email, Revoke)
- Overall band score display
- Membership expiry tracking

### Future Enhancements (Not in this version)

Potential future improvements:
- Advanced search filters (by membership type, expiry date range)
- Export filtered results to CSV
- Save search preferences
- Search highlighting in results

### Version History

- **15.35**: Added managed students search functionality
- **15.34**: Membership tier simplification
- **15.33**: Radio button letter prefix spacing fix
- **15.32**: Previous version

---

## Summary

Version 15.35 adds a valuable search function to the partner dashboard's managed students table, making it easy for partners to quickly locate specific users by typing in their name, username, or email address. The search works in real-time and integrates seamlessly with the existing Active/Expired filtering system.
