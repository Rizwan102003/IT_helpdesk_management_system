<?php
session_start();


$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$generatedPassword = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = trim($_POST["employee_id"]);
    

    $check = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $check->bind_param("s", $employee_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Employee ID already registered.";
    } else {

        $generatedPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
        $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);


        $stmt = $conn->prepare("INSERT INTO employees (employee_id, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $employee_id, $hashedPassword);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Helpdesk</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <form method="POST" action="">
            <input type="text" name="employee_id" placeholder="Enter Employee ID" required>
            <br><br>
            <button type="submit" class="btn">Register</button>
        </form>
        <br>
        <?php if (!empty($generatedPassword)): ?>
            <div class="success">Your generated password: <strong><?php echo $generatedPassword; ?></strong></div>
            <div>Use it to <a href="login.php">Login</a></div>
        <?php elseif (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
