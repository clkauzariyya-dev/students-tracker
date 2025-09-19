<?php
// index.php – Home Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>System Home</title>
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background: linear-gradient(135deg, #007bff, #6610f2);
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #fff;
    }
    .container {
      text-align: center;
      background: rgba(255, 255, 255, 0.1);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 500px;
    }
    h1 {
      margin-bottom: 20px;
      font-size: 28px;
    }
    p {
      margin-bottom: 30px;
      font-size: 16px;
    }
    .btn {
      display: block;
      width: 100%;
      padding: 14px;
      margin: 10px 0;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s, background 0.3s;
      text-decoration: none;
      color: #fff;
    }
    .btn:hover {
      transform: scale(1.05);
    }
    .admin { background: #e63946; }
    .student { background: #2a9d8f; }
    .public { background: #f4a261; }
  </style>
</head>
<body>
  <div class="container">
    <h1>📌 Welcome to the System</h1>
    <p>Select your portal:</p>
    <a href="admin/" class="btn admin">Admin Portal</a>
    <a href="student/cl-login.php" class="btn student">Student Portal</a>
    <a href="register/" class="btn public">Public Registration</a>
  </div>
</body>
</html>
