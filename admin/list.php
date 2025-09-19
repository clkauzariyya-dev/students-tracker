<?php
include '../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Students List</title>
<link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<div class="sidebar">
    <h2>Kauzariyya Lab</h2>
    <a href="index.php">Dashboard</a>
    <a href="list.php" class="active">Students</a>
</div>

<div class="main">
    <div class="header">
        <div class="title">Students List</div>
    </div>

    <div class="table-wrap">
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Class</th>
                <th>School</th>
                <th>Place</th>
                <th>Knowledge</th>
                <th>DOB</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <?php
            $query = mysqli_query($conn, "SELECT * FROM registrations ORDER BY id DESC");
            while($row = mysqli_fetch_assoc($query)){
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['full_name']}</td>
                        <td>{$row['class']}</td>
                        <td>{$row['school']}</td>
                        <td>{$row['place']}</td>
                        <td>{$row['knowledge']}</td>
                        <td>{$row['dob']}</td>
                        <td>{$row['phone']}</td>
                        <td>
                            <a href='edit.php?id={$row['id']}' class='btn edit'>Edit</a>
                            <a href='delete.php?id={$row['id']}' class='btn delete'>Delete</a>
                        </td>
                      </tr>";
            }
            ?>
        </table>
    </div>
</div>
</body>
</html>
