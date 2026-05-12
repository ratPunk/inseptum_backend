# inseptum_backend

PHP backend для проекта *inseptum*. Чистая MVC-подобная архитектура
(Router → Controller → Service → Repository → Model). Весь legacy-код
([`index.php`](index.php) в корне, [`controller.php`](controller.php),
папка `api/` с процедурными функциями, [`convert.php`](convert.php),
[`logger.php`](logger.php)) — удалён. Точка входа одна:
[`public/index.php`](public/index.php:1).

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

4. Проверка:

   ```
   GET  http://localhost/inseptum_backend/modules
   GET  http://localhost/inseptum_backend/modules/1
   POST http://localhost/inseptum_backend/login
   ```

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

## Маршруты

Все маршруты описаны декларативно в [`config/routes.php`](config/routes.php:1).
Файл подключается из [`Application::bootstrap()`](src/Core/Application.php:31)
с переменной `$router` в области видимости.

Пример:

```php
$router->get('/modules',       [ModuleController::class, 'index']);
$router->get('/modules/{id}',  [ModuleController::class, 'show']);
$router->post('/createmodule', [ModuleController::class, 'create']);
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

## Обработка ошибок

[`Application::handleException()`](src/Core/Application.php:70)
ловит исключения и отдаёт JSON в формате
`{status: false, message: ..., errors?: ...}` с соответствующим
HTTP-статусом:

- [`ValidationException`](src/Exceptions/ValidationException.php:1) — 422 + `errors`
- [`NotFoundException`](src/Exceptions/NotFoundException.php:1) — 404
- [`UnauthorizedException`](src/Exceptions/UnauthorizedException.php:1) — 401
- [`ConflictException`](src/Exceptions/ConflictException.php:1) — 409
- любая другая `Throwable` — 500 (с записью в лог)
