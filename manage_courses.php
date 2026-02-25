<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

// เฉพาะอาจารย์ (admin) เท่านั้นเข้าหน้านี้ได้
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$msg = "";

// --- ระบบเพิ่มวิชา ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_id = $_POST['course_id'];
    $course_name_th = $_POST['course_name_th'];
    $course_name_en = $_POST['course_name_en'];
    $credits = $_POST['credits'];
    $program_id = $_POST['program_id'];

    $sql = "INSERT INTO courses (course_id, course_name_th, course_name_en, credits, program_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssis", $course_id, $course_name_th, $course_name_en, $credits, $program_id);
    if ($stmt->execute()) {
        $msg = "success";
    } else {
        $msg = "error";
    }
}

// --- ระบบลบวิชา ---
if (isset($_GET['del_id'])) {
    $del_id = $_GET['del_id'];
    // ลบได้เฉพาะวิชาที่ยังไม่มีเด็กเลือกลงทะเบียน
    try {
        $conn->query("DELETE FROM courses WHERE course_id = '$del_id'");
        header("Location: manage_courses.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $msg = "cannot_delete"; // ลบไม่ได้เพราะติด Foreign Key (มีเด็กลงเรียนอยู่)
    }
}

$courses = $conn->query("SELECT c.*, p.program_name_th FROM courses c JOIN programs p ON c.program_id = p.program_id ORDER BY c.course_id ASC");
$programs = $conn->query("SELECT * FROM programs");
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>จัดการรายวิชา - NPRU Reg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="teacher_dashboard.php">👨‍🏫 NPRU ระบบอาจารย์ผู้สอน</a>
            <div>
                <a href="teacher_dashboard.php" class="btn btn-outline-light btn-sm me-2">ตัดเกรด</a>
                <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm rounded-4 p-4">
                    <h5 class="text-primary mb-3">➕ เพิ่มวิชาใหม่</h5>
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label">รหัสวิชา (เช่น 7154401)</label>
                            <input type="text" name="course_id" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ชื่อวิชา (ภาษาไทย)</label>
                            <input type="text" name="course_name_th" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ชื่อวิชา (ภาษาอังกฤษ)</label>
                            <input type="text" name="course_name_en" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">หน่วยกิต</label>
                            <input type="number" name="credits" class="form-control" value="3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หลักสูตร</label>
                            <select name="program_id" class="form-select" required>
                                <?php while ($p = $programs->fetch_assoc()): ?>
                                    <option value="<?php echo $p['program_id']; ?>"><?php echo $p['program_name_th']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_course" class="btn btn-primary w-100">บันทึกรายวิชา</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm rounded-4 p-4">
                    <h5 class="text-dark mb-3">📚 รายวิชาทั้งหมดในระบบ</h5>
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>รหัสวิชา</th>
                                <th class="text-start">ชื่อวิชา</th>
                                <th>หน่วยกิต</th>
                                <th>หลักสูตร</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($c = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $c['course_id']; ?></td>
                                    <td class="text-start"><?php echo $c['course_name_th']; ?></td>
                                    <td><?php echo $c['credits']; ?></td>
                                    <td><?php echo $c['program_name_th']; ?></td>
                                    <td>
                                        <a href="manage_courses.php?del_id=<?php echo $c['course_id']; ?>" onclick="return confirm('ลบวิชานี้ใช่ไหม?');" class="btn btn-danger btn-sm">ลบ</a>
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
            Swal.fire('สำเร็จ!', 'เพิ่มรายวิชาเรียบร้อยแล้ว', 'success');
        </script>
    <?php elseif ($msg == 'error'): ?>
        <script>
            Swal.fire('ข้อผิดพลาด!', 'รหัสวิชานี้อาจจะมีในระบบแล้ว', 'error');
        </script>
    <?php elseif ($msg == 'cannot_delete'): ?>
        <script>
            Swal.fire('ลบไม่ได้!', 'วิชานี้มีนักศึกษาลงทะเบียนเรียนอยู่!', 'warning');
        </script>
    <?php endif; ?>

</body>

</html>