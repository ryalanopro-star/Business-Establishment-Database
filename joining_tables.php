<?php
// Connect to database
require 'dbconfig.php';

try {
    // SQL query to get all orders with customer and product details
    $sql = "
        SELECT
            orders.order_id,
            customers.full_name,
            products.product_name,
            order_items.quantity,
            order_items.subtotal,
            orders.status
        FROM orders
        INNER JOIN customers   ON orders.customer_id       = customers.customer_id
        INNER JOIN order_items ON orders.order_id          = order_items.order_id
        INNER JOIN products    ON order_items.product_id   = products.product_id
        ORDER BY orders.order_id DESC
    ";
    // Prepare query
    $stmt = $pdo->prepare($sql);

    //Run query
    $stmt->execute();
    // Get all results
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
    <title>All Orders - PC Shop</title>
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

        .page { max-width: 1060px; margin: 48px auto; padding: 0 20px; }

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

        .btn {
            display: inline-flex; align-items: center; gap: 5px;
            font-family: inherit; font-size: 14px; font-weight: 500;
            padding: 9px 18px; border-radius: 10px; border: 1px solid #d0d0d5;
            background: #fff; color: #1d1d1f; cursor: pointer; text-decoration: none;
            transition: background .15s, transform .15s;
        }
        .btn:hover { background: #f5f5f7; transform: translateY(-1px); }
        .btn-active { background: #1d1d1f; color: #fff; border-color: #1d1d1f; }
        .btn-active:hover { background: #3a3a3c; }

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
        td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f2;
            color: #1d1d1f; vertical-align: middle;
        }
        tbody tr { background: #fff; transition: background .12s; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f0f0f2; }

        .order-id { color: #aeaeb2; font-size: 12px; }
        .customer-name { font-weight: 600; }
        .product-name { color: #3a3a3c; }
        .subtotal { font-weight: 600; font-variant-numeric: tabular-nums; }

        /* STATUS BADGE */
        .status {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 12px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
        }
        .status-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: currentColor; opacity: 0.7;
        }
        .status-completed  { background: #e8f5e8; color: #2d7a2d; }
        .status-pending    { background: #f5f5e8; color: #7a6d2d; }
        .status-cancelled  { background: #f5e8e8; color: #7a2d2d; }
        .status-processing { background: #e8eef5; color: #2d4a7a; }
        .status-default    { background: #f0f0f2; color: #6e6e73; }

        .empty {
            text-align: center; padding: 64px 24px;
            color: #aeaeb2; font-size: 15px;
        }

        .filter-bar {
            display: flex; gap: 8px; flex-wrap: wrap;
        }
    </style>
</head>
<body>

<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="joining_tables.php" class="active">Orders</a>
</nav>

<div class="page">

    <div class="page-header">
        <h1>
            All Orders
            <span class="count-badge"><?= count($results); ?> records</span>
        </h1>
        <div class="filter-bar">
            <a href="joining_tables.php" class="btn btn-active">All Orders</a>
            <a href="joining_tables_with_filter.php" class="btn">Completed</a>
        </div>
    </div>

    <div class="table-card">
        <?php if (count($results) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>

                <!--Loop through orders -->
                <?php foreach ($results as $row):
                    // Convert status to lowercase for checking
                    $s = strtolower($row['status']);
                    $statusClass = match(true) {
                        str_contains($s, 'complet')  => 'status-completed',
                        str_contains($s, 'pending')  => 'status-pending',
                        str_contains($s, 'cancel')   => 'status-cancelled',
                        str_contains($s, 'process')  => 'status-processing',
                        default                      => 'status-default',
                    };
                ?>
                <tr>
                    <td><span class="order-id">#<?= $row['order_id']; ?></span></td>
                    <td class="customer-name"><?= htmlspecialchars($row['full_name']); ?></td>
                    <td class="product-name"><?= htmlspecialchars($row['product_name']); ?></td>
                    <td><?= $row['quantity']; ?></td>
                    <td class="subtotal">₱<?= number_format($row['subtotal'], 2); ?></td>
                    <td>
                        <span class="status <?= $statusClass; ?>">
                            <span class="status-dot"></span>
                            <?= htmlspecialchars($row['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <!--No data message-->
            <div class="empty">No orders found.</div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
