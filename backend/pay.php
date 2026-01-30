<?php
// File: api/pay.php
require_once 'db.php'; // Kết nối CSDL chung

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['invoice_id'])) {
    echo json_encode(['success' => false, 'error' => 'Thiếu invoice_id']);
    exit;
}

$invoiceId = (int)$_POST['invoice_id'];
$method = isset($_POST['method']) ? trim($_POST['method']) : 'Tiền mặt';

try {
    $pdo->beginTransaction();

    // 1. Lấy thông tin hóa đơn VÀ encounter_id để truy vết lịch hẹn
    // (Phần này rất quan trọng để tìm ra lịch hẹn gốc)
    $stmt = $pdo->prepare("
        SELECT total_amount, status, encounter_id 
        FROM invoices 
        WHERE id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        throw new Exception("Không tìm thấy hóa đơn");
    }

    if ($invoice['status'] === 'paid') {
        throw new Exception("Hóa đơn này đã được thanh toán rồi");
    }

    $amount = $invoice['total_amount'];
    $encounterId = $invoice['encounter_id'];

    // 2. Ghi lịch sử thanh toán
    $stmt = $pdo->prepare("INSERT INTO payments (invoice_id, amount, method) VALUES (?, ?, ?)");
    $stmt->execute([$invoiceId, $amount, $method]);

    // 3. Cập nhật trạng thái HÓA ĐƠN -> paid
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?");
    $stmt->execute([$invoiceId]);

    // 4. CẬP NHẬT TRẠNG THÁI LỊCH HẸN -> completed
    // (Đây là đoạn code bạn đang thiếu)
    if ($encounterId) {
        // Tìm appointment_id từ bảng encounters
        $stmtEnc = $pdo->prepare("SELECT appointment_id FROM encounters WHERE id = ?");
        $stmtEnc->execute([$encounterId]);
        $enc = $stmtEnc->fetch(PDO::FETCH_ASSOC);

        if ($enc && $enc['appointment_id']) {
            // Chuyển trạng thái lịch hẹn sang 'completed' (Hoàn thành)
            $stmtAppt = $pdo->prepare("UPDATE appointments SET status = 'completed' WHERE id = ?");
            $stmtAppt->execute([$enc['appointment_id']]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Thanh toán thành công! Hồ sơ đã hoàn tất.',
        'invoice_id' => $invoiceId
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
