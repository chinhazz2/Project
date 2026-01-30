<?php
// File: api/get_daily_revenue.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once 'db.php'; // Gọi file kết nối

try {
    // Kiểm tra nếu biến $pdo chưa được khởi tạo từ db.php
    if (!isset($pdo)) {
        throw new Exception("Lỗi cấu hình: Biến kết nối \$pdo không tồn tại.");
    }

    // Tính tổng tiền thu được trong ngày hôm nay từ bảng PAYMENTS
    $sql = "SELECT SUM(amount) as total FROM payments WHERE DATE(paid_at) = CURDATE()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $amount = $row['total'] ? (float)$row['total'] : 0;

    // Hàm định dạng tiền tệ (Ví dụ: 1.2M, 500k)
    function formatMoney($number) {
        if ($number >= 1000000) return round($number / 1000000, 1) . 'M';
        if ($number >= 1000) return round($number / 1000, 0) . 'k';
        return number_format($number, 0, ',', '.');
    }

    echo json_encode([
        'amount' => $amount,
        'formatted' => formatMoney($amount) . ' ₫'
    ]);

} catch (Exception $e) {
    echo json_encode(['amount' => 0, 'formatted' => '0 ₫', 'error' => $e->getMessage()]);
}
?>
