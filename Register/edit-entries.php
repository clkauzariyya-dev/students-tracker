<?php
// admin/edit-entries.php
require_once "../db.php";

$message = "";

/* -----------------------
   AJAX: fetch a registration
   ----------------------- */
if (isset($_GET['fetch']) && isset($_GET['id'])) {
    $sid = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM registrations WHERE id = $sid LIMIT 1");
    if ($res && $res->num_rows > 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($res->fetch_assoc(), JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
}

/* -----------------------
   POST: Save into students
   ----------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_student'])) {
    // handle cropped image (base64)
    $imageFileName = null;
    if (!empty($_POST['cropped_image'])) {
        $imgData = $_POST['cropped_image'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
            $imgData = substr($imgData, strpos($imgData, ',') + 1);
            $type = strtolower($type[1]);
            $imgData = str_replace(' ', '+', $imgData);
            $decoded = base64_decode($imgData);
            if ($decoded !== false) {
                $allowed = ['jpg','jpeg','png','gif'];
                if (!in_array($type, $allowed)) $type = 'png';
                $fileName = time() . "_profile." . $type;
                $targetDir = "../uploads/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $filePath = $targetDir . $fileName;
                if (file_put_contents($filePath, $decoded) !== false) {
                    $imageFileName = $fileName;
                } else {
                    $message = "❌ Failed to save image file.";
                }
            } else {
                $message = "❌ Invalid image data.";
            }
        } else {
            $message = "❌ Invalid image format.";
        }
    }

    // collect and sanitize
    $reg_id    = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
    $full_name = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $phone     = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $class_id  = intval($_POST['class_id'] ?? 0);
    $school    = $conn->real_escape_string(trim($_POST['school'] ?? ''));
    $place     = $conn->real_escape_string(trim($_POST['place'] ?? ''));
    $district  = $conn->real_escape_string(trim($_POST['district'] ?? ''));
    $knowledge = $conn->real_escape_string(trim($_POST['knowledge'] ?? ''));
    $dob       = $conn->real_escape_string(trim($_POST['dob'] ?? ''));
    $extra     = $conn->real_escape_string(trim($_POST['extra'] ?? ''));
    $teacher_id= intval($_POST['teacher_id'] ?? 0);

    // If no new image, try registration's profile_image
    if (!$imageFileName && $reg_id) {
        $r = $conn->query("SELECT profile_image FROM registrations WHERE id = $reg_id LIMIT 1");
        if ($r && $r->num_rows) {
            $rr = $r->fetch_assoc();
            if (!empty($rr['profile_image'])) $imageFileName = $rr['profile_image'];
        }
    }

    // Ensure teacher_id is set (if not provided, attempt lookup from classes)
    if ($teacher_id === 0 && $class_id > 0) {
        $r = $conn->query("SELECT teacher_id FROM classes WHERE id = " . intval($class_id) . " LIMIT 1");
        if ($r && $r->num_rows) {
            $teacher_id = intval($r->fetch_assoc()['teacher_id']);
        }
    }

    // Insert into students
    $sql = "INSERT INTO students
      (full_name, class_id, school, place, district, knowledge, dob, profile_image, extra, phone, teacher_id)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $message = "❌ Prepare failed: " . $conn->error;
    } else {
        // types: s i s s s s s s s s i  -> "sissssssssi"
        $imgParam = $imageFileName ?: null;
        $stmt->bind_param(
            "sissssssssi",
            $full_name,
            $class_id,
            $school,
            $place,
            $district,
            $knowledge,
            $dob,
            $imgParam,
            $extra,
            $phone,
            $teacher_id
        );
        if ($stmt->execute()) {
            $insertedId = $stmt->insert_id;
            $stmt->close();
            // redirect to view page
            header("Location: student-view.php?id=" . $insertedId);
            exit;
        } else {
            $message = "❌ Insert failed: " . $stmt->error;
            $stmt->close();
        }
    }
}

/* Fetch registrations and classes (with teacher names) for the form */
$regs = $conn->query("SELECT id, full_name FROM registrations ORDER BY full_name ASC");
$classes = $conn->query("
    SELECT c.id, c.class_name, c.teacher_id, t.teacher_name
    FROM classes c
    LEFT JOIN class_teachers t ON c.teacher_id = t.id
    ORDER BY c.class_name ASC
");

/* Kerala districts */
$kerala_districts = [
  "Thiruvananthapuram","Kollam","Pathanamthitta","Alappuzha","Kottayam","Idukki",
  "Ernakulam","Thrissur","Palakkad","Malappuram","Kozhikode","Wayanad","Kannur","Kasaragod"
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Registration → students</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
  <style>
    body { background:#f7f9fc; padding:20px; font-family: system-ui, "Segoe UI", Roboto, Arial; }
    .card { max-width:980px; margin:0 auto; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,.06); }
    .crop-preview { width:250px; height:250px; border:2px solid #e2e8f0; border-radius:8px; object-fit:cover; }
  </style>
</head>
<body>
<div class="card p-4">
  <h3 class="mb-3">Edit Registration → Save to <code>students</code> table</h3>

  <?php if ($message): ?>
    <div class="alert <?= strpos($message,'✅')!==false ? 'alert-success':'alert-danger' ?>"><?= $message ?></div>
  <?php endif; ?>

  <!-- Registration selector -->
  <div class="mb-4">
    <label class="form-label">Select registration</label>
    <select id="regSelect" class="form-select">
      <option value="">-- choose student from registrations --</option>
      <?php while ($r = $regs->fetch_assoc()): ?>
        <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
      <?php endwhile; ?>
    </select>
    <div class="form-text">You can also open this page with <code>?id=REG_ID</code> to pre-select.</div>
  </div>

  <form id="studentForm" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="registration_id" id="registration_id" value="">
    <input type="hidden" name="cropped_image" id="cropped_image" value="">
    <input type="hidden" name="teacher_id" id="teacher_id" value="">

    <div class="row">
      <div class="col-md-8">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Full name</label>
            <input type="text" name="full_name" id="full_name" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Class</label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">-- select class --</option>
              <?php
              // Output classes with teacher data attributes
              $classes->data_seek(0);
              while ($c = $classes->fetch_assoc()):
                $tname = $c['teacher_name'] ?? '';
              ?>
                <option value="<?= (int)$c['id'] ?>"
                        data-teacher-id="<?= (int)$c['teacher_id'] ?>"
                        data-teacher-name="<?= htmlspecialchars($tname, ENT_QUOTES) ?>">
                  <?= htmlspecialchars($c['class_name']) ?>
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">School</label>
            <input type="text" name="school" id="school" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Place</label>
            <input type="text" name="place" id="place" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">District</label>
            <select name="district" id="district" class="form-select">
              <option value="">-- select district --</option>
              <?php foreach ($kerala_districts as $d): ?>
                <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Teacher</label>
            <input type="text" id="teacher_name" class="form-control" readonly placeholder="Teacher (auto)" />
          </div>

          <div class="col-md-6">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" id="dob" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Knowledge</label>
            <input type="text" name="knowledge" id="knowledge" class="form-control">
          </div>

          <div class="col-12">
            <label class="form-label">Extra notes</label>
            <textarea name="extra" id="extra" rows="3" class="form-control"></textarea>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="mb-2">
          <label class="form-label">Profile Image (crop to 250×250)</label>
          <input type="file" id="profileImage" accept="image/*" class="form-control">
        </div>

        <div class="text-center mt-3">
          <img id="croppedResult" class="crop-preview" src="https://via.placeholder.com/250" alt="preview">
        </div>

        <div class="mt-3 d-grid gap-2">
          <button type="button" id="openCropBtn" class="btn btn-outline-primary" disabled>Open Cropper</button>
          <button type="submit" name="submit_student" class="btn btn-success" disabled>Save to students</button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Cropper Modal -->
<div class="modal fade" id="cropModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop Image — 250×250</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <img id="cropperImage" style="width:100%; display:block;">
      </div>
      <div class="modal-footer">
        <button type="button" id="cropBtn" class="btn btn-primary">Crop & Use</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
const regSelect = document.getElementById('regSelect');
const studentForm = document.getElementById('studentForm');
const registration_id = document.getElementById('registration_id');
const classSelect = document.getElementById('class_id');
const teacherIdInput = document.getElementById('teacher_id');
const teacherNameInput = document.getElementById('teacher_name');
const openCropBtn = document.getElementById('openCropBtn');
const submitBtn = studentForm.querySelector('[name="submit_student"]');

const profInput = document.getElementById('profileImage');
const cropperImage = document.getElementById('cropperImage');
const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
const cropBtn = document.getElementById('cropBtn');
let cropper = null;

const fields = ['full_name','phone','school','place','knowledge','dob','extra','district'];

// enable/disable form controls (except regSelect)
function setFormEnabled(enabled) {
  Array.from(studentForm.querySelectorAll('input, select, textarea, button')).forEach(el=>{
    if (el === regSelect) return;
    if (el === openCropBtn) { el.disabled = !enabled; return; }
    if (el.name === 'submit_student') { el.disabled = !enabled; return; }
    if (el.type === 'file') { el.disabled = !enabled; return; }
    if (el.name) el.disabled = !enabled;
  });
}
setFormEnabled(false);

// fetch registration row and populate form
async function loadRegistration(id) {
  if (!id) {
    setFormEnabled(false);
    registration_id.value = '';
    fields.forEach(f => { const el = document.getElementById(f); if (el) el.value = ''; });
    classSelect.value = '';
    teacherNameInput.value = '';
    teacherIdInput.value = '';
    document.getElementById('croppedResult').src = 'https://via.placeholder.com/250';
    document.getElementById('cropped_image').value = '';
    return;
  }

  try {
    const res = await fetch(`edit-entries.php?fetch=1&id=${encodeURIComponent(id)}`);
    if (!res.ok) throw new Error('Fetch failed');
    const data = await res.json();

    registration_id.value = data.id || '';
    document.getElementById('full_name').value = data.full_name || '';
    document.getElementById('phone').value = data.phone || '';
    document.getElementById('school').value = data.school || '';
    document.getElementById('place').value = data.place || '';
    document.getElementById('knowledge').value = data.knowledge || '';
    document.getElementById('dob').value = data.dob || '';
    document.getElementById('extra').value = data.extra || '';
    document.getElementById('district').value = data.district || '';

    // set class selection (class_id from registrations)
    if (data.class_id) {
      classSelect.value = data.class_id;
      const selectedOption = classSelect.selectedOptions[0];
      if (selectedOption) {
        teacherNameInput.value = selectedOption.dataset.teacherName || '';
        teacherIdInput.value   = selectedOption.dataset.teacherId || '';
      }
    } else {
      classSelect.value = '';
      teacherNameInput.value = '';
      teacherIdInput.value = '';
    }

    // profile image preview (use registrations image if present)
    if (data.profile_image) {
      let src = data.profile_image;
      if (!/^https?:\/\//i.test(src) && !src.startsWith('/')) src = '../uploads/' + src;
      document.getElementById('croppedResult').src = src;
      document.getElementById('cropped_image').value = '';
    } else {
      document.getElementById('croppedResult').src = 'https://via.placeholder.com/250';
      document.getElementById('cropped_image').value = '';
    }

    setFormEnabled(true);
  } catch (err) {
    alert('Failed to fetch registration data.');
    console.error(err);
  }
}

// on registration select
regSelect.addEventListener('change', function(){
  loadRegistration(this.value);
});

// on class change: set teacher name/id
classSelect.addEventListener('change', function(){
  const opt = this.selectedOptions[0];
  teacherNameInput.value = opt ? (opt.dataset.teacherName || '') : '';
  teacherIdInput.value = opt ? (opt.dataset.teacherId || '') : '';
});

// Cropper flow: when file chosen open modal and create cropper
profInput.addEventListener('change', function(){
  const f = this.files[0];
  if (!f) return;
  const reader = new FileReader();
  reader.onload = function(evt){
    cropperImage.src = evt.target.result;
    cropModal.show();
    if (cropper) { cropper.destroy(); cropper = null; }
    setTimeout(()=> {
      cropper = new Cropper(cropperImage, { aspectRatio: 1, viewMode: 1, autoCropArea: 1 });
    }, 200);
  };
  reader.readAsDataURL(f);
});

// crop button -> set preview + hidden base64
cropBtn.addEventListener('click', function(){
  if (!cropper) return;
  const canvas = cropper.getCroppedCanvas({ width: 250, height: 250 });
  const dataUrl = canvas.toDataURL('image/png');
  document.getElementById('croppedResult').src = dataUrl;
  document.getElementById('cropped_image').value = dataUrl;
  cropModal.hide();
  setTimeout(()=> { if(cropper){ cropper.destroy(); cropper = null; } }, 300);
});

// enable openCrop and submit when form enabled
const observer = new MutationObserver(() => {
  const disabled = submitBtn.disabled;
  openCropBtn.disabled = disabled;
});
observer.observe(submitBtn, { attributes: true, attributeFilter: ['disabled'] });

// Pre-select registration if ?id=... provided
(function(){
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (id) {
    regSelect.value = id;
    // trigger change after a short delay to ensure DOM ready
    setTimeout(()=> regSelect.dispatchEvent(new Event('change')), 150);
  }
})();

// bootstrap validation
(() => {
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
</body>
</html>
