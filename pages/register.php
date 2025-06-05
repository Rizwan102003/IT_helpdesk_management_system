<?php
session_start();


$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$generatedPassword = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = trim($_POST["employee_id"]);
    $employee_name = trim($_POST["employee_name"]);
    $dept = trim($_POST["dept"]);
    $designation = trim($_POST["designation"]);
    $email = trim($_POST["email"]);
    $contact = trim($_POST["contact"]);
    

    $check = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
    $check->bind_param("s", $employee_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Employee ID already registered.";
    } else {

        $generatedPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);
        $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (employee_id,
        employee_name,
        dept,
        designation,
        email,
        contact,
        password) 
        VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssss", $employee_id,$employee_name, $dept, $designation, $email, $contact, $hashedPassword);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Helpdesk</title>
    <link rel="stylesheet" href="../styles/register.css">
</head>
<body>
    <div class="container">
        <h2>Register</h2>
        <form method="POST" action="">
            <input type="text" name="employee_id" placeholder="Enter Employee ID" required>
            <input type="text" name="employee_name" placeholder="Enter Employee Name" required>
            <input type="text" name="dept" placeholder="Enter Employee Dept" required>
            <input type="text" name="designation" placeholder="Enter Employee designation" required>
            <input type="text" name="email" placeholder="Enter Employee email-id" required>
            <input type="text" name="contact" placeholder="Enter Employee Contact Number" required>
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
