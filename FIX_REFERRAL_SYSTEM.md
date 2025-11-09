# Fix Referral System - Database Column Missing

## ❌ Error Encountered

```
Fatal error: Unknown column 'u.referred_by' in 'where clause'
```

## 🔍 Root Cause

The `users` table is missing the `referred_by` column which is needed to track which affiliate referred each user.

## ✅ Solution Applied

### 1. Database Fix Script Created
**File**: `fix-referral-column.php`

This script will:
- Check if `referred_by` column exists
- Add the column if missing
- Add foreign key constraint
- Display current table structure

### 2. Signup Process Updated
**File**: `signup.php`

Updated to:
- Check for affiliate referral code during signup
- Get affiliate ID from referral code
- Insert user with `referred_by` field set
- Properly link new users to their referrer

## 🚀 How to Fix

### Step 1: Run Database Fix Script

Visit this URL in your browser:
```
http://localhost/GyanBazaar/fix-referral-column.php
```

This will:
1. Add `referred_by` column to `users` table
2. Set up foreign key relationship
3. Show you the updated table structure

### Step 2: Verify Fix

After running the script, you should see:
- ✅ Column 'referred_by' added successfully
- ✅ Foreign key constraint added
- ✅ Table structure displayed

### Step 3: Test Affiliate Dashboard

Visit:
```
http://localhost/GyanBazaar/affiliate-dashboard.php
```

The "My Referrals & Their Purchases" section should now work without errors.

## 📊 Database Changes

### Column Added:
```sql
ALTER TABLE users 
ADD COLUMN referred_by INT NULL AFTER role;
```

### Foreign Key Added:
```sql
ALTER TABLE users 
ADD CONSTRAINT fk_referred_by 
FOREIGN KEY (referred_by) REFERENCES affiliates(id) 
ON DELETE SET NULL;
```

### Updated Users Table Structure:
```
users
├── id (INT, PRIMARY KEY)
├── name (VARCHAR)
├── email (VARCHAR)
├── password (VARCHAR)
├── role (ENUM)
├── referred_by (INT, NULL) ← NEW COLUMN
├── status (VARCHAR)
├── email_verified (BOOLEAN)
└── created_at (TIMESTAMP)
```

## 🔄 How Referral Tracking Works Now

### 1. User Clicks Referral Link
```
http://localhost/GyanBazaar/?ref=ABC123
```
- Cookie `affiliate_ref` is set with code "ABC123"

### 2. User Signs Up
- System checks for `affiliate_ref` cookie
- Looks up affiliate ID from referral code
- Creates user with `referred_by` field set to affiliate ID

### 3. User Makes Purchase
- Commission is calculated
- Stored in `affiliate_commissions` table
- Linked to affiliate via `affiliate_id`

### 4. Affiliate Views Dashboard
- Query joins `users` and `affiliates` via `referred_by`
- Shows all users where `referred_by = affiliate_id`
- Displays their purchase history

## 🧪 Testing Steps

### Test 1: Database Fix
1. Visit `fix-referral-column.php`
2. Verify column is added
3. Check for success messages

### Test 2: New Signup with Referral
1. Get affiliate referral link
2. Open in incognito/private window
3. Sign up new account
4. Check database: `SELECT * FROM users WHERE email = 'newemail@test.com'`
5. Verify `referred_by` field is set

### Test 3: Affiliate Dashboard
1. Login as affiliate
2. Go to affiliate dashboard
3. Check "My Referrals & Their Purchases" section
4. Should show referred users
5. Click "View Purchases" button
6. Should show purchase details

### Test 4: Purchase Tracking
1. Login as referred user
2. Make a purchase
3. Login as affiliate
4. Check dashboard
5. Should see new commission

## 📝 Manual Database Fix (Alternative)

If the script doesn't work, run this SQL manually in phpMyAdmin:

```sql
-- Add referred_by column
ALTER TABLE users 
ADD COLUMN referred_by INT NULL AFTER role;

-- Add foreign key (optional, may fail if data integrity issues)
ALTER TABLE users 
ADD CONSTRAINT fk_referred_by 
FOREIGN KEY (referred_by) REFERENCES affiliates(id) 
ON DELETE SET NULL;

-- Verify column was added
DESCRIBE users;
```

## 🔧 Troubleshooting

### Issue: Foreign Key Constraint Fails

**Error**: "Cannot add foreign key constraint"

**Solution**:
1. Check if `affiliates` table exists
2. Verify `affiliates.id` is INT type
3. Check for orphaned data (users with invalid affiliate IDs)

**Fix**:
```sql
-- Remove foreign key requirement
ALTER TABLE users 
ADD COLUMN referred_by INT NULL;

-- Don't add foreign key constraint
```

### Issue: Column Already Exists

**Error**: "Duplicate column name 'referred_by'"

**Solution**: Column already exists, no action needed. The error in affiliate-dashboard.php was likely a different issue.

### Issue: Existing Users Not Linked

**Problem**: Users signed up before this fix don't have `referred_by` set

**Solution**: 
```sql
-- Update existing users based on affiliate_referrals table
UPDATE users u
JOIN affiliate_referrals ar ON u.id = ar.referred_user_id
JOIN affiliates a ON ar.affiliate_id = a.id
SET u.referred_by = a.id
WHERE u.referred_by IS NULL;
```

## 📋 Files Modified

1. ✅ **fix-referral-column.php** (NEW)
   - Database fix script
   - Adds missing column
   - Shows table structure

2. ✅ **signup.php** (MODIFIED)
   - Updated user creation
   - Sets referred_by field
   - Links users to affiliates

3. ✅ **affiliate-dashboard.php** (ALREADY UPDATED)
   - Uses referred_by column
   - Shows referral details
   - Displays purchase history

4. ✅ **get-referral-purchases.php** (ALREADY CREATED)
   - API endpoint for purchase data
   - Uses referred_by for validation

## ✅ Verification Checklist

After applying fix:
- [ ] Run fix-referral-column.php
- [ ] See success message
- [ ] Check users table structure
- [ ] Test new signup with referral link
- [ ] Verify referred_by is set in database
- [ ] Login as affiliate
- [ ] View affiliate dashboard
- [ ] See referrals list
- [ ] Click "View Purchases"
- [ ] See purchase details
- [ ] No errors in browser console
- [ ] No PHP errors

## 🎯 Expected Results

### Before Fix:
❌ Fatal error: Unknown column 'u.referred_by'
❌ Affiliate dashboard crashes
❌ Cannot view referrals

### After Fix:
✅ No errors
✅ Affiliate dashboard loads
✅ Referrals list displays
✅ Purchase details show
✅ New signups properly tracked

## 📞 Next Steps

1. **Run the fix**: Visit `fix-referral-column.php`
2. **Test signup**: Create test account with referral link
3. **Verify dashboard**: Check affiliate dashboard works
4. **Clean up**: Delete `fix-referral-column.php` after successful fix

---

**Once fixed, your affiliate referral tracking system will be fully functional! 🎉**
