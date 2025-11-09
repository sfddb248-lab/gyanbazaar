# Course Video Playlist System - Complete Guide

## Features Implemented ✅

### 1. Video Playlist Management
- Create multiple sections/modules for each course
- Upload multiple videos per section
- Organize videos with custom ordering
- Support for 500MB video files per upload

### 2. Video Features
- **Video Upload:** Support for MP4, WebM, OGG, QuickTime formats
- **File Size:** Up to 500MB per video
- **Notes:** PDF notes attachment for each video
- **Preview:** Mark videos as free preview
- **Duration:** Automatic video duration detection
- **Progress Tracking:** Track user watch progress

### 3. Admin Features
- Section/Module management
- Video upload with progress bar
- Edit video details and notes
- Delete videos and sections
- Reorder videos within sections
- View video statistics

### 4. User Features
- Course viewer with video player
- Sidebar navigation with all videos
- Download PDF notes per video
- Mark videos as completed
- Auto-save watch progress
- Resume from last position
- Video download protection

## Database Setup

### Step 1: Run SQL Update
Execute the SQL file to create required tables:

```sql
-- Run this in phpMyAdmin or MySQL command line
source update-database-courses.sql
```

Or manually run the SQL from `update-database-courses.sql`

### Tables Created:
1. **course_sections** - Course modules/sections
2. **course_videos** - Video files and metadata
3. **user_video_progress** - User watch progress tracking

## PHP Configuration for Large Uploads

### Method 1: Edit php.ini (Recommended)

1. Open `C:\xampp\php\php.ini`
2. Find and update:
```ini
upload_max_filesize = 500M
post_max_size = 550M
max_execution_time = 600
max_input_time = 600
memory_limit = 512M
```
3. Restart Apache

### Method 2: .htaccess (Already Updated)
The `.htaccess` file has been updated with these settings.

## Admin Usage Guide

### Creating a Course with Videos

#### Step 1: Create Course Product
1. Go to **Admin → Products**
2. Click "Add Product"
3. Set **Product Type** to "Course"
4. Fill in course details
5. Save

#### Step 2: Add Sections/Modules
1. Click the **Video icon** (🎥) next to the course
2. Add sections (e.g., "Introduction", "Module 1", etc.)
3. Set order for each section

#### Step 3: Upload Videos
1. Click "Upload Video" for a section
2. Fill in video details:
   - Title
   - Description
   - Order number
   - Free preview (optional)
3. Select video file (max 500MB)
4. Upload PDF notes (optional)
5. Wait for upload to complete
6. Click "Save Video"

#### Step 4: Manage Videos
- **Edit:** Update title, description, notes
- **Delete:** Remove video and files
- **Reorder:** Change order numbers
- **Preview:** Mark as free preview

## User Experience

### Accessing Courses
1. User purchases course
2. Goes to "My Orders"
3. Clicks "View Course"
4. Opens course viewer

### Watching Videos
- Click any video in sidebar
- Video plays in main area
- Progress auto-saves every 10 seconds
- Download notes if available
- Mark as completed when done

### Progress Tracking
- ✅ Green checkmark = Completed
- 🎬 Play icon = Not started
- Auto-complete at 90% watched

## File Structure

```
admin/
├── course-videos.php          # Manage sections & videos
├── upload-course-video.php    # Upload new video
├── edit-course-video.php      # Edit video details
├── delete-course-video.php    # Delete video
└── ajax-upload-video.php      # AJAX video upload handler

course-viewer.php              # User video player
ajax-mark-video-complete.php   # Mark video complete
ajax-save-video-progress.php   # Save watch progress

assets/uploads/courses/
├── videos/                    # Video files
└── notes/                     # PDF notes
```

## Video Upload Process

### Technical Flow:
1. User selects video file
2. JavaScript validates file size (500MB max)
3. AJAX uploads to `ajax-upload-video.php`
4. Progress bar shows upload status
5. Server validates and saves file
6. Returns file path to form
7. User fills remaining details
8. Submits form to save video record

### Security Features:
- File type validation (video formats only)
- Size limit enforcement (500MB)
- Unique filename generation
- Secure file storage
- Video download protection
- Right-click disabled on player

## Supported Video Formats

- MP4 (Recommended)
- WebM
- OGG
- QuickTime (MOV)
- AVI

## Notes System

- **Format:** PDF only
- **Size:** No specific limit (reasonable sizes)
- **Access:** Download button in video player
- **Management:** Upload, replace, or delete anytime

## Progress Tracking

### Auto-Save:
- Every 10 seconds during playback
- Stores watched duration
- Calculates completion percentage

### Completion:
- Manual: "Mark as Completed" button
- Auto: When 90% of video watched
- Visual: Green checkmark in sidebar

## Troubleshooting

### Upload Fails
1. Check PHP configuration (php.ini)
2. Verify .htaccess settings
3. Check folder permissions (777 for uploads/)
4. Restart Apache after config changes

### Video Won't Play
1. Check video format (use MP4)
2. Verify file path in database
3. Check file exists in uploads folder
4. Test in different browser

### Large File Upload Timeout
1. Increase `max_execution_time` in php.ini
2. Use smaller video files
3. Compress videos before upload
4. Check server timeout settings

## Best Practices

### Video Optimization:
- Use MP4 format with H.264 codec
- Compress videos before upload
- Recommended resolution: 1080p or 720p
- Keep file size under 500MB

### Course Structure:
- Create logical sections/modules
- Order videos sequentially
- Use clear, descriptive titles
- Add notes for important videos
- Mark intro videos as preview

### Notes:
- Keep PDFs under 10MB
- Use clear formatting
- Include key points and resources
- Name files descriptively

## Admin Quick Links

- **Manage Products:** `/admin/products.php`
- **Course Videos:** `/admin/course-videos.php?product=ID`
- **Upload Video:** `/admin/upload-course-video.php?section=ID&product=ID`

## User Quick Links

- **My Orders:** `/orders.php`
- **Course Viewer:** `/course-viewer.php?id=PRODUCT_ID`
- **Specific Video:** `/course-viewer.php?id=PRODUCT_ID&video=VIDEO_ID`

## Future Enhancements (Optional)

- Video quality selection
- Playback speed control
- Subtitle support
- Quiz after videos
- Certificate generation
- Bulk video upload
- Video transcoding
- Streaming optimization
- Mobile app support

## Support

For issues or questions:
1. Check this documentation
2. Review error logs
3. Verify database tables exist
4. Check PHP configuration
5. Test with smaller files first
