<?php
require_once "../db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="sidebar">
  <h2>Admin</h2>
  <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>">Dashboard</a>
  <a href="list.php" class="<?= basename($_SERVER['PHP_SELF'])=='list.php'?'active':'' ?>">Student List</a>
  <a href="classes.php" class="<?= basename($_SERVER['PHP_SELF'])=='classes.php'?'active':'' ?>">Classes</a>
  <a href="../">Back to Site</a>
</div>
<div class="main">
