<?php
session_start();

$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = trim($_POST["employee_id"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION["employee_id"] = $employee_id;
            if ($row["employee_id"] === "999999") {
                header("Location: /helpdesk/pages/super_admin.php");
            } elseif ($row["level"] === "L1") {
                header("Location: /helpdesk/pages/admin_home.php");
            }elseif ($row["level"] === "L2") {
                header("Location: /helpdesk/pages/senior_home.php");
            } else {
                header("Location: /helpdesk/pages/employee_home.php");
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Employee ID not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Helpdesk</title>
    <link rel="stylesheet" href="../styles/login.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="">
            <input type="text" name="employee_id" placeholder="Employee ID" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit" class="btn">Login</button>
        </form>
        <br>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>