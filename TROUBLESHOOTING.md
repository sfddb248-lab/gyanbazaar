# Troubleshooting eBook Viewer

## Issue: Can't Read Notes/eBook

### Quick Tests:

1. **Test if product exists:**
   - Visit: `http://localhost/DigitalKhazana/check-product.php`
   - Should show product details and file path

2. **Test PDF access:**
   - Visit: `http://localhost/DigitalKhazana/test-pdf.php`
   - Try all 4 test methods

3. **Test direct PDF:**
   - Visit: `http://localhost/DigitalKhazana/uploads/products/69063102d33b2_1762013442.pdf`
   - Should open PDF in browser

### Common Issues:

#### 1. PDF Not Loading in Viewer

**Symptoms:** Blank page or error in ebook-viewer.php

**Solutions:**
- Check browser console (F12) for JavaScript errors
- Verify PDF.js CDN is accessible
- Check if PDF file exists in uploads/products/

**Fix:**
```
1. Open browser console (F12)
2. Look for errors
3. Check Network tab for failed requests
```

#### 2. .htaccess Blocking Access

**Symptoms:** 403 Forbidden error

**Solution:**
The .htaccess in uploads/products/ should allow PDF viewing:
```apache
# uploads/products/.htaccess should have:
<FilesMatch "\.pdf$">
    Header set Content-Disposition "inline"
</FilesMatch>
```

#### 3. File Path Issues

**Symptoms:** "File not found" error

**Check:**
```php
// File path should be relative: uploads/products/filename.pdf
// NOT absolute: C:\xampp\htdocs\...
```

**Fix in database:**
```sql
UPDATE products 
SET file_path = 'uploads/products/69063102d33b2_1762013442.pdf'
WHERE id = 2;
```

#### 4. CORS Issues

**Symptoms:** PDF.js can't load PDF

**Solution:** Add to main .htaccess:
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>
```

### Step-by-Step Debugging:

1. **Check Product Data:**
   ```
   Visit: check-product.php
   Verify: file_path, preview_pages, total_pages
   ```

2. **Test Direct PDF Access:**
   ```
   Visit: uploads/products/[your-file].pdf
   Should: Open PDF in browser
   ```

3. **Test PDF Viewer:**
   ```
   Visit: pdf-viewer.php?id=2
   Should: Show PDF with controls
   Check: Browser console for errors
   ```

4. **Test eBook Viewer:**
   ```
   Visit: ebook-viewer.php?id=2
   Should: Show iframe with PDF
   ```

5. **Test from Product Page:**
   ```
   Visit: product-detail.php?id=2
   Click: "Read Preview" button
   Should: Open ebook-viewer.php
   ```

### Browser Console Commands:

Open browser console (F12) and run:

```javascript
// Check if PDF.js is loaded
console.log(typeof pdfjsLib);

// Check PDF path
console.log(document.querySelector('iframe').src);

// Reload iframe
document.querySelector('iframe').src = document.querySelector('iframe').src;
```

### Database Checks:

```sql
-- Check product data
SELECT id, title, file_path, preview_pages, total_pages, product_type 
FROM products 
WHERE id = 2;

-- Verify file path format
-- Should be: uploads/products/filename.pdf
-- NOT: C:\xampp\htdocs\DigitalKhazana\uploads\products\filename.pdf
```

### File Permission Issues (Linux/Mac):

```bash
# Set correct permissions
chmod 755 uploads/products
chmod 644 uploads/products/*.pdf
```

### Still Not Working?

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Try different browser** (Chrome, Firefox, Edge)
3. **Check Apache error logs** (xampp/apache/logs/error.log)
4. **Restart Apache** from XAMPP Control Panel

### Test URLs:

- Product Detail: `http://localhost/DigitalKhazana/product-detail.php?id=2`
- eBook Viewer: `http://localhost/DigitalKhazana/ebook-viewer.php?id=2`
- PDF Viewer: `http://localhost/DigitalKhazana/pdf-viewer.php?id=2`
- Direct PDF: `http://localhost/DigitalKhazana/uploads/products/[filename].pdf`
- Diagnostics: `http://localhost/DigitalKhazana/check-product.php`
- Test Page: `http://localhost/DigitalKhazana/test-pdf.php`

### Success Indicators:

✓ check-product.php shows all data correctly
✓ Direct PDF link opens in browser
✓ pdf-viewer.php shows PDF with controls
✓ ebook-viewer.php shows iframe with PDF
✓ "Read Preview" button works from product page
✓ Page navigation works (limited to preview pages)

### Get Help:

If still not working, check:
1. Browser console errors (F12 → Console)
2. Network tab (F12 → Network)
3. Apache error logs
4. PHP error logs
