#!/bin/bash
chcp 65001

sqlite3 movies_rating.db < db_init.sql

echo " "
echo "1. Найти все пары пользователей, оценивших один и тот же фильм. Устранить дубликаты, проверить отсутствие пар с самим собой. Для каждой пары должны быть указаны имена пользователей и название фильма, который они ценили. В списке оставить первые 100 записей."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "SELECT DISTINCT
    u1.name AS user1,
    u2.name AS user2,
    m.title AS movie
FROM ratings r1
JOIN ratings r2 ON r1.movie_id = r2.movie_id AND r1.user_id < r2.user_id
JOIN users u1 ON r1.user_id = u1.id
JOIN users u2 ON r2.user_id = u2.id
JOIN movies m ON r1.movie_id = m.id
ORDER BY u1.name, u2.name, m.title
LIMIT 100;"

echo " "
echo "2. Найти 10 самых свежих оценок от разных пользователей, вывести названия фильмов, имена пользователей, оценку, дату отзыва в формате ГГГГ-ММ-ДД."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "SELECT DISTINCT
    m.title,
    u.name,
    r.rating,
    date(r.timestamp, 'unixepoch') AS rating_date
FROM ratings r
JOIN movies m ON r.movie_id = m.id
JOIN users u ON r.user_id = u.id
ORDER BY r.timestamp DESC
LIMIT 10;"

echo " "
echo "3. Вывести в одном списке все фильмы с максимальным средним рейтингом и все фильмы с минимальным средним рейтингом. Общий список отсортировать по году выпуска и названию фильма. В зависимости от рейтинга в колонке 'Рекомендуем' для фильмов должно быть написано 'Да' или 'Нет'."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "WITH AvgRatings AS (
    SELECT
        m.id,
        m.title,
        m.year,
        AVG(r.rating) AS avg_rating
    FROM movies m
    JOIN ratings r ON m.id = r.movie_id
    GROUP BY m.id
),
MinMax AS (
    SELECT
        MIN(avg_rating) AS min_rating,
        MAX(avg_rating) AS max_rating
    FROM AvgRatings
)
SELECT
    ar.title,
    ar.year,
    ar.avg_rating,
    CASE
        WHEN ar.avg_rating = (SELECT max_rating FROM MinMax) THEN 'Да'
        ELSE 'Нет'
    END AS Рекомендуем
FROM AvgRatings ar
WHERE ar.avg_rating = (SELECT min_rating FROM MinMax)
   OR ar.avg_rating = (SELECT max_rating FROM MinMax)
ORDER BY ar.year, ar.title;"

echo " "
echo "4. Вычислить количество оценок и среднюю оценку, которую дали фильмам пользователи-женщины в период с 2010 по 2012 год."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "SELECT
    COUNT(*) AS количество_оценок,
    ROUND(AVG(r.rating), 2) AS средняя_оценка
FROM ratings r
JOIN users u ON r.user_id = u.id
WHERE u.gender = 'female'
    AND strftime('%Y', datetime(r.timestamp, 'unixepoch')) BETWEEN '2010' AND '2012';"

echo " "
echo "5. Составить список фильмов с указанием их средней оценки и места в рейтинге по средней оценке. Полученный список отсортировать по году выпуска и названиям фильмов. В списке оставить первые 20 записей."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "WITH MovieRatings AS (
    SELECT
        m.id,
        m.title,
        m.year,
        ROUND(AVG(r.rating), 2) AS avg_rating,
        RANK() OVER (ORDER BY AVG(r.rating) DESC) AS rating_rank
    FROM movies m
    JOIN ratings r ON m.id = r.movie_id
    GROUP BY m.id
)
SELECT
    title,
    year,
    avg_rating,
    rating_rank
FROM MovieRatings
ORDER BY year, title
LIMIT 20;"

echo " "
echo "6. Вывести список из 10 последних зарегистрированных пользователей в формате 'Фамилия Имя|Дата регистрации' (сначала фамилия, потом имя)."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "SELECT
    SUBSTR(name, INSTR(name, ' ') + 1) || ' ' ||
    SUBSTR(name, 1, INSTR(name, ' ') - 1) || '|' ||
    register_date AS user_info
FROM users
ORDER BY register_date DESC
LIMIT 10;"

echo " "
echo "7. С помощью рекурсивного CTE составить таблицу умножения для чисел от 1 до 10. Должен получиться один столбец следующего вида:"
echo "1x1=1"
echo "1x2=2"
echo ". . ."
echo "1x10=10"
echo "2x1=2"
echo "2x2=2"
echo ". . ."
echo "10x9=90"
echo "10x10=100"
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "WITH RECURSIVE numbers(n) AS (
    SELECT 1
    UNION ALL
    SELECT n + 1
    FROM numbers
    WHERE n < 10
),
multiplication_table AS (
    SELECT
        a.n || 'x' || b.n || '=' || (a.n * b.n) AS result
    FROM numbers a, numbers b
)
SELECT result
FROM multiplication_table
ORDER BY
    CAST(SUBSTR(result, 1, INSTR(result, 'x') - 1) AS INTEGER),
    CAST(SUBSTR(result, INSTR(result, 'x') + 1, INSTR(result, '=') - INSTR(result, 'x') - 1) AS INTEGER);"

echo " "
echo "8. С помощью рекурсивного CTE выделить все жанры фильмов, имеющиеся в таблице movies (каждый жанр в отдельной строке)."
echo "--------------------------------------------------"
sqlite3 -header -box movies_rating.db "WITH RECURSIVE split_genres(genre, remaining) AS (
    SELECT
        '',
        genres || '|'
    FROM movies
    UNION ALL
    SELECT
        SUBSTR(remaining, 1, INSTR(remaining, '|') - 1),
        SUBSTR(remaining, INSTR(remaining, '|') + 1)
    FROM split_genres
    WHERE remaining != ''
)
SELECT DISTINCT genre
FROM split_genres
WHERE genre != ''
ORDER BY genre;"

echo " "
