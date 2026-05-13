-- Добавление тем (topics) для модулей: Bootstrap, HTML, PHP, JavaScript, Structure.
-- Привязка идёт через slug в module_types -> modules.module_type_id, чтобы не зависеть
-- от конкретных id в таблице `modules` на разных окружениях.
--
-- Скрипт идемпотентен: повторный запуск не создаст дубликатов
-- (используется WHERE NOT EXISTS по паре (module_id, title)).

-- ---------------------------------------------------------------------------
-- BOOTSTRAP
-- ---------------------------------------------------------------------------
INSERT INTO `topics` (`module_id`, `title`, `description`)
SELECT m.id, t.title, t.description
FROM (
    SELECT 'Bootstrap основы'              AS title, 'Основы для понимания взаимодействия' AS description UNION ALL
    SELECT 'Установка и подключение',                'Подключение через CDN, npm и сборкой Sass' UNION ALL
    SELECT 'Сетка и адаптивность',                   'Изучение grid-системы, контейнеров и breakpoints' UNION ALL
    SELECT 'Контейнеры и Flex-утилиты',              'Разница container/container-fluid, утилиты flex' UNION ALL
    SELECT 'Компоненты',                             'Кнопки, карточки, модальные окна и навигация' UNION ALL
    SELECT 'Формы и валидация',                      'Стили input, was-validated, плагины валидации' UNION ALL
    SELECT 'Навигация и Navbar',                     'Navbar, Nav, Tabs, Pills и off-canvas меню' UNION ALL
    SELECT 'Модальные окна и тосты',                 'Модалки, тосты, оффканвас, поповеры' UNION ALL
    SELECT 'Иконки Bootstrap Icons',                 'Подключение и использование набора иконок' UNION ALL
    SELECT 'Кастомизация',                           'Переопределение переменных и сборка кастомной темы' UNION ALL
    SELECT 'Темизация и Dark mode',                  'Поддержка dark mode и кастомных тем через Sass'
) AS t
JOIN `modules` m
  ON m.module_type_id = (SELECT id FROM `module_types` WHERE slug = 'bootstrap' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM `topics` x
    WHERE x.module_id = m.id AND x.title = t.title
);

-- ---------------------------------------------------------------------------
-- HTML
-- ---------------------------------------------------------------------------
INSERT INTO `topics` (`module_id`, `title`, `description`)
SELECT m.id, t.title, t.description
FROM (
    SELECT 'Структура страницы'            AS title, 'Основы структуры страницы, базовое понимание для начала создания своей страницы.' AS description UNION ALL
    SELECT 'Семантическая верстка',        'Правильное использование тегов header, main, section, article' UNION ALL
    SELECT 'Текст и типографика',          'Заголовки, абзацы, списки, цитаты, выделения' UNION ALL
    SELECT 'Ссылки и навигация',           'Виды ссылок, якоря, target, rel, навигационные паттерны' UNION ALL
    SELECT 'Изображения и picture',        'img, srcset, sizes, picture, lazy-loading' UNION ALL
    SELECT 'Мультимедиа',                  'Вставка изображений, аудио и видео' UNION ALL
    SELECT 'Формы и инпуты',               'Создание форм, типы полей, валидация' UNION ALL
    SELECT 'Таблицы',                      'thead, tbody, tfoot, scope, объединение ячеек' UNION ALL
    SELECT 'Метатеги и SEO',               'meta description, Open Graph, viewport, canonical' UNION ALL
    SELECT 'Доступность (a11y)',           'aria-атрибуты, alt, контраст, фокус, клавиатурная навигация' UNION ALL
    SELECT 'HTML5 API',                    'localStorage, sessionStorage, History, Drag and Drop'
) AS t
JOIN `modules` m
  ON m.module_type_id = (SELECT id FROM `module_types` WHERE slug = 'html' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM `topics` x
    WHERE x.module_id = m.id AND x.title = t.title
);

-- ---------------------------------------------------------------------------
-- PHP
-- ---------------------------------------------------------------------------
INSERT INTO `topics` (`module_id`, `title`, `description`)
SELECT m.id, t.title, t.description
FROM (
    SELECT 'Основы синтаксиса'             AS title, 'Переменные, массивы, условия, циклы' AS description UNION ALL
    SELECT 'Типы данных и операторы',      'Скаляры, массивы, объекты, приведение типов' UNION ALL
    SELECT 'Функции и области видимости',  'Объявление, аргументы, замыкания, тип-хинтинг' UNION ALL
    SELECT 'Массивы и строки',             'Полезные функции работы с массивами и строками' UNION ALL
    SELECT 'Работа с формами и БД',        'Обработка POST-запросов, подключение к MySQL' UNION ALL
    SELECT 'PDO и подготовленные запросы', 'Безопасная работа с БД через PDO' UNION ALL
    SELECT 'ООП и сессии',                 'Классы, авторизация, работа с сессиями' UNION ALL
    SELECT 'Наследование и интерфейсы',    'Расширение классов, абстрактные классы, интерфейсы, трейты' UNION ALL
    SELECT 'Обработка ошибок и исключения','try/catch, иерархия Throwable, кастомные исключения' UNION ALL
    SELECT 'Composer и автозагрузка',      'composer.json, PSR-4, установка пакетов' UNION ALL
    SELECT 'REST API на чистом PHP',       'Маршрутизация, JSON-ответы, статус-коды'
) AS t
JOIN `modules` m
  ON m.module_type_id = (SELECT id FROM `module_types` WHERE slug = 'php' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM `topics` x
    WHERE x.module_id = m.id AND x.title = t.title
);

-- ---------------------------------------------------------------------------
-- JAVASCRIPT
-- ---------------------------------------------------------------------------
INSERT INTO `topics` (`module_id`, `title`, `description`)
SELECT m.id, t.title, t.description
FROM (
    SELECT 'Основы языка'                  AS title, 'Переменные, типы данных, функции и события' AS description UNION ALL
    SELECT 'Операторы и управляющие конструкции', 'if/else, switch, циклы for/while, тернарный оператор' UNION ALL
    SELECT 'Функции и замыкания',          'Объявление функций, стрелочные функции, scope, closure' UNION ALL
    SELECT 'Массивы и объекты',            'map, filter, reduce, деструктуризация, spread' UNION ALL
    SELECT 'Работа с DOM',                 'Поиск элементов, изменение контента, обработка событий' UNION ALL
    SELECT 'События и делегирование',      'addEventListener, всплытие, делегирование, preventDefault' UNION ALL
    SELECT 'Асинхронность',                'Promises, async/await, fetch API' UNION ALL
    SELECT 'Fetch и работа с API',         'GET/POST запросы, обработка ошибок, AbortController' UNION ALL
    SELECT 'Модули ES',                    'import/export, default vs named, динамический import' UNION ALL
    SELECT 'Классы и ООП в JS',            'class, extends, super, статические методы' UNION ALL
    SELECT 'Обработка ошибок',             'try/catch/finally, throw, ошибки в Promise'
) AS t
JOIN `modules` m
  ON m.module_type_id = (SELECT id FROM `module_types` WHERE slug = 'javascript' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM `topics` x
    WHERE x.module_id = m.id AND x.title = t.title
);

-- ---------------------------------------------------------------------------
-- STRUCTURE (структуры данных)
-- ---------------------------------------------------------------------------
INSERT INTO `topics` (`module_id`, `title`, `description`)
SELECT m.id, t.title, t.description
FROM (
    SELECT 'Массивы и списки'              AS title, 'Статические массивы, связные списки' AS description UNION ALL
    SELECT 'Двусвязные и кольцевые списки','Реализация и операции вставки/удаления' UNION ALL
    SELECT 'Стеки и очереди',              'Принципы LIFO и FIFO, применение' UNION ALL
    SELECT 'Дек и приоритетная очередь',   'Двусторонние очереди, heap-based priority queue' UNION ALL
    SELECT 'Хеш-таблицы',                  'Хеш-функции, коллизии, открытая/закрытая адресация' UNION ALL
    SELECT 'Деревья и графы',              'Бинарные деревья, обход графов' UNION ALL
    SELECT 'Бинарные деревья поиска',      'BST, балансировка, операции' UNION ALL
    SELECT 'Куча (Heap)',                  'Min-heap, max-heap, сортировка кучей' UNION ALL
    SELECT 'Графы: BFS и DFS',             'Поиск в ширину и в глубину, представления графа' UNION ALL
    SELECT 'Сортировки',                   'Bubble, Quick, Merge, Heap — анализ сложности' UNION ALL
    SELECT 'Алгоритмическая сложность',    'Big O, оценка времени и памяти'
) AS t
JOIN `modules` m
  ON m.module_type_id = (SELECT id FROM `module_types` WHERE slug = 'structure' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM `topics` x
    WHERE x.module_id = m.id AND x.title = t.title
);
