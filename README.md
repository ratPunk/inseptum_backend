# inseptum_backend

PHP backend для проекта *inseptum*. Чистая MVC-подобная архитектура
(Router → Controller → Service → Repository → Model). Весь legacy-код
([`index.php`](index.php) в корне, [`controller.php`](controller.php),
папка `api/` с процедурными функциями, [`convert.php`](convert.php),
[`logger.php`](logger.php)) — удалён. Точка входа одна:
[`public/index.php`](public/index.php:1).

> Этот README рассчитан в том числе на то, чтобы по нему можно было
> переписать фронтенд под текущий backend без чтения PHP-кода.
> Полный контракт API — в разделе [«HTTP API»](#http-api).

---

## Оглавление

- [Быстрый старт](#быстрый-старт)
- [Базовый URL и заголовки](#базовый-url-и-заголовки)
- [Формат ответа и ошибок](#формат-ответа-и-ошибок)
- [Авторизация](#авторизация)
- [Загрузка файлов](#загрузка-файлов)
- [HTTP API](#http-api)
  - [Module Types](#module-types)
  - [Modules](#modules)
  - [Topics](#topics)
  - [Articles](#articles)
  - [Article File / Read progress](#article-file--read-progress)
  - [Tests](#tests)
  - [Test File / Results / Passed](#test-file--results--passed)
  - [Tasks](#tasks)
  - [Favorites](#favorites)
  - [Auth](#auth)
- [Структура проекта](#структура-проекта)
- [Как добавить новую сущность](#как-добавить-новую-сущность)

---

## Быстрый старт

1. Установите Composer-зависимости и регенерируйте автолоадер:

   ```bash
   composer install
   composer dump-autoload
   ```

2. Заполните доступы к БД в [`config/database.php`](config/database.php:1)
   (файл просто возвращает массив, соединение там не открывается).

3. Apache должен быть с `mod_rewrite`. Корневой
   [`.htaccess`](.htaccess:1) проксирует все запросы на
   [`public/index.php`](public/index.php:1) с параметром `?url=...`,
   чтобы приложение корректно работало в подпапке (например, MAMP:
   `http://localhost/inseptum_backend/...`).

4. Импортируйте дамп БД из [`inseptum.sql`](inseptum.sql:1).
   Для уже существующих БД отдельно прогоните миграции из папки
   [`migrations/`](migrations:1) (все скрипты идемпотентны —
   `CREATE TABLE IF NOT EXISTS`).

5. Проверка:

   ```
   GET  http://localhost/inseptum_backend/modules
   GET  http://localhost/inseptum_backend/modules/1
   POST http://localhost/inseptum_backend/login
   ```

---

## Базовый URL и заголовки

- **Base URL (dev/MAMP):** `http://localhost/inseptum_backend`
- Все ответы — `Content-Type: application/json; charset=utf-8`.
- Тело запроса принимается в одном из форматов
  (см. [`Request::fromGlobals()`](src/Http/Request.php:27)):
  - `application/json` — рекомендованный для POST без файлов.
  - `application/x-www-form-urlencoded` — поддерживается (`$_POST`).
  - `multipart/form-data` — обязательно для эндпоинтов с загрузкой
    файлов (статьи, тесты).
- Query string parameters — обычные `?key=value`, ключ `url`
  зарезервирован роутером и игнорируется в `query()`.

> CORS-заголовки в коде сейчас **не выставляются**. Если фронт
> поднимается на другом origin, нужно либо настроить CORS в
> Apache/`.htaccess`, либо добавить middleware. Это TODO для
> backend-команды.

---

## Формат ответа и ошибок

Все контроллеры (за исключением `POST /checktask`, см. ниже) отдают
JSON одного из двух видов через
[`AbstractController`](src/Http/Controllers/AbstractController.php:1).

### Успех

```json
{
  "status": true,
  "message": "Модули найдены",
  "data": [ /* payload */ ],
  "count": 12
}
```

- `status` — всегда `true` при HTTP 2xx.
- `message` — человекочитаемое сообщение (на русском).
- `data` — основной payload (объект, массив, строка или `null`).
  Поле может отсутствовать, если сервис ничего не возвращает.
- Дополнительные поля (`count`, …) добавляются «в корень» ответа,
  а не внутрь `data` — это важно для списочных эндпоинтов.

### Ошибка

```json
{
  "status": false,
  "message": "Не все поля заполнены",
  "errors": {
    "title": "Название модуля обязательно"
  }
}
```

[`Application::handleException()`](src/Core/Application.php:70)
маппит исключения на HTTP-статусы:

| Исключение | HTTP | Поле `errors` |
|---|---|---|
| [`ValidationException`](src/Exceptions/ValidationException.php:1) | 422 | да (поле → сообщение) |
| [`NotFoundException`](src/Exceptions/NotFoundException.php:1)     | 404 | нет |
| [`UnauthorizedException`](src/Exceptions/UnauthorizedException.php:1) | 401 | нет |
| [`ConflictException`](src/Exceptions/ConflictException.php:1)     | 409 | нет |
| любая `Throwable`                                                 | 500 | нет (логируется) |

Кроме того контроллеры могут вернуть `400` напрямую (через
`error()`) для базовых проверок типа «не передан id».

### Особый случай: `POST /checktask`

Возвращает **legacy-формат** без обёртки:

```json
{ "success": true, "message": "ИИ проверил задачу '...': ..." }
```

или при ошибке валидации/исключении — стандартный
`{status:false, message:..., errors?:...}`.

---

## Авторизация

> ⚠️ В текущей версии **нет JWT, нет cookie-сессий, нет middleware
> авторизации**. Любой эндпоинт доступен без заголовков. «Кто
> делает запрос» определяется по полю `user_id` в теле запроса
> (передаёт сам фронт после логина).

- `POST /login` — обычный пользователь. Возвращает в `data`:
  ```json
  { "user_id": 7, "username": "alice", "created_at": "2025-01-01 12:00:00" }
  ```
- `POST /adminlogin` — администратор. Возвращает в `data`:
  ```json
  { "user_id": 1, "username": "admin" }
  ```
- `POST /register` — регистрирует пользователя, возвращает то же,
  что и `/login`, но HTTP 201.

Фронт должен:
1. Сохранить `user_id` (например, в `localStorage`) после логина.
2. Передавать `user_id` в теле тех запросов, где он требуется
   (см. колонку «Body» в таблицах ниже).
3. Отличие админских действий (создание/правка/удаление модулей,
   тем, статей, тестов) от пользовательских **на уровне API не
   проверяется** — это TODO. Фронт должен сам прятать UI от
   неадминов.

---

## Загрузка файлов

Эндпоинты, принимающие файл, требуют `multipart/form-data`. Имя
поля файла в форме — **`file`** (см. `$request->file('file')` в
[`ArticleController`](src/Http/Controllers/ArticleController.php:48)
и [`TestController`](src/Http/Controllers/TestController.php:38)).

| Эндпоинт | Поле формы | Допустимые расширения | Куда сохраняется |
|---|---|---|---|
| `POST /createarticle`, `POST /updatearticle` | `file` | `.docx` | [`articlesFolder/`](articlesFolder) |
| `POST /createtest`, `POST /updatetest`, `POST /tests`, `POST /tests/{id}` | `file` | `.json` (валидный JSON-массив вопросов) | [`testsFolder/`](testsFolder) |

Остальные поля передаются в той же `FormData` как обычные строки
(`title`, `description`, `topic`, `topic_id`, `time_limit` и т.д.).

Пример (JS):

```js
const fd = new FormData();
fd.append('title', 'Подключение Bootstrap');
fd.append('description', 'Краткое описание');
fd.append('topic', '3');                 // topic_id
fd.append('file', fileInput.files[0]);   // .docx
await fetch('/inseptum_backend/createarticle', { method: 'POST', body: fd });
```

---

## HTTP API

Все маршруты — в [`config/routes.php`](config/routes.php:1).
В таблицах ниже:

- **Auth** — что фронт обязан передать. `—` = ничего, `user_id`
  = передать поле `user_id` в теле, `admin` = по смыслу должно
  быть доступно только админу (на бэке не проверяется).
- **Body** — поля JSON / form-data (если не указано иначе —
  `application/json`).
- **Response.data** — что окажется в поле `data` в ответе.
  Метаполя (`status`, `message`, `count` …) опущены для краткости.

### Module Types

Справочник типов модулей. Используется фронтом для подбора иконки
(`icon` → ключ react-icons) и языка подсветки синтаксиса
(`highlight_language` → язык в редакторе задач).

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET    | `/module-types`              | —     | — | `ModuleType[]` + `count` |
| GET    | `/module-types/{id}`         | —     | — | `ModuleType`. `{id}` — `int` или `slug` |
| POST   | `/createmoduletype`          | admin | `{ slug, name, icon, highlight_language?, color? }` (или `form_data`) | `ModuleType` |
| POST/PUT | `/updatemoduletype/{id}`   | admin | те же поля | `ModuleType` |
| POST/DELETE | `/deletemoduletype/{id}`| admin | — | `{ id, slug }`. **409** если есть привязанные модули |

`ModuleType`:
```json
{
  "id": 1,
  "slug": "bootstrap",
  "name": "Bootstrap",
  "icon": "FaBootstrap",
  "highlight_language": "css",
  "color": "#7952B3",
  "created_at": "...",
  "updated_at": "..."
}
```

Валидация ([`ModuleTypeValidator`](src/Validators/ModuleTypeValidator.php:1)):
`slug` — обязателен, уникален, `^[a-z0-9_-]+$`, ≤ 64;
`name` — обязателен, ≤ 100; `icon` — обязательна, ≤ 80;
`highlight_language` — опционально, ≤ 40; `color` — опционально, ≤ 20.

### Modules

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET  | `/modules`        | — | — | `Module[]` (см. ниже) + `count` |
| GET  | `/modules/{id}`   | — | — | `Module`. `{id}` может быть `int` **или** `slug` (lowercase title) |
| POST | `/createmodule`   | admin | `{ title, description, module_type_id }` или `{ form_data: { ... } }` | `Module` |
| POST | `/updatemodule`   | admin | `{ module_id, title, description, module_type_id }` (или `form_data`) | `Module` |
| POST | `/deletemodule`   | admin | `{ module_id }` | `{ id, title }` |

`Module`:
```json
{
  "id": 1,
  "title": "Bootstrap",
  "slug": "bootstrap",
  "description": "...",
  "module_type_id": 1,
  "module_type": {
    "id": 1,
    "slug": "bootstrap",
    "name": "Bootstrap",
    "icon": "FaBootstrap",
    "highlight_language": "css",
    "color": "#7952B3"
  }
}
```

Валидация ([`ModuleValidator`](src/Validators/ModuleValidator.php:1)):
`title` — обязательно, ≤ 20 символов; `description` — обязательно;
`module_type_id` — обязательное целое, существующий ID в `module_types`.

### Topics

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET  | `/topics`        | — | — | `Topic[]` + `count` |
| GET  | `/topics/{id}`   | — | — | `Topic[]` тем выбранного **module_id** + `count`. `{id}` — int или title модуля |
| POST | `/createtopic`   | admin | `{ module_id, title, description }` (или `form_data`) | `Topic` |
| POST | `/updatetopic`   | admin | `{ topic_id, module_id, title, description }` | `Topic` |
| POST | `/deletetopic`   | admin | `{ topic_id }` | `{ id, title }` |

`Topic`:
```json
{
  "id": 3,
  "module_id": 1,
  "title": "Подключение",
  "description": "...",
  "module_title": "Bootstrap",
  "module_type": {
    "id": 1,
    "slug": "bootstrap",
    "name": "Bootstrap",
    "icon": "FaBootstrap",
    "highlight_language": "css",
    "color": "#7952B3"
  }
}
```

> Поле `module_type` добавлено также в ответы `Article`, `Task` и `Test`
> везде, где раньше присутствовало `module_title`. `module_title`
> сохранено для обратной совместимости.

### Articles

> Внимательно: `/articles/{id}` — это статьи **по `topic_id`**;
> одиночная статья — `/article/{id}` (единственное число).

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET  | `/articles`         | — | — | `Article[]` + `count` |
| GET  | `/articles/{id}`    | — | — | `Article[]` для `topic_id = {id}` + `count` |
| GET  | `/article/{id}`     | — | — | `Article` |
| POST | `/createarticle`    | admin | **multipart**: `title`, `description`, `topic` (он же `topic_id`), `file` (.docx) | `Article` |
| POST | `/updatearticle`    | admin | **multipart**: `article_id`, `title`, `description`, `topic`, `file?` (если меняем) | `Article` |
| POST | `/deletearticle`    | admin | `{ article_id }` | `{ id, title }` (плюс кастомное `message` про удаление файла) |

`Article`:
```json
{
  "id": 5,
  "title": "Подключение Bootstrap",
  "description": "...",
  "module_title": "Bootstrap",
  "topic_id": 3,
  "topic_title": "Подключение",
  "test_id": 2,
  "test_title": "Тест по подключению",
  "task_id": null,
  "task_title": null,
  "file_path": "bootstrap_connect.docx",
  "created_at": "2025-01-10 14:22:00"
}
```

### Article File / Read progress

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET  | `/articlefile/{id}`              | — | — | строка с **HTML** (конвертация .docx) |
| GET  | `/readarticle/{id}/{user_id}`    | user_id (в URL) | — | `{ id, user_id, article_id, is_read, ... }` (HTTP 201, если запись только что создалась) |
| POST | `/readarticle`                   | user_id | `{ article_id, user_id }` | та же запись с `is_read = 1` |

### Tests

> У `/tests/{id}` исторически дублирующие маршруты. Используйте
> любые, удобные фронту:

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| GET    | `/tests`           | — | — | `Test[]` + `count` |
| GET    | `/tests/{id}`      | — | — | `Test` |
| POST   | `/createtest` **или** `/tests` | admin | **multipart**: `title`, `description?`, `time_limit?` (default `20`), `topic_id?`, `file` (.json) | `{ id, title, question_count, file_path }` (HTTP 201) |
| POST   | `/updatetest` **или** `/updatetest/{id}` **или** `/tests/{id}` | admin | **multipart**: `test_id` (если не в URL), `title`, `description`, `time_limit`, `file?` | сырое DB-row теста |
| DELETE | `/tests/{id}`      | admin | — | `{ id, title }` |

`Test`:
```json
{
  "id": 2,
  "title": "Тест по подключению",
  "description": "...",
  "time_limit": 20,
  "question_count": 4,
  "file_path": "bootstrap_connect",
  "article_title": "Подключение Bootstrap",
  "module_title": "Bootstrap"
}
```

### Test File / Results / Passed

Файл теста — JSON-массив вопросов, формат см.
[`testsFolder/bootstrap_connect.json`](testsFolder/bootstrap_connect.json:1):

```json
[
  {
    "id": 1,
    "question": "Как подключить Bootstrap?",
    "answers": ["npm install bootstrap", "..."],
    "correctAnswer": "npm install bootstrap"
  }
]
```

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| POST | `/gettestfile`    | — | `{ test_id, question_id? }` | массив вопросов **без** `correctAnswer`; если передан `question_id` — один вопрос или `null` |
| POST | `/gettestresults` | — | `{ test_id, user_answers: [{ questionId, answer }, ...] }` | число правильных ответов (`int`) |
| POST | `/setpassedtest`  | user_id | `{ user_id, test_id }` | `bool` (true = тест помечен пройденным) |
| POST | `/getpassedtest`  | user_id | `{ user_id, test_id }` | `bool` (true = пройден) |
| POST | `/getpassedtests` | user_id | `{ user_id }` | `int[]` — массив `test_id` всех пройденных тестов пользователя (**batch**, замена N запросов `/getpassedtest`); + `count` |

### Tasks

| Method | Path | Auth | Body | Response |
|---|---|---|---|---|
| GET  | `/tasks`           | — | — | `data: Task[]` + `count` |
| GET  | `/tasks/{id}`      | — | — | `data: Task` |
| POST | `/checktask`       | user_id (опц.) | `{ taskId, code, user_id? }` | **legacy:** `{ success: bool, message: string }` (без `status/data`). При успехе и переданном `user_id` задача **автоматически** отмечается пройденной. |
| POST | `/setpassedtask`   | user_id | `{ user_id, task_id }` | `data: bool` (true = задача помечена пройденной) |
| POST | `/getpassedtask`   | user_id | `{ user_id, task_id }` | `data: bool` (true = пройдена) |
| POST | `/getpassedtasks`  | user_id | `{ user_id }` | `data: int[]` — массив `task_id` всех пройденных задач пользователя (**batch**); + `count` |

`Task` (joined):
```json
{
  "id": 1,
  "title": "...",
  "description": "...",
  "topic_id": 3,
  "topic_title": "Подключение",
  "module_title": "Bootstrap"
  // плюс прочие поля таблицы tasks
}
```

Ограничения `/checktask`: пустой код или > 5000 символов → 422.
Сейчас проверка — **заглушка** (баланс фигурных скобок + мок-ответ),
интеграция с ИИ — TODO в [`TaskService`](src/Services/TaskService.php:65).

### Favorites

`favorite_type` — `"article"`, `"test"` или `"task"`.

| Method | Path | Auth | Body | Response |
|---|---|---|---|---|
| POST | `/getfavorite`  | user_id | `{ user_id, favorite_type }` | `data: row[]` из `user_article_favorite` / `user_test_favorite` / `user_task_favorite` + `count` |
| POST | `/setfavorite`  | user_id | `{ user_id, favorite_id, favorite_type }` | `data: null`, `message` сообщает «добавлено / удалено» (toggle) |

### Auth

| Method | Path | Auth | Body | Response.data |
|---|---|---|---|---|
| POST | `/register`   | — | `{ username, password, confirm_password }` | `{ user_id, username, created_at }` (HTTP 201) |
| POST | `/login`      | — | `{ username, password }` | `{ user_id, username, created_at }` |
| POST | `/adminlogin` | — | `{ username, password }` | `{ user_id, username }` |

Валидация ([`UserValidator`](src/Validators/UserValidator.php:1)):
- `username` — 3..20 символов;
- `password` — ≥ 3 символа;
- `register`: `password === confirm_password`, иначе 422;
- занятый `username` → 409 (`ConflictException`).

---

## Структура проекта

```
config/
  database.php                — массив параметров подключения к БД
  routes.php                  — регистрация маршрутов (router DSL)
public/
  index.php                   — единственный front-controller
  .htaccess
src/
  Core/
    Application.php           — бутстрап, обработка исключений
    Container.php             — DI-контейнер с автовайрингом
    Database.php              — обёртка PDO
    Logger.php                — файловый логгер (storage/logs/app.log)
    Router.php                — роутер с {param}-плейсхолдерами
  Exceptions/                 — иерархия исключений приложения
  Http/
    Request.php, Response.php, JsonResponse.php
    Controllers/              — AbstractController + контроллеры по сущностям
  Models/                     — простые data-модели
  Repositories/               — доступ к БД (extends AbstractRepository)
  Services/                   — бизнес-логика
  Support/
    DocxConverter.php         — конвертер .docx → HTML (порт convert.php)
  Validators/                 — валидаторы запросов
storage/logs/                 — логи (создаётся автоматически)
articlesFolder/               — загруженные .docx статей
testsFolder/                  — JSON-файлы тестов
```

## Как добавить новую сущность

Цепочка: **Model → Repository → Service → Controller → Route**.

1. **Model** — [`src/Models/`](src/Models): простая структура с
   `fromArray()` / `toArray()`.
2. **Repository** — [`src/Repositories/`](src/Repositories), наследует
   [`AbstractRepository`](src/Repositories/AbstractRepository.php:1).
   Получает PDO через DI.
3. **Service** — [`src/Services/`](src/Services): бизнес-логика, бросает
   доменные исключения из [`src/Exceptions/`](src/Exceptions).
4. **Validator** (опц.) — [`src/Validators/`](src/Validators),
   наследует [`AbstractValidator`](src/Validators/AbstractValidator.php:1).
5. **Controller** — [`src/Http/Controllers/`](src/Http/Controllers),
   наследует [`AbstractController`](src/Http/Controllers/AbstractController.php:1).
   Возвращает [`JsonResponse`](src/Http/JsonResponse.php:1).
6. **Route** — регистрация в [`config/routes.php`](config/routes.php:1).

DI-контейнер ([`Container`](src/Core/Container.php:1)) автоматически
резолвит зависимости по type-hint'ам в конструкторе — отдельные
биндинги нужны только для не-классовых параметров (см. пример
`DocxConverter` в [`Application::bootstrap()`](src/Core/Application.php:31)).
