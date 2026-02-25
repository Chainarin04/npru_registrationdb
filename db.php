<?php
// เปิดโหมดแสดง Error จะได้ไม่จอขาว
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "root"; // username ของ XAMPP ปกติคือ root
$pass = "";     // password ของ XAMPP ปกติจะเว้นว่างไว้
$dbname = "NPRU_RegistrationDB";

// ปิดโหมด Error ของระบบ MySQL ชั่วคราว เพื่อให้เราเขียนข้อความเตือนเองได้
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($host, $user, $pass, $dbname);

// ดักจับ Error ถ้าเชื่อมต่อฐานข้อมูลไม่ได้
if ($conn->connect_error) {
    die("<h3 style='color:red; text-align:center; margin-top:50px;'>❌ เชื่อมต่อฐานข้อมูลล้มเหลว! <br>กรุณาเช็คชื่อฐานข้อมูลในไฟล์ db.php</h3>");
}

$conn->set_charset("utf8mb4");
