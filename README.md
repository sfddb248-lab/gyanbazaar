# GyanBazaar - Digital Product Selling Platform

A complete, responsive digital product marketplace built with PHP, MySQL, and MDBootstrap. Features adaptive layouts for both mobile (native app experience) and desktop (professional website).

## Features

### User Side
- **Authentication**: Signup, Login, Password Reset, Profile Management
- **Product Browsing**: Search, Filter by Category/Price, Sort by Popularity
- **Shopping**: Cart Management, Secure Checkout, Multiple Payment Gateways
- **Orders**: Purchase History, Secure Downloads with Expiry, Invoice Generation
- **Responsive Design**: Mobile-first with bottom navigation, Desktop with professional navbar
- **Dark/Light Mode**: Theme toggle support

### Admin Side
- **Dashboard**: Sales statistics, Recent orders, Top products
- **Product Management**: Add/Edit/Delete products, Upload files, Manage categories
- **Order Management**: View all orders, Transaction tracking, Refund management
- **User Management**: View users, Block/Unblock, Purchase history
- **Coupons**: Create discount codes (Flat/Percentage), Set expiry & limits
- **Reports**: Daily/Monthly sales, Revenue analytics, Payment method breakdown
- **Settings**: Payment gateway configuration, Tax settings, Site branding

## Tech Stack
- **Frontend**: PHP, MDBootstrap 7.1, Font Awesome 6.5
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Payment Gateways**: Razorpay, Stripe, PayPal (configurable)

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional)

### Setup Steps

1. **Clone/Download the project**
   ```bash
   git clone https://github.com/nitin9917/gyanbazaar.git
   cd gyanbazaar
   ```

2. **Create Database**
   - Open phpMyAdmin or MySQL command line
   - Import the `database.sql` file
   ```sql
   mysql -u root -p < database.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php`
   - Update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'gyanbazaar');
   ```

4. **Configure Site URL**
   - Edit `config/config.php`
   - Update SITE_URL:
   ```php
   define('SITE_URL', 'http://localhost/GyanBazaar');
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 assets/uploads/
   ```

6. **Access the Application**
   - Frontend: `http://localhost/GyanBazaar`
   - Admin Panel: `http://localhost/GyanBazaar/admin`

### Default Admin Credentials
- **Email**: admin@gyanbazaar.com
- **Password**: admin123

**⚠️ Important**: Change the admin password immediately after first login!

## Configuration

### Payment Gateway Setup

1. **Razorpay**
   - Sign up at https://razorpay.com
   - Get API Key and Secret
   - Add to Admin → Settings → Payment Gateway

2. **Stripe**
   - Sign up at https://stripe.com
   - Get Publishable and Secret keys
   - Add to Admin → Settings → Payment Gateway

3. **PayPal**
   - Sign up at https://developer.paypal.com
   - Create app and get Client ID and Secret
   - Add to Admin → Settings → Payment Gateway

### Email Configuration
- Edit `includes/functions.php`
- Update `sendEmail()` function with your SMTP settings

## Project Structure

```
GyanBazaar/
├── admin/                  # Admin panel files
│   ├── includes/          # Admin header/footer
│   ├── index.php          # Dashboard
│   ├── products.php       # Product management
│   ├── orders.php         # Order management
│   ├── users.php          # User management
│   ├── coupons.php        # Coupon management
│   ├── reports.php        # Analytics & reports
│   └── settings.php       # System settings
├── assets/
│   └── uploads/           # Product files & images
├── config/
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── includes/
│   ├── header.php         # User header
│   ├── footer.php         # User footer
│   └── functions.php      # Helper functions
├── index.php              # Homepage
├── products.php           # Product listing
├── product-detail.php     # Product details
├── cart.php               # Shopping cart
├── checkout.php           # Checkout page
├── orders.php             # User orders
├── profile.php            # User profile
├── login.php              # Login page
├── signup.php             # Registration
├── download.php           # Secure download handler
└── database.sql           # Database schema
```

## Features in Detail

### Responsive Design
- **Mobile**: Native app-like experience with bottom navigation, full-width cards
- **Desktop**: Professional website with navbar, grid layouts, sidebar navigation
- **Adaptive**: Smooth transitions between breakpoints

### Security Features
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Secure file downloads with ownership verification
- Download limits and expiry dates

### Payment Flow
1. User adds products to cart
2. Applies coupon (optional)
3. Proceeds to checkout
4. Selects payment method
5. Payment processed via gateway
6. Order created with transaction ID
7. Instant access to downloads

### Download System
- Secure file storage outside web root (recommended)
- Download count tracking
- Expiry date enforcement
- Ownership verification
- Direct file serving with proper headers

## Customization

### Adding New Payment Gateway
1. Add gateway settings to `settings` table
2. Update `checkout.php` to handle new gateway
3. Add gateway option in admin settings

### Changing Theme Colors
- Edit CSS variables in `includes/header.php`:
```css
:root {
    --primary-color: #1266f1;
    --secondary-color: #b23cfd;
}
```

### Adding New Product Categories
- Go to Admin Panel
- Use phpMyAdmin to add categories to `categories` table
- Or extend admin panel with category management

## Troubleshooting

### Database Connection Error
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists

### File Upload Issues
- Check `assets/uploads/` permissions (755 or 777)
- Verify `upload_max_filesize` in php.ini
- Check `post_max_size` in php.ini

### Payment Gateway Not Working
- Verify API keys in Admin → Settings
- Check gateway mode (test/live)
- Review error logs

### Downloads Not Working
- Check file paths in database
- Verify file exists in uploads folder
- Check download limits and expiry

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License
This project is open-source and available for personal and commercial use.

## Support
For issues and questions:
- Check documentation
- Review code comments
- GitHub: https://github.com/nitin9917/gyanbazaar

## Credits
- MDBootstrap: https://mdbootstrap.com
- Font Awesome: https://fontawesome.com
- PHP: https://php.net

---

**Built with ❤️ for digital entrepreneurs**
