<?php
require 'dbconfig.php';

$allowed_statuses = ['Pending', 'Completed', 'Cancelled'];
$status = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses)
    ? $_GET['status']
    : 'Pending';

// Handle status update (mark order as completed/cancelled)
$update_msg  = '';
$update_type = '';
if (isset($_POST['update_status'], $_POST['order_id'])) {
    $new_status = $_POST['update_status'];
    $oid        = (int) $_POST['order_id'];
    if (in_array($new_status, $allowed_statuses)) {
        try {
            $upStmt = $pdo->prepare("UPDATE orders SET status = :status WHERE order_id = :id");
            $upStmt->bindParam(':status', $new_status);
            $upStmt->bindParam(':id', $oid, PDO::PARAM_INT);
            $upStmt->execute();
            $update_msg  = "Order #$oid status updated to $new_status.";
            $update_type = 'success';
        } catch (PDOException $e) {
            $update_msg  = "Error: " . $e->getMessage();
            $update_type = 'error';
        }
    }
}

try {
    $sql = "
        SELECT
            orders.order_id,
            orders.order_date,
            orders.status,
            customers.customer_id,
            customers.full_name,
            customers.email,
            products.product_id,
            products.product_name,
            products.category,
            order_items.quantity,
            order_items.subtotal
        FROM orders
        INNER JOIN customers   ON orders.customer_id     = customers.customer_id
        INNER JOIN order_items ON orders.order_id        = order_items.order_id
        INNER JOIN products    ON order_items.product_id = products.product_id
        WHERE orders.status = :status
        ORDER BY orders.order_id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $results = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Status counts for badges
try {
    $countSql  = "SELECT status, COUNT(*) as cnt FROM orders GROUP BY status";
    $countStmt = $pdo->query($countSql);
    $rawCounts = $countStmt->fetchAll();
    $statusCounts = [];
    foreach ($rawCounts as $r) {
        $statusCounts[$r['status']] = $r['cnt'];
    }
} catch (PDOException $e) {
    $statusCounts = [];
}

$totalAmount = count($results) ? array_sum(array_column($results, 'subtotal')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — PC Shop</title>
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

        .page { max-width: 1100px; margin: 48px auto; padding: 0 20px; }

        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 20px; gap: 16px; flex-wrap: wrap;
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
        .subtitle { font-size: 14px; color: #6e6e73; margin-top: 4px; }

        /* FILTER TABS */
        .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 22px; }

        .filter-tab {
            font-family: inherit; font-size: 13px; font-weight: 500;
            padding: 7px 16px; border-radius: 20px;
            border: 1px solid #d0d0d5; background: #fff;
            color: #6e6e73; cursor: pointer; text-decoration: none;
            transition: all .15s; display: inline-flex; align-items: center; gap: 6px;
        }
        .filter-tab:hover { background: #f5f5f7; color: #1d1d1f; }
        .filter-tab.active-pending    { background: #f5f5e8; color: #7a6d2d; border-color: #d4c87a; }
        .filter-tab.active-completed  { background: #e8f5e8; color: #2d7a2d; border-color: #7ac47a; }
        .filter-tab.active-cancelled  { background: #f5e8e8; color: #7a2d2d; border-color: #c47a7a; }

        .tab-count {
            background: rgba(0,0,0,.08);
            font-size: 11px; font-weight: 700;
            padding: 1px 7px; border-radius: 20px;
        }

        /* ALERT */
        .alert {
            font-size: 13px; font-weight: 500;
            padding: 12px 16px; border-radius: 10px;
            margin-bottom: 20px; border: 1px solid transparent;
        }
        .alert-success { background: #e8f5e8; color: #2d7a2d; border-color: #b8e0b8; }
        .alert-error   { background: #f5e8e8; color: #7a2d2d; border-color: #e0b8b8; }

        /* SUMMARY BAR */
        .summary-bar {
            display: flex; gap: 28px; align-items: center;
            padding: 14px 20px;
            background: #fafafa;
            border-bottom: 1px solid #f0f0f2;
            font-size: 13px; color: #6e6e73;
        }
        .summary-bar strong { color: #1d1d1f; }

        /* TABLE CARD */
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
            padding: 14px 16px; text-align: left;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.8px; text-transform: uppercase; color: #fff;
        }
        th:last-child { text-align: center; }
        td {
            padding: 13px 16px;
            border-bottom: 1px solid #f0f0f2;
            color: #1d1d1f; vertical-align: middle;
        }
        td:last-child { text-align: center; }
        tbody tr { background: #fff; transition: background .12s; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f0f0f2; }

        .order-id { color: #aeaeb2; font-size: 12px; font-weight: 600; }
        .customer-name { font-weight: 600; }
        .customer-email { font-size: 12px; color: #aeaeb2; margin-top: 2px; }
        .product-name { color: #3a3a3c; font-weight: 500; }
        .category-tag {
            display: inline-block; background: #f0f0f2; color: #3a3a3c;
            font-size: 11px; font-weight: 500; padding: 2px 7px; border-radius: 20px;
        }
        .subtotal { font-weight: 700; font-variant-numeric: tabular-nums; }
        .order-date { font-size: 12px; color: #aeaeb2; }

        /* STATUS BADGE */
        .status {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: 0.7; }
        .status-completed  { background: #e8f5e8; color: #2d7a2d; }
        .status-pending    { background: #f5f5e8; color: #7a6d2d; }
        .status-cancelled  { background: #f5e8e8; color: #7a2d2d; }
        .status-default    { background: #f0f0f2; color: #6e6e73; }

        /* ACTION BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 4px;
            font-family: inherit; font-size: 12px; font-weight: 500;
            padding: 5px 12px; border-radius: 7px; border: none;
            cursor: pointer; text-decoration: none;
            transition: background .15s, transform .1s;
        }
        .btn-complete { background: #e8f5e8; color: #2d7a2d; border: 1px solid #b8e0b8; }
        .btn-complete:hover { background: #d0ecd0; transform: translateY(-1px); }
        .btn-cancel   { background: #f5e8e8; color: #7a2d2d; border: 1px solid #e0b8b8; }
        .btn-cancel:hover   { background: #ecd0d0; transform: translateY(-1px); }
        .action-wrap { display: flex; gap: 5px; justify-content: center; flex-wrap: wrap; }

        .empty {
            text-align: center; padding: 64px 24px;
            color: #aeaeb2; font-size: 15px;
        }
    </style>
</head>
<body>

<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="fetch.php">Order</a>
    <a href="joining_tables_with_filter.php" class="active">Orders</a>
</nav>

<div class="page">

    <div class="page-header">
        <div>
            <h1>
                Orders
                <span class="count-badge"><?= count($results); ?> results</span>
            </h1>
            <p class="subtitle">Filtered by: <strong><?= htmlspecialchars($status); ?></strong></p>
        </div>
        <a href="fetch.php" style="
            display:inline-flex;align-items:center;gap:6px;
            font-family:inherit;font-size:14px;font-weight:500;
            padding:9px 20px;border-radius:10px;
            background:#1d1d1f;color:#fff;text-decoration:none;
            box-shadow:0 1px 3px rgba(0,0,0,.1);
            transition:background .15s;
        ">+ New Order</a>
    </div>

    <!-- Status Flash Message -->
    <?php if ($update_msg): ?>
        <div class="alert alert-<?= $update_type; ?>"><?= htmlspecialchars($update_msg); ?></div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="filter-bar">
        <?php
        $tabMeta = [
            'Pending'   => 'active-pending',
            'Completed' => 'active-completed',
            'Cancelled' => 'active-cancelled',
        ];
        foreach ($tabMeta as $tabStatus => $activeClass):
            $cnt = $statusCounts[$tabStatus] ?? 0;
            $isActive = ($tabStatus === $status);
        ?>
        <a href="joining_tables_with_filter.php?status=<?= urlencode($tabStatus); ?>"
           class="filter-tab <?= $isActive ? $activeClass : ''; ?>">
            <?= htmlspecialchars($tabStatus); ?>
            <span class="tab-count"><?= $cnt; ?></span>
        </a>
        <?php endforeach; ?>
        <a href="joining_tables.php" class="filter-tab" style="margin-left:4px;">View All →</a>
    </div>

    <div class="table-card">

        <?php if (count($results) > 0): ?>

        <div class="summary-bar">
            <span>Orders: <strong><?= count($results); ?></strong></span>
            <span>Total Revenue: <strong>₱<?= number_format($totalAmount, 2); ?></strong></span>
            <span>Status: <strong><?= htmlspecialchars($status); ?></strong></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Date</th>
                    <th>Status</th>
                    <?php if ($status === 'Pending'): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row):
                    $s = strtolower($row['status']);
                    $statusClass = match(true) {
                        str_contains($s, 'complet') => 'status-completed',
                        str_contains($s, 'pending') => 'status-pending',
                        str_contains($s, 'cancel')  => 'status-cancelled',
                        default                     => 'status-default',
                    };
                ?>
                <tr>
                    <td><span class="order-id">#<?= $row['order_id']; ?></span></td>
                    <td>
                        <div class="customer-name"><?= htmlspecialchars($row['full_name']); ?></div>
                        <?php if ($row['email']): ?>
                            <div class="customer-email"><?= htmlspecialchars($row['email']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="product-name"><?= htmlspecialchars($row['product_name']); ?></div>
                        <span class="category-tag"><?= htmlspecialchars($row['category']); ?></span>
                    </td>
                    <td><?= (int) $row['quantity']; ?></td>
                    <td class="subtotal">₱<?= number_format($row['subtotal'], 2); ?></td>
                    <td class="order-date"><?= date('M d, Y', strtotime($row['order_date'])); ?></td>
                    <td>
                        <span class="status <?= $statusClass; ?>">
                            <span class="status-dot"></span>
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                    <?php if ($status === 'Pending'): ?>
                    <td>
                        <div class="action-wrap">
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= $row['order_id']; ?>">
                                <input type="hidden" name="update_status" value="Completed">
                                <button type="submit" class="btn btn-complete"
                                    onclick="return confirm('Mark Order #<?= $row['order_id']; ?> as Completed?')">
                                    ✓ Complete
                                </button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= $row['order_id']; ?>">
                                <input type="hidden" name="update_status" value="Cancelled">
                                <button type="submit" class="btn btn-cancel"
                                    onclick="return confirm('Cancel Order #<?= $row['order_id']; ?>?')">
                                    ✕ Cancel
                                </button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php else: ?>
            <div class="empty">
                No <strong><?= htmlspecialchars($status); ?></strong> orders found.
                <br><br>
                <a href="fetch.php" style="color:#1d1d1f;font-weight:600;">Place a new order →</a>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>
