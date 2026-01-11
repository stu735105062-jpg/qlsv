<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// 1. KIỂM TRA QUYỀN TRUY CẬP
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'];
$username = $_SESSION['username'] ?? 'User';

// 2. XỬ LÝ XUẤT EXCEL (FIX LỖI FONT)
if (isset($_GET['export'])) {
    $filename = "Danh_sach_sinh_vien_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM - Quan trọng để không lỗi font
    echo '<table border="1"><tr style="background-color: #4361ee; color: white;">
            <th>ID</th><th>Họ Tên</th><th>Email</th><th>Điện Thoại</th><th>Tỷ lệ (%)</th></tr>';
            
    $res = mysqli_query($conn, "SELECT s.*, COUNT(a.id) as total, SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present 
                                FROM student_info s LEFT JOIN attendance a ON s.id = a.student_id GROUP BY s.id");
    while($row = mysqli_fetch_assoc($res)){
        $p = ($row['total'] > 0) ? round(($row['present'] / $row['total']) * 100) : 0;
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['phone']}</td><td>{$p}%</td></tr>";
    }
    echo '</table>';
    exit;
}

// 3. XỬ LÝ ĐIỂM DANH (CHỈ ADMIN)
if (isset($_POST['mark_attendance']) && $role === 'admin') {
    $date = $_POST['attendance_date'];
    $present_ids = $_POST['present'] ?? [];
    
    $all_students = mysqli_query($conn, "SELECT id FROM student_info");
    $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE status = ?");
    
    while ($row = mysqli_fetch_assoc($all_students)) {
        $sid = $row['id'];
        $status = in_array($sid, $present_ids) ? 'present' : 'absent';
        $stmt->bind_param("isss", $sid, $date, $status, $status);
        $stmt->execute();
    }
    header("Location: home.php?status=success");
    exit;
}

// 4. TRUY VẤN DỮ LIỆU HIỂN THỊ
$students = [];
$attendanceData = [];
$query = "SELECT s.*, COUNT(a.id) as total_sessions, SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count
          FROM student_info s LEFT JOIN attendance a ON s.id = a.student_id
          GROUP BY s.id ORDER BY s.id DESC";
if ($res = mysqli_query($conn, $query)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['percent'] = ($row['total_sessions'] > 0) ? round(($row['present_count'] / $row['total_sessions']) * 100) : 0;
        $students[] = $row;
        $attendanceData[] = ['name' => $row['name'], 'percent' => $row['percent']];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS PRO - Quản lý</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --sidebar-width: 260px; --primary: #4361ee; --dark: #1e1e2d; }
        body { font-family: 'Inter', sans-serif; background: #f4f7fe; color: #2b3674; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark); color: white; transition: 0.3s; }
        .main-content { margin-left: var(--sidebar-width); padding: 30px; transition: 0.3s; }
        .nav-link { color: #a2a3b7; padding: 12px 20px; border-radius: 10px; margin: 5px 15px; border: none; background: none; text-align: left; cursor: pointer; display: flex; align-items: center; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: rgba(67, 97, 238, 0.15); color: var(--primary); }
        .nav-link.active { background: var(--primary); color: white; }
        .card-custom { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.03); background: #fff; }
        .chart-box { position: relative; height: 280px; width: 100%; }
        .progress { height: 8px; border-radius: 10px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="p-4 text-center"><h3 class="fw-bold text-white">SMS PRO</h3><small class="text-muted">Management System</small></div>
    <nav class="nav flex-column mt-3">
        <button class="nav-link active" data-tab="dashboard"><i class="bi bi-speedometer2 me-3"></i> Dashboard</button>
        <button class="nav-link" data-tab="students"><i class="bi bi-people me-3"></i> Sinh viên</button>
        <?php if($role == 'admin'): ?>
            <button class="nav-link" data-tab="attendance"><i class="bi bi-calendar-check me-3"></i> Điểm danh</button>
        <?php endif; ?>
        <div class="mt-auto p-3" style="position: absolute; bottom: 0; width: 100%;">
            <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-3"></i> Đăng xuất</a>
        </div>
    </nav>
</aside>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Xin chào, <?= htmlspecialchars($username) ?> 👋</h4>
        <span class="badge bg-white text-primary shadow-sm p-3 rounded-pill border">Quyền: <?= strtoupper($role) ?></span>
    </div>

    <div id="dashboard" class="content-section">
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-graph-up me-2"></i>Tỷ lệ chuyên cần hệ thống</h5>
            <div class="chart-box">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <div id="students" class="content-section d-none">
        <div class="card card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Danh sách sinh viên</h5>
                <a href="?export=true" class="btn btn-success btn-sm rounded-pill px-3">
                    <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel
                </a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-muted">
                        <tr>
                            <th>SINH VIÊN</th>
                            <th>LIÊN HỆ</th>
                            <th width="30%">CHUYÊN CẦN</th>
                            <th class="text-end">TỶ LỆ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                <small class="text-muted">ID: #<?= $s['id'] ?></small>
                            </td>
                            <td><small><?= $s['email'] ?></small></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: <?= $s['percent'] ?>%"></div>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-primary"><?= $s['percent'] ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if($role == 'admin'): ?>
    <div id="attendance" class="content-section d-none">
        <div class="card card-custom p-4 mx-auto" style="max-width: 600px;">
            <h5 class="fw-bold text-center mb-4"><i class="bi bi-check2-all me-2"></i>Điểm danh nhanh</h5>
            <form method="POST">
                <div class="mb-4 text-center">
                    <label class="small text-muted d-block mb-2">Ngày điểm danh</label>
                    <input type="date" name="attendance_date" class="form-control form-control-lg text-center mx-auto" style="max-width: 250px;" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="list-group list-group-flush mb-4" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($students as $s): ?>
                    <label class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                        <span><?= htmlspecialchars($s['name']) ?></span>
                        <input class="form-check-input" type="checkbox" name="present[]" value="<?= $s['id'] ?>" style="width: 22px; height: 22px;">
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="mark_attendance" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">Xác nhận lưu dữ liệu</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Xử lý chuyển tab chuyên nghiệp
    document.querySelectorAll('.nav-link[data-tab]').forEach(btn => {
        btn.onclick = function() {
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.add('d-none'));
            document.getElementById(this.dataset.tab).classList.remove('d-none');
        };
    });

    // Cấu hình biểu đồ
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_reverse(array_column($attendanceData, 'name'))) ?>,
            datasets: [{
                label: 'Tỷ lệ đi học (%)',
                data: <?= json_encode(array_reverse(array_column($attendanceData, 'percent'))) ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#4361ee'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
</body>
</html>