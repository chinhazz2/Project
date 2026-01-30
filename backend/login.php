<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$pass = $input['password'] ?? '';

// Tìm user trong DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$user]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if ($account && password_verify($pass, $account['password'])) {
    $_SESSION['user_id'] = $account['id'];
    $_SESSION['fullname'] = $account['full_name'];
    $_SESSION['role'] = $account['role'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Sai tài khoản hoặc mật khẩu!']);
}
?>