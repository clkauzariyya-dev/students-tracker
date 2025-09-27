<?php
// admin/print.php
// Single-file mini-application: Config | DB | Model | Controller | View | Router
// PHP 7.4+ recommended

declare(strict_types=1);

// -----------------------------
// Configuration
// -----------------------------
class Config {
    public static $uploadsDir = __DIR__ . '/../Uploads/';
    public static $uploadsWeb = '../Uploads/';
    public static $defaultImage = '../assets/default.png';
}

// -----------------------------
// Utilities
// -----------------------------
function e($str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url($path): string {
    return $path;
}

// -----------------------------
// Database Wrapper
// -----------------------------
class DB {
    private mysqli $conn;

    public function __construct(mysqli $conn) {
        $this->conn = $conn;
        $this->conn->set_charset('utf8mb4');
    }

    public function fetchStudentById(int $id): ?array {
        $sql = "
            SELECT s.*, c.class_name,
                   ct_class.teacher_name AS class_teacher_name,
                   ct_student.teacher_name AS student_teacher_name
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN class_teachers ct_class ON c.teacher_id = ct_class.id
            LEFT JOIN class_teachers ct_student ON s.teacher_id = ct_student.id
            WHERE s.id = ?
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new RuntimeException('DB prepare error: ' . $this->conn->error);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}

// -----------------------------
// Student Model
// -----------------------------
class Student {
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function get(string $key, $default = ''): string {
        return (string)($this->data[$key] ?? $default);
    }

    public function fullName(): string {
        return $this->get('full_name');
    }

    public function className(): string {
        return $this->get('class_name') ?: $this->get('class_id');
    }

    public function dob(): string {
        return $this->get('dob');
    }

    public function age(): string {
        $dob = $this->dob();
        if (!$dob) return '-';
        try {
            $d1 = new DateTime($dob);
            $d2 = new DateTime();
            return (string)$d1->diff($d2)->y;
        } catch (Exception $e) {
            return '-';
        }
    }

    public function profileImagePath(): string {
        $pi = trim($this->get('profile_image'));
        if (!$pi) return Config::$defaultImage;

        if (preg_match('#^https?://#i', $pi)) {
            return $pi;
        }

        if (file_exists(Config::$uploadsDir . $pi)) {
            return Config::$uploadsWeb . $pi;
        }

        if (file_exists(dirname(Config::$uploadsDir) . '/admin/Uploads/' . $pi)) {
            return '../admin/Uploads/' . $pi;
        }

        return Config::$defaultImage;
    }
}

// -----------------------------
// View
// -----------------------------
class View {
    public static function header(string $title): void {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= e($title) ?></title>
            <style>
                /* A4 size: 210mm x 297mm */
                @page { 
                    size: A4; 
                    margin: 15mm; 
                }
                body { 
                    font-family: Arial, Helvetica, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    background: #fff; 
                    font-size: 12pt; 
                    line-height: 1.4; 
                }
                .form-sheet { 
                    width: 100%; 
                    max-width: 180mm; 
                    margin: 0 auto; 
                    padding: 10mm; 
                    box-sizing: border-box; 
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 10mm; 
                }
                .header h1 { 
                    margin: 0; 
                    font-size: 16pt; 
                    color: #1a2b3c; 
                    text-transform: uppercase; 
                }
                .header p { 
                    margin: 4pt 0; 
                    font-size: 10pt; 
                    color: #444; 
                }
                .photo { 
                    float: right; 
                    width: 40mm; 
                    height: 50mm; 
                    border: 1px solid #ccc; 
                    margin-left: 5mm; 
                    margin-bottom: 5mm; 
                }
                .photo img { 
                    width: 100%; 
                    height: 100%; 
                    object-fit: cover; 
                    display: block; 
                }
                table.details { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 5mm; 
                    font-size: 10pt; 
                }
                table.details th, table.details td { 
                    border: 1px solid #999; 
                    padding: 6pt; 
                    text-align: left; 
                    vertical-align: top; 
                }
                table.details th { 
                    width: 35%; 
                    background: #f5f6f5; 
                    font-weight: bold; 
                }
                .section { 
                    margin-top: 8mm; 
                    clear: both; 
                }
                .section h3 { 
                    font-size: 12pt; 
                    color: #1a2b3c; 
                    margin: 0 0 4pt; 
                }
                .terms { 
                    font-size: 9pt; 
                    margin-top: 5mm; 
                    border: 1px solid #ddd; 
                    padding: 8pt; 
                    background: #fafafa; 
                }
                .terms h4 { 
                    font-size: 10pt; 
                    margin: 8pt 0 4pt; 
                }
                .terms ul { 
                    margin: 0; 
                    padding-left: 20pt; 
                }
                .terms li { 
                    margin-bottom: 3pt; 
                }
                .signatures { 
                    display: flex; 
                    flex-wrap: wrap; 
                    gap: 5mm; 
                    margin-top: 8mm; 
                    justify-content: space-between; 
                }
                .sig-box { 
                    width: 22%; 
                    text-align: center; 
                    min-width: 35mm; 
                }
                .sig-line { 
                    border-top: 1px dotted #000; 
                    margin-top: 25mm; 
                }
                .sig-name { 
                    font-size: 9pt; 
                    margin-top: 2mm; 
                }
                .student-sig { 
                    width: 40%; 
                    margin-top: 5mm; 
                }
                .actions { 
                    text-align: right; 
                    margin-bottom: 5mm; 
                }
                .btn { 
                    display: inline-block; 
                    padding: 6pt 10pt; 
                    border-radius: 4pt; 
                    text-decoration: none; 
                    font-size: 10pt; 
                    cursor: pointer; 
                }
                .btn.back { 
                    background: #6c757d; 
                    color: #fff; 
                    margin-right: 5pt; 
                }
                .btn.print { 
                    background: #007bff; 
                    color: #fff; 
                }
                .page-break { 
                    page-break-before: always; 
                }
                @media print {
                    .actions { display: none; }
                    .form-sheet { 
                        padding: 0; 
                        box-shadow: none; 
                        border: none; 
                    }
                    .page-break { 
                        page-break-before: always; 
                    }
                }
            </style>
        </head>
        <body>
        <?php
    }

    public static function footer(): void {
        echo "</body></html>";
    }

    public static function notFound(string $message = 'Not found'): void {
        self::header('Not found');
        echo "<div class='form-sheet'><h2>" . e($message) . "</h2></div>";
        self::footer();
        exit;
    }

    public static function renderPrint(Student $student, string $printedAt): void {
        self::header('Application Form - ' . $student->fullName());
        ?>
        <!-- Front Side -->
        <div class="form-sheet">
            <div class="actions">
                <a class="btn back" href="<?= e(url('student-view.php?id=' . $student->get('id'))) ?>">← Back</a>
                <button class="btn print" onclick="window.print()">🖨 Print</button>
            </div>

            <div class="header">
                <h1>Student Application Form</h1>
                <p>Kauzariyya Madrasa Registration System</p>
                <p><strong>Date:</strong> <?= e($printedAt) ?></p>
            </div>

            <div class="photo">
                <img src="<?= e($student->profileImagePath()) ?>" alt="Student Photo">
            </div>

            <table class="details" role="table" aria-label="Student Details">
                <tr><th>Full Name</th><td><?= e($student->fullName()) ?></td></tr>
                <tr><th>Class</th><td><?= e($student->className()) ?></td></tr>
                <tr><th>School</th><td><?= e($student->get('school')) ?></td></tr>
                <tr><th>Place</th><td><?= e($student->get('place')) ?></td></tr>
                <tr><th>District</th><td><?= e($student->get('district')) ?></td></tr>
                <tr><th>Date of Birth</th><td><?= e($student->dob()) ?> (Age: <?= e($student->age()) ?>)</td></tr>
                <tr><th>Knowledge</th><td><?= e($student->get('knowledge')) ?></td></tr>
                <tr><th>Phone</th><td><?= e($student->get('phone')) ?></td></tr>
                <tr><th>Extra Notes</th><td><?= nl2br(e($student->get('extra'))) ?></td></tr>
            </table>

            <div class="section">
                <h3>Declaration</h3>
                <p>I hereby declare that the above information is true and correct to the best of my knowledge.</p>
            </div>
        </div>

        <!-- Back Side -->
        <div class="form-sheet page-break">
            <div class="section">
                <h3>Computer Lab Terms & Conditions</h3>
                <div class="terms">
                    <p>By logging in and using the computer lab system, every student agrees to abide by the following rules and conditions:</p>
                    <h4>1. General Conduct</h4>
                    <ul>
                        <li>Students must use the computer lab only for academic and madrasa-related purposes.</li>
                        <li>Respectful behavior is expected at all times inside the lab.</li>
                        <li>Students must follow Islamic ethics in all online and offline activities.</li>
                    </ul>
                    <h4>2. Use of Computers & Internet</h4>
                    <ul>
                        <li>Computers may only be used for learning, research, and approved projects.</li>
                        <li>Accessing prohibited content (haram material, games, entertainment, or non-educational sites) is strictly forbidden.</li>
                        <li>Students may not install or remove any software without permission.</li>
                        <li>Personal devices may only be connected with the permission of the lab administrator.</li>
                    </ul>
                    <h4>3. Respect for Islam & Madrasa Rules</h4>
                    <ul>
                        <li>Any action that contradicts Islamic values or Kauzariyya madrasa rules will not be tolerated.</li>
                        <li>Spreading false, harmful, or un-Islamic content is strictly prohibited.</li>
                        <li>Students must avoid online debates, chats, or activities that can harm Islamic faith or morals.</li>
                    </ul>
                    <h4>4. Data & Privacy</h4>
                    <ul>
                        <li>Students must not tamper with or attempt to access other students’ files.</li>
                        <li>Academic progress and status updates will be monitored by administrators for educational purposes.</li>
                        <li>All online activities inside the lab may be logged for security and discipline.</li>
                    </ul>
                    <h4>5. Discipline & Penalties</h4>
                    <ul>
                        <li>Minor violations will result in warnings or temporary suspension.</li>
                        <li>Severe violations such as engaging in un-Islamic activities, spreading falsehood, or disobeying madrasa authorities will result in permanent suspension from the computer lab.</li>
                        <li>In extreme cases, disciplinary action may extend to academic consequences as decided by the administration.</li>
                    </ul>
                </div>
            </div>

            <div class="section">
                <h3>Student Signature</h3>
                <div class="sig-box student-sig">
                    <div class="sig-line"></div>
                    <div class="sig-name"><?= e($student->fullName() ?: 'Student') ?></div>
                </div>
            </div>

            <div class="section">
                <div class="signatures" aria-hidden="true">
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name"><?= e($student->get('class_teacher_name') ?: 'Class Teacher') ?></div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name">Asif Ustad (HOD CL)</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name">Siraj Ustad (HOD THALEEMATH)</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-name">Haris Ustad</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        self::footer();
    }
}

// -----------------------------
// Controller
// -----------------------------
class PrintController {
    private DB $db;

    public function __construct(DB $db) {
        $this->db = $db;
    }

    public function printAction(?int $id): void {
        if ($id === null || $id <= 0) {
            View::notFound('Invalid student id.');
        }

        $raw = $this->db->fetchStudentById($id);
        if (!$raw) {
            View::notFound('Student not found.');
        }

        $student = new Student($raw);
        $printedAt = (new DateTime())->format('d-m-Y');

        View::renderPrint($student, $printedAt);
    }
}

// -----------------------------
// Router
// -----------------------------
class Router {
    private PrintController $controller;

    public function __construct(PrintController $controller) {
        $this->controller = $controller;
    }

    public function run(): void {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->controller->printAction($id === false ? null : $id);
    }
}

// -----------------------------
// Bootstrap
// -----------------------------
try {
    require_once __DIR__ . '/../db.php';
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new RuntimeException('Database connection ($conn) not found. Ensure ../db.php sets $conn (mysqli).');
    }

    $db = new DB($conn);
    $controller = new PrintController($db);
    $router = new Router($controller);
    $router->run();

} catch (Throwable $e) {
    View::header('Error');
    echo '<div class="form-sheet"><h2>Error</h2><p>' . e($e->getMessage()) . '</p></div>';
    View::footer();
    exit;
}