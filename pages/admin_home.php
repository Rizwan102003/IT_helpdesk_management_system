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

if (isset($_GET['section']) && $_GET['section'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$sql_user = "SELECT employee_id, employee_name, dept, section, designation FROM users WHERE employee_id = '$employee_id'";
$res_user = $conn->query($sql_user);
$employee_data = null;
$employee_name = "";
if ($res_user && $res_user->num_rows > 0) {
    $employee_data = $res_user->fetch_assoc();
    $employee_name = $employee_data['employee_name'];
}

$sql_all_complaints = "SELECT * FROM complaint WHERE senior_officer = '$employee_id' ORDER BY date DESC";
$res_all = $conn->query($sql_all_complaints);

$sql_my_complaints = "SELECT * FROM complaint WHERE employee_id = '$employee_id' ORDER BY date DESC";
$res_my = $conn->query($sql_my_complaints);

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
    header("Location: admin_home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Home - IT Centre</title>
    <style>
        body {font-family: Arial; margin:0; padding:0; background: #f4f4f4;}
        .navbar {background:#007bff; color:white; padding:15px; font-size:22px;}
        .sidebar {width:200px; background:#77bbf2; height:100vh; position:fixed; top:0; left:0; color:white;}
        .sidebar a {display:block; color:white; padding:15px; text-decoration:none;}
        .sidebar a:hover {background:#213cb0;}
        .content {margin-left:200px; padding:20px;}
        table {width:100%; border-collapse: collapse;}
        th, td {border:1px solid #ccc; padding:8px; text-align:left;}
        .btn {padding:6px 10px; background:#007bff; color:white; border:none; cursor:pointer;}
        .btn:hover {background:#0056b3;}
    </style>
</head>
<body>

<div class="navbar">
    &nbsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
    Welcome, <?= htmlspecialchars($employee_name) ?> (Admin)
</div>

<div class="sidebar">
    <a href="?section=reports">Reports</a>
    <a href="?section=profile">Profile</a>
    <a href="?section=logout">Logout</a>
</div>

<div class="content">
<?php
$section = isset($_GET['section']) ? $_GET['section'] : 'reports';

if ($section == 'reports') {
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

<?php
} elseif ($section == 'profile') {
?>
    <h2>My Profile</h2>
    <table>
        <tr><th>Employee ID</th><td><?= htmlspecialchars($employee_data['employee_id']) ?></td></tr>
        <tr><th>Name</th><td><?= htmlspecialchars($employee_data['employee_name']) ?></td></tr>
        <tr><th>Department</th><td><?= htmlspecialchars($employee_data['dept']) ?></td></tr>
        <tr><th>Section</th><td><?= htmlspecialchars($employee_data['section']) ?></td></tr>
        <tr><th>Designation</th><td><?= htmlspecialchars($employee_data['designation']) ?></td></tr>
    </table>
    <br>
    <a href="change_password.php"><button class="btn">Change Password</button></a>
<?php } ?>
</div>
</body>
</html>