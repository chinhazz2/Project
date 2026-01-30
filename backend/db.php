<?php
$host = 'localhost';
$dbname = 'clinic_db'; // Tên database phải đúng với trong phpMyAdmin
$username = 'root';    // User mặc định của XAMPP
$password = '';        // Mật khẩu mặc định của XAMPP là rỗng

try {
    // Tạo biến $pdo (QUAN TRỌNG: Tên biến phải là $pdo)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Cấu hình chế độ báo lỗi
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Nếu kết nối lỗi, trả về JSON để frontend nhận biết
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]);
    exit();
}
?>
