<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "helpdesk");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle update submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $description = $conn->real_escape_string($_POST['description']);
    $status = $conn->real_escape_string($_POST['status']);

    $sql_update = "UPDATE complaint SET description='$description', status='$status' WHERE complaint_id='$complaint_id'";
    if ($conn->query($sql_update) === TRUE) {
        $message = "Complaint updated successfully.";
    } else {
        $message = "Error updating complaint: " . $conn->error;
    }
}

// Fetch complaint details
if (isset($_POST['complaint_id'])) {
    $complaint_id = $conn->real_escape_string($_POST['complaint_id']);
    $sql = "SELECT * FROM complaint WHERE complaint_id='$complaint_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $complaint = $result->fetch_assoc();
    } else {
        die("Complaint not found.");
    }
} elseif (isset($_GET['complaint_id'])) {
    // After update, redirect back with GET param
    $complaint_id = $conn->real_escape_string($_GET['complaint_id']);
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

        <label>Status:</label>
        <select name="status" required>
            <option value="Pending" <?= $complaint['status']=='Pending' ? 'selected':'' ?>>Pending</option>
            <option value="In Progress" <?= $complaint['status']=='In Progress' ? 'selected':'' ?>>In Progress</option>
            <option value="Resolved" <?= $complaint['status']=='Resolved' ? 'selected':'' ?>>Resolved</option>
            <option value="Closed" <?= $complaint['status']=='Closed' ? 'selected':'' ?>>Closed</option>
        </select>

        <button type="submit" name="save_changes" class="btn">Save Changes</button>
    </form>
</div>

</body>
</html>
