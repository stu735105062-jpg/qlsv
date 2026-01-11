<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Lấy và làm sạch dữ liệu đầu vào
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Truy vấn lấy mật khẩu đã băm và vai trò của user
    // Lưu ý: SQL của bạn có cột role, hãy lấy nó ra để dùng sau này
    $stmt = $conn->prepare("SELECT password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 3. Kiểm tra mật khẩu
        // Nếu bạn dùng password_hash() khi đăng ký, hãy dùng password_verify() ở đây
        // Nếu hiện tại DB vẫn là 123456 (plain text), tạm thời dùng so sánh trực tiếp:
        if ($password === $user['password']) {
            
            // 4. Thiết lập Session an toàn
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role']; // Lưu quyền: admin hoặc staff
            
            // Làm mới ID session để chống tấn công Session Fixation
            session_regenerate_id(true);

            header("Location: home.php");
            exit;
        } else {
            $_SESSION['error'] = "Mật khẩu không chính xác.";
        }
    } else {
        $_SESSION['error'] = "Tài khoản không tồn tại.";
    }

    $stmt->close();
    $conn->close();
    
    header("Location: index.php");
    exit;
}