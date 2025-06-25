<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql_level = "SELECT level, designation FROM users WHERE employee_id='$employee_id'";
$res_level = $conn->query($sql_level);
$data = $res_level->fetch_assoc();
$level = $data['level'] ?? '';
$designation = $data['designation'] ?? '';

$back_link = match($level) {
    'L0' => 'admin_home.php',
    'L1' => 'junior_admin_home.php',
    'L2' => 'senior_home.php',
    'L3' => 'employee_home.php',
    default => '#'
};

$cid = $_POST['complaint_id'] ?? $_GET['complaint_id'] ?? null;
if (!$cid) die("Invalid request.");
$sql = "SELECT * FROM complaint WHERE complaint_id='" . $conn->real_escape_string($cid) . "'";
$res = $conn->query($sql);
if (!$res || $res->num_rows === 0) die("Complaint not found.");
$complaint = $res->fetch_assoc();

$junior_admins = [];
$senior_officers = [];
if ($level === 'L0') {
    $res_juniors = $conn->query("SELECT employee_id, employee_name FROM users WHERE level = 'L1'");
    while ($row = $res_juniors->fetch_assoc()) $junior_admins[] = $row;
}
if ($level === 'L3') {
    $res_seniors = $conn->query("SELECT employee_id, employee_name FROM users WHERE level = 'L2'");
    while ($row = $res_seniors->fetch_assoc()) $senior_officers[] = $row;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);

    if (isset($_POST['add_remark'])) {
        $remark = $conn->real_escape_string($_POST['remark']);

        $sent_to = $complaint['employee_id'];
        $res_to = $conn->query("SELECT designation FROM users WHERE employee_id='$sent_to'");
        $designation_to = ($res_to && $res_to->num_rows > 0) ? $res_to->fetch_assoc()['designation'] : 'Unknown';

        $sql = "INSERT INTO movement (complaint_id, sent_from, designation_from, sent_to, designation_to, status, remark, timestamp)
                VALUES ('$complaint_id', '$employee_id', '$designation', '$sent_to', '$designation_to', 'Pending', '$remark', NOW())";

        $message = ($conn->query($sql)) ? "Remark submitted successfully." : "Error submitting remark: " . $conn->error;
    }

    if ($level === 'L2' && isset($_POST['forward'])) {
        $sql = "UPDATE complaint SET senior_officer='999999' WHERE complaint_id='$complaint_id'";
        $message = $conn->query($sql) ? "Forwarded to admin." : "Error: " . $conn->error;
    }

    if (($level === 'L2' || $level === 'L1') && isset($_POST['reject'])) {
        $res = $conn->query("SELECT employee_id FROM complaint WHERE complaint_id='$complaint_id'");
        if ($res && $res->num_rows > 0) {
            $emp_id = $res->fetch_assoc()['employee_id'];
            $sql = "UPDATE complaint SET senior_officer='$emp_id', status='Rejected' WHERE complaint_id='$complaint_id'";
            $message = $conn->query($sql) ? "Complaint rejected back to employee." : "Error: " . $conn->error;
        }
    }

    if ($level === 'L0' && isset($_POST['assign_junior'])) {
        $junior_id = $conn->real_escape_string($_POST['junior_officer']);
        $sql = "UPDATE complaint SET senior_officer='$junior_id' WHERE complaint_id='$complaint_id'";
        $message = $conn->query($sql) ? "Assigned to junior admin." : "Error: " . $conn->error;
    }

    if ($level === 'L1' && isset($_POST['update_status_l1'])) {
        $status = $conn->real_escape_string($_POST['status']);
        $sql = "UPDATE complaint SET status='$status' WHERE complaint_id='$complaint_id'";
        $message = $conn->query($sql) ? "Status updated." : "Error: " . $conn->error;
    }

    if ($level === 'L3' && isset($_POST['resend'])) {
        $description = $conn->real_escape_string($_POST['description']);
        $senior_id = $conn->real_escape_string($_POST['senior_officer']);
        $sql = "UPDATE complaint SET description='$description', senior_officer='$senior_id' WHERE complaint_id='$complaint_id'";
        $message = $conn->query($sql) ? "Resent to senior officer." : "Error: " . $conn->error;
    }
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
        .btn-danger {background: #dc3545;}
        .btn-danger:hover {background: #c82333;}
        .back-link {
            margin-top: 10px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
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

            <label>Assign to Junior Admin:</label>
            <select name="junior_officer" required>
                <option value="">-- Select Junior Admin --</option>
                <?php foreach ($junior_admins as $admin): ?>
                    <option value="<?= $admin['employee_id'] ?>"><?= $admin['employee_name'] ?> (<?= $admin['employee_id'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_junior" class="btn">Assign</button>

        <?php elseif ($level === 'L1' || $level === 'L2'): ?>
            <label>Enter Remark:</label>
            <textarea name="remark" placeholder="Write your remark here..." required></textarea>
            <button type="submit" name="add_remark" class="btn">Submit Remark</button>
            <?php if ($level === 'L2'): ?>
                <button type="submit" name="forward" class="btn">Forward to Admin</button>
            <?php endif; ?>
            <button type="submit" name="reject" class="btn btn-danger">Reject Complaint</button>

        <?php elseif ($level === 'L3'): ?>
            <label>Forward to Senior Officer</label>
            <select name="senior_officer" required>
                <option value="">-- Select Senior Officer --</option>
                <?php foreach ($senior_officers as $officer): ?>
                    <option value="<?= $officer['employee_id'] ?>"><?= $officer['employee_name'] ?> (<?= $officer['employee_id'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="resend" class="btn">Resend Complaint</button>
        <?php endif; ?>
    </form>
    <a href="<?= htmlspecialchars($back_link) ?>" class="back-link">Back</a>
</div>
</body>
</html>