<?php
// public/register.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$mobile = $_GET['mobile'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = trim($_POST['mobile'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($mobile === '' || $name === '') {
        $error = "Both mobile and name are required.";
    } else {
        // Insert new user
        $sql = "INSERT INTO users (mobile, name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $mobile, $name);
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            header('Location: ../student/chat.php');
            exit;
        } else {
            if ($conn->errno == 1062) $error = "This mobile is already registered. Try logging in.";
            else $error = "DB Error: " . $conn->error;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register — Classroom Chat</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="col-md-6 mx-auto">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="mb-3">New user — enter your name</h4>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?=h($error)?></div>
        <?php endif; ?>
        <form method="post" class="row g-2">
          <input type="hidden" name="mobile" value="<?=h($mobile)?>">
          <div class="col-12">
            <input name="name" class="form-control" placeholder="Your name" required>
          </div>
          <div class="col-12 d-grid">
            <button class="btn btn-success">Join Chat</button>
          </div>
        </form>
        <hr>
        <a href="index.php" class="btn btn-link">Back</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>
