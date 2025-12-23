# Quick Example IELTS Exercise - Upload Instructions

## What is this?

`quick-example.xml` is a minimal IELTS reading exercise with just 5 questions that you can upload to WordPress for quick testing. It includes:

- 1 reading passage about the IELTS test
- 3 True/False/Not Given questions
- 2 Multiple Choice questions
- 10-minute timer
- Standard layout (not computer-based)
- 60% pass percentage

## How to Upload to WordPress

Yes, you use the **default WordPress import feature**! Here's how:

### Step 1: Access WordPress Importer

1. Log in to your WordPress admin dashboard
2. Navigate to **Tools → Import**
3. Find **WordPress** in the list of importers
4. If you haven't installed it yet, click **Install Now**, then **Run Importer**
5. If already installed, just click **Run Importer**

### Step 2: Upload the XML File

1. Click **Choose File** button
2. Select `quick-example.xml` from your computer
3. Click **Upload file and import**

### Step 3: Map the Author

1. WordPress will ask you to assign posts to an author
2. Either:
   - Select an existing user from the dropdown (recommended)
   - Or create a new user (if needed)
3. **Optional**: Check "Download and import file attachments" if you want to import images (not needed for this example)

### Step 4: Complete Import

1. Click **Submit**
2. WordPress will process the XML file
3. You'll see a success message: "All done. Have fun!"

### Step 5: View Your Imported Exercise

1. Go to **IELTS Courses → Exercises** (or your custom post type menu)
2. You should see "Quick Example IELTS Reading Exercise" in the list
3. Click to view or edit it

## What Gets Imported?

The XML file imports:
- ✅ Exercise title and slug
- ✅ Reading passage content
- ✅ All 5 questions with answers
- ✅ Question feedback
- ✅ Exercise settings (timer, layout, scoring, etc.)
- ✅ Custom metadata (pass percentage, exercise label, etc.)

## After Import

Once imported, you can:
- Preview the exercise on your site
- Edit questions or settings in WordPress
- Assign it to courses or lessons
- Customize the layout or timer
- Add more questions if needed

## Troubleshooting

### "WordPress importer not found"
- Go to **Tools → Import**
- Click **Install Now** under WordPress
- Then click **Run Importer**

### "Failed to import"
- Make sure the XML file isn't corrupted
- Check file size (should be around 8-9 KB)
- Try re-downloading the XML file

### "Exercise doesn't appear in list"
- Check under the correct post type (usually **IELTS Courses → Exercises**)
- Verify the import completed successfully (look for success message)
- Check if it was imported as a draft (filter by status)

### "Questions not showing up"
- Make sure the IELTS Course Manager plugin is active
- Check that custom fields were imported (edit the post and look for custom fields)

## Next Steps

Once you've successfully imported this example:
1. Test it on your site to make sure it works
2. Use `create-test-xml.php` to create your own custom tests
3. Follow the instructions in `HOW-TO-CREATE-TESTS.md` for full tests

## Technical Details

- **Format**: WordPress eXtended RSS (WXR) 1.2
- **Post Type**: `ielts_quiz`
- **Encoding**: UTF-8
- **Questions**: Serialized PHP arrays in custom meta fields
- **Compatible with**: WordPress 5.0+ and IELTS Course Manager plugin

---

**That's it!** The default WordPress importer handles everything. No special plugins or complicated steps needed. 🎉
