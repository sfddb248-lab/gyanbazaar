# 🎓 Test Course Video System - Step by Step Guide

## ✅ Test Course Created!

A test course has been automatically created for you to test the video playlist features.

---

## 📋 Quick Access Links

### Admin Panel:
```
http://localhost/DigitalKhazana/admin/login.php
```

### View All Products:
```
http://localhost/DigitalKhazana/admin/products.php
```

### Manage Test Course Videos:
```
http://localhost/DigitalKhazana/admin/course-videos.php?product=3
```

### Upload Video to Test Section:
```
http://localhost/DigitalKhazana/admin/upload-course-video.php?section=1&product=3
```

---

## 🎯 Step-by-Step Testing Guide

### Step 1: Login to Admin Panel

1. Open browser and go to:
   ```
   http://localhost/DigitalKhazana/admin/login.php
   ```

2. Login with your admin credentials

### Step 2: View Products List

1. Click on **"Products"** in the sidebar
   
2. You should see:
   - Your existing products
   - **"Test Course - Video Playlist"** (new)
   - A **video icon (🎥)** next to the test course

3. The video icon looks like this: 🎥 (green button)

### Step 3: Access Course Video Management

**Option A - Click Video Icon:**
- Click the green video icon (🎥) next to "Test Course - Video Playlist"

**Option B - Direct URL:**
- Go to: `http://localhost/DigitalKhazana/admin/course-videos.php?product=3`

### Step 4: You Should See

The course video management page with:

```
┌─────────────────────────────────────────────────┐
│ Course Videos: Test Course - Video Playlist    │
│                                                 │
│ [Add New Section/Module]                       │
│                                                 │
│ Section: Introduction (1 videos)               │
│   [Upload Video] [Delete]                      │
│                                                 │
│   No videos in this section yet.              │
└─────────────────────────────────────────────────┘
```

### Step 5: Upload Your First Video

1. Click **"Upload Video"** button

2. Fill in the form:
   - **Video Title:** e.g., "Welcome to the Course"
   - **Description:** e.g., "Introduction video"
   - **Order:** 1 (already filled)
   - **Free Preview:** Check if you want it free

3. **Select Video File:**
   - Click "Choose File" under "Upload Video"
   - Select a video file (MP4 recommended, max 500MB)
   - Wait for upload progress bar

4. **Optional - Upload Notes:**
   - Click "Choose File" under "Upload Notes"
   - Select a PDF file

5. Click **"Save Video"**

### Step 6: Verify Video Upload

After saving, you should see:
- Video appears in the section list
- Shows video title, duration, size
- Edit and delete buttons available

### Step 7: Add More Sections

1. Go back to course videos page
2. Use "Add New Section/Module" form at top
3. Enter section name (e.g., "Module 1", "Advanced Topics")
4. Click "Add Section"
5. Upload videos to new section

### Step 8: Test Video Player (User View)

1. Create a test order for the course (or mark existing order as completed)

2. Login as a regular user

3. Go to "My Orders"

4. Click "View Course" next to the test course

5. You should see:
   - Video player
   - Sidebar with all sections and videos
   - Click any video to play
   - Download notes button (if notes uploaded)

---

## 🔍 Troubleshooting

### Can't See Video Icon?

**Check 1:** Is the product type "course"?
```sql
SELECT id, title, product_type FROM products WHERE id = 3;
```
Should show: `product_type = 'course'`

**Check 2:** Clear browser cache
- Press Ctrl+Shift+Delete
- Clear cache
- Refresh page

**Check 3:** Check if file exists
- Verify file exists: `admin/course-videos.php`
- Check file permissions

### Video Upload Fails?

**Check 1:** Apache restarted?
- Open XAMPP Control Panel
- Stop and Start Apache

**Check 2:** File size?
- Max 500MB per video
- Try smaller file first (under 100MB)

**Check 3:** PHP settings?
Run: `php verify-course-system.php`
Should show: `upload_max_filesize = 500M`

**Check 4:** Folder permissions?
- Check folder exists: `assets/uploads/courses/videos/`
- Should be writable

### Video Won't Play?

**Check 1:** Video format?
- Use MP4 format (H.264 codec)
- Avoid exotic formats

**Check 2:** File exists?
- Check: `assets/uploads/courses/videos/`
- Verify file is there

**Check 3:** Browser console?
- Press F12
- Check Console tab for errors

---

## 📊 Test Checklist

Use this checklist to verify everything works:

### Admin Features:
- [ ] Can see video icon (🎥) in products list
- [ ] Can access course-videos.php page
- [ ] Can add new sections
- [ ] Can upload videos (with progress bar)
- [ ] Can upload PDF notes
- [ ] Can edit video details
- [ ] Can delete videos
- [ ] Can reorder videos
- [ ] Can set preview videos

### User Features:
- [ ] Can access course viewer
- [ ] Video plays correctly
- [ ] Can see all sections in sidebar
- [ ] Can switch between videos
- [ ] Can download notes
- [ ] Progress saves automatically
- [ ] Can mark as completed
- [ ] Completion shows in sidebar (✅)

### Upload Features:
- [ ] Can upload videos up to 500MB
- [ ] Progress bar shows during upload
- [ ] Duration detected automatically
- [ ] File size calculated correctly
- [ ] PDF notes upload works

---

## 🎬 Sample Test Videos

If you don't have test videos, you can:

1. **Use existing videos** from your computer
2. **Download free test videos** from:
   - https://sample-videos.com/
   - https://test-videos.co.uk/

3. **Create a quick test video:**
   - Use phone camera
   - Record 10-30 seconds
   - Transfer to computer

---

## 📝 Test Course Details

**Course ID:** 3
**Course Title:** Test Course - Video Playlist
**Product Type:** course
**Price:** $99.99
**Status:** active

**Section ID:** 1
**Section Title:** Introduction
**Section Description:** Welcome to the course

---

## 🚀 Next Steps After Testing

Once you've verified everything works:

1. **Create Real Courses:**
   - Go to Products → Add Product
   - Set type to "Course"
   - Add your actual course content

2. **Upload Real Videos:**
   - Organize in logical sections
   - Add helpful notes
   - Set appropriate preview videos

3. **Delete Test Course:**
   - Go to Products
   - Delete "Test Course - Video Playlist"
   - Or keep it for future testing

---

## 💡 Tips for Best Results

### Video Optimization:
- Use MP4 format with H.264 codec
- Resolution: 1080p or 720p
- Keep files under 500MB (compress if needed)
- Use consistent naming

### Course Structure:
- Create logical sections (Introduction, Module 1, Module 2, etc.)
- Order videos sequentially
- Use clear, descriptive titles
- Add notes for important videos

### Notes:
- Keep PDFs under 10MB
- Use clear formatting
- Include key points and resources
- Name files descriptively

---

## 📞 Need Help?

### Documentation:
- **COURSE_VIDEO_SYSTEM.md** - Complete guide
- **ALL_TASKS_COMPLETED.md** - Full implementation report
- **QUICK_REFERENCE.txt** - Quick reference

### Verification:
Run: `php verify-course-system.php`

### Check Courses:
Run: `php check-courses.php`

---

## ✅ Success Indicators

You'll know it's working when:

1. ✅ Video icon (🎥) appears next to course products
2. ✅ Course videos page loads without errors
3. ✅ Can upload videos with progress bar
4. ✅ Videos appear in section list
5. ✅ Can play videos in course viewer
6. ✅ Progress tracking works
7. ✅ Notes download works

---

**Test Course Created:** November 5, 2025
**Course ID:** 3
**Section ID:** 1
**Status:** ✅ Ready for Testing

🎉 Start Testing Your Course Video System! 🎉
