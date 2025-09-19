<?php
require_once "db.php";

// fetch all students
$result = $conn->query("SELECT * FROM registrations ORDER BY id ASC");
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Print All Posters</title>
<style>
body { font-family: Arial, sans-serif; background:#fff; margin:0; }
.poster {
  width:794px; min-height:1123px; background:#fff;
  margin:20px auto; padding:30px 40px; box-sizing:border-box;
  font-size:14px; line-height:1.5; color:#222;
  page-break-after:always;
}
.poster-header {
  background:linear-gradient(90deg, #2e7d32, #66bb6a);
  color:#fff; padding:12px 20px; border-radius:6px;
  text-align:center; font-size:20px; font-weight:bold;
}
.poster-fields { margin-top:30px; }
.field-row { display:flex; margin-bottom:12px; }
.field-label { width:180px; font-weight:bold; }
.field-value { flex:1; border-bottom:1px dashed #aaa; padding-left:6px; }
.profile {
  width:120px; height:150px; border:2px dashed #777;
  display:flex; align-items:center; justify-content:center;
  font-size:12px; color:#555; float:right; margin-left:20px;
}
.signatures { display:flex; justify-content:space-around; margin-top:60px; }
.sig { text-align:center; }
.sig-line { border-top:1px solid #000; margin-top:40px; width:80%; margin:auto; }
@media print {
  body { background:#fff; }
}
</style>
</head>
<body onload="window.print()">

<?php foreach($students as $s): ?>
  <div class="poster">
    <div class="poster-header">REGISTRATION FORM</div>
    <div class="profile">
      <?php if ($s['profile_image']): ?>
        <img src="<?= e($s['profile_image']) ?>" style="max-width:100%;max-height:100%;">
      <?php else: ?>PHOTO<?php endif; ?>
    </div>
    <div class="poster-fields">
      <div class="field-row"><div class="field-label">Name:</div><div class="field-value"><?= e($s['full_name']) ?></div></div>
      <div class="field-row"><div class="field-label">Class:</div><div class="field-value"><?= e($s['class']) ?></div></div>
      <div class="field-row"><div class="field-label">School:</div><div class="field-value"><?= e($s['school']) ?></div></div>
      <div class="field-row"><div class="field-label">Place:</div><div class="field-value"><?= e($s['place']) ?></div></div>
      <div class="field-row"><div class="field-label">Knowledge:</div><div class="field-value"><?= e($s['knowledge']) ?></div></div>
      <div class="field-row"><div class="field-label">DOB:</div><div class="field-value"><?= e($s['dob']) ?></div></div>
      <div class="field-row"><div class="field-label">Phone:</div><div class="field-value"><?= e($s['phone']) ?></div></div>
    </div>
    <div class="signatures">
      <div class="sig"><div class="sig-line"></div>Siraj Ustad</div>
      <div class="sig"><div class="sig-line"></div>Asif Ustad</div>
      <div class="sig"><div class="sig-line"></div>Haris Ustad</div>
    </div>
  </div>
<?php endforeach; ?>

</body>
</html>
