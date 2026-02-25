<?php
session_start();
session_unset();    // ลบตัวแปร session ทั้งหมด
session_destroy();  // ทำลาย session
header("Location: index.php"); // เด้งกลับไปหน้าแรก
exit();
