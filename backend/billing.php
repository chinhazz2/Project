<?php
// File: api/billing.php
require_once 'db.php';

if (!isset($_GET['encounter_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing encounter_id']);
    exit;
}

$encounterId = (int)$_GET['encounter_id'];

try {
    // Lưu ý: Đã đổi $db thành $pdo
    // 1. Lấy thuốc
    $stmt = $pdo->prepare("
        SELECT 
            m.name,
            m.price,
            pi.qty,
            (m.price * pi.qty) AS subtotal
        FROM prescriptions pr
        JOIN prescription_items pi ON pr.id = pi.prescription_id
        JOIN medicines m ON pi.medicine_id = m.id
        WHERE pr.encounter_id = ?
    ");
    $stmt->execute([$encounterId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    foreach ($items as $i) {
        $total += $i['subtotal'];
    }

    // 2. Kiểm tra invoice
    $stmt = $pdo->prepare("SELECT id FROM invoices WHERE encounter_id = ?");
    $stmt->execute([$encounterId]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        // Tạo mới
        $stmt = $pdo->prepare("INSERT INTO invoices (encounter_id, total_amount, status) VALUES (?, ?, 'unpaid')");
        $stmt->execute([$encounterId, $total]);
        $invoiceId = $pdo->lastInsertId();
    } else {
        // Cập nhật
        $invoiceId = $invoice['id'];
        $stmt = $pdo->prepare("UPDATE invoices SET total_amount = ? WHERE id = ?");
        $stmt->execute([$total, $invoiceId]);
    }

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoiceId,
        'items' => $items,
        'total_amount' => $total
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
