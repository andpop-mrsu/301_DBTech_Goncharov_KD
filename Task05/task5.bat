#!/bin/bash
chcp 65001

sqlite3 movies_rating.db < db_init.sql

echo " "
echo "1. Для каждого фильма выведите его название, год выпуска и средний рейтинг. Дополнительно добавьте столбец rank_by_avg_rating, в котором укажите ранг фильма среди всех фильмов по убыванию среднего рейтинга (фильмы с одинаковым средним рейтингом должны получить одинаковый ранг). Используйте оконную функцию RANK() или DENSE_RANK(). В результирующем наборе данных оставить 10 фильмов с наибольшим рангом."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH MovieStats AS (
    SELECT
        m.id,
        m.title,
        m.year,
        ROUND(AVG(r.rating), 2) AS avg_rating,
        RANK() OVER (ORDER BY AVG(r.rating) DESC) AS rank_by_avg_rating
    FROM movies m
    JOIN ratings r ON m.id = r.movie_id
    GROUP BY m.id, m.title, m.year
)
SELECT
    title,
    year,
    avg_rating,
    rank_by_avg_rating
FROM MovieStats
ORDER BY rank_by_avg_rating ASC
LIMIT 10;"

echo " "
echo "2. С помощью рекурсивного CTE выделить все жанры фильмов, имеющиеся в таблице movies. Для каждого жанра рассчитать средний рейтинг avg_rating фильмов в этом жанре. Выведите genre, avg_rating и ранг жанра по убыванию среднего рейтинга, используя оконную функцию RANK()"
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres(genre, remaining) AS (
    SELECT
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, 1, instr(genres, '|') - 1)
            ELSE genres
        END,
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, instr(genres, '|') + 1)
            ELSE ''
        END
    FROM movies
    WHERE genres != '(no genres listed)'
    UNION ALL
    SELECT
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, 1, instr(remaining, '|') - 1)
            ELSE remaining
        END,
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, instr(remaining, '|') + 1)
            ELSE ''
        END
    FROM split_genres
    WHERE remaining != ''
),
unique_genres AS (
    SELECT DISTINCT trim(genre) AS genre
    FROM split_genres
    WHERE genre != '' AND genre IS NOT NULL
),
GenreRatings AS (
    SELECT
        ug.genre,
        ROUND(AVG(r.rating), 2) AS avg_rating,
        RANK() OVER (ORDER BY AVG(r.rating) DESC) AS genre_rank
    FROM unique_genres ug
    JOIN movies m ON m.genres LIKE '%' || ug.genre || '%'
    JOIN ratings r ON m.id = r.movie_id
    GROUP BY ug.genre
)
SELECT genre, avg_rating, genre_rank
FROM GenreRatings
WHERE avg_rating IS NOT NULL
ORDER BY genre_rank, genre;"

echo " "
echo "3. Посчитайте количество фильмов в каждом жанре. Выведите два столбца: genre и movie_count, отсортировав результат по убыванию количества фильмов."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres(genre, remaining) AS (
    SELECT
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, 1, instr(genres, '|') - 1)
            ELSE genres
        END,
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, instr(genres, '|') + 1)
            ELSE ''
        END
    FROM movies
    WHERE genres != '(no genres listed)'
    UNION ALL
    SELECT
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, 1, instr(remaining, '|') - 1)
            ELSE remaining
        END,
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, instr(remaining, '|') + 1)
            ELSE ''
        END
    FROM split_genres
    WHERE remaining != ''
),
unique_genres AS (
    SELECT DISTINCT trim(genre) AS genre
    FROM split_genres
    WHERE genre != '' AND genre IS NOT NULL
)
SELECT genre, COUNT(*) AS movie_count
FROM unique_genres ug
JOIN movies m ON m.genres LIKE '%' || ug.genre || '%'
GROUP BY ug.genre
ORDER BY movie_count DESC;"

echo " "
echo "4. Найдите жанры, в которых чаще всего оставляют теги (комментарии). Для этого подсчитайте общее количество записей в таблице tags для фильмов каждого жанра. Выведите genre, tag_count и долю этого жанра в общем числе тегов (tag_share), выраженную в процентах."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH RECURSIVE split_genres(genre, remaining) AS (
    SELECT
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, 1, instr(genres, '|') - 1)
            ELSE genres
        END,
        CASE
            WHEN instr(genres, '|') > 0 THEN substr(genres, instr(genres, '|') + 1)
            ELSE ''
        END
    FROM movies
    WHERE genres != '(no genres listed)'
    UNION ALL
    SELECT
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, 1, instr(remaining, '|') - 1)
            ELSE remaining
        END,
        CASE
            WHEN instr(remaining, '|') > 0 THEN substr(remaining, instr(remaining, '|') + 1)
            ELSE ''
        END
    FROM split_genres
    WHERE remaining != ''
),
unique_genres AS (
    SELECT DISTINCT trim(genre) AS genre
    FROM split_genres
    WHERE genre != '' AND genre IS NOT NULL
),
GenreTags AS (
    SELECT ug.genre, COUNT(t.id) AS tag_count
    FROM unique_genres ug
    JOIN movies m ON m.genres LIKE '%' || ug.genre || '%'
    JOIN tags t ON m.id = t.movie_id
    GROUP BY ug.genre
),
TotalTags AS (
    SELECT COUNT(*) AS total_count FROM tags
)
SELECT
    gt.genre,
    gt.tag_count,
    ROUND((gt.tag_count * 100.0 / tt.total_count), 2) AS tag_share
FROM GenreTags gt, TotalTags tt
ORDER BY gt.tag_count DESC;"

echo " "
echo "5. Для каждого пользователя рассчитайте: общее количество выставленных оценок, средний выставленный рейтинг, дату первой и последней оценки (по полю timestamp в таблице ratings). Выведите user_id, rating_count, avg_rating, first_rating_date, last_rating_date. Отсортируйте результат по убыванию количества оценок и выведите только 10 первых строк."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    u.id AS user_id,
    COUNT(r.id) AS rating_count,
    ROUND(AVG(r.rating), 2) AS avg_rating,
    datetime(MIN(r.timestamp), 'unixepoch') AS first_rating_date,
    datetime(MAX(r.timestamp), 'unixepoch') AS last_rating_date
FROM users u
JOIN ratings r ON u.id = r.user_id
GROUP BY u.id
ORDER BY rating_count DESC
LIMIT 10;"

echo " "
echo "6. Сегментируйте пользователей по типу поведения:
«Комментаторы» — пользователи, у которых количество тегов (tags) больше количества оценок (ratings),
«Оценщики» — наоборот, оценок больше, чем тегов,
«Активные» — и оценок, и тегов ≥ 10,
«Пассивные» — и оценок, и тегов < 5. Выведите user_id, общее число оценок, общее число тегов и категорию поведения. Используйте CASE."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH UserStats AS (
    SELECT
        u.id AS user_id,
        COUNT(r.id) AS rating_count,
        COUNT(t.id) AS tag_count
    FROM users u
    LEFT JOIN ratings r ON u.id = r.user_id
    LEFT JOIN tags t ON u.id = t.user_id
    GROUP BY u.id
)
SELECT user_id, rating_count, tag_count,
    CASE
        WHEN rating_count >= 10 AND tag_count >= 10 THEN 'Активные'
        WHEN rating_count < 5 AND tag_count < 5 THEN 'Пассивные'
        WHEN tag_count > rating_count THEN 'Комментаторы'
        WHEN rating_count > tag_count THEN 'Оценщики'
        ELSE 'Сбалансированные'
    END AS behavior_category
FROM UserStats
ORDER BY user_id;"

echo " "
echo "7. Для каждого пользователя выведите его имя и последний фильм, который он оценил (по времени из ratings.timestamp). Если пользователь не оценивал ни одного фильма, он всё равно должен быть в результате (с NULL в полях фильма). Результат: user_id, name, last_rated_movie_title, last_rating_timestamp."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "WITH UserLastRatings AS (
    SELECT
        u.id AS user_id,
        u.name,
        r.movie_id,
        r.timestamp,
        ROW_NUMBER() OVER (PARTITION BY u.id ORDER BY r.timestamp DESC) AS rn
    FROM users u
    LEFT JOIN ratings r ON u.id = r.user_id
)
SELECT ulr.user_id, ulr.name, m.title AS last_rated_movie_title,
       datetime(ulr.timestamp, 'unixepoch') AS last_rating_timestamp
FROM UserLastRatings ulr
LEFT JOIN movies m ON ulr.movie_id = m.id
WHERE ulr.rn = 1
ORDER BY ulr.user_id;"

echo " "
