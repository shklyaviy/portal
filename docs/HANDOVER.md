# Передача проекта: что сделано и что осталось

Документ для разработчика, который подключает синхронизацию с 1С.
Сайт, админка, SEO и API приёма данных готовы; **не сделана только выгрузка на стороне 1С**.

## Статус по пунктам стартового брифа

| # | Требование | Статус | Где смотреть |
|---|-----------|--------|--------------|
| 1 | Laravel + MySQL | ✅ Laravel 12, Filament 3, MySQL (локально SQLite) | `composer.json`, `.env.example` |
| 2 | Синхронизация структуры, номенклатуры, цен; полная — вручную, авто — только изменённые | ✅ на стороне сайта: `POST /api/1c/exchange` делает **upsert по `externalId`**, ничего не удаляет, одинаково принимает полный и частичный пакет (`sync.mode` = `full` / `incremental`). Остатки не синхронизируем (решение заказчика). ⏳ Сам пуш из 1С — задача разработчика 1С | `app/Http/Controllers/Api/OneCExchangeController.php`, `app/Services/OneC/CatalogSyncService.php`, тесты `tests/Feature/OneCExchangeTest.php` |
| 3 | Торговые предложения с отдельным URL/описанием/мета | ✅ каждое ТП передаётся отдельной записью `products[]` со своим `externalId` → отдельный URL, описание, SEO. Группировка ТП под общей карточкой пока не нужна; при необходимости добавляется поле `parent_product_id` | `docs/1c-exchange-api.md` |
| 4 | 301-редиректы старых URL на новые | ✅ таблица `redirects` + middleware; управление в админке (SEO → Редиректы); **при смене slug в админке 301 создаётся автоматически**; slug при синхронизации никогда не меняется | `app/Http/Middleware/HandleRedirects.php`, `app/Models/Concerns/RedirectsOnSlugChange.php`, `tests/Feature/RedirectTest.php` |
| 5 | Показ/скрытие цен и наличия по группам, массово | ✅ флаги `show_prices` / `show_availability` у категорий и товаров, массовые действия в таблицах админки | `app/Filament/Resources/CategoryResource.php`, `ProductResource.php` |
| 6 | Наполнение из админки, визуальный редактор, данные не теряются при синке | ✅ RichEditor для товаров, категорий, страниц, новостей, объектов, покрытий. Синк заполняет описание/фото **только если поле пустое**, SEO-поля не трогает, имя можно «запереть» (`name_locked`) | `CatalogSyncService::upsertProduct()` |
| 7 | Выводятся все товары независимо от наличия; URL не зависит от остатка; в наличии — первыми | ✅ скрывается только `is_published = false`; сортировка `is_in_stock desc, sort_order, name` | `CatalogController::show()` |
| 8 | sitemap, robots, микроразметка, canonical, ресайзы, alt, адаптив | ✅ `/sitemap.xml`, `/robots.txt` генерируются; JSON-LD (Product, Offer, CollectionPage, BreadcrumbList); canonical в layout; ресайз GD 1600px + превью 400px при загрузке; alt = название; адаптивная вёрстка | `SitemapController`, `RobotsController`, `app/Support/ImageResizer.php`, `resources/views/layouts/app.blade.php` |
| 9 | title/meta для категорий и товаров + шаблоны | ✅ ручные поля `seo_title`, `seo_description`, `seo_h1` + шаблоны с переменными `[name] [sku] [category] [price]` (также `{name}` / `%name%`). Ручное значение имеет приоритет | SEO → SEO шаблоны, `app/Support/SeoResolver.php`, `tests/Feature/SeoTemplateTest.php` |
| 10 | Логирование сеансов, устойчивость к битым файлам | ✅ каждый запрос пишется в `sync_logs` (успех/ошибка, режим, batchId, статистика, hash пакета) и виден в админке (Система → Логи синка); битый JSON / отсутствие обязательных полей → HTTP 400 без изменений в БД; исключения перехватываются | `OneCExchangeController`, `SyncLogResource` |
| 11 | Разработка через git | ✅ этот репозиторий | — |
| 12 | Админка на 15 000 позиций, быстрый поиск, сортировки | ✅ серверная пагинация 25/50/100, поиск по названию/артикулу/ID 1С, сортировки, глобальный поиск в шапке, фильтры | `ProductResource` |
| 13 | Shared-хостинг без VPS | ✅ PHP 8.2 + MySQL, document root `public/`, без Node/очередей-демонов | `docs/DEPLOY-SHARED-HOSTING.md` |

Дополнительно к брифу:

- Витрина полностью переверстана (главная, каталог с левым деревом и поиском, карточки товаров/категорий, покрытия, объекты, калькулятор, контакты).
- Приём заявок с форм → таблица `leads` (CRM → Заявки).
- Страницы `О компании`, `Контакты`, услуги редактируются в админке (Контент → Страницы).
- Весь контент старого сайта перенесён: 241 категория с текстами, 11 покрытий, 12 объектов с галереями, 13 страниц.

## Что нужно сделать разработчику 1С-синхронизации

1. **Реализовать выгрузку из 1С** в формате из `docs/1c-exchange-api.md` (JSON, `POST /api/1c/exchange`, Basic Auth или заголовок `x-1c-token`).
   - Полная выгрузка — по кнопке/вручную (`sync.mode = "full"`).
   - По расписанию — только изменённые с прошлого успешного сеанса (`sync.mode = "incremental"`).
   - Пакеты дробить по 500–1000 товаров.
   - `externalId` = стабильный GUID/код 1С. Это ключ; по нему сайт обновляет запись.
2. **Если используется стандартный модуль обмена CommerceML** (`checkauth` → `init` → `file` → `import`) вместо JSON — добавить контроллер протокола, распарсить `import.xml` / `offers.xml` в массив того же формата и передать в `CatalogSyncService::upsertCatalog()`. Вся логика upsert, защиты контента админки, слагов и логов уже там; менять её не нужно.
3. **Сопоставить структуру групп 1С с существующими категориями сайта.** На сайте 241 категория с SEO-текстами и URL старого сайта (поле `slug`, `external_id` пока пустой). Чтобы синк «попал» в них, а не создал дубли:
   - либо проставить `external_id` существующим категориям (в админке или SQL) = ID группы в 1С,
   - либо в выгрузке передавать `slug` существующей категории — при первом импорте запись найдётся по `external_id`, а slug сохранится.
   - Если структура 1С другая — старые URL закрываются 301-редиректами (SEO → Редиректы, поддерживается массовый ввод через админку/SQL).
4. **Учётные данные**: задать `ONE_C_USER` / `ONE_C_PASSWORD` или `ONE_C_TOKEN` в `.env` на сервере.
5. **Проверка**: после первого пакета смотреть Система → Логи синка (статус, статистика), витрину и тесты `php artisan test`.

Не входит в задачу синка и на сайте намеренно не реализовано: остатки (решение заказчика), передача заказов с сайта в 1С (отдельный этап).

## Развёртывание с контентом

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force              # админ, SEO-шаблоны
php artisan content:import-snapshot      # контент сайта
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Подробнее: `docs/DEPLOY-SHARED-HOSTING.md`.

## Ключевые точки в коде

| Что | Файл |
|-----|------|
| Приём пакета 1С, валидация, логи | `app/Http/Controllers/Api/OneCExchangeController.php` |
| Upsert категорий/товаров/цен/характеристик | `app/Services/OneC/CatalogSyncService.php` |
| Авторизация обмена | `app/Http/Middleware/AuthenticateOneC.php`, `config/services.php` → `onec` |
| Витрина каталога, поиск | `app/Http/Controllers/CatalogController.php`, `app/Support/CatalogNav.php` |
| SEO-шаблоны | `app/Support/SeoResolver.php` |
| 301-редиректы | `app/Http/Middleware/HandleRedirects.php`, `app/Models/Concerns/RedirectsOnSlugChange.php` |
| Схема БД | `database/migrations/2026_08_06_210000_create_catalog_tables.php` |
| Снимок контента | `app/Console/Commands/ExportContentSnapshot.php`, `ImportContentSnapshot.php` |
