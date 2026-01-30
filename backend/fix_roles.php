<?php
// File: api/fix_roles.php
require_once 'db.php';

echo "<h1>🛠️ CÔNG CỤ CHUẨN HÓA QUYỀN (ROLES)</h1>";

try {
    // 1. Sửa tất cả tài khoản có chữ 'bacsi' thành quyền DOCTOR
    $sqlDoctor = "UPDATE users SET role = 'doctor' WHERE username LIKE '%bacsi%'";
    $stmt1 = $pdo->prepare($sqlDoctor);
    $stmt1->execute();
    echo "<p>✅ Đã cập nhật quyền <b>BÁC SĨ (doctor)</b> cho: " . $stmt1->rowCount() . " tài khoản.</p>";

    // 2. Sửa tất cả tài khoản có chữ 'thungan' thành quyền CASHIER
    $sqlCashier = "UPDATE users SET role = 'cashier' WHERE username LIKE '%thungan%'";
    $stmt2 = $pdo->prepare($sqlCashier);
    $stmt2->execute();
    echo "<p>✅ Đã cập nhật quyền <b>THU NGÂN (cashier)</b> cho: " . $stmt2->rowCount() . " tài khoản.</p>";

    // 3. Sửa tất cả tài khoản có chữ 'admin' thành quyền ADMIN
    $sqlAdmin = "UPDATE users SET role = 'admin' WHERE username LIKE '%admin%'";
    $stmt3 = $pdo->prepare($sqlAdmin);
    $stmt3->execute();
    echo "<p>✅ Đã cập nhật quyền <b>QUẢN TRỊ (admin)</b> cho: " . $stmt3->rowCount() . " tài khoản.</p>";

    // 4. In danh sách để kiểm tra
    echo "<hr><h3>📋 DANH SÁCH TÀI KHOẢN HIỆN TẠI:</h3>";
    $users = $pdo->query("SELECT username, full_name, role FROM users")->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Username</th><th>Họ tên</th><th>Quyền (Role)</th></tr>";
    foreach ($users as $u) {
        $roleColor = ($u['role'] == 'admin') ? 'red' : (($u['role'] == 'doctor') ? 'blue' : 'green');
        echo "<tr>";
        echo "<td>{$u['username']}</td>";
        echo "<td>{$u['full_name']}</td>";
        echo "<td style='color:$roleColor; font-weight:bold'>{$u['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><a href='logout.php' style='background:red; color:white; padding:10px; text-decoration:none'>👉 BẤM VÀO ĐÂY ĐỂ ĐĂNG XUẤT & THỬ LẠI</a>";

} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>