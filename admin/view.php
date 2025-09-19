<?php
// Database connection
require_once "../db.php";

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("❌ Invalid request.");
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM registrations WHERE id = $id");

if ($result->num_rows === 0) {
    die("❌ Student not found.");
}

$student = $result->fetch_assoc();

// Calculate age
$age = '';
if (!empty($student['dob'])) {
    $dob = new DateTime($student['dob']);
    $today = new DateTime();
    $age = $dob->diff($today)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Student - <?= htmlspecialchars($student['full_name']) ?></title>
  <style>
    body { font-family: "Segoe UI", sans-serif; background: #f5f7fa; margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 600px; width: 100%; }
    .profile { text-align: center; }
    .profile img { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid #007bff; margin-bottom: 15px; }
    h2 { text-align: center; margin-bottom: 20px; color: #333; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 10px; border-bottom: 1px solid #eee; }
    td:first-child { font-weight: bold; width: 40%; color: #444; }
    .actions { margin-top: 20px; display: flex; justify-content: space-between; }
    .btn { padding: 10px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
    .back { background: #6c757d; color: #fff; }
    .edit { background: #ffc107; color: #000; }
    .print { background: #007bff; color: #fff; }
  </style>
  <script>
    function printPage() {
      window.print();
    }
  </script>
</head>
<body>
  <div class="card">
    <div class="profile">
      <?php if (!empty($student['profile_image'])): ?>
        <img src="../uploads/<?= htmlspecialchars($student['profile_image']) ?>" alt="Profile">
      <?php else: ?>
        <img src="https://via.placeholder.com/120" alt="No Image">
      <?php endif; ?>
      <h2><?= htmlspecialchars($student['full_name']) ?></h2>
    </div>

    <table>
      <tr><td>ID</td><td><?= $student['id'] ?></td></tr>
      <tr><td>Age</td><td><?= $age ?: '-' ?></td></tr>
      <tr><td>Class</td><td><?= htmlspecialchars($student['class']) ?></td></tr>
      <tr><td>School</td><td><?= htmlspecialchars($student['school']) ?></td></tr>
      <tr><td>Place</td><td><?= htmlspecialchars($student['place']) ?></td></tr>
      <tr><td>Knowledge</td><td><?= nl2br(htmlspecialchars($student['knowledge'])) ?></td></tr>
      <tr><td>Date of Birth</td><td><?= htmlspecialchars($student['dob']) ?></td></tr>
    </table>

    <div class="actions">
      <a href="list.php"><button class="btn back">⬅ Back</button></a>
      <a href="edit.php?id=<?= $student['id'] ?>"><button class="btn edit">✏ Edit</button></a>
      <button class="btn print" onclick="printPage()">🖨 Print</button>
    </div>
  </div>
</body>
</html>
