<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit();
}

// ตั้งค่า Header ให้ Browser รู้ว่ากำลังส่งไฟล์ Excel กลับไป
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Student_Grades_Report.xls");
header("Pragma: no-cache");
header("Expires: 0");

// พิมพ์ BOM เพื่อให้ Excel อ่านภาษาไทยออก
echo "\xEF\xBB\xBF";

$sql = "SELECT s.student_id, s.first_name, s.last_name, c.course_id, c.course_name_th, r.semester, r.academic_year, rd.grade 
        FROM registration_details rd
        JOIN registrations r ON rd.reg_id = r.reg_id
        JOIN students s ON r.student_id = s.student_id
        JOIN courses c ON rd.course_id = c.course_id
        ORDER BY r.academic_year DESC, r.semester DESC, s.student_id ASC";
$result = $conn->query($sql);
?>

<table border="1">
    <thead>
        <tr>
            <th>รหัสนักศึกษา</th>
            <th>ชื่อ</th>
            <th>นามสกุล</th>
            <th>รหัสวิชา</th>
            <th>ชื่อวิชา</th>
            <th>เทอม</th>
            <th>ปีการศึกษา</th>
            <th>เกรด</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td>="<?php echo $row['student_id']; ?>"</td>
                <td><?php echo $row['first_name']; ?></td>
                <td><?php echo $row['last_name']; ?></td>
                <td>="<?php echo $row['course_id']; ?>"</td>
                <td><?php echo $row['course_name_th']; ?></td>
                <td><?php echo $row['semester']; ?></td>
                <td><?php echo $row['academic_year']; ?></td>
                <td><?php echo $row['grade'] ? $row['grade'] : 'รอผล'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>