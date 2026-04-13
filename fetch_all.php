<?php
// Include database connection file
require 'dbconfig.php';

try {
    // SQL query to fetch all products from the database
    $sql  = "SELECT * FROM products";

    // Prepare the SQL statement (prevents SQL injection)
    $stmt = $pdo->prepare($sql);

    // Execute the query
    $stmt->execute();

    // Fetch all results as an associative array
    $results = $stmt->fetchAll();

} catch (PDOException $e) {
    // Stop execution and show error if query fails
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fetch All Products - PC Shop</title>

    <style>
        /* Reset default browser spacing */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Helvetica Neue", sans-serif;
            background: #f5f5f7;
            color: #1d1d1f;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Navigation bar styling */
        nav {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid #e0e0e0;
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center;
            padding: 0 40px; height: 52px; gap: 28px;
        }

        nav .brand {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: -0.4px;
            color: #1d1d1f;
            text-decoration: none;
            margin-right: auto;
        }

        nav a {
            font-size: 14px;
            color: #6e6e73;
            text-decoration: none;
            font-weight: 450;
            transition: color .15s;
        }

        nav a:hover { color: #1d1d1f; }

        /* Page container */
        .page {
            max-width: 960px;
            margin: 48px auto;
            padding: 0 20px;
        }

        h1 {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", sans-serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 24px;
        }

        /* DEBUG SECTION (for testing database output) */
        .debug-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .debug-label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #6e6e73;
        }

        .debug-block {
            background: #1d1d1f;
            color: #a8ff78;
            font-family: "SF Mono", "Menlo", "Monaco", monospace;
            font-size: 12px;
            line-height: 1.6;
            border-radius: 12px;
            padding: 20px 24px;
            overflow-x: auto;
            margin-bottom: 28px;
            border: 1px solid #2a2a2c;
            display: none;
        }

        .debug-block.visible { display: block; }

        .toggle-btn {
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            border: 1px solid #d0d0d5;
            background: #fff;
            color: #3a3a3c;
            cursor: pointer;
        }

        .toggle-btn:hover { background: #f0f0f2; }

        /* TABLE DESIGN */
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
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #fff;
        }

        td {
            padding: 14px 18px;
            border-bottom: 1px solid #f0f0f2;
            color: #1d1d1f;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) { background: #fafafa; }
        tbody tr:hover { background: #f0f0f2; }
        tbody tr:last-child td { border-bottom: none; }

        .product-name { font-weight: 600; }

        .category-tag {
            display: inline-block;
            background: #f0f0f2;
            color: #3a3a3c;
            font-size: 12px;
            font-weight: 500;
            padding: 2px 9px;
            border-radius: 20px;
        }

        .price {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body>

<!-- Navigation Menu -->
<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="joining_tables.php">Orders</a>
</nav>

<div class="page">

    <!-- Page Title -->
    <h1>All Products</h1>

    <!-- Debug Section: shows raw database output for testing -->
    <div class="debug-toggle">
        <span class="debug-label">Debug Output</span>
        <button class="toggle-btn" onclick="toggleDebug()">Show</button>
    </div>

    <!-- Raw data output (hidden by default) -->
    <div class="debug-block" id="debugBlock">
        <pre><?php print_r($results); ?></pre>
    </div>

    <!-- Product Table -->
    <div class="table-card">
        <table>

            <!-- Table Header -->
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                </tr>
            </thead>

            <tbody>
                <!-- Loop through each product result -->
                <?php foreach ($results as $row): ?>
                <tr>
                    <td><span style="color:#aeaeb2;font-size:12px;">#<?= $row['product_id']; ?></span></td>

                    <!-- Escape output for security (prevents XSS) -->
                    <td class="product-name"><?= htmlspecialchars($row['product_name']); ?></td>

                    <td><span class="category-tag"><?= htmlspecialchars($row['category']); ?></span></td>

                    <!-- Format price properly -->
                    <td class="price">₱<?= number_format($row['price'], 2); ?></td>

                    <td><?= $row['stock_quantity']; ?> units</td>
                </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<script>
// Toggle debug section visibility
function toggleDebug() {
    const block = document.getElementById('debugBlock');
    const btn   = document.querySelector('.toggle-btn');

    block.classList.toggle('visible');

    // Change button text depending on state
    btn.textContent = block.classList.contains('visible') ? 'Hide' : 'Show';
}
</script>

</body>
</html>