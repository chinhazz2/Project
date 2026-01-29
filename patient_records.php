<?php
// File: api/patient_records.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");;
// Khi trình duyệt gửi phương thức OPTIONS, chúng ta trả về status 200 và dừng script ngay lập tức.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kết nối database
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'clinic_db';
    $username = 'root';
    $password = '';
    
    try {
        $conn = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $conn;
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Kết nối database thất bại: ' . $e->getMessage()]);
        exit;
    }
}

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Xử lý các request
switch($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

// Lấy danh sách bệnh nhân hoặc chi tiết 1 bệnh nhân
function handleGet($conn) {
    // Lấy chi tiết 1 bệnh nhân + lịch sử khám
    if(isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Thông tin bệnh nhân
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$patient) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy bệnh nhân']);
            return;
        }
        
        // Lịch sử khám bệnh
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.encounter_date,
                e.diagnosis,
                e.notes,
                u.full_name as doctor_name,
                d.specialty
            FROM encounters e
            LEFT JOIN doctors doc ON e.doctor_id = doc.id
            LEFT JOIN users u ON doc.user_id = u.id
            LEFT JOIN doctors d ON e.doctor_id = d.id
            WHERE e.patient_id = ?
            ORDER BY e.encounter_date DESC
        ");
        $stmt->execute([$id]);
        $encounters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lịch hẹn sắp tới
        $stmt = $conn->prepare("
            SELECT 
                a.id,
                a.start_time,
                a.end_time,
                a.status,
                a.reason,
                u.full_name as doctor_name
            FROM appointments a
            LEFT JOIN doctors doc ON a.doctor_id = doc.id
            LEFT JOIN users u ON doc.user_id = u.id
            WHERE a.patient_id = ? AND a.start_time >= NOW()
            ORDER BY a.start_time ASC
        ");
        $stmt->execute([$id]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'patient' => $patient,
            'encounters' => $encounters,
            'appointments' => $appointments
        ]);
    }
    // Tìm kiếm / Lấy danh sách bệnh nhân
    else {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;
        
        if($search) {
            $searchTerm = "%$search%";
            $stmt = $conn->prepare("
                SELECT * FROM patients 
                WHERE full_name LIKE ? 
                   OR phone LIKE ? 
                   OR insurance_no LIKE ?
                ORDER BY created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as total FROM patients 
                WHERE full_name LIKE ? OR phone LIKE ? OR insurance_no LIKE ?
            ");
            $countStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        } else {
            $stmt = $conn->prepare("
                SELECT * FROM patients 
                ORDER BY created_at DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute([]);
            
            $countStmt = $conn->query("SELECT COUNT(*) as total FROM patients");
        }
        
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'patients' => $patients,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit)
        ]);
    }
}

// Thêm bệnh nhân mới
function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate dữ liệu
    if(empty($data['full_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Họ tên không được để trống']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO patients (full_name, dob, gender, phone, address, insurance_no, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $data['full_name'],
            $data['dob'] ?? null,
            $data['gender'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['insurance_no'] ?? null
        ]);
        
        $newId = $conn->lastInsertId();
        
        // Lấy thông tin bệnh nhân vừa tạo
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$newId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Thêm bệnh nhân thành công',
            'patient' => $patient
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi khi thêm bệnh nhân: ' . $e->getMessage()]);
    }
}

// Cập nhật thông tin bệnh nhân
function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID bệnh nhân không hợp lệ']);
        return;
    }
    
    if(empty($data['full_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Họ tên không được để trống']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE patients 
            SET full_name = ?, dob = ?, gender = ?, phone = ?, address = ?, insurance_no = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['full_name'],
            $data['dob'] ?? null,
            $data['gender'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['insurance_no'] ?? null,
            $data['id']
        ]);
        
        // Lấy thông tin đã cập nhật
        $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
        $stmt->execute([$data['id']]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'message' => 'Cập nhật thông tin thành công',
            'patient' => $patient
        ]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi khi cập nhật: ' . $e->getMessage()]);
    }
}

// Xóa bệnh nhân
function handleDelete($conn) {
    if(!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID bệnh nhân không hợp lệ']);
        return;
    }
    
    $id = intval($_GET['id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        
        if($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Xóa bệnh nhân thành công']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy bệnh nhân']);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi khi xóa: ' . $e->getMessage()]);
    }
}
?>