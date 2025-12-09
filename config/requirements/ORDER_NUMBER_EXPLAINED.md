i9# 📦 Order Number System

## Format
```
ORD-YYYYMMDD-XXXXXX
```

### Example Order Numbers:
- `ORD-20251126-A3F2B1` - Order placed on Nov 26, 2025
- `ORD-20251127-C8D4E9` - Order placed on Nov 27, 2025
- `ORD-20251201-F1A5C2` - Order placed on Dec 1, 2025

## Components:

| Part | Description | Example |
|------|-------------|---------|
| `ORD` | Prefix (Order) | ORD |
| `YYYYMMDD` | Date (Year-Month-Day) | 20251126 |
| `XXXXXX` | Unique 6-char code | A3F2B1 |

## Benefits:

✅ **Human-readable** - Easy to communicate over phone/email
✅ **Sortable by date** - Orders naturally sort chronologically
✅ **Unique** - Each order gets a unique identifier
✅ **Professional** - Better than just showing database IDs
✅ **Trackable** - Customers can easily reference their order

## Where It Appears:

1. **Order Confirmation Page** - After checkout
2. **Customer Order History** - `account/orders.php`
3. **Admin Order List** - `admin/orders/index.php`
4. **Order Details Page** - `admin/orders/view.php`
5. **Email Receipts** - (when implemented)
6. **Invoices** - (when implemented)

## Database Storage:

```sql
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,        -- Internal ID: 1, 2, 3...
    order_number VARCHAR(50) UNIQUE,                -- Customer-facing: ORD-20251126-A3F2B1
    user_id INT NOT NULL,
    address_id INT,
    -- ... other fields
);
```

## Code Implementation:

```php
// Generate unique order number
$order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

// Verify uniqueness (extremely rare collision, but good practice)
$check = $pdo->prepare("SELECT order_id FROM orders WHERE order_number = ?");
$check->execute([$order_number]);

// Regenerate if exists
while ($check->fetch()) {
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $check->execute([$order_number]);
}

// Store in database
INSERT INTO orders (user_id, order_number, address_id, ...)
VALUES (?, ?, ?, ...)
```

## Display Logic:

### Admin Panel:
```php
// Primary display: order_number
// Fallback: order_id if order_number is empty
<?= !empty($order['order_number']) ? $order['order_number'] : 'Order #' . $order['order_id'] ?>
```

### Customer View:
```php
// Same logic - graceful fallback for old orders without order_number
<?= !empty($order['order_number']) ? $order['order_number'] : 'Order #' . $order['order_id'] ?>
```

## Backward Compatibility:

✅ **Old orders without order_number** - Will display as "Order #123"
✅ **New orders with order_number** - Will display as "ORD-20251126-A3F2B1"
✅ **No data loss** - Existing orders continue to work

## Future Enhancements:

🔮 **Sequential numbering:** `ORD-00001`, `ORD-00002`
🔮 **Branch codes:** `BLR-ORD-20251126-001` (Bangalore branch)
🔮 **Barcode integration:** Generate barcodes from order numbers
🔮 **QR codes:** For package scanning
🔮 **Customer tracking:** Public tracking page using order number

## Notes:

- Order numbers are generated **once** when order is placed
- They are **immutable** - never changed after creation
- They are **globally unique** across the entire system
- Format can be customized by modifying the generation logic in `checkout/place_order.php`

