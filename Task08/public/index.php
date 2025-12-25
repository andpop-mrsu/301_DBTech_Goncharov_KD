<?php
session_start();
require_once __DIR__ . "/../includes/db.php";

$db = Database::getInstance();
$action = $_GET["action"] ?? "list";
$studentId = $_GET["student_id"] ?? 0;

// Обработка основных действий
if ($action === "exams") {
    // Работа с результатами экзаменов
    $subAction = $_GET["sub_action"] ?? "list";
    $id = $_GET["id"] ?? 0;

    if ($subAction === "create" || $subAction === "edit") {
        $examResult = null;
        $student = null;
        
        if ($subAction === "edit") {
            $examResult = $db->getExamResult($id);
            if (!$examResult) {
                setFlash("Запись не найдена", "error");
                redirect("index.php?action=exams&student_id=$studentId");
            }
            $student = $db->getStudent($examResult["student_id"]);
        } else {
            $student = $db->getStudent($studentId);
            if (!$student) {
                setFlash("Студент не найден", "error");
                redirect("index.php");
            }
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $_POST;
            $data["student_id"] = $student["id"];
            if ($subAction === "edit") {
                $data["id"] = $id;
                // При редактировании передаем exam_id из существующей записи
                if ($examResult && isset($examResult["exam_id"])) {
                    $data["exam_id"] = $examResult["exam_id"];
                }
            }

            if ($db->saveExamResult($data)) {
                setFlash("Результат экзамена сохранен");
                redirect("index.php?action=exams&student_id=" . $student["id"]);
            } else {
                setFlash("Ошибка при сохранении", "error");
            }
        }

        // Получаем данные для формы
        $groups = $db->getAllGroups();
        $subjects = $db->getAllSubjects();
        $academicYears = $db->getAcademicYears();
        
        require "../templates/exams.php";
    } elseif ($subAction === "delete") {
        if (
            $_SERVER["REQUEST_METHOD"] === "POST" &&
            isset($_POST["confirm"])
        ) {
            $examResult = $db->getExamResult($id);
            $studentIdForRedirect = $examResult["student_id"];
            $db->deleteExamResult($id);
            setFlash("Результат экзамена удален");
            redirect("index.php?action=exams&student_id=$studentIdForRedirect");
        }

        $examResult = $db->getExamResult($id);
        if (!$examResult) {
            setFlash("Запись не найдена", "error");
            redirect("index.php");
        }
        $student = $db->getStudent($examResult["student_id"]);
        require "../templates/exams.php";
    } else {
        // Список результатов экзаменов
        $student = $db->getStudent($studentId);
        if (!$student) {
            setFlash("Студент не найден", "error");
            redirect("index.php");
        }
        $examResults = $db->getExamResults($studentId);
        require "../templates/exams.php";
    }
} else {
    // Главная страница - список студентов
    $groupId = $_GET["group_id"] ?? null;
    $students = $db->getStudents($groupId);
    $groups = $db->getAllGroups();
    
    if ($action === "create" || $action === "edit") {
        // Форма создания/редактирования студента
        $student = null;
        if ($action === "edit" && isset($_GET["id"])) {
            $student = $db->getStudent($_GET["id"]);
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = $_POST;
            if ($action === "edit" && isset($_GET["id"])) {
                $data["id"] = $_GET["id"];
            }

            if ($db->saveStudent($data)) {
                setFlash("Данные студента сохранены");
                redirect("index.php" . ($groupId ? "?group_id=$groupId" : ""));
            } else {
                setFlash("Ошибка при сохранении", "error");
            }
        }
    } elseif ($action === "delete") {
        // Удаление студента
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirm"])) {
            $db->deleteStudent($_GET["id"]);
            setFlash("Студент удален");
            redirect("index.php" . ($groupId ? "?group_id=$groupId" : ""));
        }

        $student = $db->getStudent($_GET["id"]);
    }
    require "../templates/students.php";
}
?>
