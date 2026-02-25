<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

// เฉพาะอาจารย์ (admin) เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$msg = "";

// --- ระบบเพิ่มนักศึกษาใหม่ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $program_id = $_POST['program_id'];
    $username = $_POST['username']; // ใช้เป็นชื่อล็อกอิน

    // 1. เพิ่มลงตาราง students
    $sql1 = "INSERT INTO students (student_id, first_name, last_name, program_id) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssss", $student_id, $first_name, $last_name, $program_id);

    if ($stmt1->execute()) {
        // 2. สร้างบัญชีผู้ใช้ในตาราง users อัตโนมัติ (รหัสผ่านตั้งต้น 1234)
        $sql2 = "INSERT INTO users (username, password, student_id, role) VALUES (?, '1234', ?, 'student')";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("ss", $username, $student_id);
        $stmt2->execute();
        $msg = "success";
    } else {
        $msg = "error";
    }
}

// --- ระบบลบนักศึกษา ---
if (isset($_GET['del_id'])) {
    $del_id = $_GET['del_id'];
    try {
        $conn->query("DELETE FROM users WHERE student_id = '$del_id'"); // ลบบัญชีล็อกอินก่อน
        $conn->query("DELETE FROM students WHERE student_id = '$del_id'"); // แล้วค่อยลบประวัตินักศึกษา
        header("Location: manage_students.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $msg = "cannot_delete"; // ลบไม่ได้ถ้าเด็กลงทะเบียนเรียนไปแล้ว
    }
}

$students = $conn->query("SELECT s.*, p.program_name_th, u.username FROM students s JOIN programs p ON s.program_id = p.program_id LEFT JOIN users u ON s.student_id = u.student_id ORDER BY s.student_id ASC");
$programs = $conn->query("SELECT * FROM programs");
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการนักศึกษา - NPRU Reg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="teacher_dashboard.php">👨‍🏫 NPRU ระบบอาจารย์ผู้สอน</a>
            <div>
                <a href="manage_courses.php" class="btn btn-outline-light btn-sm me-2">จัดการวิชา</a>
                <a href="teacher_dashboard.php" class="btn btn-outline-light btn-sm me-2">ตัดเกรด</a>
                <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-4 p-4 border-top border-4 border-success">
                    <h5 class="text-success mb-3">👤 เพิ่มนักศึกษาใหม่</h5>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label">รหัสนักศึกษา</label>
                            <input type="text" name="student_id" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ชื่อ</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">นามสกุล</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">หลักสูตร</label>
                            <select name="program_id" class="form-select" required>
                                <?php while ($p = $programs->fetch_assoc()): ?>
                                    <option value="<?php echo $p['program_id']; ?>"><?php echo $p['program_name_th']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label text-primary">Username สำหรับเข้าระบบ</label>
                            <input type="text" name="username" class="form-control" placeholder="เช่น somsak123" required>
                            <small class="text-muted">* รหัสผ่านเริ่มต้นคือ 1234</small>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-success w-100">บันทึกนักศึกษา</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm rounded-4 p-4">
                    <h5 class="text-dark mb-3">📋 รายชื่อนักศึกษาทั้งหมด</h5>
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>รหัสนักศึกษา</th>
                                <th class="text-start">ชื่อ-นามสกุล</th>
                                <th>หลักสูตร</th>
                                <th>Username</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $s['student_id']; ?></td>
                                    <td class="text-start"><?php echo $s['first_name'] . " " . $s['last_name']; ?></td>
                                    <td><?php echo $s['program_name_th']; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $s['username']; ?></span></td>
                                    <td>
                                        <a href="manage_students.php?del_id=<?php echo $s['student_id']; ?>" onclick="return confirm('ลบนักศึกษาคนนี้ใช่ไหม?');" class="btn btn-danger btn-sm">ลบ</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($msg == 'success'): ?>
        <script>
            Swal.fire('สำเร็จ!', 'เพิ่มนักศึกษาและสร้างบัญชีเรียบร้อยแล้ว', 'success');
        </script>
    <?php elseif ($msg == 'error'): ?>
        <script>
            Swal.fire('ข้อผิดพลาด!', 'รหัสนักศึกษา หรือ Username นี้อาจจะมีในระบบแล้ว', 'error');
        </script>
    <?php elseif ($msg == 'cannot_delete'): ?>
        <script>
            Swal.fire('ลบไม่ได้!', 'นักศึกษาคนนี้มีการลงทะเบียนเรียนไปแล้ว!', 'warning');
        </script>
    <?php endif; ?>
</body>

</html>