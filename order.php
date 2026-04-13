<?php
require 'dbconfig.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$product_id  = isset($_POST['product_id'])  ? (int) $_POST['product_id']  : 0;
$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
$quantity    = isset($_POST['quantity'])    ? (int) $_POST['quantity']    : 0;
$unit_price  = isset($_POST['unit_price'])  ? (float) $_POST['unit_price'] : 0.0;

// Basic validation
if ($product_id <= 0 || $customer_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields.']);
    exit;
}

try {
    // Check stock
    $checkStmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = :id");
    $checkStmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $product = $checkStmt->fetch();

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

    // Insert into orders
    $orderSql  = "INSERT INTO orders (customer_id, status) VALUES (:customer_id, 'Pending')";
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $orderStmt->execute();
    $order_id = (int) $pdo->lastInsertId();

    // Insert into order_items
    $itemSql  = "INSERT INTO order_items (order_id, product_id, quantity, subtotal)
                 VALUES (:order_id, :product_id, :quantity, :subtotal)";
    $itemStmt = $pdo->prepare($itemSql);
    $itemStmt->bindParam(':order_id',   $order_id,   PDO::PARAM_INT);
    $itemStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $itemStmt->bindParam(':quantity',   $quantity,   PDO::PARAM_INT);
    $itemStmt->bindParam(':subtotal',   $subtotal);
    $itemStmt->execute();

    // Deduct stock
    $stockSql  = "UPDATE products SET stock_quantity = stock_quantity - :qty WHERE product_id = :id";
    $stockStmt = $pdo->prepare($stockSql);
    $stockStmt->bindParam(':qty', $quantity, PDO::PARAM_INT);
    $stockStmt->bindParam(':id',  $product_id, PDO::PARAM_INT);
    $stockStmt->execute();

    $pdo->commit();

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'subtotal' => $subtotal,
        'message'  => 'Order placed successfully.'
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
