# Adding Videos to Sub Lesson Pages

## Overview
Sub Lesson pages support embedding videos from YouTube, Vimeo, and other video platforms. When a video URL is added, the page automatically displays in a responsive two-column layout with the video alongside the content.

## How to Add a Video

### Step 1: Edit a Sub Lesson
1. In WordPress admin, navigate to **IELTS Courses > Sub lessons**
2. Click on an existing Sub Lesson to edit, or create a new one

### Step 2: Locate the Video Field
1. Scroll down to the **"Sub Lesson Settings"** meta box
   - This appears below the main content editor
   - In the block editor (Gutenberg), it may be at the bottom of the page
2. Look for the **"Media & Resources"** section
3. Find the **"Video URL (Optional)"** field

### Step 3: Enter Video URL
1. Paste the URL of your video into the "Video URL (Optional)" field
   - Example YouTube URL: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`
   - Example Vimeo URL: `https://vimeo.com/123456789`
2. The placeholder text shows an example format
3. Leave the field empty if you don't want a video on this page

### Step 4: Save
1. Click **Update** or **Publish** to save your changes
2. View the Sub Lesson page on the frontend to see the video

## Supported Video Platforms

WordPress automatically embeds videos from these popular platforms:
- **YouTube** - `https://www.youtube.com/watch?v=...`
- **Vimeo** - `https://vimeo.com/...`
- **DailyMotion** - `https://www.dailymotion.com/video/...`
- **And many more** - Any platform supported by WordPress oEmbed

For a complete list, see: [WordPress Embeds Documentation](https://wordpress.org/support/article/embeds/)

## Frontend Display

### With Video URL
When a video URL is provided:
- **Desktop**: Two-column layout with video (45% width) on the left, content (55% width) on the right
- **Mobile**: Video appears above the content in a stacked layout
- **Video**: Maintains 16:9 aspect ratio and is responsive

### Without Video URL
When the field is left empty:
- **All devices**: Standard full-width text layout
- No video is displayed

## Tips

1. **Use YouTube or Vimeo**: These are the most reliable platforms for embedding
2. **Get the right URL**: Copy the URL from your browser's address bar when viewing the video
3. **Test the embed**: Save and preview your Sub Lesson to ensure the video displays correctly
4. **Responsive design**: The layout automatically adjusts for mobile devices
5. **Performance**: Using video URLs (instead of uploading files) keeps your site fast and saves storage space

## Troubleshooting

**Video doesn't appear:**
- Check that you entered a valid URL
- Ensure the video is publicly accessible (not private)
- Try a different video platform
- Clear your browser cache and reload the page

**Video not in two-column layout:**
- Ensure you entered a URL in the "Video URL" field (not "Additional Resource URL")
- Save/Update the Sub Lesson and refresh the frontend page
- Check that your theme doesn't override the plugin's layout

**Meta box not visible:**
- Check **Screen Options** at the top-right of the edit page
- Ensure "Sub Lesson Settings" is checked
- The meta box appears below the content editor in the block editor

## Need Help?

If you're still having trouble finding or using the video field:
1. Look for the **"Sub Lesson Settings"** meta box
2. Find the **"Media & Resources"** section heading
3. The **"Video URL (Optional)"** field should be clearly visible with a placeholder

The video functionality is built-in and ready to use - no additional setup required!
