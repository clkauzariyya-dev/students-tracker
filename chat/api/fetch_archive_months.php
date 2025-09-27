<?php
// api/fetch_archive_months.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

// List months (year+month) that have messages not in current month
$sql = "SELECT YEAR(created_at) AS y, MONTH(created_at) AS m, COUNT(*) AS cnt
        FROM chats
        WHERE NOT (MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()))
        GROUP BY y, m
        ORDER BY y DESC, m DESC";
$res = $conn->query($sql);
$months = [];
while ($r = $res->fetch_assoc()) {
    $label = date('F Y', mktime(0,0,0,$r['m'],1,$r['y']));
    $months[] = ['year' => intval($r['y']), 'month' => intval($r['m']), 'label' => $label, 'count' => intval($r['cnt'])];
}
echo json_encode(['success'=>true, 'months'=>$months]);
exit;
