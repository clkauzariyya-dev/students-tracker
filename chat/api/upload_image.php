<?php
// api/upload_image.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success'=>false, 'error'=>'No file']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false, 'error'=>'Upload error']);
    exit;
}

$maxBytes = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxBytes) {
    echo json_encode(['success'=>false, 'error'=>'File exceeds 5 MB']);
    exit;
}

$allowed = ['image/jpeg','image/png','image/gif','image/webp','image/avif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['success'=>false, 'error'=>'Invalid image type']);
    exit;
}
finfo_close($finfo);

// Ensure uploads folder exists and writable
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$target = $uploadDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success'=>false, 'error'=>'Save failed']);
    exit;
}

// Return path relative to client pages
$relativePath = '../uploads/' . $filename;
echo json_encode(['success'=>true, 'path'=>$relativePath]);
exit;
