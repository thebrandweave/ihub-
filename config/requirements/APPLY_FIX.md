# 🔧 Database Fix: Orders and Addresses

## Problem
When placing orders, addresses and order items weren't being stored properly because:
1. ❌ The `addresses` table didn't exist in the database
2. ❌ The `orders` table was missing the `address_id` column
3. ❌ Migration files weren't applied

## ✅ Solution

### Option 1: Run Migration (Recommended for existing databases)
This will add the missing tables and columns **without losing existing data**.

**Steps:**
1. Open phpMyAdmin or your MySQL client
2. Select your database: `ihub_electronics`
3. Go to the **SQL** tab
4. Copy and paste the contents of: `migration/fix_orders_and_addresses.sql`
5. Click **Go** to execute

### Option 2: Fresh Install
If you're starting fresh or want to recreate the database:

**Steps:**
1. **Backup your data** (if you have existing data)
2. Drop the database: `DROP DATABASE ihub_electronics;`
3. Run the updated `schema.sql` file which now includes everything

## 🧪 Verify the Fix

After running the migration, verify the changes:

```sql
-- Check if addresses table exists
SHOW TABLES LIKE 'addresses';

-- Check orders table structure
DESCRIBE orders;

-- Should show: order_id, order_number, user_id, address_id, order_date, total_amount, status, payment_status, payment_method, shipping_address, delivered_at, cod_verified
```

## 📝 What Changed

### Files Updated:
1. ✅ `config/requirements/schema.sql` - Complete database structure
2. ✅ `checkout/place_order.php` - Improved order placement with transactions
3. ✅ `migration/fix_orders_and_addresses.sql` - Migration for existing databases

### Database Changes:
- ✅ Added `addresses` table with full address fields
- ✅ Added `address_id` column to `orders` table
- ✅ Added `order_number`, `delivered_at`, `cod_verified` columns to `orders`
- ✅ Added foreign key constraint from orders to addresses
- ✅ Added transaction support and error handling in place_order.php
- ✅ Added stock validation before order placement
- ✅ **Order numbers auto-generated** (Format: `ORD-20251126-A3F2B1`)

### PHP Code Improvements:
- ✅ Order number generation with uniqueness check
- ✅ Transaction rollback on errors
- ✅ Stock validation before order placement
- ✅ Better error messages and logging
- ✅ Display order numbers in admin and customer views

## 🎯 Test the Fix

1. **Create an address:**
   - Go to Account → My Addresses
   - Add a new address

2. **Place an order:**
   - Add products to cart
   - Go to checkout
   - Select an address
   - Place order

3. **Verify in database:**
   ```sql
   SELECT * FROM orders ORDER BY order_id DESC LIMIT 1;
   SELECT * FROM order_items WHERE order_id = [last_order_id];
   ```

## 🚨 Troubleshooting

**Error: "Table 'addresses' doesn't exist"**
→ Run the migration SQL file

**Error: "Unknown column 'address_id' in 'field list'"**
→ Run the migration SQL file to add missing columns

**Order items not showing**
→ Check that products exist in the cart before placing order
→ Check error logs for transaction failures

## 📞 Need Help?
Check the PHP error log for detailed error messages if orders still fail.

