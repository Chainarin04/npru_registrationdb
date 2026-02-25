<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// ดึงข้อมูลนักศึกษา
$stmt = $conn->prepare("SELECT s.*, p.program_name_th FROM students s JOIN programs p ON s.program_id = p.program_id WHERE s.student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// สมมติว่าพิมพ์ใบแจ้งหนี้ของเทอมล่าสุดที่เด็กลงทะเบียน
$sql_courses = "SELECT c.course_id, c.course_name_th, c.credits FROM registration_details rd 
                JOIN registrations r ON rd.reg_id = r.reg_id 
                JOIN courses c ON rd.course_id = c.course_id 
                WHERE r.student_id = ? AND r.academic_year = 2567 AND r.semester = 1"; // เปลี่ยนเทอม/ปี ได้ตามต้องการ
$stmt_courses = $conn->prepare($sql_courses);
$stmt_courses->bind_param("s", $student_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result();

$total_credits = 0;
$fee_per_credit = 500;
$university_fee = 2000;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบแจ้งชำระเงินค่าเทอม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: white !important;
            }

            .card {
                border: 1px solid #000 !important;
                box-shadow: none !important;
            }
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            background: #fff;
        }
    </style>
</head>

<body class="bg-light p-4">

    <div class="container invoice-box card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="text-primary fw-bold">มหาวิทยาลัยราชภัฏนครปฐม (NPRU)</h2>
                <h5>ใบแจ้งชำระเงินค่าลงทะเบียนเรียน (Invoice)</h5>
            </div>
            <div class="text-end">
                <p class="mb-0">วันที่พิมพ์: <?php echo date("d/m/Y"); ?></p>
                <button onclick="window.print()" class="btn btn-primary mt-2 no-print">🖨️ พิมพ์ใบแจ้งหนี้</button>
                <a href="dashboard.php" class="btn btn-secondary mt-2 no-print">กลับหน้าแรก</a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <strong>ชื่อ-นามสกุล:</strong> <?php echo $student['first_name'] . " " . $student['last_name']; ?><br>
                <strong>รหัสนักศึกษา:</strong> <?php echo $student['student_id']; ?><br>
                <strong>หลักสูตร:</strong> <?php echo $student['program_name_th']; ?>
            </div>
            <div class="col-sm-6 text-end">
                <strong>ภาคเรียนที่:</strong> 1 / 2567<br>
                <strong>กำหนดชำระภายใน:</strong> 30 พฤศจิกายน 2567
            </div>
        </div>

        <table class="table table-bordered text-center">
            <thead class="table-light">
                <tr>
                    <th>รหัสวิชา</th>
                    <th class="text-start">ชื่อวิชา</th>
                    <th>หน่วยกิต</th>
                    <th>จำนวนเงิน (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $courses->fetch_assoc()): ?>
                    <?php
                    $total_credits += $c['credits'];
                    $cost = $c['credits'] * $fee_per_credit;
                    ?>
                    <tr>
                        <td><?php echo $c['course_id']; ?></td>
                        <td class="text-start"><?php echo $c['course_name_th']; ?></td>
                        <td><?php echo $c['credits']; ?></td>
                        <td><?php echo number_format($cost, 2); ?></td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3" class="text-end">ค่าบำรุงมหาวิทยาลัย</td>
                    <td><?php echo number_format($university_fee, 2); ?></td>
                </tr>
                <tr class="fw-bold fs-5 table-primary">
                    <td colspan="3" class="text-end">ยอดชำระสุทธิ (Total Amount)</td>
                    <?php $total_amount = ($total_credits * $fee_per_credit) + $university_fee; ?>
                    <td class="text-danger"><?php echo number_format($total_amount, 2); ?> ฿</td>
                </tr>
            </tbody>
        </table>

        <div class="text-center mt-5">
            <p class="mb-2">สแกนเพื่อชำระเงินผ่าน Mobile Banking</p>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=PAY-NPRU-<?php echo $student_id; ?>-<?php echo $total_amount; ?>" alt="QR Code">
            <p class="mt-2 text-muted">Ref1: <?php echo $student_id; ?> | Ref2: 25671</p>
        </div>
    </div>

</body>

</html>