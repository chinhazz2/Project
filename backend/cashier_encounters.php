<?php

require_once 'db.php';

try {

    $sql = "
        SELECT 
            e.id AS encounter_id,
            p.full_name AS patient_name,
            e.encounter_date,
            i.total_amount,
            IFNULL(i.status, 'unpaid') AS status
        FROM encounters e
        JOIN patients p ON e.patient_id = p.id
        LEFT JOIN invoices i ON i.encounter_id = e.id
        WHERE i.id IS NULL OR i.status = 'unpaid'
        ORDER BY e.encounter_date ASC
    ";

    $stmt = $conn->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
