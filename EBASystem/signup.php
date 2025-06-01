<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sign Up - CvSU Silang</title>
    <link rel="stylesheet" href="html.css">
</head>

<body>

    <form class="login-box" action="signup.php" method="POST">
        <img src="image/Cavite_State_University_(CvSU).png" alt="Logo" class="logo">
        <h2>Cavite State University - Silang Campus</h2>
        <h3>EBA Inventory</h3>
        <h4>CREATE ACCOUNT</h4>

        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <div class="buttons">
            <button type="submit" class="login-btn">SignUp</button>
            <a href="login.php" class="signup-btn">Back</a>
        </div>
    </form>

</body>

</html>

<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validate email domain
    if (!preg_match("/^[a-zA-Z0-9._%+-]+\\.(admin|staff)@cvsu\\.edu\\.ph$/", $email, $matches)) {
        echo "<script>alert('Email must follow the format: name.admin@cvsu.edu.ph or name.staff@cvsu.edu.ph'); window.location.href='signup.php';</script>";
        exit;
    }

    // Extract role from email
    $role = $matches[1]; // 'admin' or 'staff'

    // Check if already registered
    $check = $conn->prepare("SELECT * FROM logs WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Email already registered.'); window.location.href='login.php';</script>";
    } else {
        // Insert user into logs
        $stmt = $conn->prepare("INSERT INTO logs(name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        if ($stmt->execute()) {
            echo "<script>alert('Registration successful.'); window.location.href='login.php';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>