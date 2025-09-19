<?php
require_once "../db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cropped_image'])) {
    $imgData = $_POST['cropped_image'];

    // Convert base64 to image file
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $decodedData = base64_decode($imgData);

    $fileName = time() . "_profile.png";
    $targetDir = "../uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $filePath = $targetDir . $fileName;
    file_put_contents($filePath, $decodedData);

    // Collect other form fields
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone     = $conn->real_escape_string($_POST['phone']);
    $class     = $conn->real_escape_string($_POST['class']);
    $school    = $conn->real_escape_string($_POST['school']);
    $place     = $conn->real_escape_string($_POST['place']);
    $knowledge = $conn->real_escape_string($_POST['knowledge']);
    $dob       = $conn->real_escape_string($_POST['dob']);

    // Save into DB
    $stmt = $conn->prepare("INSERT INTO registrations 
        (full_name, phone, class, school, place, knowledge, dob, profile_image) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $full_name, $phone, $class, $school, $place, $knowledge, $dob, $fileName);

    if ($stmt->execute()) {
        $message = "✅ Registration successful!";
    } else {
        $message = "❌ Database error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registration with Image Crop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
  <style>
    body { background:#eef2f7; display:flex; justify-content:center; align-items:center; min-height:100vh; }
    .container { background:#fff; padding:30px; border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,.15); width:750px; }
    h2 { text-align:center; margin-bottom:20px; }
    .form-row { display:flex; gap:20px; }
    #croppedResult { width:250px; height:250px; border:2px solid #ddd; margin-top:10px; object-fit:cover; border-radius:8px; }
  </style>
</head>
<body>
<div class="container">
  <h2>Student Registration</h2>

  <?php if ($message): ?>
    <div class="alert <?= strpos($message,'✅')!==false ? 'alert-success':'alert-danger' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <form method="POST" id="registerForm">
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Profile Picture</label>
        <input type="file" id="profileImage" accept="image/*" class="form-control" required>
        <input type="hidden" name="cropped_image" id="croppedImage">
        <img id="croppedResult" src="https://via.placeholder.com/250" alt="Preview">
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Phone Number</label>
        <input type="text" name="phone" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Class</label>
        <select name="class" class="form-select" required>
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

    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">School</label>
        <input type="text" name="school" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Place</label>
        <input type="text" name="place" class="form-control" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Knowledge</label>
      <textarea name="knowledge" class="form-control" rows="3" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="dob" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Register</button>
  </form>
</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop Image (250×250)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img id="cropperImage" style="max-width:100%; display:block;">
      </div>
      <div class="modal-footer">
        <button type="button" id="cropBtn" class="btn btn-success">Crop & Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
let cropper;
const profileImage = document.getElementById('profileImage');
const cropperImage = document.getElementById('cropperImage');
const cropResult = document.getElementById('croppedResult');
const croppedImageInput = document.getElementById('croppedImage');
const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));

profileImage.addEventListener('change', function(e){
  const file = e.target.files[0];
  if (file){
    const reader = new FileReader();
    reader.onload = function(e){
      cropperImage.src = e.target.result;
      cropModal.show();
      if(cropper) cropper.destroy();
      setTimeout(()=> {
        cropper = new Cropper(cropperImage, {
          aspectRatio: 1,
          viewMode: 1
        });
      }, 200);
    }
    reader.readAsDataURL(file);
  }
});

document.getElementById('cropBtn').addEventListener('click', function(){
  const canvas = cropper.getCroppedCanvas({ width: 250, height: 250 });
  const dataUrl = canvas.toDataURL("image/png");
  cropResult.src = dataUrl;
  croppedImageInput.value = dataUrl;
  cropModal.hide();
});
</script>
</body>
</html>
