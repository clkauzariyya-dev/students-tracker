<?php
require_once "../db.php";

if (!isset($_GET['id'])) {
    die("Invalid request");
}

$student_id = intval($_GET['id']);

// Fetch student with teacher details
$sql = "SELECT s.*, t.teacher_name 
        FROM students s 
        LEFT JOIN class_teachers t ON s.teacher_id = t.id 
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Details</title>
  <link rel="stylesheet" href="../assets/style.css">
  <style>
    .container { max-width: 800px; margin: 30px auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
    h2 { text-align: center; margin-bottom: 20px; }
    .profile { text-align: center; }
    .profile img { width: 250px; height: 250px; object-fit: cover; border-radius: 12px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
    th { background: #007bff; color: #fff; }
    .btn { display: inline-block; margin-top: 20px; padding: 10px 15px; border: none; border-radius: 8px; text-decoration: none; color: #fff; background: #007bff; transition: 0.3s; }
    .btn:hover { background: #0056b3; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Student Details</h2>
    
    <div class="profile">
      <?php if (!empty($student['profile_image'])): ?>
        <img src="../uploads/<?= htmlspecialchars($student['profile_image']) ?>" alt="Profile Image">
      <?php else: ?>
        <img src="../assets/default.png" alt="Default Profile">
      <?php endif; ?>
    </div>

    <table>
      <tr><th>Full Name</th><td><?= htmlspecialchars($student['full_name']) ?></td></tr>
      <tr><th>Class</th><td><?= htmlspecialchars($student['class_id']) ?></td></tr>
      <tr><th>School</th><td><?= htmlspecialchars($student['school']) ?></td></tr>
      <tr><th>Place</th><td><?= htmlspecialchars($student['place']) ?></td></tr>
      <tr><th>District</th><td><?= htmlspecialchars($student['district']) ?></td></tr>
      <tr><th>Knowledge</th><td><?= htmlspecialchars($student['knowledge']) ?></td></tr>
      <tr><th>Date of Birth</th><td><?= htmlspecialchars($student['dob']) ?></td></tr>
      <tr><th>Phone</th><td><?= htmlspecialchars($student['phone']) ?></td></tr>
      <tr><th>Teacher</th><td><?= htmlspecialchars($student['teacher_name']) ?></td></tr>
      <tr><th>Extra Notes</th><td><?= nl2br(htmlspecialchars($student['extra'])) ?></td></tr>
      <tr><th>Created At</th><td><?= htmlspecialchars($student['created_at']) ?></td></tr>
      <tr><th>Updated At</th><td><?= htmlspecialchars($student['updated_at']) ?></td></tr>
    </table>

    <a href="edit-entries.php?id=<?= $student['id'] ?>" class="btn">Edit Student</a>
    <a href="list.php" class="btn" style="background:#28a745;">Back to List</a>
  </div>
</body>
</html>
