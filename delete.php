<?php
// Connect to database
require 'dbconfig.php';

// Check if ID is provided in URL
if (isset($_GET['id'])) {

    // Get the product ID
    $id = $_GET['id'];

    try {
        // SQL query to delete product
        $sql  = "DELETE FROM products WHERE product_id = :id";

        // Prepare query
        $stmt = $pdo->prepare($sql);

        // Bind ID value
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // Execute delete
        $stmt->execute();

        // Redirect back to product list
        header("Location: html_table.php");
        exit();

    } catch (PDOException $e) {
        // Show error if delete fails
        die("Error deleting record: " . $e->getMessage());
    }

} else {
    // If no ID provided, go back to product list
    header("Location: html_table.php");
    exit();
}
?>
