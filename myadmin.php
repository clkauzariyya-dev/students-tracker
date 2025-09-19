<?php
// myadmin.php - Mini phpMyAdmin-like single-file admin tool
// REQUIREMENTS:
// - A working db.php that sets up $conn (mysqli instance).
// - Save as myadmin.php and access protected via password below.

// ---------------- CONFIG ----------------
require_once "db.php"; // adjust path if needed

// Admin login (change this)
const ADMIN_USER = 'admin';
const ADMIN_PASSWORD = '1234'; // <<--- CHANGE THIS

// Pagination size
$PAGE_LIMIT = 30;

// ---------------- SESSION / AUTH ----------------
session_start();
if (isset($_POST['login_user'])) {
    $u = $_POST['login_user'];
    $p = $_POST['login_pass'] ?? '';
    if ($u === ADMIN_USER && $p === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: myadmin.php");
        exit;
    } else {
        $login_error = "Invalid credentials.";
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: myadmin.php");
    exit;
}
$loggedIn = !empty($_SESSION['admin_logged_in']);
if (!$loggedIn) {
    // Login form
    ?>
    <!doctype html>
    <html>
    <head><meta charset="utf-8"><title>Admin Login</title>
    <style>
    body{font-family:Arial;display:flex;align-items:center;justify-content:center;height:100vh;background:#f4f6f8}
    .box{background:#fff;padding:20px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.08);width:320px}
    input{width:100%;padding:8px;margin:8px 0;border-radius:4px;border:1px solid #ccc}
    button{padding:8px 12px;border-radius:4px;border:none;background:#2d8cff;color:#fff;cursor:pointer}
    .err{color:#b00020}
    </style>
    </head>
    <body>
      <div class="box">
        <h3>Admin Login</h3>
        <?php if(!empty($login_error)): ?><div class="err"><?=htmlspecialchars($login_error)?></div><?php endif;?>
        <form method="post">
          <input name="login_user" placeholder="Username" required />
          <input name="login_pass" placeholder="Password" type="password" required />
          <button type="submit">Login</button>
        </form>
        <p style="font-size:12px;color:#666;margin-top:8px">Change ADMIN_PASSWORD in the script after first login.</p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ---------------- HELPERS ----------------
function h($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function fetchAllTables($conn){
    $res = $conn->query("SHOW TABLES");
    $tables = [];
    while ($r = $res->fetch_array()) $tables[] = $r[0];
    return $tables;
}
function fetchForeignKeys($conn, $db){
    // returns array keyed by table.column => ['ref_table'=>, 'ref_column'=>]
    $sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $db);
    $stmt->execute();
    $res = $stmt->get_result();
    $map = [];
    while ($r = $res->fetch_assoc()) {
        $map[$r['TABLE_NAME'].'.'.$r['COLUMN_NAME']] = [
            'ref_table'=>$r['REFERENCED_TABLE_NAME'],
            'ref_column'=>$r['REFERENCED_COLUMN_NAME']
        ];
    }
    return $map;
}
function chooseDisplayColumn($conn, $table, $refCol){
    // tries to find a friendly display column (name/title/full_name or first varchar)
    $cols = [];
    $res = $conn->query("DESCRIBE `$table`");
    while ($r = $res->fetch_assoc()) $cols[] = $r;
    // prefer common names
    $candidates = ['name','title','full_name','teacher_name','class_name','label'];
    foreach ($candidates as $cand) {
        foreach ($cols as $col) if (strtolower($col['Field']) == $cand) return $col['Field'];
    }
    // first varchar/text that isn't the PK
    foreach ($cols as $col) {
        $type = strtolower($col['Type']);
        if (($type && (strpos($type,'varchar')!==false || strpos($type,'text')!==false)) && $col['Field'] != $refCol) {
            return $col['Field'];
        }
    }
    // fallback to referenced column
    return $refCol;
}

// determine DB name from connection if possible
$databaseName = $conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db'];

// ---------------- ACTIONS (Ajax/POST) ----------------
// AJAX update (inline edit)
if (isset($_POST['action']) && $_POST['action'] === 'ajax_update') {
    header('Content-Type: application/json; charset=utf-8');
    $table = $_POST['table'] ?? '';
    $id_col = $_POST['id_col'] ?? 'id';
    $id_val = $_POST['id_val'] ?? '';
    $col = $_POST['col'] ?? '';
    $val = $_POST['val'] ?? null;
    if (!$table || !$col) { echo json_encode(['ok'=>false,'err'=>'Missing table/column']); exit; }
    // sanitize names (allow alphanum and _ only)
    if (!preg_match('/^[A-Za-z0-9_]+$/',$table) || !preg_match('/^[A-Za-z0-9_]+$/',$col)) {
        echo json_encode(['ok'=>false,'err'=>'Invalid names']); exit;
    }
    $id_val_int = intval($id_val);
    // Build and run update
    $safeVal = $conn->real_escape_string($val);
    $sql = "UPDATE `$table` SET `$col` = '{$safeVal}' WHERE `$id_col` = {$id_val_int} LIMIT 1";
    if ($conn->query($sql)) echo json_encode(['ok'=>true]);
    else echo json_encode(['ok'=>false,'err'=>$conn->error]);
    exit;
}

// Delete row
if (isset($_GET['delete_row']) && isset($_GET['table']) && isset($_GET['id_col']) && isset($_GET['id'])) {
    $table = $_GET['table'];
    $id_col = $_GET['id_col'];
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM `{$table}` WHERE `{$id_col}` = {$id} LIMIT 1");
    header("Location: myadmin.php?table=".urlencode($table));
    exit;
}

// Insert row (generic)
if (isset($_POST['action']) && $_POST['action'] === 'insert_row') {
    $table = $_POST['table'];
    $data = $_POST['data'] ?? [];
    // sanitize column names
    $cols = [];
    $vals = [];
    foreach($data as $c=>$v){
        if (!preg_match('/^[A-Za-z0-9_]+$/',$c)) continue;
        $cols[] = "`$c`";
        $vals[] = "'".$conn->real_escape_string($v)."'";
    }
    if (!empty($cols)) {
        $sql = "INSERT INTO `$table` (".implode(',', $cols).") VALUES (".implode(',', $vals).")";
        if ($conn->query($sql)) {
            $last = $conn->insert_id;
            header("Location: myadmin.php?table=".urlencode($table)."&inserted={$last}");
            exit;
        } else {
            $insert_error = $conn->error;
        }
    } else {
        $insert_error = "No valid columns submitted.";
    }
}

// Run SQL console
if (isset($_POST['action']) && $_POST['action'] === 'run_sql') {
    $raw = trim($_POST['sql'] ?? '');
    $stmtResult = null;
    if ($raw !== '') {
        if ($res = $conn->query($raw)) {
            // Fetch rows if select
            if ($res instanceof mysqli_result) {
                $rows = $res->fetch_all(MYSQLI_ASSOC);
                $sql_ok = "OK: returned " . count($rows) . " rows";
            } else {
                $sql_ok = "OK: affected rows: " . $conn->affected_rows;
            }
        } else {
            $sql_err = $conn->error;
        }
    } else {
        $sql_err = "SQL empty.";
    }
}

// ---------------- UI DATA PREP ----------------
$tables = fetchAllTables($conn);
$fkMap = fetchForeignKeys($conn, $databaseName);

// If a specific table is selected, get structure and data
$selected = $_GET['table'] ?? null;
$selected = $selected ? preg_replace('/[^A-Za-z0-9_]/','',$selected) : null;

$structure = [];
$dataRows = [];
$totalRows = 0;
$offset = intval($_GET['offset'] ?? 0);
if ($selected) {
    $desc = $conn->query("DESCRIBE `{$selected}`");
    while ($r = $desc->fetch_assoc()) $structure[] = $r;
    // get total count
    $countRes = $conn->query("SELECT COUNT(*) AS c FROM `{$selected}`");
    $totalRows = $countRes->fetch_assoc()['c'] ?? 0;
    // fetch data with limit
    $limit = intval($PAGE_LIMIT);
    $res = $conn->query("SELECT * FROM `{$selected}` LIMIT {$offset}, {$limit}");
    if ($res) {
        while ($r = $res->fetch_assoc()) $dataRows[] = $r;
    }
    // Prepare FK options for any fk columns in this table
    $fkOptions = []; // ['column'=>[['key'=>val,'label'=>...],...]]
    foreach ($structure as $colDef) {
        $k = $selected.'.'.$colDef['Field'];
        if (isset($fkMap[$k])) {
            $ref = $fkMap[$k];
            $displayCol = chooseDisplayColumn($conn, $ref['ref_table'], $ref['ref_column']);
            $optRes = $conn->query("SELECT `{$ref['ref_column']}` AS id, `{$displayCol}` AS label FROM `{$ref['ref_table']}` LIMIT 500");
            $opts = [];
            while ($o = $optRes->fetch_assoc()) $opts[] = $o;
            $fkOptions[$colDef['Field']] = [
                'ref_table'=>$ref['ref_table'],
                'ref_column'=>$ref['ref_column'],
                'display'=>$displayCol,
                'options'=>$opts
            ];
        }
    }
}

// ---------------- RENDER ----------------
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>MyAdmin - <?= h($databaseName) ?></title>
<style>
:root{--bg:#f7f9fb;--sidebar:#1f2937;--accent:#2563eb}
body{margin:0;font-family:Inter,Roboto,Arial;background:var(--bg);color:#111}
.header{height:56px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid #e6e9ee}
.container{display:flex;height:calc(100vh - 56px)}
.sidebar{width:260px;background:var(--sidebar);color:#fff;padding:12px;overflow:auto}
.sidebar h2{margin:4px 0 12px;font-size:16px}
.table-link{display:block;padding:8px;border-radius:6px;color:#e6eefc;text-decoration:none;margin-bottom:6px}
.table-link.active{background:#152635}
.main{flex:1;padding:16px;overflow:auto}
.card{background:#fff;padding:12px;border-radius:8px;box-shadow:0 4px 14px rgba(16,24,40,0.06); margin-bottom:12px}
table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{border:1px solid #e6e9ee;padding:6px 8px;font-size:13px}
th{background:#f3f6f9;text-align:left}
input[type="text"],select,textarea{width:100%;box-sizing:border-box;padding:6px;border:1px solid #d1d7e0;border-radius:4px}
.btn{display:inline-block;padding:6px 10px;border-radius:6px;border:none;background:var(--accent);color:#fff;text-decoration:none;cursor:pointer}
.btn.secondary{background:#6b7280}
.small{font-size:12px;padding:4px 8px}
.footer-note{font-size:12px;color:#6b7280;margin-top:8px}
.inline-actions button{margin-right:6px}
.notice{padding:8px;background:#fff3c4;border-radius:6px;margin-bottom:12px}
.top-row{display:flex;gap:8px;align-items:center}
.kv{font-size:13px;color:#374151}
.pagination{margin-top:10px}
.pagination a{margin-right:6px;text-decoration:none;color:var(--accent);font-weight:600}
.success{background:#ecfdf5;padding:8px;border-radius:6px;color:#065f46}
.error{background:#ffe4e6;padding:8px;border-radius:6px;color:#8b1a2a}
</style>
</head>
<body>
<div class="header">
  <div style="display:flex;align-items:center;gap:12px">
    <strong style="font-size:16px">MyAdmin</strong>
    <span class="kv">Database: <?= h($databaseName) ?></span>
    <?php if ($selected): ?><span class="kv"> / Table: <?= h($selected) ?></span><?php endif; ?>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <a class="btn" href="myadmin.php">Home</a>
    <a class="btn secondary" href="myadmin.php?sql=1">SQL Console</a>
    <a class="btn" href="myadmin.php?logout=1">Logout</a>
  </div>
</div>

<div class="container">
  <div class="sidebar">
    <h2>Tables (<?= count($tables) ?>)</h2>
    <?php foreach($tables as $t): ?>
      <a class="table-link <?= $t===$selected ? 'active' : '' ?>" href="?table=<?= urlencode($t) ?>"><?= h($t) ?></a>
    <?php endforeach; ?>
    <hr style="border:none;height:1px;background:#2b3742;margin:12px 0">
    <div style="font-size:13px;color:#bcd3ff">Foreign Keys detected:</div>
    <?php if(empty($fkMap)): ?><div style="font-size:12px;color:#e6eefc;margin-top:8px">No foreign keys found in DB.</div><?php else: ?>
      <ul style="font-size:13px;margin-top:8px;padding-left:14px">
      <?php foreach($fkMap as $k=>$v): $parts = explode('.',$k); ?>
        <li><?= h($parts[0].'.'.$parts[1]) ?> → <?= h($v['ref_table'].'.'.$v['ref_column']) ?></li>
      <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="footer-note">Use SQL Console carefully. This tool executes queries with your DB user privileges.</div>
  </div>

  <div class="main">
    <?php if(!empty($sql_err)): ?><div class="card error"><?=h($sql_err)?></div><?php endif; ?>
    <?php if(!empty($sql_ok)): ?><div class="card success"><?=h($sql_ok)?></div><?php endif; ?>

    <?php if(isset($_GET['sql'])): ?>
      <div class="card">
        <h3>SQL Console</h3>
        <form method="post">
          <input type="hidden" name="action" value="run_sql">
          <textarea name="sql" rows="6" placeholder="Write SQL here..."><?= isset($_POST['sql']) ? h($_POST['sql']) : '' ?></textarea>
          <div style="margin-top:8px">
            <button class="btn" type="submit">Run</button>
            <a class="btn secondary" href="myadmin.php">Back</a>
          </div>
        </form>
        <?php if(!empty($rows) && is_array($rows)): ?>
          <h4 style="margin-top:12px">Result (first 200 rows)</h4>
          <div style="overflow:auto">
            <table>
              <tr><?php foreach(array_keys($rows[0]) as $col): ?><th><?= h($col) ?></th><?php endforeach; ?></tr>
              <?php foreach(array_slice($rows,0,200) as $r): ?>
                <tr><?php foreach($r as $c): ?><td><?= h($c) ?></td><?php endforeach; ?></tr>
              <?php endforeach; ?>
            </table>
          </div>
        <?php endif; ?>
      </div>

    <?php elseif($selected): ?>
      <div class="card">
        <div class="top-row">
          <h3>Structure: <?= h($selected) ?></h3>
          <div style="margin-left:auto" class="kv">Rows: <?= (int)$totalRows ?></div>
        </div>
        <div style="overflow:auto;margin-top:8px">
          <table>
            <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>
            <?php foreach($structure as $col): ?>
              <tr>
                <td><?= h($col['Field']) ?></td>
                <td><?= h($col['Type']) ?></td>
                <td><?= h($col['Null']) ?></td>
                <td><?= h($col['Key']) ?></td>
                <td><?= h($col['Default']) ?></td>
                <td><?= h($col['Extra']) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

      <div class="card">
        <div style="display:flex;align-items:center;gap:8px">
          <h3>Browse Rows</h3>
          <div style="margin-left:auto">
            <a class="btn small" href="?table=<?= urlencode($selected) ?>&offset=0">Refresh</a>
            <a class="btn small" href="#insert">Insert Row</a>
            <a class="btn small secondary" href="myadmin.php?table=<?= urlencode($selected) ?>&export=1">Export (CSV)</a>
          </div>
        </div>

        <?php if(isset($insert_error)): ?><div class="error"><?= h($insert_error) ?></div><?php endif; ?>
        <?php if(isset($_GET['inserted'])): ?><div class="success">Inserted ID <?= (int)$_GET['inserted'] ?></div><?php endif; ?>

        <div style="overflow:auto">
          <table id="data-table">
            <tr>
              <?php foreach(array_keys($dataRows[0] ?? ['empty'=>'']) as $col): ?><th><?= h($col) ?></th><?php endforeach; ?>
              <th>Actions</th>
            </tr>

            <?php foreach($dataRows as $row): ?>
              <tr data-id="<?= h($row[array_keys($row)[0]]) ?>">
                <?php foreach($row as $colName=>$colVal):
                      $isFK = isset($fkOptions[$colName]);
                ?>
                  <td>
                    <?php if($isFK): 
                        $opts = $fkOptions[$colName]['options'];
                        ?>
                        <select onchange="ajaxUpdate('<?= h($selected) ?>','<?= h(array_keys($row)[0]) ?>','<?= h($row[array_keys($row)[0]]) ?>','<?= h($colName) ?>', this.value)">
                          <option value="">-- none --</option>
                          <?php foreach($opts as $o): ?>
                            <option value="<?= h($o['id']) ?>" <?= ($o['id']==$colVal)?'selected':'' ?>><?= h($o['label']) ?></option>
                          <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" value="<?= h($colVal) ?>" onchange="ajaxUpdate('<?= h($selected) ?>','<?= h(array_keys($row)[0]) ?>','<?= h($row[array_keys($row)[0]]) ?>','<?= h($colName) ?>', this.value)">
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="inline-actions">
                  <a class="btn small secondary" href="myadmin.php?table=<?= urlencode($selected) ?>&edit=<?= urlencode($row[array_keys($row)[0]]) ?>">Edit</a>
                  <a class="btn small" onclick="return confirm('Delete this row?')" href="?table=<?= urlencode($selected) ?>&delete_row=1&id_col=<?= urlencode(array_keys($row)[0]) ?>&id=<?= urlencode($row[array_keys($row)[0]]) ?>">Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>

            <!-- Insert blank row -->
            <tr id="insert">
              <form method="post">
                <td colspan="<?= max(1,count($dataRows?array_keys($dataRows[0]):[])) + 1 ?>">
                  <input type="hidden" name="action" value="insert_row">
                  <input type="hidden" name="table" value="<?= h($selected) ?>">
                  <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <?php foreach($structure as $col): 
                        // skip auto_increment primary if occurs
                        if (strpos($col['Extra'],'auto_increment') !== false) continue;
                        $colName = $col['Field'];
                        $isFK = isset($fkOptions[$colName]);
                    ?>
                      <div style="min-width:220px;flex:1">
                        <label style="font-size:12px;color:#374151"><?= h($colName) ?></label>
                        <?php if($isFK): $opts = $fkOptions[$colName]['options']; ?>
                          <select name="data[<?= h($colName) ?>]">
                            <option value="">-- none --</option>
                            <?php foreach($opts as $o): ?>
                              <option value="<?= h($o['id']) ?>"><?= h($o['label']) ?></option>
                            <?php endforeach; ?>
                          </select>
                        <?php else: ?>
                          <input type="text" name="data[<?= h($colName) ?>]" />
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div style="margin-top:8px">
                    <button class="btn" type="submit">Insert</button>
                    <a class="btn secondary" href="?table=<?= urlencode($selected) ?>">Cancel</a>
                  </div>
                </td>
              </form>
            </tr>

          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
          <?php
            $cur = intval($offset/$PAGE_LIMIT)+1;
            $pages = intval(ceil($totalRows / $PAGE_LIMIT));
            for($p=1;$p<=$pages;$p++){
                $off = ($p-1)*$PAGE_LIMIT;
                echo '<a href="?table='.urlencode($selected).'&offset='.$off.'">'.($p==$cur?'<strong>'.$p.'</strong>':$p).'</a> ';
            }
          ?>
        </div>

      </div>

      <?php
      // EXPORT CSV if requested
      if (isset($_GET['export'])) {
          header('Content-Type: text/csv');
          header('Content-Disposition: attachment; filename="'. $selected .'.csv"');
          $out = fopen('php://output','w');
          // header
          if (count($dataRows)) fputcsv($out, array_keys($dataRows[0]));
          // fetch all rows (careful on big tables)
          $er = $conn->query("SELECT * FROM `{$selected}`");
          while ($r = $er->fetch_assoc()) fputcsv($out, $r);
          exit;
      }
      ?>

    <?php else: ?>
      <div class="card">
        <h3>Overview</h3>
        <p>Choose a table from the left to browse and edit its records. The tool supports inline editing (auto-saved), insert, delete, and foreign-key dropdowns where relationships exist.</p>
        <div style="margin-top:12px">
          <strong>Note:</strong> This is a lightweight admin. Always backup DB before running large operations.
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
async function ajaxUpdate(table, id_col, id_val, col, val){
  try {
    const form = new FormData();
    form.append('action', 'ajax_update');
    form.append('table', table);
    form.append('id_col', id_col);
    form.append('id_val', id_val);
    form.append('col', col);
    form.append('val', val);
    const res = await fetch('myadmin.php', { method:'POST', body: form });
    const json = await res.json();
    if (!json.ok) alert('Update error: ' + (json.err || 'unknown'));
  } catch (e){ alert('Request failed: '+e.message); }
}
</script>

</body>
</html>
