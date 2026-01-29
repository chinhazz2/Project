<?php
// File: api/get_appointments.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require_once 'db.php'; // Sử dụng kết nối chung

try {
    // Lấy ngày từ tham số hoặc mặc định là hôm nay
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    // THÊM a.id VÀO CÂU SELECT ĐỂ DÙNG CHO VIỆC CHECK-IN
    $sql = "SELECT 
        a.id, 
        p.full_name AS ten_benh_nhan, 
        p.phone AS sdt_benh_nhan,
        u.full_name AS ten_bac_si,
        a.start_time,
        a.status,
        a.reason
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    JOIN users u ON d.user_id = u.id
    WHERE DATE(a.start_time) = ?
    ORDER BY a.start_time ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$date]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
?>