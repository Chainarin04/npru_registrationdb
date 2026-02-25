<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$msg = "";

// ดึงข้อมูลเดิม
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    // อัปเดตข้อมูลทั่วไป
    $sql = "UPDATE students SET first_name=?, last_name=?, dob=?, phone=?, email=?, address=? WHERE student_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $first_name, $last_name, $dob, $phone, $email, $address, $student_id);
    $stmt->execute();

    // อัปเดตรหัสผ่าน (ถ้ากรอกมา)
    if (!empty($_POST['new_password'])) {
        $new_p = $_POST['new_password'];
        $conn->query("UPDATE users SET password='$new_p' WHERE student_id='$student_id'");
    }

    // จัดการรูปโปรไฟล์
    if (!empty($_FILES['profile_pic']['name'])) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $file_name = $student_id . "_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], "uploads/" . $file_name)) {
            $conn->query("UPDATE students SET profile_pic='$file_name' WHERE student_id='$student_id'");
        }
    }
    header("Location: profile.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลส่วนตัว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #f8f9fa;
            --card: #ffffff;
            --text: #212529;
        }

        [data-theme='dark'] {
            --bg: #1a1a1a;
            --card: #2d2d2d;
            --text: #f8f9fa;
        }

        body {
            background: var(--bg);
            color: var(--text);
            transition: 0.3s;
        }

        .card {
            background: var(--card);
            border: none;
            color: var(--text);
        }
    </style>
</head>

<body data-theme="light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold text-primary">👤 ข้อมูลส่วนตัวและบัญชี</h4>
                        <button onclick="toggleTheme()" class="btn btn-outline-secondary btn-sm">🌙 สลับโหมด</button>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row text-center mb-4">
                            <div class="col-12">
                                <img src="<?php echo (!empty($student['profile_pic']) && $student['profile_pic'] != 'default.png') ? 'uploads/' . $student['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; ?>"
                                    class="rounded-circle border border-4 border-primary object-fit-cover" width="120" height="120">
                                <div class="mt-2">
                                    <label class="btn btn-sm btn-light border">เปลี่ยนรูป <input type="file" name="profile_pic" hidden></label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">ชื่อ</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo $student['first_name']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo $student['last_name']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">อีเมล</label>
                                <input type="email" name="email" class="form-control" value="<?php echo $student['email']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">เบอร์โทรศัพท์</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo $student['phone']; ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">วันเกิด</label>
                                <input type="date" name="dob" class="form-control" value="<?php echo $student['dob']; ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">ที่อยู่</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo $student['address']; ?></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-danger">เปลี่ยนรหัสผ่านใหม่ (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label>
                                <input type="password" name="new_password" class="form-control" placeholder="รหัสผ่านใหม่">
                            </div>
                        </div>
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">บันทึกข้อมูล</button>
                            <a href="dashboard.php" class="btn btn-light border w-100">ยกเลิก</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            const body = document.body;
            const current = body.getAttribute('data-theme');
            body.setAttribute('data-theme', current === 'light' ? 'dark' : 'light');
        }
        <?php if (isset($_GET['success'])): ?>
            Swal.fire('สำเร็จ!', 'อัปเดตข้อมูลเรียบร้อย', 'success');
        <?php endif; ?>
    </script>
</body>

</html>