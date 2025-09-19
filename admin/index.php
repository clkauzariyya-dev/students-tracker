<?php 
require_once "../db.php";

// Delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM registrations WHERE id=$id");
    header("Location: index.php");
    exit;
}

// Fetch data (join classes)
$sql = "SELECT r.*, 
               CONCAT(c.class_name, IF(c.section IS NOT NULL, CONCAT(' - ', c.section), '')) AS class_display
        FROM registrations r
        LEFT JOIN classes c ON r.class_id = c.id
        ORDER BY r.id DESC";
$result = $conn->query($sql);

// Count stats
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];
$totalSchools  = $conn->query("SELECT COUNT(DISTINCT school) AS c FROM registrations")->fetch_assoc()['c'];
$totalClasses  = $conn->query("SELECT COUNT(DISTINCT class_id) AS c FROM registrations")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - Dashboard</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
  <div class="sidebar">
    <h2>Admin</h2>
    <a href="index.php" class="active">Dashboard</a>
    <a href="list.php">Student List</a>
    <a href="classes.php">Classes</a>
    <a href="../">Back to Site</a>
  </div>

  <div class="main">
    <div class="header">
      <div class="title">📋 Student Registrations</div>
      <div>
        <button class="btn" onclick="location.href='list.php'">Manage Students</button>
        <button class="btn" onclick="location.href='classes.php'">Manage Classes</button>
      </div>
    </div>

    <div class="board">
      <div class="card"><h3><?= (int)$totalStudents ?></h3><p>Total Students</p></div>
      <div class="card"><h3><?= (int)$totalSchools ?></h3><p>Total Schools</p></div>
      <div class="card"><h3><?= (int)$totalClasses ?></h3><p>Total Classes</p></div>
    </div>

    <div class="table-wrap">
      <table role="table" aria-label="registrations">
        <thead>
          <tr>
            <th>ID</th><th>Profile</th><th>Full Name</th><th>Age</th><th>Class</th><th>School</th><th>Place</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()):
            $age = !empty($row['dob']) ? (new DateTime($row['dob']))->diff(new DateTime())->y : '-';
            $rowData = [
              'id'=>(int)$row['id'], 'full_name'=>$row['full_name'], 'phone'=>$row['phone'],
              'class'=>$row['class_display'], 'school'=>$row['school'], 'place'=>$row['place'],
              'knowledge'=>$row['knowledge'], 'dob'=>$row['dob'], 'age'=>$age,
              'profile_image'=>$row['profile_image']
            ];
            $json = htmlspecialchars(json_encode($rowData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            $imgSrc = $row['profile_image'] ? '../uploads/'.$row['profile_image'] : 'https://via.placeholder.com/80';
        ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><img class="thumb" src="<?= htmlspecialchars($imgSrc) ?>" alt="thumb"></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($age) ?></td>
            <td><?= htmlspecialchars($row['class_display']) ?></td>
            <td><?= htmlspecialchars($row['school']) ?></td>
            <td><?= htmlspecialchars($row['place']) ?></td>
            <td>
              <button class="btn view view-btn" data-row="<?= $json ?>">View</button>
              <a href="edit.php?id=<?= $row['id'] ?>"><button class="btn edit">Edit</button></a>
              <a href="index.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this record?')">
                <button class="btn delete">Delete</button>
              </a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Popup -->
  <div class="popup" id="popup">
    <div class="box">
      <div class="close" id="popupClose">&times;</div>
      <div id="popup-data"></div>
    </div>
  </div>

<script>
function escapeHtml(str){ if(!str) return ''; return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
const popup = document.getElementById('popup');
const popupData = document.getElementById('popup-data');
document.querySelectorAll('.view-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const data = JSON.parse(btn.dataset.row);
    let html = `<h3>${escapeHtml(data.full_name)}</h3>`;
    html += `<div class="info-row"><div class="info"><strong>Phone:</strong> ${escapeHtml(data.phone)}</div></div>`;
    html += `<div class="info-row"><div class="info"><strong>Class:</strong> ${escapeHtml(data.class)}</div></div>`;
    html += `<div class="info-row"><div class="info"><strong>School:</strong> ${escapeHtml(data.school)}</div></div>`;
    html += `<div class="info-row"><div class="info"><strong>Place:</strong> ${escapeHtml(data.place)}</div></div>`;
    html += `<div class="info-row"><div class="info"><strong>DOB:</strong> ${escapeHtml(data.dob)} (Age: ${escapeHtml(data.age)})</div></div>`;
    if(data.profile_image){
      html += `<img src="../uploads/${escapeHtml(data.profile_image)}" 
                 onerror="this.src='https://via.placeholder.com/120'" 
                 style="width:120px;border-radius:8px;margin-top:8px">`;
    } else {
      html += `<img src="https://via.placeholder.com/120" 
                 style="width:120px;border-radius:8px;margin-top:8px">`;
    }
    html += `<button class="print-btn" onclick="window.print()">Print</button>`;
    popupData.innerHTML = html;
    popup.style.display='flex';
  });
});
document.getElementById('popupClose').addEventListener('click',()=>{popup.style.display='none';});
window.addEventListener('click', e => { if(e.target==popup) popup.style.display='none'; });
</script>

</body>
</html>
