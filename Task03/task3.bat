#!/bin/bash
chcp 65001

sqlite3 movies_rating.db < db_init.sql

echo " "
echo "1. Составить список фильмов, имеющих хотя бы одну оценку. Список фильмов отсортировать по году выпуска и по названиям. В списке оставить первые 10 фильмов."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT movies.id, movies.title, movies.year
FROM movies
WHERE EXISTS (SELECT 1 FROM ratings WHERE ratings.movie_id = movies.id)
ORDER BY movies.year, movies.title
LIMIT 10;"

echo " "
echo "2. Вывести список всех пользователей, фамилии (не имена!) которых начинаются на букву 'A'. Полученный список отсортировать по дате регистрации. В списке оставить первых 5 пользователей."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT users.id, users.name, users.email, users.gender, users.register_date, users.speciality
FROM users
WHERE SUBSTR(users.name, INSTR(users.name, ' ') + 1) LIKE 'A%'
ORDER BY users.register_date
LIMIT 5;"

echo " "
echo "3. Написать запрос, возвращающий информацию о рейтингах в более читаемом формате: имя и фамилия эксперта, название фильма, год выпуска, оценка и дата оценки в формате ГГГГ-ММ-ДД. Отсортировать данные по имени эксперта, затем названию фильма и оценке. В списке оставить первые 50 записей."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    users.name AS expert_name,
    movies.title AS movie_title,
    movies.year AS release_year,
    ratings.rating,
    date(datetime(ratings.timestamp, 'unixepoch')) AS rating_date
FROM ratings
JOIN users ON ratings.user_id = users.id
JOIN movies ON ratings.movie_id = movies.id
ORDER BY users.name, movies.title, ratings.rating
LIMIT 50;"

echo " "
echo "4. Вывести список фильмов с указанием тегов, которые были им присвоены пользователями. Сортировать по году выпуска, затем по названию фильма, затем по тегу. В списке оставить первые 40 записей."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    movies.title,
    movies.year,
    tags.tag,
    users.name AS user_name
FROM tags
JOIN movies ON tags.movie_id = movies.id
JOIN users ON tags.user_id = users.id
ORDER BY movies.year, movies.title, tags.tag
LIMIT 40;"

echo " "
echo "5. Вывести список самых свежих фильмов. В список должны войти все фильмы последнего года выпуска, имеющиеся в базе данных. Запрос должен быть универсальным, не зависящим от исходных данных."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    movies.title,
    CASE
        WHEN movies.year = 'NULL' OR movies.year IS NULL THEN 'Не указан'
        ELSE movies.year
    END AS year,
    movies.genres
FROM movies
WHERE movies.year = (SELECT MAX(CAST(movies.year AS INTEGER)) FROM movies WHERE movies.year != 'NULL' AND movies.year IS NOT NULL)
ORDER BY movies.title;"

echo " "
echo "6. Найти все комедии, выпущенные после 2000 года, которые понравились мужчинам (оценка не ниже 4.5). Для каждого фильма в этом списке вывести название, год выпуска и количество таких оценок. Результат отсортировать по году выпуска и названию фильма."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    movies.title,
    movies.year,
    COUNT(*) AS high_ratings_count
FROM movies
JOIN ratings ON movies.id = ratings.movie_id
JOIN users ON ratings.user_id = users.id
WHERE movies.genres LIKE '%Comedy%'
    AND movies.year > 2000
    AND ratings.rating >= 4.5
    AND users.gender = 'male'
GROUP BY movies.id
ORDER BY movies.year, movies.title;"

echo " "
echo "7. Провести анализ занятий (профессий) пользователей - вывести количество пользователей для каждого рода занятий. Найти самую распространенную и самую редкую профессию посетителей сайта."
echo "--------------------------------------------------"
sqlite3 movies_rating.db -box -echo "SELECT
    users.speciality AS profession,
    COUNT(*) AS user_count
FROM users
GROUP BY users.speciality
ORDER BY user_count DESC;"

echo " "
echo "Самая распространенная профессия:"
sqlite3 movies_rating.db -box -echo "SELECT
    speciality AS profession,
    COUNT(*) AS user_count
FROM users
GROUP BY users.speciality
ORDER BY user_count DESC
LIMIT 1;"

echo " "
echo "Самая редкая профессия:"
sqlite3 movies_rating.db -box -echo "SELECT
    speciality AS profession,
    COUNT(*) AS user_count
FROM users
GROUP BY users.speciality
ORDER BY user_count ASC
LIMIT 1;"

echo " "
