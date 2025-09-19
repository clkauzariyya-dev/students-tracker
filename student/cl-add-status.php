<?php
// cl-add-status.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Status - Student Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f8f9fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .status-box {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0px 0px 12px rgba(0,0,0,0.1);
      width: 400px;
    }
    .status-box h3 {
      text-align: center;
      margin-bottom: 20px;
      font-weight: 600;
      color: #0d6efd;
    }
    .btn-custom {
      width: 100%;
    }
  </style>
</head>
<body>

  <div class="status-box">
    <h3>Add Status</h3>
    <form method="post" action="cl-add-status.php">
      <div class="mb-3">
        <label for="title" class="form-label">Status Title</label>
        <input type="text" class="form-control" id="title" name="title" placeholder="Enter status title" required>
      </div>

      <div class="mb-3">
        <label for="desc" class="form-label">Description</label>
        <textarea class="form-control" id="desc" name="desc" rows="3" placeholder="Enter description" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-custom">Save Status</button>
    </form>
  </div>

</body>
</html>
