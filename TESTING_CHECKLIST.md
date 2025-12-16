# Testing Checklist for Permalink and Padding Fixes

## Quick Verification Steps

### ✅ Permalink Fixes

#### Test 1: Deactivation/Reactivation
1. Visit a course page (e.g., `/ielts-course/your-course/`)
   - [ ] Page loads correctly
2. Deactivate the IELTS Course Manager plugin
3. Try visiting the same course page
   - [ ] Shows 404 error (expected - plugin is deactivated)
4. Reactivate the plugin
5. Visit the course page again
   - [ ] **Page loads correctly WITHOUT manual permalink reset**

#### Test 2: Version Update Simulation
```bash
# In wp-config.php or plugin file, temporarily change version
# From: define('IELTS_CM_VERSION', '1.0.0');
# To:   define('IELTS_CM_VERSION', '1.0.1');
```
1. Change the version constant
2. Visit any page on the site
3. Visit a course/lesson page
   - [ ] **Page loads correctly WITHOUT manual permalink reset**
4. Check database:
   ```sql
   SELECT option_value FROM wp_options WHERE option_name = 'ielts_cm_version';
   ```
   - [ ] Shows new version '1.0.1'

#### Test 3: Transient Cache
```sql
-- Check transient is set
SELECT option_name, option_value FROM wp_options WHERE option_name LIKE '%ielts_cm_version%';
```
- [ ] Should see `_transient_ielts_cm_version_checked` with current version
- [ ] Transient expires after 1 hour

### ✅ Padding Fixes

#### Test 1: Course Page Padding
1. Visit any course page
2. Use browser DevTools (F12) → Inspect the `<main>` element
3. Check computed styles:
   - [ ] `padding-top: 60px`
   - [ ] `padding-bottom: 60px`
   - [ ] `padding-left: 40px`
   - [ ] `padding-right: 40px`
4. Visual check:
   - [ ] Content has proper spacing from header
   - [ ] Content has proper spacing from footer
   - [ ] Not cramped against edges

#### Test 2: Lesson Page Padding
1. Visit any lesson page
2. Check computed styles on `<main>` element
   - [ ] Same padding as course page
3. Visual check:
   - [ ] Proper spacing all around

#### Test 3: Course Archive Padding
1. Visit course archive page (`/ielts-course/`)
2. Check computed styles
   - [ ] Same padding applied
3. Visual check:
   - [ ] Course grid has proper spacing

#### Test 4: Theme Compatibility
1. Switch to Twenty Twenty-Three theme
2. Visit course/lesson pages
   - [ ] Padding still works
3. Switch to Twenty Twenty-One theme
4. Visit course/lesson pages
   - [ ] Padding still works
5. Switch back to original theme
   - [ ] Everything still works

## Expected Results

### Permalink Tests
✅ No manual permalink reset needed after deactivation
✅ No manual permalink reset needed after version update
✅ Automatic version detection works
✅ Transient cache optimizes performance

### Padding Tests
✅ 60px top/bottom padding on all IELTS pages
✅ 40px left/right padding on all IELTS pages
✅ Works with any WordPress theme
✅ Content not cramped against header/footer

## Troubleshooting

### If permalinks don't work:
1. Check if post types are registered:
   ```php
   global $wp_post_types;
   var_dump(isset($wp_post_types['ielts_course']));
   ```
2. Manually flush: Settings → Permalinks → Save Changes
3. Check `.htaccess` file permissions

### If padding doesn't show:
1. Clear browser cache (Ctrl+Shift+Delete)
2. Check DevTools for CSS conflicts
3. Verify CSS file is loaded:
   - View source → Look for `frontend.css`
4. Check inline styles in page source

## Browser Testing
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile browsers

## Performance Check
- [ ] Page load time not significantly affected
- [ ] No console errors
- [ ] Database queries not excessive

## Final Checklist
- [ ] All permalink tests pass
- [ ] All padding tests pass
- [ ] Works on multiple themes
- [ ] No console errors
- [ ] No PHP errors
- [ ] Documentation is clear
- [ ] Ready for production

---

**Status:** All tests should pass ✅  
**Security:** No vulnerabilities ✅  
**Performance:** Optimized with transient cache ✅
