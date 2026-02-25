<?php
session_start();
require_once 'db.php';

// 1. ตรวจสอบการ Login และสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
// กันเหนียว: ถ้าไม่มี program_id ใน session ให้ไปดึงจาก DB ใหม่
if (!isset($_SESSION['program_id'])) {
    $stmt_find = $conn->prepare("SELECT program_id FROM students WHERE student_id = ?");
    $stmt_find->bind_param("s", $student_id);
    $stmt_find->execute();
    $res_find = $stmt_find->get_result()->fetch_assoc();
    $_SESSION['program_id'] = $res_find['program_id'] ?? 0;
}
$program_id = $_SESSION['program_id'];

// 2. ตั้งค่าปีการศึกษา/เทอม (ควรดึงจากระบบตั้งค่ากลาง)
$current_semester = 1;
$current_year = 2567;

// 3. ดึงรายวิชาจากแผนการเรียน
$sql_plan = "SELECT c.* FROM study_plan sp 
             JOIN courses c ON sp.course_id = c.course_id 
             WHERE sp.program_id = ? AND sp.semester = ? AND sp.academic_year = ?";
$stmt = $conn->prepare($sql_plan);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
} // เช็ก Error ตรงนี้

$stmt->bind_param("iii", $program_id, $current_semester, $current_year);
$stmt->execute();
$plan_courses = $stmt->get_result();

// 4. จัดการการส่งฟอร์ม (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['courses'])) {
    $selected_courses = $_POST['courses'];

    // เริ่มต้น Transaction เพื่อความปลอดภัยของข้อมูล
    $conn->begin_transaction();

    try {
        // ลบข้อมูลการลงทะเบียนเดิมของเทอมนี้ (ถ้ามี) เพื่อป้องกันข้อมูลซ้ำซ้อน
        $check_old = $conn->prepare("SELECT reg_id FROM registrations WHERE student_id = ? AND semester = ? AND academic_year = ?");
        $check_old->bind_param("sii", $student_id, $current_semester, $current_year);
        $check_old->execute();
        $old_reg = $check_old->get_result()->fetch_assoc();

        if ($old_reg) {
            $old_id = $old_reg['reg_id'];
            $conn->query("DELETE FROM registration_details WHERE reg_id = $old_id");
            $conn->query("DELETE FROM registrations WHERE reg_id = $old_id");
        }

        // สร้างหัวข้อการลงทะเบียนใหม่
        $ins_reg = $conn->prepare("INSERT INTO registrations (student_id, semester, academic_year) VALUES (?, ?, ?)");
        $ins_reg->bind_param("sii", $student_id, $current_semester, $current_year);
        $ins_reg->execute();
        $reg_id = $conn->insert_id;

        // บันทึกรายวิชา
        $ins_detail = $conn->prepare("INSERT INTO registration_details (reg_id, course_id) VALUES (?, ?)");
        foreach ($selected_courses as $c_id) {
            $ins_detail->bind_param("is", $reg_id, $c_id);
            $ins_detail->execute();
        }

        $conn->commit(); // ยืนยันข้อมูลลง DB ทั้งหมด
        echo "<script>sessionStorage.setItem('status', 'success'); window.location='grades.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback(); // ถ้าพังให้ยกเลิกทั้งหมด
        $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ลงทะเบียนเรียน - NPRU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="fw-bold mb-0 text-success">✏️ ลงทะเบียนเรียน (<?php echo "$current_semester/$current_year"; ?>)</h4>
                            <a href="dashboard.php" class="btn btn-light btn-sm rounded-pill px-3">กลับหน้าหลัก</a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error_msg)): ?>
                            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                        <?php endif; ?>

                        <p class="text-muted small">รายวิชาที่แสดงด้านล่างอ้างอิงตาม <b>แผนการเรียน</b> ในหลักสูตรของคุณ</p>

                        <form method="POST" id="enrollForm">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="80" class="text-center">
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>รหัสวิชา</th>
                                            <th>ชื่อรายวิชา (ไทย)</th>
                                            <th class="text-center">หน่วยกิต</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($plan_courses->num_rows > 0): ?>
                                            <?php while ($row = $plan_courses->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" name="courses[]" value="<?php echo $row['course_id']; ?>" class="form-check-input course-check">
                                                    </td>
                                                    <td><span class="badge bg-secondary"><?php echo $row['course_id']; ?></span></td>
                                                    <td class="fw-semibold"><?php echo $row['course_name_th']; ?></td>
                                                    <td class="text-center"><?php echo $row['credits']; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    ไม่พบรายวิชาในแผนการเรียนเทอมนี้ <br>
                                                    <small>(กรุณาติดต่อเจ้าหน้าที่ หรือเช็กในระบบหลังบ้าน)</small>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 border-top pt-4">
                                <button type="submit" class="btn btn-success px-5 fw-bold rounded-pill shadow-sm py-2">
                                    บันทึกการลงทะเบียน
                                </button>
                                <p class="text-danger small mt-2 d-none" id="errorHint">! กรุณาเลือกอย่างน้อย 1 รายวิชา</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ระบบ "เลือกทั้งหมด"
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.course-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // ตรวจสอบก่อนส่งฟอร์ม
        document.getElementById('enrollForm').addEventListener('submit', function(e) {
            const checked = document.querySelectorAll('.course-check:checked');
            if (checked.length === 0) {
                e.preventDefault();
                document.getElementById('errorHint').classList.remove('d-none');
                Swal.fire('คำเตือน', 'กรุณาเลือกวิชาที่ต้องการลงทะเบียนอย่างน้อย 1 วิชา', 'warning');
            }
        });
    </script>
</body>

</html>