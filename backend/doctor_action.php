<?php
require_once 'db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 1. Lấy danh sách chờ khám hoặc chờ kê đơn
if ($method == 'GET') {
    if ($action == 'get_waiting') {
        // Lấy danh sách bệnh nhân đang chờ khám (status = waiting)
        $stmt = $conn->query("
            SELECT a.id as appointment_id, p.full_name, p.phone, a.reason, a.status 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            WHERE a.status = 'waiting'
            ORDER BY a.start_time ASC
        ");
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($action == 'get_diagnosed') {
        // Lấy danh sách đã khám xong, chờ kê đơn (status = diagnosed)
        // Cần join bảng encounters để lấy chẩn đoán
        $stmt = $conn->query("
            SELECT a.id as appointment_id, p.full_name, e.diagnosis, e.id as encounter_id
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN encounters e ON e.appointment_id = a.id
            WHERE a.status = 'diagnosed'
            ORDER BY a.start_time ASC
        ");
        echo json_encode($stmt->fetchAll());
    }
}

// 2. Xử lý Lưu chẩn đoán & Lưu đơn thuốc
if ($method == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action == 'save_diagnosis') {
        // Bước 1: Lưu vào bảng encounters
        $stmt = $conn->prepare("
            INSERT INTO encounters (appointment_id, patient_id, diagnosis, notes, encounter_date)
            SELECT id, patient_id, ?, ?, NOW() 
            FROM appointments WHERE id = ?
        ");
        $stmt->execute([$data['diagnosis'], $data['notes'], $data['appointment_id']]);
        
        // Bước 2: Cập nhật trạng thái lịch hẹn sang 'diagnosed'
        $stmt = $conn->prepare("UPDATE appointments SET status = 'diagnosed' WHERE id = ?");
        $stmt->execute([$data['appointment_id']]);

        echo json_encode(['success' => true]);
    }

    elseif ($action == 'save_prescription') {
        try {
            $conn->beginTransaction();

            $encounter_id = $data['encounter_id'];
            $medicines = $data['medicines']; // Mảng danh sách thuốc [{id, qty, dose, price, name}...]

            // 1. Tạo đơn thuốc
            $stmt = $conn->prepare("INSERT INTO prescriptions (encounter_id, created_at) VALUES (?, NOW())");
            $stmt->execute([$encounter_id]);
            $prescription_id = $conn->lastInsertId();

            $total_amount = 0;

            // 2. Lưu chi tiết đơn thuốc và tính tiền
            $stmt_item = $conn->prepare("
                INSERT INTO prescription_items (prescription_id, medicine_id, qty, dose) 
                VALUES (?, ?, ?, ?)
            ");
            
            // Cập nhật kho (trừ tồn kho)
            $stmt_stock = $conn->prepare("UPDATE medicines SET stock_qty = stock_qty - ? WHERE id = ?");

            foreach ($medicines as $med) {
                $stmt_item->execute([$prescription_id, $med['id'], $med['qty'], $med['dose']]);
                $stmt_stock->execute([$med['qty'], $med['id']]);
                
                // Tính tổng tiền (Giá x Số lượng)
                $total_amount += ($med['price'] * $med['qty']);
            }

            // 3. TẠO HÓA ĐƠN (Để Thu ngân thấy)
            $stmt_inv = $conn->prepare("
                INSERT INTO invoices (encounter_id, total_amount, status, created_at) 
                VALUES (?, ?, 'unpaid', NOW())
            ");
            $stmt_inv->execute([$encounter_id, $total_amount]);

            // 4. Cập nhật trạng thái xong quy trình bác sĩ -> chuyển sang chờ thanh toán
            // Tìm appointment_id từ encounter để update
            $stmt_find_app = $conn->prepare("SELECT appointment_id FROM encounters WHERE id = ?");
            $stmt_find_app->execute([$encounter_id]);
            $app_id = $stmt_find_app->fetchColumn();

            $conn->prepare("UPDATE appointments SET status = 'payment_pending' WHERE id = ?")->execute([$app_id]);

            $conn->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
?>
