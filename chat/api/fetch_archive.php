<?php
// api/fetch_archive.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
if ($year <= 0 || $month <= 0) {
    echo json_encode(['success'=>false, 'error'=>'Invalid month']);
    exit;
}

$sql = "SELECT c.id, c.user_id, c.message, c.image_path, c.link, c.created_at, u.name
        FROM chats c JOIN users u ON c.user_id = u.id
        WHERE MONTH(c.created_at) = ? AND YEAR(c.created_at) = ?
        ORDER BY c.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $month, $year);
$stmt->execute();
$res = $stmt->get_result();

$messages = [];
while ($row = $res->fetch_assoc()) {
    if ($row['image_path'] && strpos($row['image_path'], 'http') !== 0) {
        $row['image_path'] = '../uploads/' . basename($row['image_path']);
    }
    $messages[] = $row;
}
echo json_encode(['success'=>true, 'messages'=>$messages]);
exit;
