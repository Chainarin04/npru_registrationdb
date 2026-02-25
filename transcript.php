<?php
session_start();
require_once 'db.php';
$student_id = $_SESSION['student_id'];

// ดึงข้อมูลวิชาและเกรด แยกตามปีและเทอม
$sql = "SELECT r.semester, r.academic_year, c.course_id, c.course_name_th, c.credits, rd.grade 
        FROM registration_details rd
        JOIN registrations r ON rd.reg_id = r.reg_id
        JOIN courses c ON rd.course_id = c.course_id
        WHERE r.student_id = ? 
        ORDER BY r.academic_year ASC, r.semester ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$data = $stmt->get_result();

$transcript = [];
while ($row = $data->fetch_assoc()) {
    $transcript[$row['academic_year']][$row['semester']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Transcript - NPRU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }

        .transcript-container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 40px;
        }
    </style>
</head>

<body class="bg-secondary py-5">
    <div class="transcript-container shadow-lg">
        <div class="text-center mb-5">
            <h3 class="fw-bold">ใบรายงานผลการเรียน (Transcript)</h3>
            <p>มหาวิทยาลัยราชภัฏนครปฐม</p>
        </div>

        <?php foreach ($transcript as $year => $semesters): ?>
            <?php foreach ($semesters as $sem => $courses): ?>
                <h6 class="fw-bold mt-4">ภาคเรียนที่ <?php echo $sem; ?> ปีการศึกษา <?php echo $year; ?></h6>
                <table class="table table-sm table-bordered mb-4">
                    <thead class="table-light">
                        <tr>
                            <th>รหัสวิชา</th>
                            <th>ชื่อวิชา</th>
                            <th>หน่วยกิต</th>
                            <th>เกรด</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><?php echo $c['course_id']; ?></td>
                                <td><?php echo $c['course_name_th']; ?></td>
                                <td><?php echo $c['credits']; ?></td>
                                <td><?php echo $c['grade'] ?: 'W'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <div class="mt-5 text-center no-print">
            <button onclick="window.print()" class="btn btn-dark px-4">🖨️ พิมพ์ทรานสคริปต์</button>
            <a href="dashboard.php" class="btn btn-outline-dark">กลับหน้าหลัก</a>
        </div>
    </div>
</body>

</html>