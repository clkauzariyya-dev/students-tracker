<?php
// api/send_message.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success'=>false, 'error'=>'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$message = trim($_POST['message'] ?? '');
$image_path = trim($_POST['image_path'] ?? '') ?: null;
$link = null;

// If message looks like a URL, store in link (optional)
if ($message !== '' && preg_match('~^https?://~i', $message)) {
    $link = $message;
    // keep message as-is to display as link as well
}

if ($message === '' && !$image_path) {
    echo json_encode(['success'=>false, 'error'=>'Empty message']);
    exit;
}

$sql = "INSERT INTO chats (user_id, message, image_path, link) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('isss', $user_id, $message, $image_path, $link);
if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success'=>false, 'error'=>$conn->error]);
}
exit;
