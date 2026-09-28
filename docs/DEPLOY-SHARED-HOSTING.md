# Деплой Portalfirma (Laravel) на shared hosting

VPS не обязателен: достаточно обычного PHP-хостинга (Beget и аналоги) с PHP 8.2+ и MySQL.

## Document root

Укажите document root на каталог **`public/`** приложения:

```
.../laravel/public
```

Нельзя отдавать корень репозитория — иначе будут доступны `.env` и исходники.

## Требования

- PHP **8.2+** (расширения: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`)
- MySQL 5.7+ / 8.x (или MariaDB)
- Composer (локально или на сервере) для `composer install --no-dev`

## `.env` (MySQL)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://portalfirma.ru

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

ONE_C_USER=...
ONE_C_PASSWORD=...
ONE_C_TOKEN=...
```

Локально можно оставить `DB_CONNECTION=sqlite` (см. README).

## Шаги после загрузки файлов

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force              # админ (admin@portalfirma.local / password) + SEO-шаблоны
php artisan content:import-snapshot      # контент сайта: каталог, страницы, покрытия, объекты
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Права: `storage/` и `bootstrap/cache/` должны быть writable веб-сервером.
После первого входа смените пароль администратора.

## Картинки

Все используемые картинки контента (категории, покрытия, калькулятор, акции) лежат в репозитории
в `public/images` и `public/templates` — отдельно ничего копировать не нужно.
Загружаемые через админку файлы попадают в `storage/app/public` (нужен `php artisan storage:link`).

## Админка и обмен 1С

- Админка Filament: `https://домен/admin`
- Обмен каталога: `POST https://домен/api/1c/exchange` (Basic Auth или `x-1c-token`), см. `docs/1c-exchange-api.md`
- Учётные данные обмена: `ONE_C_USER` / `ONE_C_PASSWORD` или `ONE_C_TOKEN` в `.env`

## Что не нужно

- Отдельный Node/VPS для фронта, сборка ассетов (CSS/JS статические)
- Очереди-демоны: `QUEUE_CONNECTION=database` используется только для отложенных задач, обмен работает синхронно
