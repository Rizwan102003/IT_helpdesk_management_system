<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$message = "";

$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "New passwords do not match.";
    } else {
        $sql = "SELECT password FROM users WHERE employee_id = '$employee_id'";
        $res = $conn->query($sql);

        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $hashed_password = $row['password'];

            if (password_verify($current, $hashed_password)) {
                $new_hashed = password_hash($new, PASSWORD_DEFAULT);
                $sql_update = "UPDATE users SET password = '$new_hashed' WHERE employee_id = '$employee_id'";
                if ($conn->query($sql_update) === TRUE) {
                    $message = "Password changed successfully.";
                } else {
                    $message = "Error updating password.";
                }
            } else {
                $message = "Current password is incorrect.";
            }
        } else {
            $message = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; padding: 30px; }
        .form-box { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; }
        .btn { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .msg { margin-top: 15px; color: red; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Change Password</h2>
    <form method="POST">
        <label>Current Password:</label><br>
        <input type="password" name="current_password" required><br>

        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br>

        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" required><br>

        <button class="btn" type="submit">Update Password</button>
    </form>

    <?php if ($message): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
