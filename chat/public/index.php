<?php
// public/index.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    if ($mobile === '') {
        $error = "Please enter mobile number.";
    } else {
        // Check if user exists
        $sql = "SELECT id, name FROM users WHERE mobile = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $mobile);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $u = $res->fetch_assoc();
            $_SESSION['user_id'] = $u['id'];
            header('Location: ../student/chat.php');
            exit;
        } else {
            // Redirect to register with mobile passed
            header('Location: register.php?mobile=' . urlencode($mobile));
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Classroom Public Chat — Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="col-md-6 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">Enter your mobile to join the classroom chat</h4>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?=h($error)?></div>
        <?php endif; ?>
        <form method="post" class="row g-2">
          <div class="col-12">
            <input name="mobile" class="form-control" placeholder="Mobile number" value="<?=isset($mobile)?h($mobile):''?>" required>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-primary">Proceed</button>
          </div>
        </form>
        <hr>
        <small class="text-muted">This chat is public to anyone using this link in the same classroom. Images (≤ 5 MB) and links allowed.</small>
      </div>
    </div>
  </div>
</div>
</body>
</html>
