<?php
require_once "../db.php";

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$id = intval($_GET['id']);
$query = $conn->query("SELECT * FROM registrations WHERE id = $id");
$row = $query->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cropped_image'])) {
    $imgData = $_POST['cropped_image'];

    // Convert base64 to image file if updated
    $imageFileName = $row['profile_image'];
    if (!empty($imgData)) {
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

        $imageFileName = $fileName;
    }

    // Collect updated fields
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $phone     = $conn->real_escape_string($_POST['phone']);
    $class     = $conn->real_escape_string($_POST['class']);
    $school    = $conn->real_escape_string($_POST['school']);
    $place     = $conn->real_escape_string($_POST['place']);
    $knowledge = $conn->real_escape_string($_POST['knowledge']);
    $dob       = $conn->real_escape_string($_POST['dob']);

    // Update query
    $stmt = $conn->prepare("UPDATE registrations 
        SET full_name=?, phone=?, class=?, school=?, place=?, knowledge=?, dob=?, profile_image=? 
        WHERE id=?");
    $stmt->bind_param("ssssssssi", $full_name, $phone, $class, $school, $place, $knowledge, $dob, $imageFileName, $id);

    if ($stmt->execute()) {
        $message = "✅ Student updated successfully!";
        // Refresh row data
        $query = $conn->query("SELECT * FROM registrations WHERE id = $id");
        $row = $query->fetch_assoc();
    } else {
        $message = "❌ Update failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Student</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
  <link href='../assets/register.css' rel=stylesheet>
  <style>
    body { background:#eef2f7; display:flex; justify-content:center; align-items:center; min-height:100vh; }
    .container { background:#fff; padding:30px; border-radius:16px; box-shadow:0 6px 18px rgba(0,0,0,.15); width:750px; }
    h2 { text-align:center; margin-bottom:20px; }
    #croppedResult { width:250px; height:250px; border:2px solid #ddd; margin-top:10px; object-fit:cover; border-radius:8px; }
  </style>
</head>
<body>
<div class="container">
  <h2>✏️ Edit Student</h2>

  <?php if ($message): ?>
    <div class="alert <?= strpos($message,'✅')!==false ? 'alert-success':'alert-danger' ?>">
      <?= $message ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= $row['full_name'] ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Profile Picture</label>
        <input type="file" id="profileImage" accept="image/*" class="form-control">
        <input type="hidden" name="cropped_image" id="croppedImage">
        <img id="croppedResult" src="../uploads/<?= $row['profile_image'] ?: 'default.png' ?>" alt="Preview">
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= $row['phone'] ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Class</label>
        <input type="text" name="class" class="form-control" value="<?= $row['class'] ?>" required>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-6">
        <label class="form-label">School</label>
        <input type="text" name="school" class="form-control" value="<?= $row['school'] ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Place</label>
        <input type="text" name="place" class="form-control" value="<?= $row['place'] ?>" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Knowledge</label>
      <textarea name="knowledge" class="form-control" rows="3"><?= $row['knowledge'] ?></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Date of Birth</label>
      <input type="date" name="dob" class="form-control" value="<?= $row['dob'] ?>" required>
    </div>

    <button type="submit" class="btn btn-success w-100">Update Student</button>
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
