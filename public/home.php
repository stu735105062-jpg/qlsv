<?php
session_start();

// --- 1. CẤU HÌNH KẾT NỐI ---
$host = 'localhost'; $user = 'root'; $pass = ''; $db = 'student_management';
$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Kết nối thất bại: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8mb4");

// --- 2. XỬ LÝ ĐĂNG XUẤT ---
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy(); 
    // Sau khi đăng xuất, chuyển hướng về trang login (thay index.php bằng file login của bạn nếu cần)
    header("Location: login.php"); 
    exit;
}

// --- 3. GIẢ LẬP ĐĂNG NHẬP ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}
$u_id = $_SESSION['user_id'];
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE id = $u_id");
$current_user = mysqli_fetch_assoc($user_res);
$role = $current_user['role'] ?? 'student';

// --- 4. XỬ LÝ XUẤT EXCEL ---
if (isset($_GET['export_excel'])) {
    $filename = "Bang_Diem_Sinh_Vien_" . date('dmY') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    echo "\xEF\xBB\xBF"; 
    echo "STT\tHọ Tên\tEmail\tLớp\tGPA\n";
    $res = mysqli_query($conn, "SELECT s.*, c.class_name, (SELECT AVG(score) FROM grades WHERE student_id = s.id) as gpa FROM student_info s LEFT JOIN classes c ON s.class_id = c.class_id");
    $stt = 1;
    while($row = mysqli_fetch_assoc($res)) {
        echo $stt++ . "\t" . $row['name'] . "\t" . $row['email'] . "\t" . ($row['class_name'] ?? 'N/A') . "\t" . round($row['gpa'],2) . "\n";
    }
    exit;
}

// --- 5. XỬ LÝ LƯU/XÓA DỮ LIỆU ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $role == 'admin') {
    if (isset($_POST['add_class'])) {
        $name = mysqli_real_escape_string($conn, $_POST['class_name']);
        $teacher = mysqli_real_escape_string($conn, $_POST['teacher_name']);
        mysqli_query($conn, "INSERT INTO classes (class_name, teacher_name) VALUES ('$name', '$teacher')");
    }
    if (isset($_POST['add_student'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $c_id = (int)$_POST['class_id'];
        mysqli_query($conn, "INSERT INTO student_info (name, email, phone, class_id) VALUES ('$name', '$email', '$phone', $c_id)");
    }
    if (isset($_POST['delete_id'])) {
        $del_id = (int)$_POST['delete_id'];
        mysqli_query($conn, "DELETE FROM student_info WHERE id = $del_id");
    }
    header("Location: " . $_SERVER['PHP_SELF']); exit;
}

// --- 6. TRUY VẤN DỮ LIỆU ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_search = $search ? "WHERE s.name LIKE '%$search%' OR s.email LIKE '%$search%'" : "";

if ($role == 'admin') {
    $students = mysqli_fetch_all(mysqli_query($conn, "SELECT s.*, c.class_name, (SELECT AVG(score) FROM grades WHERE student_id = s.id) as gpa FROM student_info s LEFT JOIN classes c ON s.class_id = c.class_id $where_search ORDER BY s.id DESC"), MYSQLI_ASSOC);
} else {
    $u_name = $current_user['username'];
    $students = mysqli_fetch_all(mysqli_query($conn, "SELECT s.*, c.class_name, (SELECT AVG(score) FROM grades WHERE student_id = s.id) as gpa FROM student_info s LEFT JOIN classes c ON s.class_id = c.class_id WHERE s.name = '$u_name'"), MYSQLI_ASSOC);
}

$classes = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM classes"), MYSQLI_ASSOC);
$present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='present'"))['count'];
$absent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='absent'"))['count'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>SMS PRO - Hệ thống Quản lý</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --sidebar: #0b1437; --primary: #4318ff; --bg: #f4f7fe; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--sidebar); color: white; padding: 25px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 40px; }
        .card-custom { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .nav-link { color: #a3aed0; padding: 15px; border-radius: 12px; margin-bottom: 10px; border: none; background: none; width: 100%; text-align: left; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background: var(--primary); color: white; }
        /* CSS Nút đăng xuất */
        .logout-btn { color: #ff5e5e; text-decoration: none; padding: 15px; border-radius: 12px; transition: 0.3s; margin-top: auto; }
        .logout-btn:hover { background: rgba(255, 94, 94, 0.1); color: #ff5e5e; }
    </style>
</head>
<body>

<aside class="sidebar">
    <h3 class="fw-bold mb-5 text-center text-primary">SMS PRO</h3>
    <nav>
        <button class="nav-link active" onclick="showTab('tab-data')"><i class="bi bi-grid-fill me-2"></i> Dashboard</button>
        <button class="nav-link" onclick="showTab('tab-charts')"><i class="bi bi-bar-chart-fill me-2"></i> Biểu đồ</button>
        <?php if($role == 'admin'): ?>
            <hr>
            <p class="small text-muted text-uppercase fw-bold">Quản trị</p>
            <button class="nav-link" data-bs-toggle="modal" data-bs-target="#modalAddClass"><i class="bi bi-folder-plus me-2"></i> Thêm Lớp</button>
            <button class="nav-link" data-bs-toggle="modal" data-bs-target="#modalAddStudent"><i class="bi bi-person-plus-fill me-2"></i> Thêm Sinh viên</button>
        <?php endif; ?>
    </nav>

    <a href="?action=logout" class="logout-btn fw-bold" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')">
        <i class="bi bi-box-arrow-left me-2"></i> Đăng xuất
    </a>
</aside>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Quản lý Học tập</h2>
            <small class="text-muted">Xin chào, <b><?= htmlspecialchars($current_user['username']) ?></b> (Quyền: <?= strtoupper($role) ?>)</small>
        </div>
        
        <form class="d-flex gap-2 w-50" method="GET">
            <input type="text" name="search" class="form-control rounded-pill border-0 shadow-sm px-4" placeholder="Tìm tên sinh viên hoặc email..." value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div id="tab-data">
        <div class="card card-custom p-4 bg-white">
            <div class="d-flex justify-content-between mb-4">
                <h5 class="fw-bold">Bảng kết quả học tập</h5>
                <a href="?export_excel=1" class="btn btn-success rounded-pill px-3"><i class="bi bi-file-earmark-excel me-1"></i> Xuất File Excel</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead class="text-muted small">
                        <tr><th>HỌ VÀ TÊN</th><th>LỚP</th><th>ĐIỂM TRUNG BÌNH (GPA)</th><?php if($role=='admin') echo '<th class="text-end">HÀNH ĐỘNG</th>'; ?></tr>
                    </thead>
                    <tbody>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="small text-muted"><?= $s['email'] ?></div>
                            </td>
                            <td><span class="badge bg-light text-primary"><?= $s['class_name'] ?? 'N/A' ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold me-2"><?= number_format($s['gpa'],2) ?></span>
                                    <div class="progress w-50" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $s['gpa']>=5?'primary':'danger' ?>" style="width: <?= ($s['gpa']/10)*100 ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <?php if($role == 'admin'): ?>
                            <td class="text-end">
                                <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa?')">
                                    <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash3-fill"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-charts" class="d-none">
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold mb-4">Phân tích điểm số (GPA)</h5>
                    <canvas id="gpaChart"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold mb-4">Tỷ lệ chuyên cần (%)</h5>
                    <canvas id="attendanceChart"></canvas>
                    <div class="mt-4 text-center">
                        <span class="badge bg-success">Đi học: <?= $present ?></span>
                        <span class="badge bg-danger">Vắng: <?= $absent ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php if($role == 'admin'): ?>
<div class="modal fade" id="modalAddClass" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content card-custom p-3">
            <div class="modal-header border-0"><h5 class="fw-bold">Thêm Lớp học mới</h5></div>
            <div class="modal-body">
                <input type="text" name="class_name" class="form-control mb-3" placeholder="Tên lớp (VD: CNTT K1)" required>
                <input type="text" name="teacher_name" class="form-control mb-3" placeholder="Tên Giảng viên phụ trách">
                <button type="submit" name="add_class" class="btn btn-primary w-100 py-2 rounded-pill">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalAddStudent" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content card-custom p-3">
            <div class="modal-header border-0"><h5 class="fw-bold">Thêm Sinh viên</h5></div>
            <div class="modal-body">
                <input type="text" name="name" class="form-control mb-3" placeholder="Họ và tên" required>
                <input type="email" name="email" class="form-control mb-3" placeholder="Email liên hệ">
                <input type="text" name="phone" class="form-control mb-3" placeholder="Số điện thoại">
                <select name="class_id" class="form-select mb-3" required>
                    <option value="">-- Chọn lớp học --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['class_id'] ?>"><?= $c['class_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="add_student" class="btn btn-primary w-100 py-2 rounded-pill">Lưu Sinh viên</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function showTab(id) {
        document.getElementById('tab-data').classList.add('d-none');
        document.getElementById('tab-charts').classList.add('d-none');
        document.getElementById(id).classList.remove('d-none');
        document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }

    const ctxGpa = document.getElementById('gpaChart').getContext('2d');
    new Chart(ctxGpa, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($students, 'name')) ?>,
            datasets: [{
                label: 'Điểm GPA',
                data: <?= json_encode(array_column($students, 'gpa')) ?>,
                backgroundColor: '#4318ff',
                borderRadius: 8
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctxAtt, {
        type: 'doughnut',
        data: {
            labels: ['Đi học', 'Vắng'],
            datasets: [{
                data: [<?= $present ?>, <?= $absent ?>],
                backgroundColor: ['#05cd99', '#ee5d50'],
                borderWidth: 0
            }]
        },
        options: { cutout: '70%', plugins: { legend: { position: 'bottom' } } }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>