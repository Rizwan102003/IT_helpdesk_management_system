<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];

$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Senior Name
$sql_user = "SELECT employee_name FROM users WHERE employee_id = '$employee_id'";
$res_user = $conn->query($sql_user);
$employee_name = "";
if ($res_user && $res_user->num_rows > 0) {
    $employee_name = $res_user->fetch_assoc()['employee_name'];
}

// Fetch complaints for Reports (all complaints)
$sql_all_complaints = "SELECT * FROM complaint ORDER BY date DESC";
$res_all = $conn->query($sql_all_complaints);

// Fetch complaints submitted by this senior (View Status)
$sql_my_complaints = "SELECT * FROM complaint WHERE employee_id = '$employee_id' ORDER BY date DESC";
$res_my = $conn->query($sql_my_complaints);

// Handle complaint update (Edit from modal form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_complaint'])) {
    $cid = $conn->real_escape_string($_POST['complaint_id']);
    $status = $conn->real_escape_string($_POST['status']);
    $description = $conn->real_escape_string($_POST['description']);
    
    $sql_update = "UPDATE complaint SET status='$status', description='$description' WHERE complaint_id='$cid'";
    if ($conn->query($sql_update) === TRUE) {
        $message = "Complaint updated successfully.";
    } else {
        $message = "Error updating complaint: " . $conn->error;
    }
    header("Location: senior_home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Senior Officer Home - IT Centre</title>
    <style>
        body {font-family: Arial; margin:0; padding:0; background: #f4f4f4;}
        .navbar {background:#007bff; color:white; padding:15px; font-size:22px;}
        .sidebar {width:200px; background:#333; height:100vh; position:fixed; top:0; left:0; color:white;}
        .sidebar a {display:block; color:white; padding:15px; text-decoration:none;}
        .sidebar a:hover {background:#575757;}
        .content {margin-left:200px; padding:20px;}
        table {width:100%; border-collapse: collapse;}
        th, td {border:1px solid #ccc; padding:8px; text-align:left;}
        .btn {padding:6px 10px; background:#007bff; color:white; border:none; cursor:pointer;}
        .btn:hover {background:#0056b3;}
    </style>
</head>
<body>

<div class="navbar">
    Welcome, <?= htmlspecialchars($employee_name) ?> (Senior Officer)
</div>

<div class="sidebar">
    <a href="?section=register">Register Complaint</a>
    <a href="?section=status">View Status</a>
    <a href="?section=reports">Reports</a>
</div>

<div class="content">

<?php
// Section rendering
$section = isset($_GET['section']) ? $_GET['section'] : 'register';

if ($section == 'register') {
    // Simple complaint register form
?>
    <h2>Register New Complaint</h2>
    <form method="POST" action="senior_register_complaint.php">
        <label>Type:</label>
        <select name="type" required>
            <option value="software">Software</option>
            <option value="hardware">Hardware</option>
            <option value="network">Network</option>
        </select><br><br>

        <label>Description:</label><br>
        <textarea name="description" required></textarea><br><br>

        <button type="submit" class="btn">Submit</button>
    </form>

<?php
} elseif ($section == 'status') {
?>
    <h2>My Registered Complaints</h2>
    <table>
        <tr><th>Type</th><th>Description</th><th>Status</th><th>Date</th></tr>
        <?php while($row = $res_my->fetch_assoc()) { ?>
            <tr>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
            </tr>
        <?php } ?>
    </table>

<?php
} elseif ($section == 'reports') {
?>
    <h2>All Complaints (Reports)</h2>
    <table>
        <tr><th>ID</th><th>Employee ID</th><th>Type</th><th>Description</th><th>Status</th><th>Date</th><th>Action</th></tr>
        <?php while($row = $res_all->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['complaint_id'] ?></td>
                <td><?= $row['employee_id'] ?></td>
                <td><?= $row['type'] ?></td>
                <td><?= htmlspecialchars(substr($row['description'],0,30)) ?>...</td>
                <td><?= $row['status'] ?></td>
                <td><?= $row['date'] ?></td>
                <td>
                    <form method="POST" action="senior_view_complaint.php" style="display:inline;">
                        <input type="hidden" name="complaint_id" value="<?= $row['complaint_id'] ?>">
                        <button type="submit" class="btn">View</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

<?php } ?>

</div>

</body>
</html>
