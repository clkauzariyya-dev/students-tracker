<?php 
// admin.php - improved admin panel with working action buttons
require_once "../db.php";

// Delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM registrations WHERE id=$id");
    header("Location: admin.php");
    exit;
}

// Fetch data
$result = $conn->query("SELECT * FROM registrations ORDER BY id DESC");

// Count board stats
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM registrations")->fetch_assoc()['c'];
$totalSchools = $conn->query("SELECT COUNT(DISTINCT school) AS c FROM registrations")->fetch_assoc()['c'];
$totalClasses = $conn->query("SELECT COUNT(DISTINCT class) AS c FROM registrations")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel - Registrations</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root{
      --bg:#0f172a; --panel:#1e293b; --muted:#cbd5e1; --accent:#38bdf8; --card:#334155;
      --success:#22c55e; --danger:#ef4444; --yellow:#facc15;
    }
    *{box-sizing:border-box}
    body {
      margin: 0;
      font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial;
      background: var(--bg);
      color: #e6eef8;
      display:flex;
      min-height:100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 220px;
      background: var(--panel);
      min-height: 100vh;
      padding: 22px 12px;
    }
    .sidebar h2 { text-align:center; margin:8px 0 24px; color:#fff; font-size:20px; }
    .sidebar a {
      display:block; padding:10px 16px; color:var(--muted); text-decoration:none; margin-bottom:6px; border-radius:8px;
    }
    .sidebar a.active, .sidebar a:hover { background: var(--card); color:#fff; }

    /* Main */
    .main { flex:1; padding:28px; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; }
    .title { font-size:20px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; }
    .board { display:flex; gap:18px; margin-bottom:22px; }
    .card {
      background:var(--panel); padding:18px; border-radius:12px; flex:1; text-align:center; box-shadow:0 6px 20px rgba(2,6,23,0.6);
    }
    .card h3 { margin:0; color:var(--accent); font-size:18px; }
    .card p { margin:8px 0 0; color:var(--muted); font-size:13px; }

    /* Table */
    .table-wrap { background:transparent; border-radius:12px; overflow:hidden; }
    table {
      width:100%; border-collapse:collapse; min-width:900px;
      background:linear-gradient(180deg,var(--panel),#172033);
    }
    th, td {
      padding:12px 10px; border-bottom:1px solid rgba(255,255,255,0.04); text-align:center; vertical-align:middle; color:#e6eef8;
      font-size:14px;
    }
    th { background:var(--card); font-weight:600; color:#fff; }
    tr:hover td { background: rgba(255,255,255,0.02); }
    img.thumb { width:56px; height:56px; object-fit:cover; border-radius:8px; display:inline-block; }

    .btn { padding:8px 10px; border-radius:8px; border:none; cursor:pointer; font-weight:600; }
    .btn.view { background:var(--success); color:#042007; margin-right:6px; }
    .btn.delete { background:var(--danger); color:#fff; }
    .btn.edit { background:var(--yellow); color:#111; }

    /* popup */
    .popup { display:none; position:fixed; inset:0; background:rgba(2,6,23,0.6); align-items:center; justify-content:center; z-index:30; }
    .popup .box {
      width:420px; max-width:94%; background:var(--panel); border-radius:10px; padding:18px; color:var(--muted); position:relative;
    }
    .popup .close { position:absolute; right:12px; top:8px; font-size:20px; color:#fff; cursor:pointer; }
    .popup .box h3 { color:#fff; margin-top:0; margin-bottom:8px; }
    .popup .info-row { display:flex; gap:12px; align-items:center; }
    .popup .info { flex:1; text-align:left; color:var(--muted); }

    .print-btn {
      background:#3b82f6;color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer;margin-top:12px;
    }

    @media (max-width:980px){
      .board{flex-direction:column}
      .table-wrap{overflow:auto}
    }
  </style>
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
      <div class="card">
        <h3><?= (int)$totalStudents ?></h3>
        <p>Total Students</p>
      </div>
      <div class="card">
        <h3><?= (int)$totalSchools ?></h3>
        <p>Total Schools</p>
      </div>
      <div class="card">
        <h3><?= (int)$totalClasses ?></h3>
        <p>Total Classes</p>
      </div>
    </div>

    <div class="table-wrap">
      <table role="table" aria-label="registrations">
        <thead>
          <tr>
            <th>ID</th>
            <th>Profile</th>
            <th>Full Name</th>
            <th>Age</th>
            <th>Class</th>
            <th>School</th>
            <th>Place</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()):
            // compute age (server side)
            $age = '-';
            if (!empty($row['dob'])) {
                try {
                    $dob = new DateTime($row['dob']);
                    $today = new DateTime();
                    $age = $dob->diff($today)->y;
                } catch(Exception $e) { $age = '-'; }
            }

            // Build a compact array to embed as JSON for JS
            $rowData = [
              'id' => (int)$row['id'],
              'full_name' => $row['full_name'],
              'phone' => $row['phone'] ?? '',
              'class' => $row['class'],
              'school' => $row['school'],
              'place' => $row['place'],
              'knowledge' => $row['knowledge'],
              'dob' => $row['dob'],
              'age' => $age,
              'profile_image' => $row['profile_image'] ?? ''
            ];
            $json = htmlspecialchars(json_encode($rowData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
        ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td>
              <?php
                $imgSrc = $row['profile_image'] ?? '';
                if ($imgSrc && !preg_match('#^https?://#', $imgSrc) && !str_starts_with($imgSrc, '/')) {
                    // ensure path is correct relative to admin folder
                    $imgOut = '../' . ltrim($imgSrc, '/');
                } else {
                    $imgOut = $imgSrc ?: 'https://via.placeholder.com/80';
                }
              ?>
              <img class="thumb" src="<?= htmlspecialchars($imgOut, ENT_QUOTES) ?>" alt="thumb">
            </td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($age) ?></td>
            <td><?= htmlspecialchars($row['class']) ?></td>
            <td><?= htmlspecialchars($row['school']) ?></td>
            <td><?= htmlspecialchars($row['place']) ?></td>
            <td>
              <button class="btn view view-btn" data-row="<?= $json ?>">View</button>
              <a href="edit.php?id=<?= (int)$row['id'] ?>"><button class="btn edit">Edit</button></a>
              <a href="admin.php?delete=<?= (int)$row['id'] ?>" onclick="return confirm('Delete this record?')">
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
    <div class="box" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
      <div class="close" id="popupClose">&times;</div>
      <div id="popup-data"></div>
    </div>
  </div>

<script>
  // safe text escape
  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  // convert path stored in DB to usable src from admin folder
  function resolveImagePath(path){
    if(!path) return 'https://via.placeholder.com/140';
    if(/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    // path likely relative like "uploads/xxx.jpg" -> from /admin we need ../uploads/xxx.jpg
    return '../' + path.replace(/^\/+/,'');
  }

  // open popup with provided JS object
  function openRecordPopup(data){
    window.currentRecord = data; // keep for printing
    const container = document.getElementById('popup-data');
    const imgSrc = resolveImagePath(data.profile_image || '');
    const html = `
      <h3 id="popupTitle">${escapeHtml(data.full_name)}</h3>
      <div class="info-row">
        <div style="flex:0 0 140px; text-align:center; margin-right:12px;">
          <img src="${escapeHtml(imgSrc)}" style="width:120px;height:120px;object-fit:cover;border-radius:8px;display:block;margin:0 auto 8px;">
        </div>
        <div class="info">
          <div><strong>Name:</strong> ${escapeHtml(data.full_name)}</div>
          <div><strong>Phone:</strong> ${escapeHtml(data.phone)}</div>
          <div><strong>Class:</strong> ${escapeHtml(data.class)}</div>
          <div><strong>School:</strong> ${escapeHtml(data.school)}</div>
          <div><strong>Place:</strong> ${escapeHtml(data.place)}</div>
          <div><strong>Age:</strong> ${escapeHtml(data.age)}</div>
          <div style="margin-top:8px;"><strong>Knowledge:</strong><div style="margin-top:6px;color:var(--muted)">${escapeHtml(data.knowledge)}</div></div>
          <div style="margin-top:8px;"><strong>DOB:</strong> ${escapeHtml(data.dob)}</div>
        </div>
      </div>
      <div style="text-align:right;margin-top:12px;">
        <button class="print-btn" onclick="printRecord()">🖨️ Print</button>
        <a href="edit.php?id=${encodeURIComponent(data.id)}"><button class="btn edit">✏ Edit</button></a>
      </div>
    `;
    container.innerHTML = html;
    document.getElementById('popup').style.display = 'flex';
  }

  // Print the currentRecord in a clean window with signature
  function printRecord(){
    const data = window.currentRecord;
    if(!data) return alert('No record selected.');
    const imgSrc = resolveImagePath(data.profile_image || '');
    const content = `
      <div style="font-family:Arial,Helvetica,sans-serif;padding:24px;color:#111;">
        <h2 style="text-align:center;margin-bottom:12px;">Student Profile</h2>
        <div style="display:flex;gap:18px;align-items:center">
          <img src="${escapeHtml(imgSrc)}" style="width:150px;height:150px;object-fit:cover;border-radius:8px;">
          <div style="flex:1;">
            <p><strong>Name:</strong> ${escapeHtml(data.full_name)}</p>
            <p><strong>Phone:</strong> ${escapeHtml(data.phone)}</p>
            <p><strong>Class:</strong> ${escapeHtml(data.class)}</p>
            <p><strong>School:</strong> ${escapeHtml(data.school)}</p>
            <p><strong>Place:</strong> ${escapeHtml(data.place)}</p>
            <p><strong>Age:</strong> ${escapeHtml(data.age)}</p>
            <p><strong>DOB:</strong> ${escapeHtml(data.dob)}</p>
          </div>
        </div>
        <div style="margin-top:16px;"><strong>Knowledge:</strong><div style="margin-top:6px;">${escapeHtml(data.knowledge)}</div></div>
        <div style="margin-top:60px;border-top:1px solid #333;padding-top:8px;text-align:left">
          Signature: _____________________________
        </div>
      </div>
    `;
    const w = window.open('','_blank','width=900,height=700');
    w.document.write('<!doctype html><html><head><meta charset="utf-8"><title>Print</title></head><body>');
    w.document.write(content);
    w.document.write('</body></html>');
    w.document.close();
    w.focus();
    setTimeout(()=>w.print(), 300);
  }

  // delegate view button clicks
  document.addEventListener('click', function(e){
    const vb = e.target.closest('.view-btn');
    if(vb){
      const raw = vb.dataset.row;
      if(!raw) return;
      try {
        const data = JSON.parse(raw);
        openRecordPopup(data);
      } catch(err){
        console.error('Failed to parse row data', err, raw);
        alert('An error occurred while opening the record.');
      }
    }
    if(e.target.id === 'popupClose' || e.target.closest('#popup .close')){
      document.getElementById('popup').style.display = 'none';
    }
  });

  // close on ESC
  document.addEventListener('keydown', (ev)=> {
    if(ev.key === 'Escape') document.getElementById('popup').style.display = 'none';
  });
</script>
</body>
</html>
