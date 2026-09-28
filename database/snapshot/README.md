# Снимок контента

JSON-дамп контентных таблиц (каталог, страницы, SEO, редиректы).
Создаётся командой `php artisan content:export-snapshot`, восстанавливается
`php artisan content:import-snapshot` на любой БД (MySQL / SQLite).

Не содержит пользователей, заявок, логов синхронизации и медиа.