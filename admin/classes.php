<?php
include '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Classes</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="sidebar">
    <h2>Kauzariyya Lab</h2>
    <a href="index.php">Dashboard</a>
    <a href="list.php">Students</a>
    <a href="classes.php" class="active">Classes</a>
</div>
<div class="main">
    <div class="header">
        <div class="title">Class List</div>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>Class Name</th>
                <th>Teacher</th>
                <th>Action</th>
            </tr>
            <?php
            $query = mysqli_query($conn,"SELECT * FROM classes ORDER BY id DESC");
            while($row = mysqli_fetch_assoc($query)){
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['class_name']}</td>
                        <td>{$row['teacher']}</td>
                        <td>
                            <a href='edit.php?class_id={$row['id']}' class='btn edit'>Edit</a>
                            <a href='delete.php?class_id={$row['id']}' class='btn delete'>Delete</a>
                        </td>
                      </tr>";
            }
            ?>
        </table>
    </div>
</div>
</body>
</html>
