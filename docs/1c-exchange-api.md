# API обмена каталога 1С → сайт (Portalfirma / Laravel)

Микросервис 1С по таймеру **пушит** данные на сайт. Сайт только принимает и делает upsert по `externalId`.

**Базовый URL (прод):** `https://<домен-сайта>/api/1c/exchange`  
**Локально:** `http://localhost:8000/api/1c/exchange`

---

## Авторизация

Поддерживается одно из:

1. **Basic Auth** — логин/пароль (`ONE_C_USER` / `ONE_C_PASSWORD`)
2. **Токен** — заголовок `x-1c-token: <ONE_C_TOKEN>` или `Authorization: Bearer <ONE_C_TOKEN>`

Без валидных учётных данных → HTTP `401`.

---

## Рекомендуемый формат: JSON

`POST /api/1c/exchange`  
`Content-Type: application/json`  
(+ Basic Auth или токен)

### Тело запроса

```json
{
  "sync": {
    "mode": "incremental",
    "since": "2026-08-06T10:00:00Z",
    "batchId": "uuid-сеанса"
  },
  "priceTypes": [
    { "externalId": "retail", "name": "Розничная", "isDefault": true }
  ],
  "categories": [
    {
      "externalId": "guid-or-code-category",
      "name": "Металлочерепица",
      "parentExternalId": "guid-parent-or-null",
      "slug": "krovlya/metallocherepitsa"
    }
  ],
  "products": [
    {
      "externalId": "guid-or-code-product",
      "name": "Металлочерепица Monterrey 0.5 RAL 8017",
      "sku": "MT-MONT-05-8017",
      "slug": "krovlya/metallocherepitsa/monterrey-05-ral-8017",
      "categoryExternalId": "guid-or-code-category",
      "price": 689,
      "currency": "RUB",
      "unit": "м²",
      "published": true,
      "attributes": [
        { "externalId": "attr-thickness", "name": "Толщина", "value": "0.5 мм" },
        { "name": "Цвет RAL", "value": "8017" }
      ],
      "prices": [
        { "priceTypeExternalId": "retail", "amount": 689, "currency": "RUB" }
      ]
    }
  ]
}
```

### Поля

| Поле | Обязательно | Комментарий |
|------|-------------|-------------|
| `externalId` | да | Стабильный GUID/код 1С. Ключ upsert |
| `name` | да | Наименование (не обновляется, если `name_locked` на сайте) |
| `sku` | желательно | Артикул |
| `categoryExternalId` | желательно | Привязка к группе |
| `price` / `prices` | да (для цен) | Розница минимум |
| `attributes` | по необходимости | Характеристики |
| `slug` | нет | Если нет — сайт сгенерирует из названия; существующий slug не меняется |
| `description` / `imageUrl` | только если поле на сайте пустое | Контент админки не затирается |
| `stock` | **не нужно** | Остатки не синхронизируем |
| `seo_*` | **не слать** | SEO ведётся в админке |

### Ответ успеха

```json
{
  "ok": true,
  "stats": {
    "categories": 3,
    "products": 12,
    "prices": 12,
    "attributes": 40,
    "priceTypes": 1
  }
}
```

Ошибка: `{ "ok": false, "error": "..." }` + HTTP `400`.

Логи сеансов пишутся в таблицу `sync_logs` (Filament → Логи синка).

---

## Полная и инкрементальная выгрузка

1. Полная выгрузка — **только вручную** (`sync.mode = "full"`).
2. По таймеру — **только изменённые** с прошлого успешного сеанса (`sync.mode = "incremental"`).
3. Размер пакета дробить (500–1000 товаров), если дельта большая; каждый пакет — отдельный запрос с одним `batchId`.

Сайт обрабатывает оба режима одинаково — **upsert по `externalId`**:

- запись есть → обновляются цена, цены по типам, характеристики, категория, артикул, единица; **slug/URL не меняется**;
- записи нет → создаётся (slug из `slug` или транслита названия, уникальность гарантируется);
- записи, которых нет в пакете, **не удаляются и не скрываются** — поэтому частичный пакет безопасен;
- повторная отправка того же пакета ничего не дублирует (идемпотентно).

Что **не перезаписывается** из 1С, если уже заполнено в админке: `description`, `imageUrl`, все `seo_*`,
`name` при включённом флаге «Имя защищено от 1С», флаги видимости (`published`, показ цены/наличия).

Чтобы снять товар с продажи — выключить его в админке (`is_published`), а не удалять из выгрузки.

Поле `sync` используется для логирования (`mode`, `batchId`) и видно в админке.

---

## Сопоставление с существующим каталогом сайта

На сайте уже есть **241 категория** со старыми URL и SEO-текстами (`slug`, например `krovlya/metallocherepitsa`),
у них пока пустой `external_id`. Перед первым полным импортом:

- проставить существующим категориям `external_id` = код/GUID группы 1С (админка → Каталог → Категории → поле «1C ID», либо SQL),
- тогда синк обновит их, а не создаст дубли; URL и тексты сохранятся.

Если структура групп в 1С другая и URL меняются — старые адреса закрываются 301 (админка → SEO → Редиректы).

---

## Ошибки и логи

| HTTP | Когда |
|------|-------|
| 401 | нет/неверные учётные данные |
| 400 | не JSON, пустое тело, нет `categories`/`products`/`priceTypes`, у элемента нет `externalId` или `name`, ошибка при обработке |
| 200 | пакет применён, в ответе `stats` |

Любой запрос (успех или ошибка) пишется в `sync_logs` и виден в админке: **Система → Логи синка**
(статус, режим, `batchId`, статистика, текст ошибки, hash пакета). При ошибке валидации данные в БД не меняются.

Тесты обмена: `tests/Feature/OneCExchangeTest.php` (`php artisan test`).

---

## Если используется стандартный модуль обмена (CommerceML)

Сайт сейчас принимает **JSON**. Если 1С будет отдавать CommerceML (`checkauth` → `init` → `file` → `import`,
файлы `import.xml` / `offers.xml`), нужно:

1. добавить контроллер протокола (GET/POST `?type=catalog&mode=...`) с той же авторизацией `onec.auth`;
2. распарсить XML в массив формата этого документа (`categories`, `products`, `priceTypes`);
3. вызвать `app(App\Services\OneC\CatalogSyncService::class)->upsertCatalog($payload)` и записать `SyncLog`.

Логика upsert, защиты контента админки, слагов и логов уже реализована — её менять не требуется.

---

## Что синхронизируем / что нет

| Данные | Синк из 1С |
|--------|------------|
| Структура каталога (группы) | да |
| Номенклатура / ТП | да |
| Цены (типы цен) | да |
| Характеристики для карточки | да |
| Остатки | **нет** |
| Фото, описания, SEO meta | **нет** (если уже заполнены в админке) |
| published/скрытие | задаётся при создании; далее управляется на сайте |

---

## Пример curl

```bash
curl -u 'USER:PASSWORD' \
  -H 'Content-Type: application/json' \
  -d @packet.json \
  'http://localhost:8000/api/1c/exchange'
```

Или с токеном:

```bash
curl -H 'x-1c-token: YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d @packet.json \
  'http://localhost:8000/api/1c/exchange'
```
