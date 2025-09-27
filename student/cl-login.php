<?php
// cl-login.php
session_start();
require_once __DIR__ . '/../db.php'; // adjust path if needed

// If already logged in
if (isset($_SESSION['student_id'])) {
    header('Location: cl-dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    if ($phone === '') {
        $error = "Please enter your phone number.";
    } else {
        // find student by phone
        $stmt = $conn->prepare("SELECT id, full_name FROM students WHERE phone = ? LIMIT 1");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $student = $res->fetch_assoc();
            $_SESSION['student_id'] = (int)$student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            header("Location: cl-dashboard.php");
            exit;
        } else {
            $error = "No student found with that phone number.";
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- NOTE: Bootstrap JS still required in pages; CSS imported inside student-bootstrap.css via @import -->
  <link rel="stylesheet" href="../assets/student-bootstrap.css">
</head>
<body>
  <div class="student-shell">
    <div class="student-card">
      <div class="row gx-4 gy-3 align-items-center">
        <div class="col-12 col-md-6">
          <h2 class="mb-1">🎓 Student Login</h2>
          <p class="mb-3" style="color:var(--muted)">Sign in with the phone number you provided during registration.</p>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
              <label class="form-label" for="phone">Phone number</label>
              <input id="phone" name="phone" class="form-control" type="text" placeholder="+91 9XXXXXXXXX" required>
            </div>

            <div class="d-grid">
              <button class="btn btn-accent" type="submit">Login</button>
            </div>
          </form>

          <a class="student-link mt-3 d-inline-block" href="../index.php">← Back to Home</a>
        </div>

        <div class="col-12 col-md-6 text-center">
          <img src="../assets/default.png" alt="students" style="max-width:220px; width:100%; border-radius:12px; border:1px solid rgba(255,255,255,0.05)">
          <p class="mt-3" style="color:var(--muted)">Welcome to the Kauzariyya student portal.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- bootstrap JS (bundle includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
  // small client-side validation feedback
  (function(){
    'use strict';
    const form = document.querySelector('.needs-validation');
    form && form.addEventListener('submit', function(e){
      if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  })();
  </script>
</body>
</html>
