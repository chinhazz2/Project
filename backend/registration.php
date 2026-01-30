<?php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Hỗ trợ cả Form Data truyền thống nếu cần
    if (!$data) $data = $_POST;

    $full_name = $data['full_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $reason = $data['reason'] ?? '';

    if (empty($full_name) || empty($phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu thông tin bắt buộc']);
        exit;
    }

    try {
        // 1. Kiểm tra hoặc tạo bệnh nhân mới
        $stmt = $pdo->prepare("SELECT id FROM patients WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $patient = $stmt->fetch();

        if ($patient) {
            $patient_id = $patient['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO patients (full_name, phone) VALUES (?, ?)");
            $stmt->execute([$full_name, $phone]);
            $patient_id = $pdo->lastInsertId();
        }

        // 2. Tạo lượt khám (Status = waiting)
        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, start_time, status, reason) 
            VALUES (?, NOW(), 'waiting', ?)
        ");
        $stmt->execute([$patient_id, $reason]);

        echo json_encode(['success' => true, 'message' => 'Đã tiếp nhận bệnh nhân thành công']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
