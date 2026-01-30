<?php
// File: api/check_session.php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'logged_in' => true,
        'user' => $_SESSION['fullname'] ?? 'User',
        
        // --- QUAN TRỌNG: Cần thêm dòng này để JS biết là ai ---
        'role' => $_SESSION['role'] ?? 'guest' 
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>