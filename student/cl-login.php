<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Login</title>
  <style>
    body {
      font-family: "Segoe UI", sans-serif;
      background: linear-gradient(135deg, #2a9d8f, #264653);
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      color: #fff;
    }
    .login-container {
      background: rgba(255, 255, 255, 0.1);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }
    h2 {
      margin-bottom: 20px;
    }
    .form-group {
      margin-bottom: 16px;
      text-align: left;
    }
    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }
    input {
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 15px;
      outline: none;
    }
    input:focus {
      border: 2px solid #f4a261;
    }
    button {
      width: 100%;
      padding: 14px;
      margin-top: 10px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      background: #f4a261;
      color: #fff;
      transition: transform 0.2s, background 0.3s;
    }
    button:hover {
      background: #e76f51;
      transform: scale(1.05);
    }
    .back-link {
      display: block;
      margin-top: 15px;
      color: #ddd;
      text-decoration: none;
      font-size: 14px;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>🎓 Student Login</h2>
    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Student ID / Username</label>
        <input type="text" id="username" name="username" placeholder="Enter your ID" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>
      <button type="submit">Login</button>
    </form>
    <a href="../index.php" class="back-link">⬅ Back to Home</a>
  </div>
</body>
</html>
