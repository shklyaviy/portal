# Portalfirma — сайт кровельного центра «Портал»

Готовый сайт на **Laravel 12 + Filament 3 + MySQL** (локально — SQLite): витрина, каталог,
админка с визуальным редактором, SEO, приём заявок и **API для обмена с 1С**.

Осталось подключить только выгрузку из 1С на стороне 1С — см. [`docs/HANDOVER.md`](docs/HANDOVER.md)
и [`docs/1c-exchange-api.md`](docs/1c-exchange-api.md).

## Стек

| Часть | Технология |
|-------|------------|
| Бэкенд / витрина | Laravel 12, Blade, обычный CSS/JS (`public/css`, `public/js`), без сборки |
| Админка | Filament 3 — `/admin` |
| БД | MySQL 5.7+/8 (прод), SQLite (локально и тесты) |
| Медиа | `storage/app/public` + ресайз GD при загрузке |
| Обмен 1С | `POST /api/1c/exchange` (Basic Auth или токен), JSON, upsert по `externalId` |
| Хостинг | обычный shared-хостинг, document root = `public/` |

## Требования

- PHP **8.2+** с расширениями `pdo_mysql`/`pdo_sqlite`, `mbstring`, `gd`, **`intl`** (обязательно для таблиц админки Filament), `xml`, `fileinfo`
- Composer 2
- MySQL 5.7+/8 на проде (локально достаточно SQLite)

## Быстрый старт (локально)

```bash
composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite          # локально SQLite
php artisan migrate
php artisan db:seed                     # админ + SEO-шаблоны + пилотные товары
php artisan content:import-snapshot     # весь контент сайта (каталог, страницы, покрытия, объекты)
php artisan storage:link

php artisan serve                       # http://127.0.0.1:8000
```

- Витрина: http://127.0.0.1:8000/
- Админка: http://127.0.0.1:8000/admin — `admin@portalfirma.local` / `password` (смените после первого входа)

Для MySQL пропишите `DB_*` в `.env` (пример в `.env.example`) — команды те же.

## Команды

| Команда | Что делает |
|---------|------------|
| `php artisan content:import-snapshot` | Загружает контент из `database/snapshot/*.json` (идемпотентно, работает на MySQL и SQLite) |
| `php artisan content:export-snapshot` | Обновляет снимок контента из текущей БД |
| `php artisan content:import-legacy [--force]` | Повторный импорт исходного контента старого сайта из `database/legacy-content` |
| `php artisan catalog:rebuild-tree` | Восстанавливает `parent_id` категорий из slug-путей (после legacy-импорта) |
| `php artisan test` | Тесты: обмен 1С, редиректы, SEO-шаблоны, снимок контента, витрина |
| `./scripts/pilot-1c-sync.sh` | Тестовый пакет в API обмена (нужны `ONE_C_*` в `.env`) |

## Структура

```
app/Http/Controllers/          витрина (Catalog, Page, Home, Lead, Sitemap, Robots)
app/Http/Controllers/Api/      OneCExchangeController — приём данных из 1С
app/Services/OneC/             CatalogSyncService — upsert каталога (точка расширения для 1С)
app/Http/Middleware/           AuthenticateOneC, HandleRedirects (301)
app/Filament/Resources/        админка: товары, категории, страницы, новости, объекты,
                               покрытия, SEO-шаблоны, редиректы, заявки, логи синхронизации
app/Support/                   SeoResolver (шаблоны [name]…), CatalogNav, HtmlContent, ImageResizer, Slug
resources/views/               Blade-шаблоны витрины
public/css, public/js          стили и скрипты (без сборщика)
public/images, public/templates картинки контента, покрытий, калькулятора, акций
database/migrations/           схема БД
database/snapshot/             снимок контента для развёртывания
database/legacy-content/       исходный JSON-экспорт старого сайта
docs/                          передача проекта, API 1С, деплой
tests/Feature/                 автотесты
```

## URL-схема (сохранена со старого сайта)

- `/catalog.html`, `/catalog/{путь}.html` — категории и товары (slug хранит полный путь)
- `/pokrytiya/`, `/pokrytiya/{slug}.html` — покрытия
- `/projects.html`, `/projects/{slug}.html` — объекты
- `/news.html`, `/news/{slug}.html` — новости
- `/about.html`, `/contacts.html`, `/service/{slug}.html` — страницы (редактируются в админке)
- `/sitemap.xml`, `/robots.txt` — генерируются автоматически
- Любой старый URL можно перенаправить через админку → SEO → Редиректы; при смене slug 301 создаётся сам

## Документация

- [`docs/HANDOVER.md`](docs/HANDOVER.md) — что сделано по каждому пункту ТЗ и что осталось (1С)
- [`docs/1c-exchange-api.md`](docs/1c-exchange-api.md) — формат обмена и требования к выгрузке
- [`docs/DEPLOY-SHARED-HOSTING.md`](docs/DEPLOY-SHARED-HOSTING.md) — развёртывание на shared-хостинге
