<?php
// api/fetch_messages.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Only fetch messages of current month
$sql = "SELECT c.id, c.user_id, c.message, c.image_path, c.link, c.created_at, u.name
        FROM chats c
        JOIN users u ON c.user_id = u.id
        WHERE c.id > ? AND MONTH(c.created_at) = MONTH(CURRENT_DATE()) AND YEAR(c.created_at) = YEAR(CURRENT_DATE())
        ORDER BY c.id ASC
        LIMIT 200";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $last_id);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    // If image_path is stored as relative, ensure full path
    if ($row['image_path'] && strpos($row['image_path'], 'http') !== 0) {
        $row['image_path'] = dirname((isset($_SERVER['SCRIPT_NAME'])?$_SERVER['SCRIPT_NAME']:'/')).'/uploads/'.basename($row['image_path']);
        // the front-end will use relative path; adjust if necessary
        $row['image_path'] = '../uploads/' . basename($row['image_path']);
    }
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
exit;
