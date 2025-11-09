# eBook/Notes Feature Guide

## Overview
The DigitalKhazana platform now supports eBooks and notes with preview functionality. Users can read a limited number of pages for free before purchasing the full content.

## Features Added

### 1. Database Changes
- Added `product_type` field (digital, ebook, course)
- Added `preview_pages` field (number of free preview pages)
- Added `total_pages` field (total pages in the ebook)

### 2. Admin Features
- Upload PDF files for ebooks
- Set number of preview pages
- Set total pages
- Product type selection (Digital Product, eBook/Notes, Course)

### 3. User Features
- **Preview Mode**: Read limited pages without purchase
- **Full Access**: After purchase, read all pages
- **PDF Viewer**: Built-in PDF viewer with zoom controls
- **Download**: Download purchased ebooks

## Setup Instructions

### 1. Run Database Migration
```sql
-- Run this SQL to update your existing database
SOURCE migrate-ebook.sql;
```

Or manually run:
```sql
ALTER TABLE products 
ADD COLUMN product_type ENUM('digital', 'ebook', 'course') DEFAULT 'digital' AFTER category_id,
ADD COLUMN preview_pages INT DEFAULT 0 AFTER screenshots,
ADD COLUMN total_pages INT DEFAULT 0 AFTER preview_pages;
```

### 2. Create Upload Directory
Create the directory for storing PDF files:
```
mkdir -p uploads/products
chmod 755 uploads/products
```

### 3. Configure PHP Settings
Ensure your `php.ini` allows large file uploads:
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
```

## How to Add an eBook

1. **Login as Admin**
2. **Go to Products** → Click "Add Product"
3. **Fill in Details**:
   - Product Title
   - Description
   - Price
   - Category
   - **Product Type**: Select "eBook/Notes"
4. **Upload PDF File**: Click "Choose File" and select your PDF
5. **Set Preview Pages**: Enter number of pages users can read for free (e.g., 10)
6. **Set Total Pages**: Enter total number of pages in the PDF
7. **Click Save**

## User Experience

### For Non-Purchased Users:
- See "Read Preview" button on product page
- Can read first X pages (as set by admin)
- See message: "You're viewing a preview (X of Y pages)"
- Prompted to purchase for full access

### For Purchased Users:
- Can read all pages
- Can download the PDF file
- No restrictions on page navigation

## File Structure

```
/admin/
  - products.php (Updated with ebook fields)
  - upload-file.php (New - handles PDF uploads)
  
/
  - ebook-viewer.php (New - ebook reader interface)
  - pdf-viewer.php (New - PDF rendering with restrictions)
  - product-detail.php (Updated with preview button)
  - migrate-ebook.sql (Database migration)
  
/uploads/products/
  - (PDF files stored here)
```

## Security Features

- File type validation (only PDF and ZIP allowed)
- File size limit (50MB max)
- Page access control based on purchase status
- Secure file path handling
- Admin-only upload access

## Supported File Types

- **PDF** (.pdf) - For ebooks and notes
- **ZIP** (.zip) - For bundled content

## Tips

1. **Optimal Preview Pages**: Set 5-15 pages for preview to give users a good taste
2. **File Size**: Keep PDFs under 50MB for better performance
3. **Page Count**: Ensure accurate total page count for better UX
4. **Descriptions**: Add detailed descriptions to help users understand the content

## Troubleshooting

### Upload fails
- Check directory permissions: `chmod 755 uploads/products`
- Verify PHP upload limits in php.ini
- Check file size (max 50MB)

### PDF not displaying
- Ensure file path is correct in database
- Check if file exists in uploads/products/
- Verify PDF is not corrupted

### Preview not working
- Ensure preview_pages > 0
- Check if product_type is set to 'ebook'
- Verify user is not already logged in with purchase

## Future Enhancements

- Bookmarking pages
- Highlighting and notes
- Mobile-optimized reader
- Multiple file format support (EPUB, MOBI)
- Reading progress tracking
