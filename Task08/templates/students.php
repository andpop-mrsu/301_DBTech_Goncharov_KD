<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление студентами</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Список студентов</h1>

        <?php
        $flash = getFlash();
        if ($flash): ?>
            <div class="alert <?= html($flash["type"]) ?>"><?= html($flash["message"]) ?></div>
        <?php endif; ?>

        <?php if ($action === "create" || $action === "edit"): ?>
            <h2><?= $action === "edit" ? "Редактировать студента" : "Добавить студента" ?></h2>
            <form action="index.php?action=<?= $action . (isset($student["id"]) ? "&id=" . $student["id"] : "") ?>" method="post" class="form">
                <div class="form-group">
                    <label for="student_code">Код студента:</label>
                    <input type="text" id="student_code" name="student_code" value="<?= html($student["student_code"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия *:</label>
                    <input type="text" id="last_name" name="last_name" value="<?= html($student["last_name"] ?? "") ?>" required>
                </div>
                <div class="form-group">
                    <label for="first_name">Имя *:</label>
                    <input type="text" id="first_name" name="first_name" value="<?= html($student["first_name"] ?? "") ?>" required>
                </div>
                <div class="form-group">
                    <label for="patronymic">Отчество:</label>
                    <input type="text" id="patronymic" name="patronymic" value="<?= html($student["patronymic"] ?? "") ?>">
                </div>
                <div class="form-group">
                    <label for="birth_date">Дата рождения *:</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?= html($student["birth_date"] ?? "") ?>" required>
                </div>
                <div class="form-group">
                    <label>Пол *:</label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="gender" value="M" <?= ($student["gender"] ?? "") === "M" ? "checked" : "" ?> required>
                            Мужской
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="gender" value="F" <?= ($student["gender"] ?? "") === "F" ? "checked" : "" ?> required>
                            Женский
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="current_group_id">Группа *:</label>
                    <select id="current_group_id" name="current_group_id" required>
                        <option value="">-- Выберите группу --</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= html($group["id"]) ?>" <?= ($student["current_group_id"] ?? "") == $group["id"] ? "selected" : "" ?>>
                            <?= html($group["group_code"]) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="enrollment_year">Год поступления:</label>
                    <input type="number" id="enrollment_year" name="enrollment_year" value="<?= html($student["enrollment_year"] ?? date("Y")) ?>" min="2000" max="<?= date("Y") + 1 ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn save">Сохранить</button>
                    <a href="index.php<?= isset($_GET["group_id"]) ? "?group_id=" . html($_GET["group_id"]) : "" ?>" class="btn cancel">Отмена</a>
                </div>
            </form>
        <?php elseif ($action === "delete" && isset($student)): ?>
            <div class="confirmation">
                <h2>Удалить студента</h2>
                <p>Вы уверены, что хотите удалить студента "<?= html($student["last_name"] . " " . $student["first_name"]) ?>"?</p>
                <p>Это действие невозможно отменить.</p>
                <form action="index.php?action=delete&id=<?= $student["id"] ?>" method="post" class="confirmation-actions">
                    <button type="submit" name="confirm" value="1" class="btn delete">Удалить</button>
                    <a href="index.php<?= isset($_GET["group_id"]) ? "?group_id=" . html($_GET["group_id"]) : "" ?>" class="btn cancel">Отмена</a>
                </form>
            </div>
        <?php else: ?>
            <!-- Фильтр по группе -->
            <div class="filter-section">
                <form method="get" action="index.php" class="filter-form">
                    <label for="group_filter">Фильтр по группе:</label>
                    <select id="group_filter" name="group_id" onchange="this.form.submit()">
                        <option value="">Все группы</option>
                        <?php foreach ($groups as $group): ?>
                        <option value="<?= html($group["id"]) ?>" <?= (isset($_GET["group_id"]) && $_GET["group_id"] == $group["id"]) ? "selected" : "" ?>>
                            <?= html($group["group_code"]) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (!empty($students)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Группа</th>
                            <th>Фамилия</th>
                            <th>Имя</th>
                            <th>Отчество</th>
                            <th>Дата рождения</th>
                            <th>Пол</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= html($student["group_code"] ?? "-") ?></td>
                                <td><?= html($student["last_name"]) ?></td>
                                <td><?= html($student["first_name"]) ?></td>
                                <td><?= html($student["patronymic"] ?? "-") ?></td>
                                <td><?= formatDate($student["birth_date"]) ?></td>
                                <td><?= formatGender($student["gender"]) ?></td>
                                <td class="actions">
                                    <a href="index.php?action=edit&id=<?= $student["id"] ?><?= isset($_GET["group_id"]) ? "&group_id=" . html($_GET["group_id"]) : "" ?>" class="btn edit">Редактировать</a>
                                    <a href="index.php?action=delete&id=<?= $student["id"] ?><?= isset($_GET["group_id"]) ? "&group_id=" . html($_GET["group_id"]) : "" ?>" class="btn delete">Удалить</a>
                                    <a href="index.php?action=exams&student_id=<?= $student["id"] ?>" class="btn add">Результаты экзаменов</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Нет студентов для отображения.</p>
            <?php endif; ?>

            <div class="add-button-container">
                <a href="index.php?action=create<?= isset($_GET["group_id"]) ? "&group_id=" . html($_GET["group_id"]) : "" ?>" class="button">Добавить студента</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

