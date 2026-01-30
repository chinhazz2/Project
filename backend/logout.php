<?php
session_start();
session_destroy(); // Xóa sạch phiên làm việc
header("Location: ../login.html"); // Quay về trang đăng nhập
exit;
?>