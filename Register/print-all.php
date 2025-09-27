<?php
// admin/print-all.php
// Print application forms for all students (front + back) with Guidelines & Examples

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

// -----------------------------
// Configuration
// -----------------------------
class Config {
    public static $uploadsDir = __DIR__ . '/../uploads/';   // server path to uploads
    public static $uploadsWeb = '../uploads/';             // web path to uploads (relative from admin)
    public static $defaultImage = '../assets/default.png'; // fallback image
}

// -----------------------------
// Helpers
// -----------------------------
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// -----------------------------
// DB wrapper
// -----------------------------
class DB {
    private mysqli $conn;
    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        $this->conn->set_charset('utf8mb4');
    }
    public function fetchAllStudents(): array {
        $sql = "
            SELECT s.*, c.class_name,
                   ct_class.teacher_name AS class_teacher_name,
                   ct_student.teacher_name AS student_teacher_name
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN class_teachers ct_class ON c.teacher_id = ct_class.id
            LEFT JOIN class_teachers ct_student ON s.teacher_id = ct_student.id
            ORDER BY s.class_id, s.full_name
        ";
        $res = $this->conn->query($sql);
        if (!$res) throw new RuntimeException('DB query error: ' . $this->conn->error);
        return $res->fetch_all(MYSQLI_ASSOC);
    }
}

// -----------------------------
// Model
// -----------------------------
class Student {
    private array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function get(string $k, $d = '') { return (string)($this->data[$k] ?? $d); }
    public function fullName(): string { return $this->get('full_name'); }
    public function className(): string { return $this->get('class_name') ?: $this->get('class_id'); }
    public function dob(): string { return $this->get('dob'); }
    public function age(): string {
        $dob = $this->dob();
        if (!$dob) return '-';
        try { $d1 = new DateTime($dob); return (string)$d1->diff(new DateTime())->y; }
        catch (Exception $e) { return '-'; }
    }
    public function profileImagePath(): string {
        $pi = trim($this->get('profile_image'));
        if ($pi === '') return Config::$defaultImage;
        if (preg_match('#^https?://#i', $pi)) return $pi;
        if (str_starts_with($pi, '/')) return $pi;
        if (file_exists(Config::$uploadsDir . $pi)) return Config::$uploadsWeb . ltrim($pi, '/');
        // try alternate folder
        if (file_exists(__DIR__ . '/../Uploads/' . $pi)) return '../Uploads/' . ltrim($pi, '/');
        return Config::$defaultImage;
    }
}

// -----------------------------
// View
// -----------------------------
class View {
    public static function header(string $title): void {
        ?>
        <!doctype html>
        <html lang="en">
        <head>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width,initial-scale=1">
          <title><?= e($title) ?></title>
          <style>
            @page { size: A4; margin: 15mm; }
            body { font-family: "Segoe UI", Roboto, Arial, sans-serif; margin:0; background:#f5f7fb; color:#111; }
            .topbar { max-width:1100px; margin:10px auto; padding:0 12px; display:flex; justify-content:flex-end; gap:8px; }
            .btn { padding:8px 12px; border-radius:6px; text-decoration:none; color:#fff; cursor:pointer; background:#0d6efd; border:none; }
            .btn.secondary { background:#6c757d; }

            .container { max-width:900px; margin:10px auto; padding:0 12px; }

            .form-sheet { background:#fff; padding:18px; border-radius:8px; box-shadow:0 8px 28px rgba(10,10,20,0.06); margin-bottom:18px; }
            .header { text-align:center; margin-bottom:8px; }
            .header h1 { margin:0; font-size:16px; color:#0b5ed7; text-transform:uppercase; }
            .header p { margin:6px 0 0; color:#58616b; font-size:13px; }

            .photo { float:right; width:40mm; height:50mm; border:1px solid #e2e8f0; margin-left:10px; border-radius:4px; overflow:hidden; background:#fff; }
            .photo img { width:100%; height:100%; object-fit:cover; display:block; }

            table.details { width:100%; border-collapse:collapse; margin-top:6px; font-size:13px; }
            table.details th, table.details td { border:1px solid #e8eef7; padding:8px; text-align:left; vertical-align:top; }
            table.details th { width:32%; background:#f8fbff; color:#244; font-weight:700; }

            .declaration { margin-top:12px; font-size:13px; color:#2b2b2b; }

            .sign-row { display:flex; gap:12px; margin-top:22px; justify-content:space-between; }
            .sign { flex:1; text-align:center; background:#fff; border:1px solid #e6edf5; padding:10px; border-radius:6px; min-width:120px; }
            .sign .line { border-top:1px solid #333; margin-top:32px; display:block; }
            .sign .who { margin-top:6px; font-weight:700; color:#111; }

            /* BACK SIDE */
            .back { background:#fff; padding:18px; border-radius:8px; box-shadow:0 8px 28px rgba(10,10,20,0.06); margin-bottom:18px; }

            .rules { font-size:13px; color:#222; margin-top:6px; }
            .rules ul { margin:6px 0 0 18px; }
            .examples { margin-top:12px; background:#f8fafc; border:1px solid #e7eef8; padding:10px; border-radius:6px; }
            .examples table { width:100%; border-collapse:collapse; font-size:13px; }
            .examples th, .examples td { text-align:left; padding:6px; border-bottom:1px dashed #e2e8ef; }
            .examples th { width:30%; color:#0b5ed7; font-weight:700; }

            .guidance { font-size:12px; color:#4b5563; margin-top:10px; }

            .page-break { page-break-after: always; }

            @media print {
                .topbar { display:none; }
                .form-sheet, .back { box-shadow:none; border-radius:0; margin:0; padding:10mm; }
            }
            @media (max-width:720px) {
                .photo { width:30mm; height:40mm; }
            }
          </style>
        </head>
        <body>
        <div class="topbar">
            <a class="btn secondary" href="admin.php">← Back to Admin</a>
            <button class="btn" onclick="window.print()">🖨 Print All</button>
        </div>
        <div class="container">
        <?php
    }

    public static function footer(): void {
        echo "</div></body></html>";
    }

    public static function renderStudent(Student $s, string $printedAt, bool $first = false): void {
        $age = $s->age();
        $photo = $s->profileImagePath();
        $classTeacherName = $s->get('class_teacher_name');
        ?>
        <!-- FRONT -->
        <div class="form-sheet <?= $first ? '' : 'page-break' ?>">
            <div class="header">
                <h1>Student Application Form</h1>
                <p>Kauzariyya Madrasa — Student Registration</p>
                <p><small>Printed: <?= e($printedAt) ?></small></p>
            </div>

            <div class="photo" aria-hidden="true">
                <img src="<?= e($photo) ?>" alt="Photo of <?= e($s->fullName()) ?>">
            </div>

            <table class="details" role="table" aria-label="Student details">
                <tr><th>Full name</th><td><?= e($s->fullName()) ?></td></tr>
                <tr><th>Class</th><td><?= e($s->className()) ?></td></tr>
                <tr><th>School</th><td><?= e($s->get('school')) ?></td></tr>
                <tr><th>Place</th><td><?= e($s->get('place')) ?></td></tr>
                <tr><th>District</th><td><?= e($s->get('district')) ?></td></tr>
                <tr><th>Date of birth</th><td><?= e($s->dob()) ?> (Age: <?= e($age) ?>)</td></tr>
                <tr><th>Knowledge</th><td><?= e($s->get('knowledge')) ?></td></tr>
                <tr><th>Phone</th><td><?= e($s->get('phone')) ?></td></tr>
                <tr><th>Extra notes</th><td><?= nl2br(e($s->get('extra'))) ?></td></tr>
            </table>

            <div class="declaration">
                <strong>Declaration:</strong>
                <p>I hereby declare that the information provided above is true and correct to the best of my knowledge and I will abide by the rules of the institution.</p>
            </div>
            </div>
        </div>

        <!-- BACK -->
            <div style="margin-top:18px;">
                <strong>Student signature</strong>
                <div style="border-top:1px solid #333; margin-top:36px; width:40%;"></div>
            </div>

            <div style="margin-top:22px; display:flex; gap:12px; justify-content:space-between;">
                <div style="flex:1; text-align:center;">
                    <div style="border-top:1px solid #333; margin-top:36px;"></div>
                    <div style="margin-top:6px; font-weight:700;"><?= e($classTeacherName ?: 'Class Teacher') ?></div>
                    <div style="font-size:12px;color:#666;">Class Teacher</div>
                </div>
                <div style="flex:1; text-align:center;">
                    <div style="border-top:1px solid #333; margin-top:36px;"></div>
                    <div style="margin-top:6px; font-weight:700;">Asif Ustad</div>
                    <div style="font-size:12px;color:#666;">HOD CL</div>
                </div>
                <div style="flex:1; text-align:center;">
                    <div style="border-top:1px solid #333; margin-top:36px;"></div>
                    <div style="margin-top:6px; font-weight:700;">Siraj Ustad</div>
                    <div style="font-size:12px;color:#666;">HOD THALEEMATH</div>
                </div>
                <div style="flex:1; text-align:center;">
                    <div style="border-top:1px solid #333; margin-top:36px;"></div>
                    <div style="margin-top:6px; font-weight:700;">Haris Ustad</div>
                    <div style="font-size:12px;color:#666;">Teacher</div>
                </div>
            </div>
        </div>
        <?php
    }
}

// -----------------------------
// Controller
// -----------------------------
class PrintAllController {
    private DB $db;
    public function __construct(DB $db) { $this->db = $db; }
    public function run(): void {
        $raw = $this->db->fetchAllStudents();
        View::header('Print All — Application Forms');
        if (empty($raw)) {
            echo "<div class='container'><div class='form-sheet'><h2>No students found.</h2></div></div>";
            View::footer();
            return;
        }
        $printedAt = (new DateTime())->format('d-m-Y');
        $first = true;
        foreach ($raw as $r) {
            $student = new Student($r);
            View::renderStudent($student, $printedAt, $first);
            $first = false;
        }
        View::footer();
    }
}

// -----------------------------
// Bootstrap
// -----------------------------
try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection ($conn) not found. Ensure ../db.php defines $conn.');
    }
    $db = new DB($conn);
    (new PrintAllController($db))->run();
} catch (Throwable $ex) {
    echo '<pre>Error: ' . e($ex->getMessage()) . '</pre>';
    exit(1);
}
