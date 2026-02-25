<?php
session_start();
require_once 'db.php';

$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ระบบป้องกันจอขาว: เช็คก่อนว่าในฐานข้อมูลมีตาราง users หรือยัง?
    $check_table = $conn->query("SHOW TABLES LIKE 'users'");
    if ($check_table->num_rows == 0) {
        die("<h3 style='color:red; text-align:center; margin-top:50px;'>❌ ไม่พบตาราง 'users' ในฐานข้อมูล!<br>คุณต้องเอาโค้ดสร้างตาราง users ไปรันใน phpMyAdmin ก่อนครับ</h3>");
    }

    // ดึงข้อมูลผู้ใช้
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role']; // เก็บสิทธิ์การใช้งาน

            // แยกว่าเป็นใครล็อกอินมา
            if ($row['role'] == 'admin') {
                // ถ้าเป็นอาจารย์ (admin) ให้ไปหน้า teacher_dashboard.php
                header("Location: teacher_dashboard.php");
            } else {
                // ถ้าเป็นนักศึกษา ให้ไปหน้า dashboard.php ปกติ
                $_SESSION['student_id'] = $row['student_id'];
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error_msg = "ชื่อผู้ใช้ หรือ รหัสผ่านไม่ถูกต้อง!";
        }
        $stmt->close();
    } else {
        die("เกิดข้อผิดพลาดกับคำสั่ง SQL: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบลงทะเบียนเรียน NPRU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            background: white;
            width: 100%;
            max-width: 400px;
        }

        .btn-custom {
            background-color: #0d6efd;
            color: white;
            border-radius: 50px;
            padding: 10px;
            font-weight: bold;
        }

        .btn-custom:hover {
            background-color: #0b5ed7;
            color: white;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="text-primary fw-bold">NPRU REG</h3>
            <p class="text-muted">เข้าสู่ระบบลงทะเบียนเรียน</p>
        </div>

        <?php if ($error_msg != ""): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-custom w-100">เข้าสู่ระบบ</button>
        </form>
    </div>

</body>

</html>