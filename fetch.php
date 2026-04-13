<?php
require 'dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    header('Content-Type: application/json');

    $customer_name = trim($_POST['customer_name'] ?? '');
    $product_id    = (int) ($_POST['product_id']    ?? 0);
    $quantity      = (int) ($_POST['quantity']       ?? 0);
    $unit_price    = (float) ($_POST['unit_price']   ?? 0);

    if ($customer_name === '' || $product_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields.']);
        exit;
    }

    try {
        // Look up customer by name (case-insensitive); insert if not found
        $findStmt = $pdo->prepare("SELECT customer_id FROM customers WHERE LOWER(full_name) = LOWER(:name) LIMIT 1");
        $findStmt->bindParam(':name', $customer_name);
        $findStmt->execute();
        $existing = $findStmt->fetch();

        if ($existing) {
            $customer_id = (int) $existing['customer_id'];
        } else {
            $insStmt = $pdo->prepare("INSERT INTO customers (full_name) VALUES (:name)");
            $insStmt->bindParam(':name', $customer_name);
            $insStmt->execute();
            $customer_id = (int) $pdo->lastInsertId();
        }

        // Check stock
        $stockStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = :id");
        $stockStmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stockStmt->execute();
        $product = $stockStmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit;
        }
        if ($product['stock_quantity'] < $quantity) {
            echo json_encode([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $product['stock_quantity'] . ' units.'
            ]);
            exit;
        }

        $subtotal = round($unit_price * $quantity, 2);

        $pdo->beginTransaction();

        // Insert order
        $orderStmt = $pdo->prepare("INSERT INTO orders (customer_id, status) VALUES (:customer_id, 'Pending')");
        $orderStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $orderStmt->execute();
        $order_id = (int) $pdo->lastInsertId();

        // Insert order item
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, subtotal)
                                   VALUES (:order_id, :product_id, :quantity, :subtotal)");
        $itemStmt->bindParam(':order_id',   $order_id,   PDO::PARAM_INT);
        $itemStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $itemStmt->bindParam(':quantity',   $quantity,   PDO::PARAM_INT);
        $itemStmt->bindParam(':subtotal',   $subtotal);
        $itemStmt->execute();

        // Deduct stock
        $deductStmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - :qty WHERE product_id = :id");
        $deductStmt->bindParam(':qty', $quantity, PDO::PARAM_INT);
        $deductStmt->bindParam(':id',  $product_id, PDO::PARAM_INT);
        $deductStmt->execute();

        $pdo->commit();

        echo json_encode([
            'success'       => true,
            'order_id'      => $order_id,
            'subtotal'      => $subtotal,
            'customer_name' => $customer_name,
            'message'       => 'Order placed successfully.'
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search !== '') {
        $sql  = "SELECT * FROM products WHERE product_name LIKE :search ORDER BY product_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search', '%' . $search . '%');
    } else {
        $sql  = "SELECT * FROM products ORDER BY product_name ASC";
        $stmt = $pdo->prepare($sql);
    }
    $stmt->execute();
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — PC Shop</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid #e0e0e0;
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center;
            padding: 0 40px; height: 52px; gap: 28px;
        }
        nav .brand { font-size: 17px; font-weight: 700; letter-spacing: -0.4px; color: #1d1d1f; text-decoration: none; margin-right: auto; }
        nav a { font-size: 14px; color: #6e6e73; text-decoration: none; font-weight: 450; transition: color .15s; }
        nav a:hover, nav a.active { color: #1d1d1f; font-weight: 600; }

        .page { max-width: 1000px; margin: 48px auto; padding: 0 20px; }

        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
        }
        h1 {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", sans-serif;
            font-size: 26px; font-weight: 700; letter-spacing: -0.5px;
        }
        .count-badge {
            font-size: 13px; color: #6e6e73; font-weight: 500;
            background: #e8e8ea; padding: 3px 11px; border-radius: 20px;
            margin-left: 10px;
        }

        /* SEARCH BAR */
        .search-bar {
            display: flex; gap: 10px; margin-bottom: 22px; align-items: center;
        }
        .search-wrap {
            position: relative; flex: 1; max-width: 420px;
        }
        .search-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: #aeaeb2; pointer-events: none;
            font-size: 15px;
        }
        .search-input {
            width: 100%;
            font-family: inherit; font-size: 14px;
            color: #1d1d1f; background: #fff;
            border: 1px solid #e0e0e0; border-radius: 10px;
            padding: 10px 14px 10px 38px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .search-input:focus {
            border-color: #b0b0b0;
            box-shadow: 0 0 0 3px rgba(0,0,0,.06);
        }
        .search-input::placeholder { color: #aeaeb2; }
        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-family: inherit; font-size: 14px; font-weight: 500;
            padding: 9px 20px; border-radius: 10px; border: none;
            cursor: pointer; text-decoration: none;
            transition: background .15s, transform .15s, box-shadow .15s;
        }
        .btn-primary { background: #1d1d1f; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .btn-primary:hover { background: #3a3a3c; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        .btn-outline { background: #fff; color: #1d1d1f; border: 1px solid #d0d0d5; }
        .btn-outline:hover { background: #f5f5f7; }
        .btn-sm { font-size: 12px; padding: 6px 13px; border-radius: 7px; }
        .btn-order { background: #1d1d1f; color: #fff; }
        .btn-order:hover { background: #3a3a3c; transform: translateY(-1px); }
        .btn-view { background: #f0f0f2; color: #1d1d1f; border: 1px solid #e0e0e0; }
        .btn-view:hover { background: #e8e8ea; }

        /* TABLE */
        .table-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        thead tr { background: #1d1d1f; }
        th {
            padding: 14px 18px; text-align: left;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.8px; text-transform: uppercase; color: #fff;
        }
        th:last-child { text-align: center; }
        td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f2;
            color: #1d1d1f; vertical-align: middle;
        }
        td:last-child { text-align: center; }
        tbody tr { background: #fff; transition: background .12s; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f0f0f2; }

        .product-name { font-weight: 600; }
        .category-tag {
            display: inline-block; background: #f0f0f2; color: #3a3a3c;
            font-size: 12px; font-weight: 500; padding: 2px 9px; border-radius: 20px;
        }
        .price { font-weight: 600; font-variant-numeric: tabular-nums; }
        .stock-low { color: #c0392b; font-weight: 600; }
        .stock-ok  { color: #3a3a3c; font-weight: 500; }
        .actions { display: flex; gap: 6px; justify-content: center; }

        .empty {
            text-align: center; padding: 64px 24px;
            color: #aeaeb2; font-size: 15px;
        }

        /* SEARCH RESULT TAG */
        .search-result-tag {
            font-size: 13px; color: #6e6e73;
            background: #f0f0f2; padding: 4px 12px;
            border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;
            margin-bottom: 16px;
        }
        .search-result-tag a { color: #6e6e73; text-decoration: none; font-weight: 600; }
        .search-result-tag a:hover { color: #1d1d1f; }

        /* MODAL OVERLAY */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 24px 64px rgba(0,0,0,.18);
            padding: 36px 40px;
            width: 100%; max-width: 440px;
            animation: modalIn .2s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(16px) scale(.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 24px;
        }
        .modal-title { font-size: 18px; font-weight: 700; letter-spacing: -0.3px; }
        .modal-subtitle { font-size: 13px; color: #6e6e73; margin-top: 3px; }
        .modal-close {
            background: #f0f0f2; border: none; border-radius: 50%;
            width: 28px; height: 28px; font-size: 16px; line-height: 1;
            cursor: pointer; color: #6e6e73; display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .modal-close:hover { background: #e0e0e2; color: #1d1d1f; }

        .form-group { margin-bottom: 18px; }
        label {
            display: block; font-size: 13px; font-weight: 600;
            color: #6e6e73; margin-bottom: 7px;
        }
        .form-hint {
            font-size: 12px; color: #aeaeb2; margin-top: 5px;
        }
        input[type="text"],
        input[type="number"] {
            width: 100%; font-family: inherit; font-size: 14px;
            color: #1d1d1f; background: #fafafa;
            border: 1px solid #e0e0e0; border-radius: 10px;
            padding: 10px 14px; outline: none;
            transition: border-color .15s, box-shadow .15s;
            -webkit-appearance: none;
        }
        input[type="text"]:focus,
        input[type="number"]:focus {
            background: #fff; border-color: #b0b0b0;
            box-shadow: 0 0 0 3px rgba(0,0,0,.06);
        }
        input::placeholder { color: #aeaeb2; }

        .modal-summary {
            background: #f5f5f7; border-radius: 10px;
            padding: 14px 16px; margin-bottom: 20px;
            font-size: 13px; color: #6e6e73;
            display: flex; flex-direction: column; gap: 6px;
        }
        .modal-summary strong { color: #1d1d1f; }
        .modal-summary .subtotal-line {
            font-size: 15px; font-weight: 700; color: #1d1d1f;
            padding-top: 6px; border-top: 1px solid #e8e8ea; margin-top: 2px;
        }

        .modal-footer { display: flex; gap: 10px; }
        .modal-footer .btn { flex: 1; justify-content: center; }

        .alert-flash {
            font-size: 13px; font-weight: 600;
            padding: 11px 16px; border-radius: 10px;
            margin-bottom: 20px; display: none;
        }
        .alert-flash.show { display: block; }
        .alert-success { background: #e8f5e8; color: #2d7a2d; border: 1px solid #b8e0b8; }
        .alert-error   { background: #f5e8e8; color: #7a2d2d; border: 1px solid #e0b8b8; }
    </style>
</head>
<body>

<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="fetch.php" class="active">Order</a>
    <a href="joining_tables.php">Orders</a>
</nav>

<div class="page">

    <div class="page-header">
        <h1>
            Products
            <span class="count-badge"><?= count($results); ?> items</span>
        </h1>
    </div>

    <!-- Search Bar -->
    <form method="GET" action="fetch.php" class="search-bar">
        <div class="search-wrap">
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by product name…"
                value="<?= htmlspecialchars($search); ?>"
                autocomplete="off"
            >
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="fetch.php" class="btn btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($search): ?>
        <div class="search-result-tag">
            Results for "<strong><?= htmlspecialchars($search); ?></strong>"
            &nbsp;·&nbsp; <a href="fetch.php">✕ Clear</a>
        </div>
    <?php endif; ?>

    <div class="table-card">
        <?php if (count($results) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><span style="color:#aeaeb2;font-size:12px;">#<?= $row['product_id']; ?></span></td>
                    <td class="product-name"><?= htmlspecialchars($row['product_name']); ?></td>
                    <td><span class="category-tag"><?= htmlspecialchars($row['category']); ?></span></td>
                    <td class="price">₱<?= number_format($row['price'], 2); ?></td>
                    <td class="<?= $row['stock_quantity'] < 5 ? 'stock-low' : 'stock-ok'; ?>">
                        <?= $row['stock_quantity']; ?> units
                    </td>
                    <td>
                        <div class="actions">
                            <?php if ($row['stock_quantity'] > 0): ?>
                                <button
                                    class="btn btn-sm btn-order"
                                    onclick="openOrderModal(<?= $row['product_id']; ?>, '<?= htmlspecialchars(addslashes($row['product_name'])); ?>', <?= $row['price']; ?>, <?= $row['stock_quantity']; ?>)"
                                >Order</button>
                            <?php else: ?>
                                <span style="font-size:12px;color:#aeaeb2;font-style:italic;">Out of stock</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty">
                <?php if ($search): ?>
                    No products found matching "<strong><?= htmlspecialchars($search); ?></strong>".
                    <a href="fetch.php" style="color:#1d1d1f;font-weight:600;"> Clear search →</a>
                <?php else: ?>
                    No products available.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ORDER MODAL -->
<div class="modal-overlay" id="orderModal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title">Place Order</div>
                <div class="modal-subtitle" id="modalProductName">—</div>
            </div>
            <button class="modal-close" onclick="closeOrderModal()">✕</button>
        </div>

        <div id="flashMsg" class="alert-flash"></div>

        <div class="modal-summary">
            <div>Product: <strong id="summaryProduct">—</strong></div>
            <div>Unit Price: <strong id="summaryPrice">—</strong></div>
            <div>Available Stock: <strong id="summaryStock">—</strong></div>
            <div class="subtotal-line">Subtotal: <span id="summarySubtotal">₱0.00</span></div>
        </div>

        <div id="orderForm">
            <input type="hidden" id="orderProductId">

            <div class="form-group">
                <label for="orderCustomerName">Customer Name</label>
                <input
                    type="text"
                    id="orderCustomerName"
                    name="customer_name"
                    placeholder="e.g. Juan dela Cruz"
                    autocomplete="off"
                    required
                >
                <div class="form-hint">Type the customer's full name. A new customer record will be created if they don't exist yet.</div>
            </div>

            <div class="form-group">
                <label for="orderQty">Quantity</label>
                <input type="number" id="orderQty" name="quantity" min="1" value="1" required>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="submitOrder()">Confirm Order</button>
                <button type="button" class="btn btn-outline" onclick="closeOrderModal()">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPrice = 0;
let currentStock = 0;

function openOrderModal(productId, productName, price, stock) {
    currentPrice = parseFloat(price);
    currentStock = parseInt(stock);

    document.getElementById('orderProductId').value = productId;
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('summaryProduct').textContent = productName;
    document.getElementById('summaryPrice').textContent = '₱' + parseFloat(price).toLocaleString('en-PH', {minimumFractionDigits:2});
    document.getElementById('summaryStock').textContent = stock + ' units';
    document.getElementById('orderQty').max = stock;
    document.getElementById('orderQty').value = 1;
    document.getElementById('orderCustomerName').value = '';
    updateSubtotal();

    document.getElementById('flashMsg').className = 'alert-flash';
    document.getElementById('flashMsg').textContent = '';
    document.getElementById('orderModal').classList.add('open');

    // Focus the customer name field
    setTimeout(() => document.getElementById('orderCustomerName').focus(), 100);
}

function closeOrderModal() {
    document.getElementById('orderModal').classList.remove('open');
    document.getElementById('orderCustomerName').value = '';
    document.getElementById('orderQty').value = 1;
}

function updateSubtotal() {
    const qty = parseInt(document.getElementById('orderQty').value) || 0;
    const subtotal = qty * currentPrice;
    document.getElementById('summarySubtotal').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits:2});
}

document.getElementById('orderQty').addEventListener('input', updateSubtotal);

async function submitOrder() {
    const productId    = document.getElementById('orderProductId').value;
    const customerName = document.getElementById('orderCustomerName').value.trim();
    const quantity     = parseInt(document.getElementById('orderQty').value);

    if (!customerName) {
        showFlash('Please enter the customer name.', 'error'); return;
    }
    if (!quantity || quantity < 1) {
        showFlash('Please enter a valid quantity.', 'error'); return;
    }
    if (quantity > currentStock) {
        showFlash('Quantity exceeds available stock (' + currentStock + ' units).', 'error'); return;
    }

    const formData = new FormData();
    formData.append('action',        'place_order');
    formData.append('product_id',    productId);
    formData.append('customer_name', customerName);
    formData.append('quantity',      quantity);
    formData.append('unit_price',    currentPrice);

    try {
        const res  = await fetch('fetch.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showFlash('✓ Order #' + data.order_id + ' placed for ' + data.customer_name + '!', 'success');
            setTimeout(() => { closeOrderModal(); location.reload(); }, 1800);
        } else {
            showFlash('Error: ' + data.message, 'error');
        }
    } catch (e) {
        showFlash('Request failed. Please try again.', 'error');
    }
}

function showFlash(msg, type) {
    const el = document.getElementById('flashMsg');
    el.textContent = msg;
    el.className = 'alert-flash show alert-' + type;
}

// Close on backdrop click
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrderModal();
});
</script>

</body>
</html>
