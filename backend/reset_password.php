<?php
// File: api/reset_all.php
require_once 'db.php';

echo "<h2>🛠️ CÔNG CỤ ĐẶT LẠI MẬT KHẨU</h2>";

try {
    // 1. Tạo mã hóa chuẩn cho số '123456'
    $new_pass = '123456';
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);

    // 2. Danh sách tài khoản cần reset
    $users = ['admin', 'bacsi1', 'thungan1', 'admin_kho'];

    foreach ($users as $u) {
        // Kiểm tra user có tồn tại không
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$u]);
        
        if ($stmt->fetch()) {
            // Có -> Update mật khẩu
            $pdo->prepare("UPDATE users SET password = ? WHERE username = ?")->execute([$hash, $u]);
            echo "<p>✅ Đã reset mật khẩu cho user <b>$u</b> thành công.</p>";
        } else {
            echo "<p style='color:gray'>⚠️ User <b>$u</b> chưa tồn tại (Không sao).</p>";
        }
    }

    echo "<hr>";
    echo "<h3>🎉 HOÀN TẤT!</h3>";
    echo "<p>Bây giờ bạn có thể đăng nhập tất cả tài khoản với mật khẩu: <b>123456</b></p>";
    echo "<a href='../login.html' style='font-size: 20px; font-weight: bold'>👉 Bấm vào đây để Đăng nhập</a>";

} catch (Exception $e) {
    echo "<h3 style='color:red'>Lỗi: " . $e->getMessage() . "</h3>";
}
?>