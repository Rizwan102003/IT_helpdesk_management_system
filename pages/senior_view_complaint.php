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

$sql_level = "SELECT level FROM users WHERE employee_id = '$employee_id'";
$res_level = $conn->query($sql_level);
$level = '';
if ($res_level && $res_level->num_rows > 0) {
    $level = $res_level->fetch_assoc()['level'];
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $description = $conn->real_escape_string($_POST['description']);

    if ($level === 'L0') {
        $status = $conn->real_escape_string($_POST['status']);
        $sql_update = "UPDATE complaint SET description='$description', status='$status' WHERE complaint_id='$complaint_id'";
    } elseif ($level === 'L2') {
        $sql_update = "UPDATE complaint SET description='$description' WHERE complaint_id='$complaint_id'";
    }

    if ($conn->query($sql_update) === TRUE) {
        $message = "Changes saved successfully.";
    } else {
        $message = "Error saving changes: " . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['forward'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $sql_forward = "UPDATE complaint SET senior_officer='999999' WHERE complaint_id='$complaint_id'";
    if ($conn->query($sql_forward) === TRUE) {
        $message = "Complaint forwarded to super admin.";
    } else {
        $message = "Error forwarding complaint: " . $conn->error;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $sql_get_emp = "SELECT employee_id FROM complaint WHERE complaint_id='$complaint_id'";
    $res_emp = $conn->query($sql_get_emp);
    if ($res_emp && $res_emp->num_rows > 0) {
        $emp_id = $res_emp->fetch_assoc()['employee_id'];
        $sql_reject = "UPDATE complaint SET senior_officer='$employee_id' WHERE complaint_id='$complaint_id'";
        if ($conn->query($sql_reject) === TRUE) {
            $message = "Complaint rejected back to employee.";
        } else {
            $message = "Error rejecting complaint: " . $conn->error;
        }
    }
}

$complaint = null;
if (isset($_POST['complaint_id']) || isset($_GET['complaint_id'])) {
    $cid = isset($_POST['complaint_id']) ? $_POST['complaint_id'] : $_GET['complaint_id'];
    $complaint_id = $conn->real_escape_string($cid);
    $sql = "SELECT * FROM complaint WHERE complaint_id='$complaint_id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $complaint = $result->fetch_assoc();
    } else {
        die("Complaint not found.");
    }
} else {
    die("Invalid request.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Complaint</title>
    <style>
        body {font-family: Arial; background: #f4f4f4; padding: 20px;}
        .container {background: white; padding: 20px; border-radius: 5px; max-width: 700px; margin: auto;}
        h2 {margin-top:0;}
        label {display: block; margin-top: 15px; font-weight: bold;}
        textarea, select, input[type="text"] {width:100%; padding:8px;}
        .btn {margin-top: 20px; padding: 10px 20px; background: #007bff; color:white; border:none;}
        .btn:hover {background: #0056b3;}
        .message {color:green; font-weight:bold; margin-top: 15px;}
        .btn-secondary {background: #6c757d;}
        .btn-secondary:hover {background: #5a6268;}
        .btn-danger {background: #dc3545;}
        .btn-danger:hover {background: #c82333;}
    </style>
</head>
<body>
<div class="container">
    <h2>Complaint Details (ID: <?= htmlspecialchars($complaint['complaint_id']) ?>)</h2>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($complaint['complaint_id']) ?>">

        <label>Employee ID:</label>
        <input type="text" value="<?= htmlspecialchars($complaint['employee_id']) ?>" readonly>

        <label>Type:</label>
        <input type="text" value="<?= htmlspecialchars($complaint['type']) ?>" readonly>

        <label>Description:</label>
        <textarea name="description"><?= htmlspecialchars($complaint['description']) ?></textarea>

        <?php if ($level === 'L0'): ?>
            <label>Status:</label>
            <select name="status" required>
                <option value="Pending" <?= $complaint['status']=='Pending' ? 'selected':'' ?>>Pending</option>
                <option value="In Progress" <?= $complaint['status']=='In Progress' ? 'selected':'' ?>>In Progress</option>
                <option value="Resolved" <?= $complaint['status']=='Resolved' ? 'selected':'' ?>>Resolved</option>
                <option value="Closed" <?= $complaint['status']=='Closed' ? 'selected':'' ?>>Closed</option>
            </select>
        <?php endif; ?>

        <button type="submit" name="save_changes" class="btn">Save Changes</button>

        <?php if ($level === 'L2'): ?>
            <button type="submit" name="forward" class="btn btn-secondary">Forward to Admin</button>
            <button type="submit" name="reject" class="btn btn-danger">Reject to Employee</button>
        <?php endif; ?>
    </form>
</div>
</body>
</html>