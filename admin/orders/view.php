<?php
require_once "../../auth/check_auth.php";
require_once "../../config/config.php";
include __DIR__ . "/../includes/header.php";

$order_id = $_GET['id'];

$order = $pdo->prepare("
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$order->execute([$order_id]);
$order = $order->fetch();

$items = $pdo->prepare("
    SELECT oi.*, p.name, p.thumbnail
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$items->execute([$order_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

// Debug: Check if order exists and items are present
if (!$order) {
    die("Order not found!");
}

$invoice_no = !empty($order['order_number']) ? $order['order_number'] : 'INV-' . $order_id;


// Status color mapping
$status_colors = [
    'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
    'shipped' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
    'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z']
];
$current_status = $status_colors[$order['status']] ?? $status_colors['pending'];

// Calculate subtotal
$subtotal = 0;
foreach($items as $item) {
    $subtotal += $item['price_at_time'] * $item['quantity'];
}
?>

<style>
/* Only visible when printing */
@media print {
  body * {
    visibility: hidden;
  }

  #printableInvoice, #printableInvoice * {
    visibility: visible;
  }

  #printableInvoice {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 30px;
  }
}
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center space-x-3">
            <a href="index.php" class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <?= !empty($order['order_number']) ? htmlspecialchars($order['order_number']) : 'Order #' . $order_id ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Order ID: #<?= $order_id ?> • Placed on <?= date("M d, Y", strtotime($order['order_date'])) ?> at <?= date("h:i A", strtotime($order['order_date'])) ?>
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="printInvoice()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-all duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                <span class="hidden sm:inline">Print Invoice</span>
                <span class="sm:hidden">Print</span>
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
    <!-- Main Content - Left Side -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Order Status Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Order Status</h3>
            </div>
            <div class="p-4 md:p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 <?= $current_status['bg'] ?> rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 <?= $current_status['text'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $current_status['icon'] ?>"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Current Status</p>
                            <p class="text-2xl font-bold text-gray-800"><?= ucfirst($order['status']) ?></p>
                        </div>
                    </div>
                    <form method="POST" action="update_status.php">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <input type="hidden" name="redirect" value="view.php?id=<?= $order_id ?>">
                        <select name="status" class="px-4 py-2.5 text-sm font-medium border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" onchange="if(confirm('Update order status?')) this.form.submit();">
                            <?php
                            $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                            foreach($statuses as $s):
                            ?>
                            <option value="<?= $s ?>" <?= ($order['status'] == $s ? 'selected' : '') ?>>
                                <?= ucfirst($s) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <!-- Order Timeline -->
                <div class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    <div class="relative flex items-start mb-4">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center z-10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="font-semibold text-gray-800">Order Placed</p>
                            <p class="text-sm text-gray-500"><?= date("F d, Y - h:i A", strtotime($order['order_date'])) ?></p>
                        </div>
                    </div>
                    
                    <div class="relative flex items-start mb-4">
                        <div class="w-8 h-8 <?= in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'bg-green-500' : 'bg-gray-300' ?> rounded-full flex items-center justify-center z-10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="font-semibold text-gray-800">Processing</p>
                            <p class="text-sm text-gray-500">Order is being prepared</p>
                        </div>
                    </div>
                    
                    <div class="relative flex items-start mb-4">
                        <div class="w-8 h-8 <?= in_array($order['status'], ['shipped', 'delivered']) ? 'bg-green-500' : 'bg-gray-300' ?> rounded-full flex items-center justify-center z-10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="font-semibold text-gray-800">Shipped</p>
                            <p class="text-sm text-gray-500">Order is on the way</p>
                        </div>
                    </div>
                    
                    <div class="relative flex items-start">
                        <div class="w-8 h-8 <?= $order['status'] == 'delivered' ? 'bg-green-500' : 'bg-gray-300' ?> rounded-full flex items-center justify-center z-10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="font-semibold text-gray-800">Delivered</p>
                            <p class="text-sm text-gray-500">Order has been delivered</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-800">Order Items (<?= count($items) ?>)</h3>
            </div>
            <div class="p-6">
                <?php if (empty($items)): ?>
                <div class="text-center py-12">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500 font-medium">No items found in this order</p>
                    <p class="text-sm text-gray-400 mt-2">This order may have been created without items or the items were deleted.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($items as $item): ?>
                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="w-20 h-20 bg-white rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <?php if(isset($item['thumbnail']) && $item['thumbnail']): ?>
                                <img src="../../uploads/products/<?= htmlspecialchars($item['thumbnail']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-800 mb-1"><?= htmlspecialchars($item['name']) ?></h4>
                            <p class="text-sm text-gray-500">Quantity: <?= $item['quantity'] ?> × ₹<?= number_format($item['price_at_time'], 2) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-800">₹<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Address Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-800">Shipping Address</h3>
            </div>
            <div class="p-6">
                <div class="flex items-start space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar - Right Side -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Customer Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-800">Customer Information</h3>
            </div>
            <div class="p-6">
                <div class="flex items-center space-x-4 mb-6">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center text-white text-2xl font-bold">
                        <?= strtoupper(substr($order['full_name'], 0, 1)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-lg font-semibold text-gray-800 truncate"><?= htmlspecialchars($order['full_name']) ?></p>
                        <p class="text-sm text-gray-500">Customer</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500">Email</p>
                            <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($order['email']) ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500">Phone</p>
                            <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($order['phone']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-800">Order Summary</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold text-gray-800">₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-semibold text-green-600">Free</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Tax</span>
                        <span class="font-semibold text-gray-800">₹0.00</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-base font-semibold text-gray-800">Total</span>
                            <span class="text-2xl font-bold text-gray-900">₹<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-sm overflow-hidden text-white p-4 md:p-6">
            <h3 class="text-base md:text-lg font-semibold mb-3 md:mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="window.print()" class="w-full flex items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-medium transition-all backdrop-blur-sm text-sm">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    <span class="hidden sm:inline">Print Invoice</span>
                    <span class="sm:hidden">Print</span>
                </button>
                <a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="w-full flex items-center justify-center px-3 md:px-4 py-2 md:py-2.5 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-medium transition-all backdrop-blur-sm text-sm">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span class="hidden sm:inline">Email Customer</span>
                    <span class="sm:hidden">Email</span>
                </a>
                <a href="delete.php?id=<?= $order_id ?>" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')" class="w-full flex items-center justify-center px-4 py-2.5 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-medium transition-all backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Order
                </a>
            </div>
        </div>
    </div>
</div>


<!-- ================= PRINTABLE INVOICE ================= -->
<div id="printableInvoice" class="hidden">

  <div class="max-w-4xl mx-auto bg-white p-8 text-gray-800">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-8 border-b pb-4">
      <div>
        <h1 class="text-3xl font-bold text-red-600">iHub Electronics</h1>
        <p class="text-sm text-gray-500">Mangaluru, Karnataka</p>
        <p class="text-sm text-gray-500">support@ihub.com</p>
      </div>

      <div class="text-right">
        <h2 class="text-2xl font-bold">INVOICE</h2>
        <p>Invoice No: <strong><?= $invoice_no ?></strong></p>
        <p>Date: <?= date("d M Y", strtotime($order['order_date'])) ?></p>
      </div>
    </div>


    <!-- Customer Details -->
    <div class="mb-4 md:mb-6 grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
      <div>
        <h3 class="font-semibold mb-2">BILL TO:</h3>
        <p class="font-semibold"><?= htmlspecialchars($order['full_name']) ?></p>
        <p><?= htmlspecialchars($order['email']) ?></p>
        <p><?= htmlspecialchars($order['phone']) ?></p>
        <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
      </div>

      <div class="text-right">
        <h3 class="font-semibold mb-2">ORDER INFO:</h3>
        <p>Order ID: #<?= $order_id ?></p>
        <p>Status: <?= ucfirst($order['status']) ?></p>
      </div>
    </div>


    <!-- Items Table -->
    <table class="w-full border border-gray-300 mb-6 text-sm">
      <thead class="bg-gray-100">
        <tr>
          <th class="border px-3 py-2 text-left">Product</th>
          <th class="border px-3 py-2 text-center">Qty</th>
          <th class="border px-3 py-2 text-right">Price</th>
          <th class="border px-3 py-2 text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td class="border px-3 py-2"><?= htmlspecialchars($item['name']) ?></td>
          <td class="border px-3 py-2 text-center"><?= $item['quantity'] ?></td>
          <td class="border px-3 py-2 text-right">₹<?= number_format($item['price_at_time'],2) ?></td>
          <td class="border px-3 py-2 text-right">
            ₹<?= number_format($item['price_at_time'] * $item['quantity'],2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>


    <!-- Total Section -->
    <div class="flex justify-end">
      <table class="w-1/2 text-sm">
        <tr>
          <td class="py-2">Subtotal:</td>
          <td class="py-2 text-right font-semibold">₹<?= number_format($subtotal,2) ?></td>
        </tr>
        <tr>
          <td class="py-2">Shipping:</td>
          <td class="py-2 text-right text-green-600 font-semibold">FREE</td>
        </tr>
        <tr class="border-t text-lg">
          <td class="py-3 font-bold">Total:</td>
          <td class="py-3 text-right font-bold">₹<?= number_format($order['total_amount'],2) ?></td>
        </tr>
      </table>
    </div>

    <!-- Footer -->
    <div class="mt-10 text-center text-xs text-gray-500 border-t pt-4">
      <p>Thank you for shopping with iHub Electronics.</p>
      <p>This is a system generated invoice.</p>
    </div>
  </div>
</div>


<script>
function printInvoice() {
  document.getElementById("printableInvoice").classList.remove("hidden");
  window.print();
}
</script>



<?php include __DIR__ . "/../includes/footer.php"; ?>