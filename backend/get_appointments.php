<?php
// File: api/get_appointments.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

try {
    if (!isset($pdo)) {
        throw new Exception("Mất kết nối CSDL");
    }

    // Lấy ngày từ tham số hoặc mặc định là hôm nay
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);

} catch (Exception $e) {
    // Trả về mảng rỗng thay vì lỗi 500 để giao diện không bị treo
    echo json_encode([]);
}
?>
