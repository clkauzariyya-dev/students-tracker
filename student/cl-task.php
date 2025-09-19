<?php
// cl-task.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Tasks</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .navbar {
      background: #0d6efd;
    }
    .navbar-brand, .nav-link {
      color: #fff !important;
    }
    .task-container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0px 4px 12px rgba(0,0,0,0.1);
    }
    .task-header {
      text-align: center;
      margin-bottom: 20px;
    }
    .task-item {
      border-bottom: 1px solid #eee;
      padding: 15px 0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .task-item:last-child {
      border-bottom: none;
    }
    .task-title {
      font-weight: 600;
      color: #0d6efd;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="cl-dashboard.php">Student Portal</a>
      <div class="d-flex">
        <a href="cl-dashboard.php" class="btn btn-light btn-sm me-2">Dashboard</a>
        <a href="cl-login.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Task List -->
  <div class="task-container">
    <div class="task-header">
      <h2 class="fw-bold text-primary">📌 My Tasks</h2>
      <p class="text-muted">Here are your upcoming tasks & assignments.</p>
    </div>

    <!-- Example Tasks -->
    <div class="task-item">
      <div>
        <span class="task-title">Complete Assignment 1</span>
        <p class="mb-0 text-muted">Due: 20th Sept 2025</p>
      </div>
      <button class="btn btn-success btn-sm">Mark as Done</button>
    </div>

    <div class="task-item">
      <div>
        <span class="task-title">Group Discussion</span>
        <p class="mb-0 text-muted">Due: 22nd Sept 2025</p>
      </div>
      <button class="btn btn-success btn-sm">Mark as Done</button>
    </div>

    <div class="task-item">
      <div>
        <span class="task-title">Prepare Presentation</span>
        <p class="mb-0 text-muted">Due: 25th Sept 2025</p>
      </div>
      <button class="btn btn-success btn-sm">Mark as Done</button>
    </div>
  </div>

</body>
</html>
