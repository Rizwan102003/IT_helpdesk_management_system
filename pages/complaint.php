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

$sql_user = "SELECT employee_name FROM users WHERE employee_id = '$employee_id'";
$res_user = $conn->query($sql_user);
$employee_name = "";
if ($res_user && $res_user->num_rows > 0) {
    $employee_name = $res_user->fetch_assoc()['employee_name'];
}

$level_result = $conn->query("SELECT level FROM users WHERE employee_id = '$employee_id'");
$level = "";
if ($level_result && $level_result->num_rows > 0) {
    $level = $level_result->fetch_assoc()['level'];
}

$back_link = ($level === "L3") ? "employee_home.php" : "senior_home.php";

$senior_officers = [];
$sql_seniors = "SELECT employee_id, employee_name FROM users WHERE level = 'L2'";
$res_seniors = $conn->query($sql_seniors);
if ($res_seniors && $res_seniors->num_rows > 0) {
    while ($row = $res_seniors->fetch_assoc()) {
        $senior_officers[] = $row;
    }
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $conn->begin_transaction();

    try {
        $type = $conn->real_escape_string($_POST['type']);
        $description = $conn->real_escape_string($_POST['description']);
        if ($level === "L3") {
            $senior_officer = $conn->real_escape_string($_POST['senior_officer']);
        } else {
            $senior_officer = '999999';
        }

        $sql_user_details = "SELECT designation, dept, section FROM users WHERE employee_id = '$employee_id'";
        $res_user_details = $conn->query($sql_user_details);

        $designation = $department = $section = "";

        if ($res_user_details && $res_user_details->num_rows > 0) {
            $row = $res_user_details->fetch_assoc();
            $designation = $row['designation'];
            $department = $row['dept'];
            $section = $row['section'];
        }

        $sql_serial = "SELECT COUNT(*) as total FROM complaint";
        $res_serial = $conn->query($sql_serial);
        $serial_number = 1;
        if ($res_serial && $res_serial->num_rows > 0) {
            $serial_number = $res_serial->fetch_assoc()['total'] + 1;
        }

        $complaint_id = strtoupper(substr($department, 0, 2) . substr($section, 0, 2) . substr($designation, 0, 2) . str_pad($serial_number, 4, '0', STR_PAD_LEFT));

        $file_name = "";
        if (isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $original_name = basename($_FILES["fileToUpload"]["name"]);
            $file_ext = pathinfo($original_name, PATHINFO_EXTENSION);
            $unique_name = uniqid() . "." . $file_ext;
            $target_file = $target_dir . $unique_name;

            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                $file_name = $unique_name;
            } else {
                throw new Exception("Error uploading file.");
            }
        }

        $sql = "INSERT INTO complaint (complaint_id, employee_id, type, description, designation, senior_officer, file_name, status, date)
                VALUES ('$complaint_id', '$employee_id', '$type', '$description', '$designation', '$senior_officer', '$file_name', 'Pending', NOW())";

        if (!$conn->query($sql)) {
            throw new Exception("Error inserting into complaint table: " . $conn->error);
        }

        $designation_from = $designation;
        $sent_to = $senior_officer;
        $designation_to = "";

        if ($senior_officer !== '999999') {
            $res_senior = $conn->query("SELECT designation FROM users WHERE employee_id = '$senior_officer'");
            if ($res_senior && $res_senior->num_rows > 0) {
                $designation_to = $res_senior->fetch_assoc()['designation'];
            } else {
                $designation_to = "Unknown";
            }
        } else {
            $designation_to = "System";
        }

        $sql_movement = "INSERT INTO movement (complaint_id, sent_from, designation_from, sent_to, designation_to, status, timestamp)
                         VALUES ('$complaint_id', '$employee_id', '$designation_from', '$sent_to', '$designation_to', 'Pending', NOW())";

        if (!$conn->query($sql_movement)) {
            throw new Exception("Error inserting into movement table: " . $conn->error);
        }

        $conn->commit();
        $message = "Complaint submitted successfully. Your Complaint ID: $complaint_id";

        if ($senior_officer !== '999999') {
    $sql_email = "SELECT email, employee_name FROM users WHERE employee_id = '$senior_officer'";
    $res_email = $conn->query($sql_email);
    if ($res_email && $res_email->num_rows > 0) {
        $email_data = $res_email->fetch_assoc();
        $senior_email = $email_data['email'];
        $senior_name = $email_data['employee_name'];

        $subject = "New Complaint Assigned: $complaint_id";
        $email_message = "Dear $senior_name,\n\nA new complaint has been assigned to you.\nComplaint ID: $complaint_id\nType: $type\nDescription: $description\n\nPlease log in to the Helpdesk portal to take necessary action.\n\nRegards,\nHelpdesk System";
        $headers = "From: helpdesk@example.com";

        if (mail($senior_email, $subject, $email_message, $headers)) {
            $message .= " Email notification sent to senior officer.";
        } else {
            $message .= " Email could not be sent.";
        }
    }
}

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

$sql = "SELECT complaint_id, type, description, status, date FROM complaint WHERE employee_id = '$employee_id' ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Employee Home - IT CENTRE</title>
<style>
  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f4f4;
  }
  .navbar {
    background-color: #007bff; 
    color: white;
    padding: 15px 20px;
    font-size: 22px;
    font-weight: bold;
    text-transform: uppercase;
  }
  .employee-name {
  font-size: 18px;
  font-weight: normal;
  text-transform: none;
  }
  .container {
    display: flex;
    min-height: 90vh;
    padding: 20px;
    gap: 20px;
  }
  .status-section {
    flex: 1;
    background: white;
    padding: 15px;
    border-radius: 5px;
    overflow-y: auto;
    max-height: 70vh;
  }
  .status-section h2 {
    margin-top: 0;
    font-size: 20px;
    border-bottom: 2px solid #007bff;
    padding-bottom: 5px;
  }
  .complaints-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .complaints-list li {
    border-bottom: 1px solid #ddd;
    padding: 8px 0;
  }
  .complaints-list li:last-child {
    border-bottom: none;
  }
  .complaint-status {
    font-weight: bold;
    color: #007bff;
  }

  .main-section {
    flex: 2;
    background: white;
    padding: 20px;
    border-radius: 5px;
  }
  .main-section h2 {
    margin-top: 0;
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
  }
  form label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #555;
  }
  form input[type="text"],
  form textarea,
  form select {
    width: 100%;
    padding: 8px 10px;
    margin-top: 5px;
    border: 1px solid #ccc;
    border-radius: 3px;
    font-size: 16px;
    resize: vertical;
  }
  form textarea {
    height: 100px;
  }
  form button {
    margin-top: 20px;
    padding: 12px 25px;
    background-color: #007bff;
    border: none;
    color: white;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    border-radius: 3px;
  }
  form button:hover {
    background-color: #0056b3;
  }
  .message {
    margin-top: 15px;
    font-weight: bold;
    color: green;
  }
  .back-button {
  margin-top: 10px;
  display: inline-block;
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  text-decoration: none;
  border-radius: 5px;
  font-size: 16px;
}
.back-button:hover {
  background-color: #0056b3;
}
</style>
</head>
<body>

<div class="navbar">
  <span>IT CENTRE</span>
  <span class="employee-name">&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;
    Welcome, <?= htmlspecialchars($employee_name) ?></span>
</div>

<div class="container">
  <aside class="status-section">
    <h2>Past Reports Status</h2>
    <?php if ($result && $result->num_rows > 0): ?>
    <ul class="complaints-list">
      <?php while ($row = $result->fetch_assoc()): ?>
      <li> 
        <strong>Type:</strong> <?=htmlspecialchars($row['type'])?><br>
        <strong>Description:</strong> <?=htmlspecialchars(substr($row['description'], 0, 50))?><?=strlen($row['description'])>50?'...':''?><br>
        <strong>Status:</strong> <span class="complaint-status"><?=htmlspecialchars($row['status'])?></span><br>
      </li>
      <?php endwhile; ?> 
    </ul>
    <?php else: ?>
      <p>No past complaints found.</p>
    <?php endif; ?>
  </aside>

  <section class="main-section">
    <h2>File a New Complaint</h2>

    <?php if ($message): ?>
    <div class="message"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <label for="type">Type</label>
      <select name="type" id="type" >
        <option value="software">Software</option>
        <option value="hardware">Hardware</option>
        <option value="network">Network</option>
    </select>

      <label for="description">Complaint Description</label>
      <textarea id="description" name="description" required placeholder="Describe your complaint"></textarea>

      <label for="upload">Image Uploads</label>
      <input type="file" name="fileToUpload" id="fileToUpload">

      <?php
      if($level==="L3"){
        ?>
      <label for="senior_officer">Forward to Senior Officer</label>
      <select name="senior_officer" id="senior_officer" required>
      <option value="">-- Select Senior Officer --</option>
      <?php foreach ($senior_officers as $officer): ?>
      <option value="<?= htmlspecialchars($officer['employee_id']) ?>">
      <?= htmlspecialchars($officer['employee_name']) ?>
      </option>
      <?php endforeach; ?>
      </select>
      <?php } ?>
      <br>
      <button type="submit">Submit Complaint</button>
    </form>
    <a href="<?= htmlspecialchars($back_link) ?>" class="back-button">Back</a>
  </section>
</div>
</body>
</html>