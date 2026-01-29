<?php
// File: api/get_daily_revenue.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- QUAN TRỌNG: SET MÚI GIỜ VIỆT NAM ---
date_default_timezone_set('Asia/Ho_Chi_Minh'); 
// ----------------------------------------

if (!file_exists('db.php')) {
    echo json_encode(['amount' => 0, 'formatted' => 'Lỗi DB']);
    exit;
}
include 'db.php';

// Code kết nối chuẩn PDO
if (!isset($conn)) {
    echo json_encode(['amount' => 0, 'formatted' => 'Mất kết nối']);
    exit;
}

try {
    $today = date('Y-m-d'); // Lúc này sẽ lấy đúng ngày hiện tại ở VN

    // Query tính tổng tiền đã thanh toán trong hôm nay
    $sql = "SELECT SUM(total_amount) as total FROM invoices WHERE status = 'paid' AND DATE(paid_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$today]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = $row['total'] ? (float)$row['total'] : 0;

    function formatMoney($number) {
        if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
        if ($number >= 1000) return round($number / 1000, 0) . 'k';
        return number_format($number);
    }

    echo json_encode([
        'amount' => $amount,
        'formatted' => formatMoney($amount) . ' ₫'
    ]);

} catch (Exception $e) {
    echo json_encode(['amount' => 0, 'formatted' => '0 ₫']);
}
?>
