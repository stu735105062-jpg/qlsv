<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Kiểm tra quyền (Chỉ cho phép nếu đã đăng nhập)
if (!isset($_SESSION['role'])) { exit("Unauthorized"); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_grade'])) {
    // Lấy dữ liệu từ form Modal "Nhập điểm số"
    $student_id = (int)$_POST['student_id'];
    $course_id  = (int)$_POST['course_id'];
    $score      = (float)$_POST['score'];

    // Chuẩn bị truy vấn lưu điểm
    $stmt = $conn->prepare("INSERT INTO grades (student_id, course_id, score) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $student_id, $course_id, $score);
    
    if ($stmt->execute()) {
        // Thành công: Quay về trang chủ và hiện thông báo
        header("Location: home.php?msg=grade_added");
    } else {
        echo "Lỗi hệ thống: " . $conn->error;
    }
    $stmt->close();
}
?>