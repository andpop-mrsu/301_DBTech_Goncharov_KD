-- Добавление пяти новых пользователей (себя и четырех соседей по группе)
INSERT INTO users (name, email, gender, register_date, occupation_id)
VALUES
('Константин Гончаров', 'k.goncharov@mail.com', 'male', date('now'),  (SELECT id FROM occupations WHERE name = 'student')),
('Павел Голиков', 'p.golikov@mail.com', 'male', date('now'), (SELECT id FROM occupations WHERE name = 'student')),
('Алина Буянкина', 'a.buyankina@mail.com', 'female', date('now'), (SELECT id FROM occupations WHERE name = 'student')),
('Вадим Еремин', 'v.eremin@mail.com', 'male', date('now'), (SELECT id FROM occupations WHERE name = 'student')),
('Илья Журин', 'i.zhurin@mail.com', 'male', date('now'), (SELECT id FROM occupations WHERE name = 'student'));

-- Добавление фильмов
INSERT INTO movies (title, year)
VALUES
('Космическая одиссея', 2024),
('Тайна старого замка', 2024),
('Смех сквозь слезы', 2024);

INSERT INTO movies_genres (movie_id, genre_id)
VALUES
((SELECT id FROM movies WHERE title = 'Космическая одиссея'),
 (SELECT id FROM genres WHERE name = 'Sci-Fi')),
((SELECT id FROM movies WHERE title = 'Космическая одиссея'),
 (SELECT id FROM genres WHERE name = 'Adventure')),

((SELECT id FROM movies WHERE title = 'Тайна старого замка'),
 (SELECT id FROM genres WHERE name = 'Mystery')),
((SELECT id FROM movies WHERE title = 'Тайна старого замка'),
 (SELECT id FROM genres WHERE name = 'Drama')),

((SELECT id FROM movies WHERE title = 'Смех сквозь слезы'),
 (SELECT id FROM genres WHERE name = 'Comedy')),
((SELECT id FROM movies WHERE title = 'Смех сквозь слезы'),
 (SELECT id FROM genres WHERE name = 'Romance'));

-- Добавление отзывов
INSERT INTO ratings (user_id, movie_id, rating, timestamp)
VALUES
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Космическая одиссея'), 4.6, strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Тайна старого замка'), 4.8, strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Смех сквозь слезы'), 4.3, strftime('%s', 'now'));

-- Добавление тегов
INSERT INTO tags (user_id, movie_id, tag, timestamp)
VALUES
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Космическая одиссея'), 'эпичный космос будущее', strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Тайна старого замка'), 'загадочный детектив тайна', strftime('%s', 'now')),
((SELECT id FROM users WHERE email = 'k.goncharov@mail.com'),
 (SELECT id FROM movies WHERE title = 'Смех сквозь слезы'), 'романтичный комедия чувства', strftime('%s', 'now'));