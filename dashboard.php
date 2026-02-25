<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// ดึงข้อมูลนักศึกษา
$sql = "SELECT s.*, p.program_name_th 
        FROM students s 
        JOIN programs p ON s.program_id = p.program_id 
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// เช็ครูปโปรไฟล์ ถ้าไม่มีให้ใช้รูปพื้นฐาน
$profile_image = (!empty($student['profile_pic']) && $student['profile_pic'] != 'default.png') ? "uploads/" . $student['profile_pic'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>หน้าแรก - NPRU Reg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">NPRU REG</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text text-white me-3">ยินดีต้อนรับ, <?php echo $student['first_name']; ?></span>
                <a href="profile.php" class="btn btn-warning btn-sm me-2">ตั้งค่าโปรไฟล์</a>
                <a href="logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center mt-3">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" width="120" height="120" class="mb-3 rounded-circle object-fit-cover border border-3 border-primary">
                        <h5 class="card-title fw-bold"><?php echo $student['first_name'] . " " . $student['last_name']; ?></h5>
                        <p class="text-muted mb-1">รหัส: <?php echo $student['student_id']; ?></p>
                        <p class="text-muted mb-2 text-sm">วันเกิด: <?php echo $student['dob'] ? date("d/m/Y", strtotime($student['dob'])) : '-'; ?></p>
                        <span class="badge bg-info text-dark"><?php echo $student['program_name_th']; ?></span>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="row">
                    <div class="col-sm-4 mb-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2">
                            <div class="card-body text-center">
                                <h4 class="text-primary mb-2">📚</h4>
                                <h6 class="fw-bold">ผลการเรียน</h6>
                                <a href="grades.php" class="btn btn-outline-primary btn-sm w-100 mt-1">ดูเกรด</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2">
                            <div class="card-body text-center">
                                <h4 class="text-success mb-2">✏️</h4>
                                <h6 class="fw-bold">ลงทะเบียน</h6>
                                <a href="enroll.php" class="btn btn-outline-success btn-sm w-100 mt-1">ลงทะเบียน</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 mb-3">
                        <div class="card border-0 shadow-sm rounded-4 h-100 p-2">
                            <div class="card-body text-center">
                                <h4 class="text-danger mb-2">💸</h4>
                                <h6 class="fw-bold">ค่าเทอม</h6>
                                <a href="invoice.php" class="btn btn-outline-danger btn-sm w-100 mt-1">พิมพ์ใบชำระ</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mt-2 border-start border-5 border-info">
                    <h5 class="text-info fw-bold mb-3">📢 ประกาศจากมหาวิทยาลัย</h5>
                    <div class="list-group">
                        <?php
                        // ดึงประกาศ 3 อันดับล่าสุด
                        $announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                        if ($announcements && $announcements->num_rows > 0):
                            while ($ann = $announcements->fetch_assoc()):
                        ?>
                                <div class="list-group-item list-group-item-action flex-column align-items-start border-0 border-bottom px-0 py-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($ann['title']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($ann['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-secondary small"><?php echo htmlspecialchars($ann['message']); ?></p>
                                </div>
                            <?php endwhile;
                        else: ?>
                            <p class="text-muted text-center my-4">ยังไม่มีประกาศใหม่ในขณะนี้</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>

</html>