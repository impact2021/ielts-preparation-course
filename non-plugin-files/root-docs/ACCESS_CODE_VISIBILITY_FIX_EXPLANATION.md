# Access Code Visibility Fix - Explanation

## The Problem

When a partner created access codes through the Partner Dashboard, the codes were successfully created in the database but **did not appear** in the "Your codes" table after the page reloaded. This made it seem like the codes weren't created at all, causing confusion.

## Root Cause Analysis

### How the System Works

1. **Code Creation Process:**
   - Partner fills out the "Create Invite Codes" form
   - Clicks submit → AJAX call to `ajax_create_invite()` 
   - Codes are created in database with `status='active'`
   - Success message shows
   - Page reloads after 3 seconds (`location.reload()`)

2. **Table Display System:**
   - The "Your codes" table has filter buttons: "Used" and "Unused"
   - On page load, JavaScript initializes the filter
   - The filter hides/shows table rows based on their `data-status` attribute

3. **The Bug:**
   - Line 1240: HTML had `class="active"` on the "Used" button
   - Line 1540: JavaScript called `IWDashboard.filterCodes('used')` on page load
   - This meant the table defaulted to showing only codes with `status='used'`
   - **Newly created codes have `status='active'`** (not 'used')
   - Result: New codes were immediately hidden by the filter!

### Visual Flow (Before Fix)

```
Partner creates 5 codes
    ↓
Codes saved to DB with status='active'
    ↓
Success message: "5 codes generated successfully!"
    ↓
Page reloads after 3 seconds
    ↓
JavaScript runs: IWDashboard.filterCodes('used')
    ↓
Filter hides all rows where data-status='active'
    ↓
Partner sees: "No codes generated yet." or empty table
    ↓
Partner is confused! 😕
```

## The Solution

**Changed the default filter from 'used' to 'available'**

### What Was Changed

**File: `includes/class-access-codes.php`**

**Change 1 - HTML (lines 1240-1241):**
```diff
- <button class="iw-filter-btn active" data-filter="used">Used</button>
- <button class="iw-filter-btn" data-filter="available">Unused</button>
+ <button class="iw-filter-btn" data-filter="used">Used</button>
+ <button class="iw-filter-btn active" data-filter="available">Unused</button>
```

**Change 2 - JavaScript (line 1540):**
```diff
- // Initialize code filter to show used by default
- IWDashboard.filterCodes('used');
+ // Initialize code filter to show available (unused) codes by default
+ IWDashboard.filterCodes('available');
```

### Visual Flow (After Fix)

```
Partner creates 5 codes
    ↓
Codes saved to DB with status='active'
    ↓
Success message: "5 codes generated successfully!"
    ↓
Page reloads after 3 seconds
    ↓
JavaScript runs: IWDashboard.filterCodes('available')
    ↓
Filter shows all rows where data-status='active'
    ↓
Partner sees: Table with 5 newly created codes! ✓
    ↓
Partner is happy! 😊
```

## Why This Is Better

1. **Solves the immediate bug** - Codes now appear after creation
2. **Better UX** - Partners typically want to see *available* codes to share with students
3. **Minimal change** - Only 4 lines changed, no database modifications
4. **Backward compatible** - Partners can still click "Used" to see redeemed codes
5. **Logical default** - Makes more sense to show unused codes by default

## Testing the Fix

### Manual Test Steps:

1. **Log in as a partner admin**
2. **Navigate to Partner Dashboard** (`[iw_partner_dashboard]` shortcode page)
3. **Verify initial state:**
   - "Unused" button should be highlighted (active)
   - Table should show any existing available codes
4. **Create new codes:**
   - Fill in: Quantity=3, Membership=Academic Module, Days=90
   - Click "Generate Codes"
5. **Verify success:**
   - Success message appears
   - 3 codes are displayed in a textarea
   - Page reloads after 3 seconds
6. **Verify codes appear:**
   - After reload, "Unused" button is still highlighted
   - Table shows the 3 newly created codes
   - Each code shows: Code, Membership, Days, Status="Available", Created date
7. **Test filters:**
   - Click "Used" button → table shows only redeemed codes
   - Click "Unused" button → table shows available codes again

### Expected Results:

✅ Codes appear immediately after creation  
✅ "Unused" filter is active by default  
✅ Can toggle between "Used" and "Unused" filters  
✅ No JavaScript errors in console  

## Technical Details

### Files Modified:
- `ielts-course-manager.php` - Version bump (15.35 → 15.36)
- `includes/class-access-codes.php` - Filter default change
- `VERSION_15_36_RELEASE_NOTES.md` - New documentation file

### Code Status Mapping:
- `status='active'` → Display as "Available" → Shown by "Unused" filter
- `status='used'` → Display as "Used" → Shown by "Used" filter
- `status='expired'` → Display as "Expired" → Shown by neither filter (would need "All" filter)

### Filter Logic (from `filterCodes()` function):
```javascript
if (status === 'available') {
    // Show codes where data-status === 'active'
    jQuery('.iw-table tbody tr').each(function() {
        if (jQuery(this).data('status') === 'active') {
            jQuery(this).show();
        } else {
            jQuery(this).hide();
        }
    });
}
```

## Security Review

✅ **No security issues** - Changes are purely UI/display logic  
✅ **No database changes** - Only affects client-side filtering  
✅ **No new user input** - Only changes default filter state  
✅ **No XSS risk** - No new output, existing escaping preserved  

## Version Numbers Updated

- Plugin header: `Version: 15.35` → `Version: 15.36`
- PHP constant: `IELTS_CM_VERSION` from `'15.35'` → `'15.36'`

---

**Created:** February 10, 2026  
**Issue:** Access codes not showing in 'Your codes' table  
**Fix:** Change default filter from 'used' to 'available'  
**Impact:** Improved UX, bug resolved, backward compatible
