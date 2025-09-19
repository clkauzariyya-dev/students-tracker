<?php
require_once "../db.php";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// CSV Export
if (isset($_POST['export_csv'])) {
    $res = $conn->query("SELECT id, full_name, class, school, place, knowledge, dob FROM registrations ORDER BY id ASC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="registrations.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ["ID","Full Name","Class","School","Place","Knowledge","Date of Birth"]);
    while ($row = $res->fetch_assoc()) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// CSV Import
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, "r")) !== FALSE) {
        fgetcsv($handle); // skip header
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $full_name = $conn->real_escape_string($data[0]);
            $class = $conn->real_escape_string($data[1]);
            $school = $conn->real_escape_string($data[2]);
            $place = $conn->real_escape_string($data[3]);
            $knowledge = $conn->real_escape_string($data[4]);
            $dob = $conn->real_escape_string($data[5]);

            $res = $conn->query("SELECT MAX(id) AS max_id FROM registrations");
            $row = $res->fetch_assoc();
            $next_id = $row['max_id'] + 1;

            $sql = "INSERT INTO registrations (id, full_name, class, school, place, knowledge, dob)
                    VALUES ($next_id, '$full_name', '$class', '$school', '$place', '$knowledge', '$dob')";
            $conn->query($sql);
        }
        fclose($handle);
    }
}

$result = $conn->query("SELECT * FROM registrations ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registered Students</title>
<style>
body { font-family: "Segoe UI", sans-serif; background: #f8f9fa; padding: 30px; }
h2 { text-align: center; margin-bottom: 20px; }
table { border-collapse: collapse; width: 100%; background: #fff; }
th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
th { background: #007bff; color: #fff; }
tr:nth-child(even) { background: #f2f2f2; }
.container { max-width: 1000px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
.actions { margin: 15px 0; display: flex; justify-content: space-between; }
button { padding: 10px 15px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
.export { background: #28a745; color: white; }
.export:hover { background: #218838; }
.import { background: #ffc107; color: black; }
.import:hover { background: #e0a800; }
</style>
</head>
<body>
<div class="container">
  <h2>Registered Students</h2>

  <div class="actions">
    <form method="post">
      <button type="submit" name="export_csv" class="export">📥 Export CSV</button>
    </form>

    <form method="post" enctype="multipart/form-data">
      <input type="file" name="csv_file" accept=".csv" required>
      <button type="submit" name="import_csv" class="import">📤 Import CSV</button>
    </form>
  </div>

  <table>
    <tr>
      <th>ID</th><th>Full Name</th><th>Class</th><th>School</th><th>Place</th><th>Knowledge</th><th>DOB</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) : ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= $row['full_name'] ?></td>
        <td><?= $row['class'] ?></td>
        <td><?= $row['school'] ?></td>
        <td><?= $row['place'] ?></td>
        <td><?= $row['knowledge'] ?></td>
        <td><?= $row['dob'] ?></td>
      </tr>
    <?php endwhile; ?>
  </table>
</div>
</body>
</html>
