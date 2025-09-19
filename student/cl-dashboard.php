<?php
// cl-dashboard.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard</title>
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
    .dashboard {
      max-width: 1000px;
      margin: 40px auto;
      padding: 20px;
    }
    .card {
      border-radius: 12px;
      box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
    }
    .card h5 {
      color: #0d6efd;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">Student Portal</a>
      <div class="d-flex">
        <a href="cl-add-status.php" class="btn btn-light btn-sm me-2">+ Add Status</a>
        <a href="cl-login.php" class="btn btn-danger btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Dashboard Content -->
  <div class="dashboard">
    <h2 class="mb-4 text-center fw-bold text-primary">Welcome to Your Dashboard</h2>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card p-3">
          <h5>📖 My Courses</h5>
          <p>View your enrolled courses and progress.</p>
          <a href="#" class="btn btn-outline-primary btn-sm">Go</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <h5>📝 My Status</h5>
          <p>Check your submitted statuses and updates.</p>
          <a href="cl-add-status.php" class="btn btn-outline-primary btn-sm">Add / View</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <h5>📊 Reports</h5>
          <p>Download academic performance and reports.</p>
          <a href="#" class="btn btn-outline-primary btn-sm">View</a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
