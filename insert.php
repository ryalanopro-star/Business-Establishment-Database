<?php
// Connect to database
require 'dbconfig.php';

// Variables for messages
$message = "";
$message_type = "";

// Check if form is submitted
if (isset($_POST['submit'])) {

    // Get form data
    $product_name   = $_POST['product_name'];
    $category       = $_POST['category'];
    $price          = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    try {
        // SQL query to insert product
        $sql = "INSERT INTO products (product_name, category, price, stock_quantity)
                VALUES (:product_name, :category, :price, :stock_quantity)";

        // Prepare query
        $stmt = $pdo->prepare($sql);

        // Bind values
        $stmt->bindParam(':product_name',   $product_name);
        $stmt->bindParam(':category',       $category);
        $stmt->bindParam(':price',          $price);
        $stmt->bindParam(':stock_quantity', $stock_quantity);

        // Execute insert
        $stmt->execute();

        // Success message
        $message      = "✓ Product added successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        // Error message
        $message      = "Error: " . $e->getMessage();
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - PC Shop</title>
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
        nav a:hover { color: #1d1d1f; }

        .page { max-width: 560px; margin: 48px auto; padding: 0 20px; }

        h1 {
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", sans-serif;
            font-size: 26px; font-weight: 700; letter-spacing: -0.5px;
            margin-bottom: 28px;
        }

        .card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            padding: 36px 40px;
        }

        .alert {
            font-size: 14px; font-weight: 500;
            padding: 13px 16px;
            border-radius: 10px;
            margin-bottom: 24px;
            border: 1px solid transparent;
        }
        .alert-success { background: #f0f0f2; border-color: #c8c8cc; color: #1d1d1f; }
        .alert-error   { background: #f7f7f7; border-color: #d0d0d5; color: #3a3a3c; }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 13px; font-weight: 600;
            color: #6e6e73;
            margin-bottom: 7px;
            letter-spacing: 0.1px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            font-family: inherit;
            font-size: 15px;
            color: #1d1d1f;
            background: #fafafa;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 11px 14px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
            -webkit-appearance: none;
        }
        input[type="text"]:focus,
        input[type="number"]:focus {
            background: #fff;
            border-color: #b0b0b0;
            box-shadow: 0 0 0 3px rgba(0,0,0,.06);
        }
        input::placeholder { color: #aeaeb2; }

        .divider { border: none; border-top: 1px solid #f0f0f2; margin: 28px 0; }

        .form-actions { display: flex; gap: 10px; align-items: center; }

        .btn {
            display: inline-flex; align-items: center;
            font-family: inherit; font-size: 14px; font-weight: 500;
            padding: 10px 22px; border-radius: 10px; border: none;
            cursor: pointer; text-decoration: none;
            transition: background .15s, transform .15s, box-shadow .15s;
        }
        .btn-primary { background: #1d1d1f; color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .btn-primary:hover { background: #3a3a3c; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.12); }
        .btn-outline { background: #fff; color: #1d1d1f; border: 1px solid #d0d0d5; }
        .btn-outline:hover { background: #f5f5f7; }
    </style>
</head>
<body>

<nav>
    <a href="html_table.php" class="brand">⌨ PC Shop</a>
    <a href="html_table.php">Products</a>
    <a href="insert.php">Add Product</a>
    <a href="joining_tables.php">Orders</a>
</nav>

<div class="page">
    <h1>Add Product</h1>

    <div class="card">
        
        <!--Show message after submit-->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type; ?>"><?= $message; ?></div>
        <?php endif; ?>

        <form method="POST">

            <!--Name-->
            <div class="form-group">
                <label for="product_name">Product Name</label>
                <input type="text" id="product_name" name="product_name" placeholder="e.g. Logitech MX Master 3" required>
            </div>

            <!--Category-->
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" placeholder="e.g. Mouse, Keyboard, Headset" required>
            </div>

            <!--Price-->
            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
            </div>
           
            <!--Stock-->
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" placeholder="0" required>
            </div>

            <hr class="divider">
            
            <!--Button-->
            <div class="form-actions">
                <button type="submit" name="submit" class="btn btn-primary">Add Product</button>
                <a href="html_table.php" class="btn btn-outline">Cancel</a>
            </div>

        </form>
    </div>
</div>

</body>
</html>
