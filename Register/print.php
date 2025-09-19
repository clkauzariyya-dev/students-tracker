<?php 
// admin/print.php
require_once "../db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid student id.");
}
$id = intval($_GET['id']);

$sql = "
  SELECT s.*, c.class_name,
         ct_class.teacher_name AS class_teacher_name,
         ct_student.teacher_name AS student_teacher_name
  FROM students s
  LEFT JOIN classes c ON s.class_id = c.id
  LEFT JOIN class_teachers ct_class ON c.teacher_id = ct_class.id
  LEFT JOIN class_teachers ct_student ON s.teacher_id = ct_student.id
  WHERE s.id = ?
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

// ✅ Fix image path
$imgSrc = '../assets/default.png'; 
if (!empty($student['profile_image'])) {
    $pi = trim($student['profile_image']);
    if (preg_match('#^https?://#i', $pi)) {
        $imgSrc = $pi;
    } elseif (file_exists("../uploads/" . $pi)) {
        $imgSrc = "../uploads/" . $pi;
    } elseif (file_exists("uploads/" . $pi)) {
        $imgSrc = "uploads/" . $pi;
    }
}

$dob = $student['dob'] ?: '';
$age = '-';
if ($dob) {
    try {
        $d1 = new DateTime($dob);
        $d2 = new DateTime();
        $age = $d1->diff($d2)->y;
    } catch (Exception $e) {
        $age = '-';
    }
}

$printedAt = date('d-m-Y');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Application Form — <?= htmlspecialchars($student['full_name']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; background:#f0f2f5; margin:0; padding:20px; }
    .form-sheet { width: 800px; margin: auto; background: #fff; padding: 30px; border: 1px solid #ccc; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
    
    .header { text-align:center; margin-bottom:20px; }
    .header h1 { margin:0; font-size:22px; text-transform:uppercase; color:#2c3e50; }
    .header p { margin:5px 0; font-size:14px; color:#555; }

    .photo { float:right; width:140px; height:180px; border:1px solid #aaa; text-align:center; margin-left:20px; }
    .photo img { width:100%; height:100%; object-fit:cover; }

    table.details { width:100%; border-collapse: collapse; margin-top:10px; }
    table.details th, table.details td { border:1px solid #444; padding:8px; text-align:left; font-size:14px; }
    table.details th { width:30%; background:#f9f9f9; }

    .section { margin-top:30px; }
    .section h3 { margin-bottom:8px; font-size:16px; color:#2c3e50; }

    .signatures { margin-top:40px; display:flex; justify-content:space-between; }
    .sig-box { text-align:center; width:22%; }
    .sig-line { border-top:1px dotted #000; margin-top:60px; }
    .sig-name { font-size:13px; margin-top:6px; }

    .actions { margin-bottom:20px; text-align:right; }
    .btn { padding:8px 14px; border:none; border-radius:4px; cursor:pointer; font-size:14px; }
    .btn.print { background:#007bff; color:#fff; }
    .btn.back { background:#6c757d; color:#fff; margin-right:8px; }

    @media print {
      .actions { display:none; }
      body { background:#fff; }
      .form-sheet { box-shadow:none; border:none; width:100%; }
    }
  </style>
</head>
<body>
  <div class="form-sheet">
    <div class="actions">
      <a href="student-view.php?id=<?= $student['id'] ?>" class="btn back">← Back</a>
      <button onclick="window.print()" class="btn print">🖨 Print</button>
    </div>

    <div class="header">
      <h1>Student Application Form</h1>
      <p>Institution Registration System</p>
      <p><strong>Date:</strong> <?= htmlspecialchars($printedAt) ?></p>
    </div>

    <div class="photo">
      <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Photo">
    </div>

    <table class="details">
      <tr><th>Full Name</th><td><?= htmlspecialchars($student['full_name']) ?></td></tr>
      <tr><th>Class</th><td><?= htmlspecialchars($student['class_name'] ?? $student['class_id']) ?></td></tr>
      <tr><th>School</th><td><?= htmlspecialchars($student['school']) ?></td></tr>
      <tr><th>Place</th><td><?= htmlspecialchars($student['place']) ?></td></tr>
      <tr><th>District</th><td><?= htmlspecialchars($student['district']) ?></td></tr>
      <tr><th>Date of Birth</th><td><?= htmlspecialchars($student['dob']) ?> (Age: <?= $age ?>)</td></tr>
      <tr><th>Knowledge</th><td><?= htmlspecialchars($student['knowledge']) ?></td></tr>
      <tr><th>Phone</th><td><?= htmlspecialchars($student['phone']) ?></td></tr>
      <tr><th>Extra Notes</th><td><?= nl2br(htmlspecialchars($student['extra'])) ?></td></tr>
    </table>

    <div class="section">
      <h3>Declaration</h3>
      <p>I hereby declare that the above information is true and correct to the best of my knowledge.</p>
    </div>

    <div class="signatures">
      <div class="sig-box"><div class="sig-line"></div><div class="sig-name">Teacher 1</div></div>
      <div class="sig-box"><div class="sig-line"></div><div class="sig-name">Teacher 2</div></div>
      <div class="sig-box"><div class="sig-line"></div><div class="sig-name">Teacher 3</div></div>
      <div class="sig-box"><div class="sig-line"></div><div class="sig-name"><?= htmlspecialchars($student['class_teacher_name'] ?: 'Class Teacher') ?></div></div>
    </div>
  </div>
</body>
</html>
