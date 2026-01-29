<?php
// File: api/get_low_stock.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'db.php';

try {
    // Đếm số thuốc có tồn kho <= 10
    $sql = "SELECT COUNT(*) as count FROM medicines WHERE stock_qty <= 10";
    $stmt = $conn->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $row['count']]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
?>