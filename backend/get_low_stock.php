<?php
// File: api/get_low_stock.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

try {
    if (!isset($pdo)) {
        throw new Exception("Mất kết nối");
    }

    // Đếm số thuốc có tồn kho <= 10
    $sql = "SELECT COUNT(*) as count FROM medicines WHERE stock_qty <= 10";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => $row['count'] ?? 0]);

} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
?>
