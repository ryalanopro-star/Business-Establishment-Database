<?php
require 'dbconfig.php';

try {
    $sql  = "SELECT * FROM products";
    $stmt = $pdo->prepare($sql);
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

        .page { max-width: 960px; margin: 48px auto; padding: 0 20px; }

        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px; gap: 16px;
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
            padding: 9px 20px; border-radius: 10px; border: none;
            cursor: pointer; text-decoration: none;
            transition: background .15s, transform .15s, box-shadow .15s;
        }
        .btn-primary { background: #1d1d1f; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .btn-primary:hover { background: #3a3a3c; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        .btn-sm { font-size: 12px; padding: 6px 13px; border-radius: 7px; }
        .btn-edit { background: #6e6e73; color: #fff; }
        .btn-edit:hover { background: #3a3a3c; transform: translateY(-1px); }
        .btn-delete { background: #fff; color: #1d1d1f; border: 1px solid #d0d0d5; }
        .btn-delete:hover { background: #f5f5f7; transform: translateY(-1px); }
        .btn-view { background: #f0f0f2; color: #1d1d1f; border: 1px solid #e0e0e0; }
        .btn-view:hover { background: #e8e8ea; transform: translateY(-1px); }

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
            padding: 14px 18px;
            text-align: left;
            font-size: 11px; font-weight: 700;
            letter-spacing: 0.8px; text-transform: uppercase;
            color: #fff;
        }
        th:last-child { text-align: center; }

        td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f2;
            color: #1d1d1f;
            vertical-align: middle;
        }
        td:last-child { text-align: center; }

        tbody tr { background: #fff; transition: background .12s; }
        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f0f0f2; }

        .product-name { font-weight: 600; }
        .category-tag {
            display: inline-block;
            background: #f0f0f2; color: #3a3a3c;
            font-size: 12px; font-weight: 500;
            padding: 2px 9px; border-radius: 20px;
        }
        .price { font-weight: 600; font-variant-numeric: tabular-nums; }
        .stock-low  { color: #6e6e73; font-weight: 500; }
        .stock-ok   { color: #3a3a3c; font-weight: 500; }

        .actions { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }

        .empty {
            text-align: center; padding: 64px 24px;
            color: #aeaeb2; font-size: 15px;
        }
    </style>
</head>
<body>

<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php" class="active">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="joining_tables.php">Orders</a>
</nav>

<div class="page">

    <div class="page-header">
        <h1>
            Products
            <span class="count-badge"><?= count($results); ?> items</span>
        </h1>
        <a href="insert.php" class="btn btn-primary">+ Add Product</a>
    </div>

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
                            <a href="fetch.php?id=<?= $row['product_id']; ?>" class="btn btn-sm btn-view">View</a>
                            <a href="update.php?id=<?= $row['product_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                            <a href="delete.php?id=<?= $row['product_id']; ?>"
                               class="btn btn-sm btn-delete"
                               onclick="return confirm('Delete \'<?= htmlspecialchars($row['product_name']); ?>\'?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty">No products found. <a href="insert.php" style="color:#1d1d1f;font-weight:600;">Add one →</a></div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
