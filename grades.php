<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// ดึงข้อมูลเกรด - ตรวจสอบ SQL ตรงนี้ให้ดี
$sql = "SELECT rd.grade, c.course_id, c.course_name_th, c.credits, r.semester, r.academic_year 
        FROM registration_details rd
        JOIN registrations r ON rd.reg_id = r.reg_id
        JOIN courses c ON rd.course_id = c.course_id
        WHERE r.student_id = ? 
        ORDER BY r.academic_year DESC, r.semester DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // ถ้า SQL พัง จะโชว์ Error จริงๆ ออกมาให้ดูครับ
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// ฟังก์ชันแปลงเกรดเป็นคะแนน
function gradeToPoint($grade)
{
    $points = ["A" => 4, "B+" => 3.5, "B" => 3, "C+" => 2.5, "C" => 2, "D+" => 1.5, "D" => 1, "F" => 0];
    return $points[$grade] ?? null;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ผลการเรียน - NPRU Reg</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm border-0 rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold">📊 ผลการเรียนทั้งหมด</h4>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">กลับหน้าหลัก</a>
            </div>

            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ปี/เทอม</th>
                        <th>รหัสวิชา</th>
                        <th>ชื่อวิชา</th>
                        <th>หน่วยกิต</th>
                        <th>เกรด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_credits = 0;
                    $total_points = 0;
                    while ($row = $result->fetch_assoc()):
                        $gp = gradeToPoint($row['grade']);
                        if ($gp !== null) {
                            $total_credits += $row['credits'];
                            $total_points += ($gp * $row['credits']);
                        }
                    ?>
                        <tr>
                            <td><?php echo $row['semester'] . "/" . $row['academic_year']; ?></td>
                            <td><?php echo $row['course_id']; ?></td>
                            <td><?php echo $row['course_name_th']; ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><span class="badge bg-primary"><?php echo $row['grade'] ?: 'รอผล'; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="alert alert-info mt-3 text-end">
                <h5 class="mb-0">GPAX สะสม: <strong><?php echo ($total_credits > 0) ? number_format($total_points / $total_credits, 2) : '0.00'; ?></strong></h5>
            </div>
        </div>
    </div>
</body>

</html>