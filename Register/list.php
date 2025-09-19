<?php
require_once "../db.php";

$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $class = $conn->real_escape_string($_POST['class']);
    $school = $conn->real_escape_string($_POST['school']);
    $place = $conn->real_escape_string($_POST['place']);
    $knowledge = $conn->real_escape_string($_POST['knowledge']);
    $dob = $conn->real_escape_string($_POST['dob']);

    // Handle image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        // Validate image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
                $profile_image = "uploads/" . $fileName;
            } else {
                $message = "❌ Failed to upload image.";
            }
        } else {
            $message = "❌ File is not a valid image.";
        }
    }

    if (empty($message)) {
        $sql = "INSERT INTO registrations (full_name, phone, class, school, place, knowledge, dob, profile_image) 
                VALUES ('$full_name', '$phone', '$class', '$school', '$place', '$knowledge', '$dob', '$profile_image')";

        if ($conn->query($sql) === TRUE) {
            $message = "✅ Registration successful!";
        } else {
            $message = "❌ Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration Form</title>
  <style>
    body { 
      font-family: "Segoe UI", sans-serif; 
      background: #eef2f7; 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      min-height: 100vh; 
      margin: 0; 
    }
    .container { 
      background: #fff; 
      padding: 40px; 
      border-radius: 16px; 
      box-shadow: 0 6px 18px rgba(0,0,0,0.15); 
      width: 100%; 
      max-width: 650px; 
    }
    h2 { 
      text-align: center; 
      color: #333; 
      margin-bottom: 25px; 
    }
    .form-row { 
      display: flex; 
      gap: 20px; 
      margin-bottom: 16px; 
    }
    .form-group { 
      flex: 1; 
      display: flex; 
      flex-direction: column; 
    }
    label { 
      font-weight: 600; 
      margin-bottom: 6px; 
      color: #444; 
    }
    input, select, textarea { 
      width: 100%; 
      padding: 12px; 
      border: 1px solid #ccc; 
      border-radius: 8px; 
      font-size: 15px; 
      transition: border-color 0.3s; 
    }
    input:focus, select:focus, textarea:focus { 
      border-color: #007bff; 
      outline: none; 
    }
    button { 
      width: 100%; 
      padding: 14px; 
      background: #007bff; 
      color: white; 
      border: none; 
      border-radius: 8px; 
      font-size: 16px; 
      cursor: pointer; 
      transition: background 0.3s; 
      margin-top: 10px; 
    }
    button:hover { background: #0056b3; }
    .message { 
      text-align: center; 
      font-weight: bold; 
      margin-bottom: 20px; 
      padding: 10px; 
      border-radius: 8px; 
    }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }

    /* Profile image preview */
    .image-preview {
      width: 250px;
      height: 250px;
      border: 2px dashed #ccc;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      margin-top: 8px;
    }
    .image-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .admin-btn {
      background: #28a745;
    }
    .admin-btn:hover {
      background: #1e7e34;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Student Registration</h2>

    <?php if (!empty($message)) : ?>
      <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
        <?= $message ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
      <div class="form-row">
        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>
        <div class="form-group">
          <label for="profile_image">Profile Picture</label>
          <input type="file" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(event)">
          <small>Recommended: 250×250px</small>
          <div class="image-preview" id="imagePreview">
            <span style="color:#888;font-size:14px;">No image selected</span>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="text" id="phone" name="phone" required>
        </div>
        <div class="form-group">
          <label for="class">Class</label>
          <select name="class" id="class" required>
            <option value="">-- اختر الصف --</option>
            <option value="الثانوية الأولى">الثانوية الأولى</option>
            <option value="الثانوية الثانية">الثانوية الثانية</option>
            <option value="الثانوية الثالثة">الثانوية الثالثة</option>
            <option value="العالية الأولى">العالية الأولى</option>
            <option value="العالي الثانية">العالي الثانية</option>
            <option value="دورة الحديث">دورة الحديث</option>
            <option value="تخصص في الفقه (أول)">تخصص في الفقه (أول)</option>
            <option value="تخصص في الفقه (ثاني)">تخصص في الفقه (ثاني)</option>
            <option value="تخصص في القراءة">تخصص في القراءة</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="school">School</label>
          <input type="text" id="school" name="school" required>
        </div>
        <div class="form-group">
          <label for="place">Place</label>
          <input type="text" id="place" name="place" required>
        </div>
      </div>

      <div class="form-group">
        <label for="knowledge">Knowledge</label>
        <textarea id="knowledge" name="knowledge" rows="3" required></textarea>
      </div>

      <div class="form-group">
        <label for="dob">Date of Birth</label>
        <input type="date" id="dob" name="dob" required>
      </div>

      <button type="submit" name="register">Register</button>
    </form>

    <button class="admin-btn" onclick="window.location.href='/Register/admin/'">Go to Admin</button>
  </div>

  <script>
    function previewImage(event) {
      const preview = document.getElementById('imagePreview');
      preview.innerHTML = "";
      const img = document.createElement('img');
      img.src = URL.createObjectURL(event.target.files[0]);
      preview.appendChild(img);
    }
  </script>
</body>
</html>
