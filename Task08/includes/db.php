<?php
// From config.php
define("DB_PATH", __DIR__ . "/../data/students.db");
define("SITE_URL", "/");

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        try {
            $dbDir = dirname(DB_PATH);
            if (!is_dir($dbDir)) {
                mkdir($dbDir, 0755, true);
            }

            $dbExists = file_exists(DB_PATH);

            $this->pdo = new PDO("sqlite:" . DB_PATH);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("PRAGMA foreign_keys = ON");

            if (!$dbExists) {
                $this->initDatabaseFromSQL();
            } else {
                // Проверяем наличие поля current_group_id и добавляем если нужно
                $this->migrateDatabase();
            }
        } catch (PDOException $e) {
            die(
                "Ошибка подключения или инициализации базы данных: " .
                    $e->getMessage()
            );
        }
    }

    private function migrateDatabase()
    {
        try {
            // Проверяем наличие поля current_group_id
            $stmt = $this->pdo->query("PRAGMA table_info(students)");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $hasCurrentGroup = false;
            foreach ($columns as $column) {
                if ($column['name'] === 'current_group_id') {
                    $hasCurrentGroup = true;
                    break;
                }
            }
            
            if (!$hasCurrentGroup) {
                $this->pdo->exec("ALTER TABLE students ADD COLUMN current_group_id INTEGER REFERENCES student_groups(id)");
            }
        } catch (Exception $e) {
            error_log("Ошибка миграции БД: " . $e->getMessage());
        }
    }

    private function initDatabaseFromSQL()
    {
        try {
            $sqlFile = __DIR__ . "/../db_init.sql";
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $this->pdo->exec($sql);
            }
        } catch (Exception $e) {
            error_log("Ошибка создания БД из SQL файла: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    // Methods for Students
    public function getStudents($groupId = null)
    {
        $sql = "
            SELECT 
                s.*,
                sg.group_code,
                sg.id as group_id
            FROM students s
            LEFT JOIN student_groups sg ON s.current_group_id = sg.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($groupId) {
            $sql .= " AND s.current_group_id = ?";
            $params[] = $groupId;
        }
        
        $sql .= " ORDER BY sg.group_code, s.last_name, s.first_name";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudent($id)
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, sg.group_code 
            FROM students s
            LEFT JOIN student_groups sg ON s.current_group_id = sg.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveStudent($data)
    {
        if (isset($data["id"])) {
            $stmt = $this->pdo->prepare(
                "UPDATE students SET student_code = ?, last_name = ?, first_name = ?, patronymic = ?, birth_date = ?, gender = ?, current_group_id = ? WHERE id = ?"
            );
            return $stmt->execute([
                $data["student_code"] ?? null,
                $data["last_name"],
                $data["first_name"],
                $data["patronymic"] ?? null,
                $data["birth_date"],
                $data["gender"],
                $data["current_group_id"] ?? null,
                $data["id"],
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO students (student_code, last_name, first_name, patronymic, birth_date, gender, enrollment_year, current_group_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([
                $data["student_code"] ?? null,
                $data["last_name"],
                $data["first_name"],
                $data["patronymic"] ?? null,
                $data["birth_date"],
                $data["gender"],
                $data["enrollment_year"] ?? date("Y"),
                $data["current_group_id"] ?? null,
                $data["status"] ?? "ACTIVE",
            ]);
        }
    }

    public function deleteStudent($id)
    {
        try {
            $this->pdo->beginTransaction();
            
            // Удаляем результаты экзаменов
            $stmt = $this->pdo->prepare("DELETE FROM exam_results WHERE student_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем зачеты
            $stmt = $this->pdo->prepare("DELETE FROM tests WHERE student_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем историю групп
            $stmt = $this->pdo->prepare("DELETE FROM group_history WHERE student_id = ?");
            $stmt->execute([$id]);
            
            // Удаляем студента
            $stmt = $this->pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("deleteStudent failed: " . $e->getMessage());
            return false;
        }
    }

    // Methods for Groups
    public function getAllGroups()
    {
        $stmt = $this->pdo->query("SELECT * FROM student_groups ORDER BY group_code");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroup($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM student_groups WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Methods for Exam Results
    public function getExamResults($studentId)
    {
        $sql = "
            SELECT 
                er.*,
                e.exam_date,
                e.academic_year,
                e.semester,
                s.name as subject_name,
                s.code as subject_code,
                sg.group_code
            FROM exam_results er
            JOIN exams e ON er.exam_id = e.id
            JOIN subjects s ON e.subject_id = s.id
            JOIN student_groups sg ON e.group_id = sg.id
            WHERE er.student_id = ?
            ORDER BY e.exam_date DESC, e.academic_year DESC, e.semester DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExamResult($id)
    {
        $sql = "
            SELECT 
                er.*,
                e.exam_date,
                e.academic_year,
                e.semester,
                e.subject_id,
                e.group_id,
                e.teacher_name,
                e.id as exam_id,
                s.name as subject_name,
                sg.group_code
            FROM exam_results er
            JOIN exams e ON er.exam_id = e.id
            JOIN subjects s ON e.subject_id = s.id
            JOIN student_groups sg ON e.group_id = sg.id
            WHERE er.id = ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveExamResult($data)
    {
        try {
            $this->pdo->beginTransaction();
            
            $examId = $data["exam_id"] ?? null;
            $isEdit = isset($data["id"]);
            
            if ($isEdit && $examId) {
                // При редактировании обновляем существующий экзамен
                $stmt = $this->pdo->prepare("
                    UPDATE exams 
                    SET subject_id = ?, group_id = ?, teacher_name = ?, exam_date = ?, academic_year = ?, semester = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data["subject_id"],
                    $data["group_id"],
                    $data["teacher_name"] ?? null,
                    $data["exam_date"] ?? date("Y-m-d"),
                    $data["academic_year"],
                    $data["semester"],
                    $examId
                ]);
            } elseif (!$examId && isset($data["subject_id"]) && isset($data["group_id"])) {
                // Ищем существующий экзамен
                $stmt = $this->pdo->prepare("
                    SELECT id FROM exams 
                    WHERE subject_id = ? AND group_id = ? AND academic_year = ? AND semester = ?
                ");
                $stmt->execute([
                    $data["subject_id"],
                    $data["group_id"],
                    $data["academic_year"],
                    $data["semester"]
                ]);
                $exam = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($exam) {
                    $examId = $exam["id"];
                    // Обновляем данные экзамена, если они изменились
                    $stmt = $this->pdo->prepare("
                        UPDATE exams 
                        SET teacher_name = ?, exam_date = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $data["teacher_name"] ?? null,
                        $data["exam_date"] ?? date("Y-m-d"),
                        $examId
                    ]);
                } else {
                    // Создаем новый экзамен
                    $stmt = $this->pdo->prepare("
                        INSERT INTO exams (subject_id, group_id, teacher_name, exam_date, academic_year, semester)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data["subject_id"],
                        $data["group_id"],
                        $data["teacher_name"] ?? null,
                        $data["exam_date"] ?? date("Y-m-d"),
                        $data["academic_year"],
                        $data["semester"]
                    ]);
                    $examId = $this->pdo->lastInsertId();
                }
            }
            
            if ($isEdit) {
                // UPDATE результата экзамена
                $stmt = $this->pdo->prepare(
                    "UPDATE exam_results SET exam_id = ?, grade = ?, passed_at = ? WHERE id = ?"
                );
                $stmt->execute([
                    $examId,
                    $data["grade"],
                    $data["passed_at"] ?? date("Y-m-d"),
                    $data["id"],
                ]);
            } else {
                // INSERT нового результата экзамена
                $stmt = $this->pdo->prepare(
                    "INSERT INTO exam_results (exam_id, student_id, grade, passed_at) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([
                    $examId,
                    $data["student_id"],
                    $data["grade"],
                    $data["passed_at"] ?? date("Y-m-d"),
                ]);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("saveExamResult failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteExamResult($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM exam_results WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Methods for Subjects
    public function getSubjectsForStudent($studentId, $academicYear, $semester)
    {
        // Получаем программу студента через его текущую группу
        $student = $this->getStudent($studentId);
        if (!$student || !$student["current_group_id"]) {
            return [];
        }
        
        $group = $this->getGroup($student["current_group_id"]);
        if (!$group) {
            return [];
        }
        
        // Для упрощения возвращаем все дисциплины
        // В реальной системе здесь должна быть логика фильтрации по программе и курсу
        $stmt = $this->pdo->query("SELECT * FROM subjects ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSubjects()
    {
        $stmt = $this->pdo->query("SELECT * FROM subjects ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper methods
    public function getStudentsByGroup($groupId)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM students 
            WHERE current_group_id = ? 
            ORDER BY last_name, first_name
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAcademicYears()
    {
        // Генерируем список учебных годов
        $years = [];
        $currentYear = date("Y");
        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
            $years[] = ($i - 1) . "/" . $i;
        }
        return $years;
    }
}

// From helpers.php
function redirect($url)
{
    header("Location: " . $url);
    exit();
}

function setFlash($message, $type = "success")
{
    $_SESSION["flash"] = ["message" => $message, "type" => $type];
}

function getFlash()
{
    if (isset($_SESSION["flash"])) {
        $flash = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        return $flash;
    }
    return null;
}

function html($text)
{
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

function formatDate($date)
{
    return date("d.m.Y", strtotime($date));
}

function formatDateTime($datetime)
{
    return date("d.m.Y H:i", strtotime($datetime));
}

function formatGender($gender)
{
    return $gender === "M" ? "Мужской" : "Женский";
}

function formatGrade($grade)
{
    $grades = [2 => "Неудовлетворительно", 3 => "Удовлетворительно", 4 => "Хорошо", 5 => "Отлично"];
    return $grades[$grade] ?? $grade;
}
?>
