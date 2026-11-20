# ЛР №8 — REST API та структуровані формати даних

## Запуск
У папці проєкту: `php -S localhost:8000` (для preview.php на Linux/macOS краще `PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000`, або XAMPP/хостинг; на Windows з php -S preview.php автоматично виконає json.php локально).
- index.php — завдання 1–3: JSON з http://lab.vntu.org/api-server/lab8.php?user=student&pass=p@ssw0rd → json_decode → таблиця (потрібен Інтернет)
- json.php — анкети з папки survey/ (читає .json і .txt) у форматі JSON
- preview.php — отримує json.php за URL і виводить таблицею

Щоб показати реальні дані ЛР №6, замініть вміст папки survey/ файлами з вашого сайту (або змініть SURVEY_DIR в includes/common.php). У survey/ зараз тестові дані: 2 файли .json та 2 файли .txt.
