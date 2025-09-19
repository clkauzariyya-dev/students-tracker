<?php
require_once "../db.php";

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM registrations WHERE id=$id");
$data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Printable Student Profile</title>
<style>
  body { font-family: Arial; padding: 40px; }
  .profile { border: 1px solid #000; padding: 20px; width: 600px; margin: auto; }
  img { max-width: 250px; border-radius: 8px; display: block; margin-bottom: 20px; }
  .field { margin-bottom: 10px; }
  .sign { margin-top: 60px; border-top: 1px solid #000; text-align: center; padding-top: 10px; }
  .print-btn { margin: 20px auto; display: block; padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
</style>
</head>
<body>
<div class="profile">
  <h2 style="text-align:center;">Student Profile</h2>
  <?php if ($data['profile_image']): ?>
    <img src="uploads/<?= $data['profile_image'] ?>" alt="Profile Image">
  <?php endif; ?>
  
  <div class="field"><b>Full Name:</b> <?= htmlspecialchars($data['full_name']) ?></div>
  <div class="field"><b>Class:</b> <?= htmlspecialchars($data['class']) ?></div>
  <div class="field"><b>School:</b> <?= htmlspecialchars($data['school']) ?></div>
  <div class="field"><b>Place:</b> <?= htmlspecialchars($data['place']) ?></div>
  <div class="field"><b>Knowledge:</b> <?= htmlspecialchars($data['knowledge']) ?></div>
  <div class="field"><b>Date of Birth:</b> <?= htmlspecialchars($data['dob']) ?></div>
  <div class="field"><b>Additional Info:</b> <?= htmlspecialchars($data['extra'] ?? '') ?></div>

  <div class="sign">Signature</div>
</div>
<button class="print-btn" onclick="window.print()">🖨 Print</button>
</body>
</html>
