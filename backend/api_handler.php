<?php
// 1. Cấu hình hiển thị lỗi và CORS
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Kết nối Database trực tiếp
$host = "localhost";
$db_name = "clinic_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("set names utf8mb4");
} catch(PDOException $e) {
    die(json_encode(["error" => "Kết nối thất bại: " . $e->getMessage()]));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 3. XỬ LÝ CÁC YÊU CẦU GET
if ($method == 'GET') {
    switch ($action) {
        case 'get_inventory': // Lấy danh sách kho thuốc
            $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'check_phone': // Kiểm tra SĐT bệnh nhân
            $phone = $_GET['phone'] ?? '';
            $stmt = $pdo->prepare("SELECT full_name FROM patients WHERE phone = ? LIMIT 1");
            $stmt->execute([$phone]);
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ["full_name" => null]);
            break;

        case 'get_doctors': // Lấy danh sách bác sĩ
            $stmt = $pdo->query("SELECT d.id, u.full_name FROM doctors d JOIN users u ON d.user_id = u.id");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
            
        default:
            echo json_encode(["error" => "Action không hợp lệ"]);
    }
}

// 4. XỬ LÝ CÁC YÊU CẦU POST (Thêm/Sửa)
if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    if (!$data) exit;

    if ($action == 'manage_medicine') { // Thêm/Sửa thuốc
        if (isset($data->id) && !empty($data->id)) {
            $sql = "UPDATE medicines SET name=?, code=?, unit=?, stock_qty=?, price=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data->name, $data->code, $data->unit, $data->stock_qty, $data->price, $data->id]);
        } else {
            $sql = "INSERT INTO medicines (name, code, unit, stock_qty, price) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$data->name, $data->code, $data->unit, $data->stock_qty, $data->price]);
        }
        echo json_encode(["success" => true]);

    } elseif ($action == 'save_appointment') { // Đặt lịch hẹn qua SĐT
        // Kiểm tra/Tạo bệnh nhân
        $stmtP = $pdo->prepare("SELECT id FROM patients WHERE phone = ? LIMIT 1");
        $stmtP->execute([$data->patient_phone]);
        $patient = $stmtP->fetch(PDO::FETCH_ASSOC);

        if ($patient) {
            $patient_id = $patient['id'];
        } else {
            $ins = $pdo->prepare("INSERT INTO patients (full_name, phone) VALUES (?, ?)");
            $ins->execute([$data->patient_name, $data->patient_phone]);
            $patient_id = $pdo->lastInsertId();
        }
        // Lưu lịch hẹn
        $sql = "INSERT INTO appointments (patient_id, doctor_id, start_time, reason, status) VALUES (?, ?, ?, ?, 'scheduled')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$patient_id, $data->doctor_id, $data->start_time, $data->reason]);
        echo json_encode(["success" => true]);
    }
    elseif ($action == 'check_in') { 
        // Nhận ID lịch hẹn
        $appt_id = $data->id;
        
        // Cập nhật trạng thái từ 'scheduled' -> 'waiting'
        $sql = "UPDATE appointments SET status = 'waiting' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$appt_id])) {
            echo json_encode(["success" => true, "message" => "Đã tiếp nhận bệnh nhân thành công"]);
        } else {
            echo json_encode(["success" => false, "error" => "Lỗi cập nhật database"]);
        }
    }
}

// 5. XỬ LÝ XÓA (DELETE)
if ($method == 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($action == 'delete_medicine' && $id) {
        $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
