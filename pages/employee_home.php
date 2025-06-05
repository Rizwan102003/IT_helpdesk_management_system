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

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $conn->real_escape_string($_POST['type']);
    $description = $conn->real_escape_string($_POST['description']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $senior_officer = $conn->real_escape_string($_POST['senior_officer']);

    $sql = "INSERT INTO complaint (employee_id, type, description, designation, senior_officer, status, date)
            VALUES ('$employee_id', '$type', '$description', '$designation', '$senior_officer', 'Pending', NOW())";

    if ($conn->query($sql) === TRUE) {
        $message = "Complaint submitted successfully.";
    } else {
        $message = "Error: " . $conn->error;
    }
}

$sql = "SELECT employee_id, type, description, status, date FROM complaint WHERE employee_id = '$employee_id' ORDER BY date DESC";
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
</style>
</head>
<body>

<div class="navbar">IT cENTRE</div>

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
        <small><em><?=date("d M Y, H:i", strtotime($row['created_at']))?></em></small>
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

    <form method="POST" action="">
      <label for="type">Type</label>
      <input type="text" id="type" name="type" required placeholder="Enter complaint type">

      <label for="description">Complaint Description</label>
      <textarea id="description" name="description" required placeholder="Describe your complaint"></textarea>

      <label for="designation">Designation</label>
      <input type="text" id="designation" name="designation" required placeholder="Your designation">

      <label for="senior_officer">Senior Officer</label>
      <input type="text" id="senior_officer" name="senior_officer" required placeholder="Name of senior officer">

      <button type="submit">Submit Complaint</button>
    </form>
  </section>

</div>

</body>
</html>
