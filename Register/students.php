<?php
// admin/students.php

require_once "../db.php";

// Fetch all students
$sql = "SELECT s.id, s.full_name, s.class_id, c.class_name, s.school, s.phone 
        FROM students s
        LEFT JOIN classes c ON s.class_id = c.id
        ORDER BY s.full_name ASC";
$result = $conn->query($sql);

$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Students List</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; margin-bottom: 20px; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #f9f9f9; }
        .btn-print { padding: 6px 12px; background: #007bff; color: #fff; border: none; border-radius: 4px; text-decoration: none; font-size: 13px; }
        .btn-print:hover { background: #0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h1>Students List</h1>

    <?php if (!empty($students)): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Class</th>
                    <th>School</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $index => $student): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td><?= htmlspecialchars($student['class_name'] ?? $student['class_id']) ?></td>
                        <td><?= htmlspecialchars($student['school']) ?></td>
                        <td><?= htmlspecialchars($student['phone']) ?></td>
                        <td>
                            <a href="print.php?id=<?= $student['id'] ?>" target="_blank" class="btn-print">Print</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No students found.</p>
    <?php endif; ?>
</div>
</body>
</html>
