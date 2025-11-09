# Fix Affiliate Commission Display Issue

## ❌ Problem

Admin panel shows commission data for users who haven't referred anyone or made any sales.

## 🔍 Root Causes

1. **Incorrect Initial Values**: When affiliates are created, fields like `total_referrals`, `total_sales`, `total_earnings` might have incorrect default values
2. **Stale Data**: Statistics not updated when referrals/sales occur
3. **Orphaned Records**: Affiliate records without corresponding users
4. **Invalid Referrals**: Users with `referred_by` pointing to non-existent affiliates

## ✅ Solutions Applied

### 1. Data Cleanup Script Created
**File**: `fix-affiliate-data.php`

This script will:
- Recalculate all affiliate statistics from actual data
- Count real referrals from `users` table
- Count real sales from `orders` table
- Calculate actual earnings from `affiliate_commissions` table
- Fix orphaned and invalid records
- Display comprehensive summary

### 2. Admin Display Updated
**File**: `admin/affiliates.php`

Updated to:
- Show badges for non-zero values
- Display "0" in muted text for zero values
- Highlight earnings with color coding
- Make it clear when there's no activity

## 🚀 How to Fix

### Step 1: Run Data Cleanup Script

Visit this URL in your browser:
```
http://localhost/GyanBazaar/fix-affiliate-data.php
```

This will:
1. ✅ Recalculate all affiliate statistics
2. ✅ Fix orphaned records
3. ✅ Clean up invalid referrals
4. ✅ Show detailed summary

### Step 2: Verify Admin Panel

Visit:
```
http://localhost/GyanBazaar/admin/affiliates.php
```

You should now see:
- Correct referral counts
- Accurate sales numbers
- Proper commission amounts
- Clear visual indicators (badges for active, muted for zero)

### Step 3: Test Affiliate Dashboard

Visit:
```
http://localhost/GyanBazaar/affiliate-dashboard.php
```

Verify:
- Stats match admin panel
- Referrals list is accurate
- Commission history is correct

## 📊 What Gets Fixed

### Before Fix:
❌ All affiliates show commission data
❌ Zero values displayed as regular numbers
❌ Confusing to identify active affiliates
❌ Stale or incorrect statistics

### After Fix:
✅ Only affiliates with activity show data
✅ Zero values clearly marked as "0" (muted)
✅ Active affiliates highlighted with badges
✅ Accurate, real-time statistics

## 🎨 Visual Improvements

### Referrals Column:
- **Has Referrals**: Blue badge with number
- **No Referrals**: Muted "0"

### Sales Column:
- **Has Sales**: Green badge with number
- **No Sales**: Muted "0"

### Earnings Column:
- **Has Earnings**: Green amount + yellow pending
- **No Earnings**: Muted "₹0.00"

## 📋 Data Recalculation Logic

### Total Referrals:
```sql
SELECT COUNT(*) 
FROM users 
WHERE referred_by = [affiliate_id]
```

### Total Sales:
```sql
SELECT COUNT(DISTINCT o.id) 
FROM orders o 
JOIN users u ON o.user_id = u.id 
WHERE u.referred_by = [affiliate_id] 
AND o.payment_status = 'completed'
```

### Total Earnings:
```sql
SELECT SUM(commission_amount) 
FROM affiliate_commissions 
WHERE affiliate_id = [affiliate_id] 
AND status IN ('approved', 'paid')
```

### Pending Earnings:
```sql
SELECT SUM(commission_amount) 
FROM affiliate_commissions 
WHERE affiliate_id = [affiliate_id] 
AND status = 'pending'
```

## 🔧 Manual Database Fix (Alternative)

If you prefer to run SQL manually in phpMyAdmin:

```sql
-- Recalculate for all affiliates
UPDATE affiliates a
LEFT JOIN (
    SELECT referred_by, COUNT(*) as ref_count
    FROM users
    WHERE referred_by IS NOT NULL
    GROUP BY referred_by
) r ON a.id = r.referred_by
LEFT JOIN (
    SELECT u.referred_by, COUNT(DISTINCT o.id) as sales_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.payment_status = 'completed'
    AND u.referred_by IS NOT NULL
    GROUP BY u.referred_by
) s ON a.id = s.referred_by
LEFT JOIN (
    SELECT affiliate_id, 
           SUM(CASE WHEN status IN ('approved', 'paid') THEN commission_amount ELSE 0 END) as total_earn,
           SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_earn
    FROM affiliate_commissions
    GROUP BY affiliate_id
) c ON a.id = c.affiliate_id
SET 
    a.total_referrals = COALESCE(r.ref_count, 0),
    a.total_sales = COALESCE(s.sales_count, 0),
    a.total_earnings = COALESCE(c.total_earn, 0),
    a.pending_earnings = COALESCE(c.pending_earn, 0);

-- Clean up invalid referrals
UPDATE users u
LEFT JOIN affiliates a ON u.referred_by = a.id
SET u.referred_by = NULL
WHERE u.referred_by IS NOT NULL AND a.id IS NULL;
```

## 🧪 Testing Checklist

### Test 1: Data Cleanup
- [ ] Run fix-affiliate-data.php
- [ ] See recalculation messages
- [ ] Check summary table
- [ ] Verify no errors

### Test 2: Admin Panel Display
- [ ] Login as admin
- [ ] Go to admin/affiliates.php
- [ ] Check affiliates with no referrals show "0" (muted)
- [ ] Check affiliates with referrals show badges
- [ ] Verify earnings display correctly

### Test 3: Affiliate Dashboard
- [ ] Login as affiliate
- [ ] Check stats match admin panel
- [ ] Verify referrals list is accurate
- [ ] Check commission history

### Test 4: New Referral
- [ ] Get affiliate link
- [ ] Sign up new user
- [ ] Check admin panel updates
- [ ] Verify affiliate dashboard updates

### Test 5: New Sale
- [ ] Login as referred user
- [ ] Make a purchase
- [ ] Check commission is created
- [ ] Verify stats update

## 📊 Expected Results

### Admin Affiliates Page:

```
┌────────────────────────────────────────────────────────────┐
│ ID │ Name      │ Referrals │ Sales │ Earnings              │
├────────────────────────────────────────────────────────────┤
│ 1  │ John Doe  │ [5]       │ [3]   │ ₹1,500.00            │
│    │           │           │       │ Pending: ₹500.00      │
├────────────────────────────────────────────────────────────┤
│ 2  │ Jane Smith│ 0         │ 0     │ ₹0.00                │
│    │           │ (muted)   │(muted)│ (muted)               │
└────────────────────────────────────────────────────────────┘

Legend:
[5] = Blue badge (has referrals)
[3] = Green badge (has sales)
0 (muted) = Gray text (no activity)
```

## 🔒 Data Integrity Checks

The fix script performs these checks:

1. **Orphaned Affiliates**: Affiliates without users
2. **Invalid Referrals**: Users pointing to non-existent affiliates
3. **Stale Statistics**: Outdated counts and amounts
4. **Missing Commissions**: Sales without commission records

## 📞 Troubleshooting

### Issue: Script Times Out

**Solution**: Increase PHP execution time
```php
// Add to top of fix-affiliate-data.php
set_time_limit(300); // 5 minutes
```

### Issue: Statistics Still Wrong

**Solution**: 
1. Check if `referred_by` column exists (run fix-referral-column.php first)
2. Verify affiliate_commissions table has data
3. Check orders have payment_status = 'completed'

### Issue: Permissions Error

**Solution**: Make sure you're logged in as admin

## 📁 Files Modified

1. ✅ **fix-affiliate-data.php** (NEW)
   - Data cleanup script
   - Recalculates all statistics
   - Fixes orphaned records

2. ✅ **admin/affiliates.php** (MODIFIED)
   - Updated display logic
   - Added badges for non-zero values
   - Improved visual clarity

## ✅ Summary

**Problem**: Incorrect commission data displayed for inactive affiliates

**Solution**: 
1. Recalculate all statistics from actual data
2. Clean up orphaned and invalid records
3. Improve visual display with badges and muted text

**Result**: Accurate, clear affiliate statistics in admin panel

---

**Run the fix now**: http://localhost/GyanBazaar/fix-affiliate-data.php

**Then check**: http://localhost/GyanBazaar/admin/affiliates.php
