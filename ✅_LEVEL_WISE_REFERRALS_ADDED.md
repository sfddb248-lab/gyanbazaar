# ✅ Level-wise Referral Display Added

## New Feature: MLM Level Breakdown

The affiliate dashboard now displays a comprehensive level-wise breakdown of referrals and earnings.

### 📊 Level-wise Table Displays:

#### For Each Level (1-5):
1. **Level Badge** - Color-coded level indicator
2. **Commission Rate** - Percentage for that level
3. **Referrals Count** - Number of users at this level
4. **Commissions Count** - Number of transactions
5. **Total Earnings** - All-time earnings from this level
6. **Pending Amount** - Unpaid commissions
7. **Paid Amount** - Completed payouts

### 🎨 Visual Features:

**Color-coded Levels:**
- Level 1 (Direct) - Blue/Primary
- Level 2 - Green/Success
- Level 3 - Cyan/Info
- Level 4 - Orange/Warning
- Level 5 - Red/Danger

**Table Sections:**
1. **Header** - Gradient primary background with icon
2. **Data Rows** - Each level with detailed stats
3. **Total Row** - Bold summary of all levels
4. **Footer** - Quick insights (average, best level, depth)

### 📈 Statistics Shown:

#### Per Level:
- Commission rate (e.g., 10%, 5%, 2%)
- Number of referrals
- Number of commissions
- Total earnings
- Pending earnings
- Paid earnings

#### Totals:
- Sum of all referrals across levels
- Sum of all commissions
- Total earnings from all levels
- Total pending amount
- Total paid amount

#### Footer Insights:
- **Average per Level** - Mean earnings per level
- **Best Performing Level** - Level with highest earnings
- **MLM Depth** - Number of active levels

### 🔢 Calculations:

**Level 1 (Direct Referrals):**
```sql
SELECT COUNT(DISTINCT referred_user_id) 
FROM affiliate_referrals 
WHERE affiliate_id = ? AND referred_user_id IS NOT NULL
```

**Level 2+ (Indirect Referrals):**
```sql
SELECT COUNT(*) 
FROM affiliate_mlm_structure 
WHERE parent_affiliate_id = ? AND level = ?
```

**Earnings by Level:**
```sql
SELECT 
    COUNT(*) as commission_count,
    SUM(commission_amount) as total_earnings,
    SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid
FROM affiliate_commissions 
WHERE affiliate_id = ? AND level = ?
```

### 📱 Responsive Design:

- **Desktop**: Full table with all columns
- **Tablet**: Scrollable table
- **Mobile**: Horizontal scroll enabled

### 🎯 User Benefits:

1. **Clear MLM Structure**
   - See exactly how many people at each level
   - Understand earnings distribution

2. **Performance Analysis**
   - Identify best performing levels
   - Track growth at each tier

3. **Financial Transparency**
   - See pending vs paid amounts per level
   - Understand commission breakdown

4. **Strategic Planning**
   - Focus on levels with most potential
   - Track MLM network growth

### 💡 Example Display:

```
Level-wise Referral & Earnings Breakdown
Multi-Level Marketing (MLM) Performance

Level | Rate | Referrals | Commissions | Total    | Pending | Paid
------|------|-----------|-------------|----------|---------|--------
  1   | 10%  |    25     |     45      | ₹4,500   | ₹1,200  | ₹3,300
  2   |  5%  |    15     |     28      | ₹1,400   | ₹400    | ₹1,000
  3   |  2%  |     8     |     12      | ₹240     | ₹80     | ₹160
------|------|-----------|-------------|----------|---------|--------
TOTAL |      |    48     |     85      | ₹6,140   | ₹1,680  | ₹4,460

Average per Level: ₹2,046.67
Best Performing Level: Level 1
MLM Depth: 3 Levels
```

### 🎨 Styling:

- Gradient header with primary color
- Hover effects on table rows
- Badge indicators for levels
- Color-coded amounts (warning for pending, success for paid)
- Shadow effects for depth
- Responsive table with horizontal scroll

### ✅ Implementation Complete

The level-wise referral breakdown is now live on the affiliate dashboard!

**Location:** After the 8 statistics cards, before performance metrics

**Access:** `http://localhost/DigitalKhazana/affiliate-dashboard.php`

---

**Last Updated:** November 5, 2025
**Status:** ✅ LIVE
