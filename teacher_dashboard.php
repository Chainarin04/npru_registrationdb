<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$msg = "";
// บันทึกเกรด
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_grade'])) {
    $detail_id = $_POST['detail_id'];
    $grade = $_POST['grade'];
    $stmt = $conn->prepare("UPDATE registration_details SET grade = ? WHERE detail_id = ?");
    $stmt->bind_param("si", $grade, $detail_id);
    if ($stmt->execute()) {
        $msg = "success";
    }
}

// บันทึกประกาศข่าวสาร
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_announcement'])) {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $stmt = $conn->prepare("INSERT INTO announcements (title, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $message);
    if ($stmt->execute()) {
        $msg = "announced";
    }
}

$sql = "SELECT rd.detail_id, s.student_id, s.first_name, s.last_name, c.course_id, c.course_name_th, rd.grade 
        FROM registration_details rd
        JOIN registrations r ON rd.reg_id = r.reg_id
        JOIN students s ON r.student_id = s.student_id
        JOIN courses c ON rd.course_id = c.course_id
        ORDER BY r.academic_year DESC, r.semester DESC, s.student_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ระบบอาจารย์ - NPRU Reg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-warning" href="teacher_dashboard.php">👨‍🏫 NPRU ระบบอาจารย์</a>
            <div>
                <a href="manage_students.php" class="btn btn-outline-light btn-sm me-2">จัดการนักศึกษา</a>
                <a href="manage_courses.php" class="btn btn-outline-light btn-sm me-2">จัดการวิชา</a>
                <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="card shadow-sm rounded-4 p-4 mb-4 border-top border-4 border-info">
            <h5 class="text-info">📢 ประกาศข่าวสารถึงนักศึกษา</h5>
            <form method="POST">
                <input type="text" name="title" class="form-control mb-2" placeholder="หัวข้อประกาศ" required>
                <textarea name="message" class="form-control mb-2" rows="2" placeholder="รายละเอียดประกาศ..." required></textarea>
                <button type="submit" name="post_announcement" class="btn btn-info text-white fw-bold w-100">ส่งประกาศ</button>
            </form>
        </div>

        <div class="card shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between mb-3">
                <h4 class="text-dark">📝 จัดการเกรดนักศึกษา</h4>
                <a href="export_excel.php" class="btn btn-success">📥 โหลด Excel</a>
            </div>

            <table id="gradeTable" class="table table-hover table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>รหัส</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>วิชา</th>
                        <th>เกรด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['student_id']; ?></td>
                            <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                            <td><?php echo $row['course_name_th']; ?></td>
                            <td><span class="badge <?php echo $row['grade'] ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $row['grade'] ?: 'รอผล'; ?></span></td>
                            <td>
                                <form method="POST" class="d-flex">
                                    <input type="hidden" name="detail_id" value="<?php echo $row['detail_id']; ?>">
                                    <select name="grade" class="form-select form-select-sm w-50 me-2" required>
                                        <option value="">เลือก</option>
                                        <option value="A" <?php echo ($row['grade'] == 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B+" <?php echo ($row['grade'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B" <?php echo ($row['grade'] == 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="C+" <?php echo ($row['grade'] == 'C+') ? 'selected' : ''; ?>>C+</option>
                                        <option value="C" <?php echo ($row['grade'] == 'C') ? 'selected' : ''; ?>>C</option>
                                        <option value="D+" <?php echo ($row['grade'] == 'D+') ? 'selected' : ''; ?>>D+</option>
                                        <option value="D" <?php echo ($row['grade'] == 'D') ? 'selected' : ''; ?>>D</option>
                                        <option value="F" <?php echo ($row['grade'] == 'F') ? 'selected' : ''; ?>>F</option>
                                    </select>
                                    <button type="submit" name="update_grade" class="btn btn-sm btn-primary">บันทึก</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // เปิดใช้งาน DataTables
        $(document).ready(function() {
            $('#gradeTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" // เปลี่ยนเมนูเป็นภาษาไทย
                }
            });
        });
    </script>

    <?php if ($msg == "success"): ?>
        <script>
            Swal.fire('สำเร็จ!', 'บันทึกเกรดเรียบร้อย', 'success').then(() => window.location = 'teacher_dashboard.php');
        </script>
    <?php elseif ($msg == "announced"): ?>
        <script>
            Swal.fire('ประกาศแล้ว!', 'นักศึกษาจะเห็นข้อความนี้ในหน้าแรก', 'info').then(() => window.location = 'teacher_dashboard.php');
        </script>
    <?php endif; ?>
</body>

</html>