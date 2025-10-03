import csv

def parse_movie_title(title):
    import re

    pattern = r'^(.*?)\s*\((\d{4})\)\s*$'
    match = re.match(pattern, title)
    if match:
        movie_title = match.group(1).strip()
        year = match.group(2).strip()
        return movie_title, year
    else:
        return title, 'NULL'

script = 'db_init.sql'

tables = {
    'movies': {
        'fields': [
            'id INTEGER PRIMARY KEY',
            'title TEXT',
            'year INTEGER',
            'genres TEXT',
        ],
        'file': 'dataset/movies.csv',
    },
    'ratings': {
        'fields': [
            'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'user_id INTEGER',
            'movie_id INTEGER',
            'rating REAL',
            'timestamp INTEGER',
        ],
        'file': 'dataset/ratings.csv',
    },
    'users': {
        'fields': [
            'id INTEGER PRIMARY KEY',
            'name TEXT',
            'email TEXT',
            'gender TEXT',
            'register_date DATETIME',
            'speciality TEXT',
        ],
        'file': 'dataset/users.txt',
    },
    'tags': {
        'fields': [
            'id INTEGER PRIMARY KEY AUTOINCREMENT',
            'user_id INTEGER',
            'movie_id INTEGER',
            'tag TEXT',
            'timestamp INTEGER',
        ],
        'file': 'dataset/tags.csv',
    },
}

for table in tables:
    with open(script, 'w') as f:
        for table_name, table_data in tables.items():
            f.write(f'DROP TABLE IF EXISTS {table_name};\n')
            f.write(f'CREATE TABLE IF NOT EXISTS {table_name} ({','.join(table_data['fields'])});\n')

        for table_name, table_data in tables.items():
            with open(table_data['file'], 'r') as file:
                if table_name in ['users', 'genres', 'occupation']:
                    reader = csv.reader(file, delimiter='|')
                else:
                    reader = csv.reader(file)
                    headers = next(reader)
                for row in reader:
                    row = [elem.replace("'", "''") for elem in row]
                    if table_name == 'movies':
                        title, year = parse_movie_title(row[1])
                        row = [row[0], title, year, row[2]]
                    if table_name == 'tags' or table_name == 'ratings':
                        f.write(f'INSERT INTO {table_name} ({','.join([i.split(' ')[0] for i in table_data['fields'][1::]])}) VALUES ({','.join(f"'{item}'" for item in row)});\n')
                    else:
                        f.write(f'INSERT INTO {table_name} VALUES ({','.join(f"'{item}'" for item in row)});\n')
