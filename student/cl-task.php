<?php
// student/cl-task.php
session_start();
require_once __DIR__ . '/../db.php'; // expects $conn (mysqli) from db.php

// check login
if (!isset($_SESSION['student_id'])) {
    header('Location: cl-login.php');
    exit;
}
$student_id = (int)$_SESSION['student_id'];

// load student basic info (class_id etc.)
$stmt = $conn->prepare("
  SELECT s.*, c.class_name, ct.teacher_name AS class_teacher_name
  FROM students s
  LEFT JOIN classes c ON s.class_id = c.id
  LEFT JOIN class_teachers ct ON s.teacher_id = ct.id
  WHERE s.id = ? LIMIT 1
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc() ?: null;
$stmt->close();

if (!$student) {
    // invalid session -> logout
    session_unset();
    session_destroy();
    header('Location: cl-login.php');
    exit;
}

// ---------- API endpoints (AJAX) ----------

// Fetch task details (modal)
if (isset($_GET['action']) && $_GET['action'] === 'task' && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    $q = $conn->prepare("SELECT t.*, ct.teacher_name FROM tasks t LEFT JOIN class_teachers ct ON t.teacher_id = ct.id WHERE t.id = ? LIMIT 1");
    $q->bind_param('i', $tid);
    $q->execute();
    $r = $q->get_result()->fetch_assoc();
    $q->close();
    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        exit;
    }

    // check if this task applies to this student (same logic as below)
    echo json_encode($r);
    exit;
}

// Mark complete (AJAX POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete' && isset($_POST['task_id'])) {
    $task_id = (int)$_POST['task_id'];

    // Ensure student_tasks table exists (create if missing)
    $conn->query("
      CREATE TABLE IF NOT EXISTS student_tasks (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        student_id INT NOT NULL,
        status ENUM('pending','done') NOT NULL DEFAULT 'pending',
        completed_at DATETIME DEFAULT NULL,
        UNIQUE KEY (task_id, student_id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // insert or update status to 'done'
    $now = date('Y-m-d H:i:s');
    // try update first
    $update = $conn->prepare("UPDATE student_tasks SET status='done', completed_at=? WHERE task_id=? AND student_id=?");
    $update->bind_param('sii', $now, $task_id, $student_id);
    $update->execute();
    if ($update->affected_rows === 0) {
        // insert
        $ins = $conn->prepare("INSERT INTO student_tasks (task_id, student_id, status, completed_at) VALUES (?, ?, 'done', ?)
                               ON DUPLICATE KEY UPDATE status='done', completed_at=VALUES(completed_at)");
        $ins->bind_param('iis', $task_id, $student_id, $now);
        $ok = $ins->execute();
        $ins->close();
        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not save status']);
            exit;
        }
    }
    $update->close();

    echo json_encode(['ok' => true, 'completed_at' => $now]);
    exit;
}

// ---------- Helper: get student's batch ids if table exists ----------
function get_student_batches(mysqli $conn, int $student_id): array {
    // optional table student_batches(student_id,batch_id)
    $batches = [];
    // check if table exists
    $res = $conn->query("SHOW TABLES LIKE 'student_batches'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("SELECT batch_id FROM student_batches WHERE student_id = ?");
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) $batches[] = (int)$row['batch_id'];
        $stmt->close();
    }
    return $batches;
}

// ---------- Load tasks, then filter per-student in PHP ----------
$allTasksRes = $conn->query("SELECT t.*, ct.teacher_name FROM tasks t LEFT JOIN class_teachers ct ON t.teacher_id = ct.id ORDER BY COALESCE(t.due_date, '9999-12-31') ASC, t.created_at DESC");
$allTasks = $allTasksRes ? $allTasksRes->fetch_all(MYSQLI_ASSOC) : [];

$student_class_id = (int)($student['class_id'] ?? 0);
$student_batches = get_student_batches($conn, $student_id);

// filter function
$applies = function(array $task) use ($student_id, $student_class_id, $student_batches): bool {
    $type = $task['assigned_type'] ?? 'all';
    $assigned_to = isset($task['assigned_to']) ? (int)$task['assigned_to'] : 0;
    if ($type === 'all') return true;
    if ($type === 'individual' && $assigned_to === $student_id) return true;
    if ($type === 'class' && $assigned_to === $student_class_id && $assigned_to !== 0) return true;
    if ($type === 'batch' && in_array($assigned_to, $student_batches, true)) return true;
    return false;
};

// fetch student-specific status map from student_tasks
$statusMap = [];
$res = $conn->query("SHOW TABLES LIKE 'student_tasks'");
if ($res && $res->num_rows > 0) {
    $stmt = $conn->prepare("SELECT task_id, status, completed_at FROM student_tasks WHERE student_id = ?");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) {
        $statusMap[(int)$row['task_id']] = $row;
    }
    $stmt->close();
}

// build filtered list
$tasks = [];
foreach ($allTasks as $t) {
    if ($applies($t)) {
        $tid = (int)$t['id'];
        $t['_status'] = $statusMap[$tid]['status'] ?? 'pending';
        $t['_completed_at'] = $statusMap[$tid]['completed_at'] ?? null;
        $tasks[] = $t;
    }
}

// ---------- HTML / UI ----------
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Tasks — <?= htmlspecialchars($student['full_name']) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Bootstrap (keeps theme in your assets file) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/student-bootstrap.css">
  <style>
    .task-table th, .task-table td { vertical-align: middle; }
    .badge-pending { background:#f59e0b; color:#fff; }
    .badge-done { background:#10b981; color:#fff; }
  </style>
</head>
<body>
  <div class="container py-4" style="max-width:1100px;">
    <div class="d-flex align-items-center mb-4">
      <div class="me-auto">
        <h3 class="text-white mb-0">Your Tasks</h3>
        <div class="text-muted small">Tasks assigned to you — individual, class, batch or all</div>
      </div>
      <div>
        <a href="cl-dashboard.php" class="btn btn-outline-light">← Dashboard</a>
      </div>
    </div>

    <div class="card p-3">
      <div class="table-responsive">
        <table class="table table-borderless task-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Assigned By</th>
              <th>Type</th>
              <th>Due</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($tasks)): ?>
              <tr><td colspan="7" class="text-center text-muted">No tasks assigned.</td></tr>
            <?php else: foreach ($tasks as $t): ?>
              <tr id="task-row-<?= (int)$t['id'] ?>">
                <td><?= (int)$t['id'] ?></td>
                <td><?= htmlspecialchars($t['title']) ?></td>
                <td><?= htmlspecialchars($t['teacher_name'] ?? 'Admin') ?></td>
                <td><?= htmlspecialchars(ucfirst($t['assigned_type'] ?? 'all')) ?></td>
                <td><?= $t['due_date'] ? htmlspecialchars($t['due_date']) : '—' ?></td>
                <td>
                  <?php if ($t['_status'] === 'done'): ?>
                    <span class="badge badge-done">Done</span>
                    <?php if (!empty($t['_completed_at'])): ?>
                      <div class="small text-muted">at <?= htmlspecialchars($t['_completed_at']) ?></div>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="badge badge-pending">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-primary me-1" onclick="openTask(<?= (int)$t['id'] ?>)">View</button>
                  <?php if ($t['_status'] !== 'done'): ?>
                    <button class="btn btn-sm btn-success" onclick="markDone(<?= (int)$t['id'] ?>, this)">Mark done</button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-secondary" disabled>Completed</button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Task Detail Modal -->
  <div class="modal fade" id="taskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="taskModalTitle">Task</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="taskModalBody">
          <div class="text-center text-muted">Loading…</div>
        </div>
        <div class="modal-footer">
          <button id="markDoneBtn" class="btn btn-success" onclick="">Mark done</button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
    let currentTaskId = null;

    function openTask(id) {
      currentTaskId = id;
      document.getElementById('taskModalTitle').innerText = 'Loading…';
      document.getElementById('taskModalBody').innerHTML = '<div class="text-center text-muted">Loading…</div>';
      fetch(`cl-task.php?action=task&id=${encodeURIComponent(id)}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            document.getElementById('taskModalTitle').innerText = 'Error';
            document.getElementById('taskModalBody').innerHTML = '<div class="text-danger">' + (data.error || 'Not found') + '</div>';
            taskModal.show();
            return;
          }
          document.getElementById('taskModalTitle').innerText = data.title || 'Task';
          let html = '<p><strong>Assigned by:</strong> ' + (data.teacher_name || 'Admin') + '</p>';
          html += '<p><strong>Type:</strong> ' + (data.assigned_type || 'all') + '</p>';
          html += '<p><strong>Due:</strong> ' + (data.due_date || '—') + '</p>';
          html += '<hr>';
          html += '<div>' + (data.description ? escapeHtml(data.description).replace(/\n/g,'<br>') : '<em>No description</em>') + '</div>';
          document.getElementById('taskModalBody').innerHTML = html;
          // set mark done button
          document.getElementById('markDoneBtn').onclick = function(){ markDone(id); };
          taskModal.show();
        }).catch(err => {
          document.getElementById('taskModalBody').innerHTML = '<div class="text-danger">Failed to fetch task</div>';
          taskModal.show();
        });
    }

    function markDone(taskId, btnEl) {
      if (!confirm('Mark this task as completed?')) return;
      // disable UI
      if (btnEl) { btnEl.disabled = true; btnEl.innerText = 'Saving…'; }
      fetch('cl-task.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: 'action=complete&task_id=' + encodeURIComponent(taskId)
      }).then(r => r.json())
        .then(data => {
          if (data.ok) {
            // update row UI
            const row = document.getElementById('task-row-' + taskId);
            if (row) {
              const statusCell = row.querySelector('td:nth-child(6)');
              statusCell.innerHTML = '<span class="badge badge-done">Done</span><div class="small text-muted">at ' + (data.completed_at || '') + '</div>';
              const actionsCell = row.querySelector('td:last-child');
              if (actionsCell) actionsCell.innerHTML = '<button class="btn btn-sm btn-secondary" disabled>Completed</button>';
            }
            // if modal open, update mark button
            const markBtn = document.getElementById('markDoneBtn');
            if (markBtn) { markBtn.disabled = true; markBtn.innerText = 'Completed'; }
          } else {
            alert('Could not mark done: ' + (data.error||'unknown'));
            if (btnEl) { btnEl.disabled = false; btnEl.innerText = 'Mark done'; }
          }
        }).catch(err => {
          alert('Request failed.');
          if (btnEl) { btnEl.disabled = false; btnEl.innerText = 'Mark done'; }
        });
    }

    // small helper to escape HTML in description preview
    function escapeHtml(s) {
      if (!s) return '';
      return s.replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; });
    }
  </script>
</body>
</html>
