<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты экзаменов</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>css/styles.css">
</head>
<body>
    <div class="container">
        <?php
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert <?= html($flash["type"]) ?>"><?= html($flash["message"]) ?></div>
        <?php endif; ?>
        
        <?php 
        $subAction = $subAction ?? "list";
        if ($subAction === "create" || $subAction === "edit"): ?>
            <h1><?= $subAction === "edit" ? "Редактирование результата экзамена" : "Добавление результата экзамена" ?></h1>
            <p><strong>Студент:</strong> <?= html($student["last_name"] . " " . $student["first_name"] . " " . ($student["patronymic"] ?? "")) ?></p>
            
            <form method="POST" class="form">
                <?php if ($subAction === "edit"): ?>
                    <input type="hidden" name="id" value="<?= html($examResult["id"]) ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Группа *</label>
                    <select name="group_id" id="group_select" required>
                        <option value="">-- Выберите группу --</option>
                        <?php foreach ($groups as $g): ?>
                        <option value="<?= html($g["id"]) ?>" 
                            <?= ($examResult["group_id"] ?? $student["current_group_id"] ?? "") == $g["id"] ? "selected" : "" ?>>
                            <?= html($g["group_code"]) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дисциплина *</label>
                    <select name="subject_id" id="subject_select" required>
                        <option value="">-- Выберите дисциплину --</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= html($s["id"]) ?>" 
                            <?= ($examResult["subject_id"] ?? "") == $s["id"] ? "selected" : "" ?>>
                            <?= html($s["name"]) ?> (<?= html($s["code"]) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Учебный год *</label>
                    <select name="academic_year" required>
                        <option value="">-- Выберите учебный год --</option>
                        <?php foreach ($academicYears as $year): ?>
                        <option value="<?= html($year) ?>" 
                            <?= ($examResult["academic_year"] ?? "") == $year ? "selected" : "" ?>>
                            <?= html($year) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Семестр *</label>
                    <select name="semester" required>
                        <option value="1" <?= ($examResult["semester"] ?? "") == "1" ? "selected" : "" ?>>1</option>
                        <option value="2" <?= ($examResult["semester"] ?? "") == "2" ? "selected" : "" ?>>2</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дата экзамена *</label>
                    <input type="date" name="exam_date" value="<?= html($examResult["exam_date"] ?? date("Y-m-d")) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Оценка *</label>
                    <select name="grade" required>
                        <option value="">-- Выберите оценку --</option>
                        <option value="5" <?= ($examResult["grade"] ?? "") == "5" ? "selected" : "" ?>>5 (Отлично)</option>
                        <option value="4" <?= ($examResult["grade"] ?? "") == "4" ? "selected" : "" ?>>4 (Хорошо)</option>
                        <option value="3" <?= ($examResult["grade"] ?? "") == "3" ? "selected" : "" ?>>3 (Удовлетворительно)</option>
                        <option value="2" <?= ($examResult["grade"] ?? "") == "2" ? "selected" : "" ?>>2 (Неудовлетворительно)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Дата сдачи</label>
                    <input type="date" name="passed_at" value="<?= html($examResult["passed_at"] ?? date("Y-m-d")) ?>">
                </div>
                
                <div class="form-group">
                    <label>Преподаватель</label>
                    <input type="text" name="teacher_name" value="<?= html($examResult["teacher_name"] ?? "") ?>">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn save">Сохранить</button>
                    <a href="index.php?action=exams&student_id=<?= $student["id"] ?>" class="btn cancel">Отмена</a>
                </div>
            </form>
            
        <?php elseif ($subAction === "delete"): ?>
            <div class="confirmation">
                <h2>Удаление результата экзамена</h2>
                <p>Вы действительно хотите удалить результат экзамена по дисциплине "<?= html($examResult["subject_name"]) ?>"?</p>
                <p>Дата: <?= formatDate($examResult["exam_date"]) ?>, Оценка: <?= html($examResult["grade"]) ?></p>
                <p>Это действие невозможно отменить.</p>
                
                <form method="POST" class="confirmation-actions">
                    <button type="submit" name="confirm" value="1" class="btn delete">Удалить</button>
                    <a href="index.php?action=exams&student_id=<?= $student["id"] ?>" class="btn cancel">Отмена</a>
                </form>
            </div>
            
        <?php else: ?>
            <h1>Результаты экзаменов студента: <?= html($student["last_name"] . " " . $student["first_name"] . " " . ($student["patronymic"] ?? "")) ?></h1>
            
            <table>
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Дисциплина</th>
                        <th>Группа</th>
                        <th>Учебный год</th>
                        <th>Семестр</th>
                        <th>Оценка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($examResults)): ?>
                    <tr>
                        <td colspan="7" class="no-data">Нет результатов экзаменов</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($examResults as $result): ?>
                        <tr>
                            <td><?= formatDate($result["exam_date"]) ?></td>
                            <td><?= html($result["subject_name"]) ?> (<?= html($result["subject_code"]) ?>)</td>
                            <td><?= html($result["group_code"]) ?></td>
                            <td><?= html($result["academic_year"]) ?></td>
                            <td><?= html($result["semester"]) ?></td>
                            <td><strong><?= html($result["grade"]) ?></strong> (<?= formatGrade($result["grade"]) ?>)</td>
                            <td class="actions">
                                <a href="index.php?action=exams&student_id=<?= $student["id"] ?>&sub_action=edit&id=<?= $result["id"] ?>" class="btn edit">Редактировать</a>
                                <a href="index.php?action=exams&student_id=<?= $student["id"] ?>&sub_action=delete&id=<?= $result["id"] ?>" class="btn delete">Удалить</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="footer">
                <a href="index.php?action=exams&student_id=<?= $student["id"] ?>&sub_action=create" class="btn add">Добавить результат экзамена</a>
                <a href="index.php" class="btn back">Назад к списку студентов</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

