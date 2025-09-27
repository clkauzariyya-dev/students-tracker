<?php
// includes/functions.php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user($conn) {
    if (!is_logged_in()) return null;
    $id = intval($_SESSION['user_id']);
    $sql = "SELECT id, mobile, name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc();
}

// Simple sanitize for output
function h($s) {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
