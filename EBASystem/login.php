<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - CvSU Silang</title>
    <link rel="stylesheet" href="html.css">
</head>

<body>
    <form class="login-box" action="login.php" method="POST">
        <img src="image/Cavite_State_University_(CvSU).png" alt="Logo" class="logo">
        <h2>Cavite State University - Silang Campus</h2>
        <h3>EBA Inventory</h3>
        <h4>WELCOME BACK!</h4>

        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>

        <div class="options">
            <label><input type="checkbox" name="remember"> Remember me</label>
        </div>

        <div class="buttons">
            <button type="submit" class="login-btn">LOGIN</button>
            <a href="signup.php" class="signup-btn">SignUp</a>
        </div>
    </form>

</html>

<?php
include 'connection.php';
session_start(); // Start the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.'); window.location.href = 'login.php';</script>";
        exit;
    }

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT * FROM logs WHERE email = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);  // Log internal error
        echo "<script>alert('An internal error occurred. Please try again later.'); window.location.href = 'login.php';</script>";
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            //Redirect to the dashboard
            if ($user['role'] == 'admin') {
                echo "<script>alert('Login successful! Welcome, " . htmlspecialchars($user['name'], ENT_QUOTES) . "'); window.location.href = 'index.php';</script>";
            } elseif ($user['role'] === 'staff') {
                echo "<script>alert('Login successful! Welcome, " . htmlspecialchars($user['name'], ENT_QUOTES) . "'); window.location.href = 'staff_dashboard.php';</script>";
            }
        } else {
            echo "<script>alert('Incorrect password.'); window.location.href = 'login.php';</script>";
        }
    } else {
        echo "<script>alert('No user found with that email.'); window.location.href = 'login.php';</script>";
    }

    $stmt->close();
}
?>