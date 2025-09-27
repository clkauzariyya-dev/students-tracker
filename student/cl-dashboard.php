<?php
// cl-dashboard.php
session_start();
require_once __DIR__ . '/../db.php'; // adjust path if needed

if (!isset($_SESSION['student_id'])) {
    header('Location: cl-login.php');
    exit;
}

$student_id = (int)$_SESSION['student_id'];

// fetch student record with class and teacher info
$sql = "
  SELECT s.*, c.class_name, ct.teacher_name AS class_teacher_name
  FROM students s
  LEFT JOIN classes c ON s.class_id = c.id
  LEFT JOIN class_teachers ct ON s.teacher_id = ct.id
  WHERE s.id = ?
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$student = $res->fetch_assoc();
$stmt->close();

if (!$student) {
    // invalid session student - logout
    session_unset();
    session_destroy();
    header('Location: cl-login.php');
    exit;
}

/**
 * Resolve profile image path robustly:
 * - Accepts stored values like "filename.png", "uploads/filename.png", "/uploads/filename.png"
 * - Accepts absolute URL (http/https) and returns it unchanged
 * - Checks ../uploads/ and ../Uploads/ to handle case differences on Windows
 * - Falls back to ../assets/default.png
 */
function student_image_src(array $studentRow): string {
    $pi = trim((string)($studentRow['profile_image'] ?? ''));

    // empty or explicit null -> use default
    if ($pi === '' || strtolower($pi) === 'null') {
        return '../assets/default.png';
    }

    // absolute URL -> return as-is
    if (preg_match('#^https?://#i', $pi)) {
        return $pi;
    }

    // Normalize: remove leading slashes and possible "uploads/" prefix (case-insensitive)
    $clean = preg_replace('#^(/+|uploads/|Uploads/)#i', '', $pi);

    // Candidate locations (prefer lowercase uploads)
    $candidates = [
        __DIR__ . '/../uploads/' . $clean,
        __DIR__ . '/../Uploads/' . $clean,
        dirname(__DIR__) . '/uploads/' . $clean,   // alternate relative
        dirname(__DIR__) . '/Uploads/' . $clean,
    ];

    foreach ($candidates as $path) {
        if ($path && file_exists($path) && is_file($path)) {
            // convert server path to web path relative to this script (student folder)
            // prefer ../uploads/ (lowercase) if that candidate matched
            if (stripos($path, DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) !== false) {
                return '../uploads/' . $clean;
            }
            if (stripos($path, DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR) !== false) {
                return '../Uploads/' . $clean;
            }
            // fallback: return relative path computed from filename
            return '../uploads/' . $clean;
        }
    }

    // nothing matched -> default
    return '../assets/default.png';
}

$imgSrc = student_image_src($student);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Dashboard — <?= htmlspecialchars($student['full_name']) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../assets/student-bootstrap.css">
</head>
<body>
  <div class="student-shell">
    <div class="student-card">
      <!-- header -->
      <div class="header-row">
        <div class="header-left">
          <img id="profilePhoto" src="<?= htmlspecialchars($imgSrc) ?>" alt="Profile" class="profile-photo" data-bs-toggle="modal" data-bs-target="#profileModal">
          <div>
            <div class="header-title"><?= htmlspecialchars($student['full_name']) ?></div>
            <div class="header-sub"><?= htmlspecialchars($student['class_name'] ?: 'Class ID: '.$student['class_id']) ?></div>
          </div>
        </div>

        <div class="text-end">
          <a href="cl-task.php" class="btn btn-outline-light me-2">Tasks</a>
          <a href="cl-add-status.php" class="btn btn-outline-light me-2">Add Status</a>
          <a href="logout.php" class="btn btn-accent">Logout</a>
        </div>
      </div>

      <!-- main content -->
      <div class="row dashboard-grid">
        <div class="col-md-8">
          <div class="card-plain p-3">
            <h5 class="mb-2">Overview</h5>
            <p style="color:var(--muted)">Quick info about your registration.</p>

            <div class="row">
              <div class="col-sm-6">
                <strong>Name</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['full_name']) ?></span>
              </div>
              <div class="col-sm-6">
                <strong>Phone</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['phone']) ?></span>
              </div>
              <div class="col-sm-6 mt-3">
                <strong>School</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['school']) ?></span>
              </div>
              <div class="col-sm-6 mt-3">
                <strong>Place</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['place']) ?></span>
              </div>
              <div class="col-sm-6 mt-3">
                <strong>District</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['district']) ?></span>
              </div>
              <div class="col-sm-6 mt-3">
                <strong>Class Teacher</strong><br>
                <span class="text-muted"><?= htmlspecialchars($student['class_teacher_name'] ?: '—') ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="text-center card-plain p-3">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="photo" class="crop-preview mb-2" style="width:160px;height:160px;border-radius:10px;">
            <div class="mt-2">
              <a href="#" class="student-link" data-bs-toggle="modal" data-bs-target="#profileModal">View full profile</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile Modal -->
  <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="profileModalLabel"><?= htmlspecialchars($student['full_name']) ?> — Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4 text-center">
              <img src="<?= htmlspecialchars($imgSrc) ?>" alt="profile" class="crop-preview" style="width:220px;height:220px;">
              <div class="mt-3">
                <strong><?= htmlspecialchars($student['full_name']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($student['phone']) ?></small>
              </div>
            </div>

            <div class="col-md-8">
              <table class="table table-borderless text-white">
                <tbody>
                  <tr><th class="text-muted">Full name</th><td><?= htmlspecialchars($student['full_name']) ?></td></tr>
                  <tr><th class="text-muted">Class</th><td><?= htmlspecialchars($student['class_name'] ?: $student['class_id']) ?></td></tr>
                  <tr><th class="text-muted">School</th><td><?= htmlspecialchars($student['school']) ?></td></tr>
                  <tr><th class="text-muted">Place</th><td><?= htmlspecialchars($student['place']) ?></td></tr>
                  <tr><th class="text-muted">District</th><td><?= htmlspecialchars($student['district']) ?></td></tr>
                  <tr><th class="text-muted">DOB</th><td><?= htmlspecialchars($student['dob']) ?></td></tr>
                  <tr><th class="text-muted">Knowledge</th><td><?= htmlspecialchars($student['knowledge']) ?></td></tr>
                  <tr><th class="text-muted">Phone</th><td><?= htmlspecialchars($student['phone']) ?></td></tr>
                  <tr><th class="text-muted">Extra</th><td><?= nl2br(htmlspecialchars($student['extra'])) ?></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <a href="print.php?id=<?= (int)$student['id'] ?>" target="_blank" class="btn btn-outline-light">Print Application</a>
          <button type="button" class="btn btn-accent" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
