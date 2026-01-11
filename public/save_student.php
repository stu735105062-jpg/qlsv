<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['role'])) { exit("Unauthorized"); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form Modal "Thêm Sinh viên"
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $class_id = (int)$_POST['class_id'];

    $stmt = $conn->prepare("INSERT INTO student_info (name, email, phone, class_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $email, $phone, $class_id);
    
    if ($stmt->execute()) {
        header("Location: home.php?msg=student_added");
    } else {
        echo "Lỗi hệ thống: " . $conn->error;
    }
    $stmt->close();
}
?>