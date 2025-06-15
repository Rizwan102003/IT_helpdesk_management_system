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

    $dept =$_POST["dept"];
    $section =$_POST["section"];
    $designation = $_POST["designation"];

    if($designation=="afa"){
        $level="L2";
    }
    else {
        $level="L3";
    }

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
        level,
        section,
        designation,
        email,
        contact,
        password) 
        VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssss", $employee_id,$employee_name, 
        $dept, 
        $level,
        $section,
        $designation, 
        $email, 
        $contact, 
        $hashedPassword);
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
            <label for="dept">Choose a dept:</label>
                <select name="dept" id="dept" >
                <option value="ee">Electrical</option>
                <option value="commercial">Commercial</option>
                <option value="traffic">Traffic</option>
                <option value="accounts">Accounts</option>
                <option value="mech">Mech</option>
                </select>
            <br><br>
            <label for="section">Choose a section:</label>
                <select id="section" name="section">
                <option value="admin">Admin</option>
                <option value="budget">Budget</option>
                <option value="establishment">Establishment</option>
                </select>
            <br><br>
            <label for="designation">Choose a designation:</label>
                <select id="designation" name="designation">
                <option value="os">OS</option>
                <option value="afa">AFA</option>
                <option value="aa">AA</option>
                <option value="jaa">JAA</option>
                <option value="se">SE</option>
                <option value="cos">COS</option>
                </select>
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