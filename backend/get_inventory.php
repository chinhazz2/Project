<?php

require_once 'db.php';

/** @var PDO $pdo */  // <--- THÊM DÒNG NÀY VÀO
// Dòng trên giúp VS Code hiểu $pdo là một kết nối PDO

$method = $_SERVER['REQUEST_METHOD'];

// 4. XỬ LÝ LẤY DANH SÁCH (GET)
if ($method == 'GET') {
    try {
        $query = "SELECT id, name, code, unit, price, stock_qty FROM medicines ORDER BY name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results ? $results : []);
    } catch(PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// 5. XỬ LÝ XÓA (DELETE)
if ($method == 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
    }
    exit;
}

// 6. XỬ LÝ THÊM/SỬA (POST)
if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    if (!$data) exit;

    if (isset($data->id) && !empty($data->id)) {
        // SỬA
        $sql = "UPDATE medicines SET name=?, code=?, unit=?, stock_qty=?, price=? WHERE id=?";
        $params = [$data->name, $data->code, $data->unit, $data->stock_qty, $data->price, $data->id];
    } else {
        // THÊM
        $sql = "INSERT INTO medicines (name, code, unit, stock_qty, price) VALUES (?, ?, ?, ?, ?)";
        $params = [$data->name, $data->code, $data->unit, $data->stock_qty, $data->price];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(["success" => true]);
    exit;
}
?>
