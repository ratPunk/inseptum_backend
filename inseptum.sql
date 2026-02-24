-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Фев 24 2026 г., 06:42
-- Версия сервера: 5.7.24
-- Версия PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `inseptum`
--

-- --------------------------------------------------------

--
-- Структура таблицы `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `title` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `test_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `articles`
--

INSERT INTO `articles` (`id`, `topic_id`, `title`, `description`, `file_path`, `created_at`, `test_id`, `task_id`) VALUES
(1, 2, 'Как работать с bootstrap', 'Статья с основами подключения bootstrap в ваш проект', 'bootstrap_connect.docx', '2026-02-13 17:41:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `title` varchar(20) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `modules`
--

INSERT INTO `modules` (`id`, `title`, `description`) VALUES
(1, 'Bootstrap', 'Материалы по Bootstrap: применение, компоненты и адаптивная верстка'),
(2, 'Javascript', 'Материалы по JavaScript: основы, фреймворки и современные практики'),
(3, 'HTML', 'Материалы по HTML: семантическая верстка и современные стандарты'),
(4, 'PHP', 'Материалы по PHP: серверное программирование и фреймворки'),
(5, 'Database', 'Материалы по базам данных: SQL, проектирование и оптимизация'),
(6, 'Structure', 'Материалы по структурам данных и алгоритмам');

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(40) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(40) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(140) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `topics`
--

INSERT INTO `topics` (`id`, `module_id`, `title`, `description`) VALUES
(1, 3, 'Структура страницы', 'Основы структуры страницы, базовое понимание для начала создания своей страницы.'),
(2, 1, 'Bootstrap основы', 'Основы для понимания взаимодействия'),
(3, 1, 'Сетка и адаптивность', 'Изучение grid-системы, контейнеров и breakpoints'),
(4, 1, 'Компоненты', 'Кнопки, карточки, модальные окна и навигация'),
(5, 1, 'Кастомизация', 'Переопределение переменных и сборка кастомной темы'),
(6, 2, 'Основы языка', 'Переменные, типы данных, функции и события'),
(7, 2, 'Работа с DOM', 'Поиск элементов, изменение контента, обработка событий'),
(8, 2, 'Асинхронность', 'Promises, async/await, fetch API'),
(9, 3, 'Семантическая верстка', 'Правильное использование тегов header, main, section, article'),
(10, 3, 'Формы и инпуты', 'Создание форм, типы полей, валидация'),
(11, 3, 'Мультимедиа', 'Вставка изображений, аудио и видео'),
(12, 4, 'Основы синтаксиса', 'Переменные, массивы, условия, циклы'),
(13, 4, 'Работа с формами и БД', 'Обработка POST-запросов, подключение к MySQL'),
(14, 4, 'ООП и сессии', 'Классы, авторизация, работа с сессиями'),
(15, 5, 'Основы SQL', 'SELECT, INSERT, UPDATE, DELETE'),
(16, 5, 'Связи и JOIN', 'Объединение таблиц, внешние ключи'),
(17, 5, 'Оптимизация запросов', 'Индексы, EXPLAIN, скорость выполнения'),
(18, 6, 'Массивы и списки', 'Статические массивы, связные списки'),
(19, 6, 'Стеки и очереди', 'Принципы LIFO и FIFO, применение'),
(20, 6, 'Деревья и графы', 'Бинарные деревья, обход графов');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(26, 'bob', '$2y$10$oRf4ymjpxtCTCU30iJNdHOf7le.lNykde54kS1RBk.GmKxDKcE4xy', '2026-02-09 07:37:20'),
(27, 'bob1', '$2y$10$IRy7Nf5Dsh96sstCPZooEe/8.hafmtc46959T/jD9.kBtRNs7wuma', '2026-02-09 07:38:25'),
(28, 'фв', '$2y$10$2vGZTQG77pSynCmwIs5kpOqg1pioJetqEnb3k2V1NWmcNYktRphS.', '2026-02-09 07:40:33'),
(29, 'ек', '$2y$10$DvaGIiCsHA77XWKO6uCFieUIPc4LkfaUTcuw6dgPR7OqbyRX3Op5C', '2026-02-09 07:41:46'),
(30, 'Иван', '$2y$10$xfGnQkKrViN0.Q/pEFzafO2MYj9Zm9/XWqCOmGPzacTP7pgv71Jvu', '2026-02-09 07:44:14'),
(31, 'Иван--', '$2y$10$thP5vu88t/x4abMapFuzmePp6KCqX3O/WCOLGrM0QGE7AQ8/COZbK', '2026-02-09 07:45:24'),
(32, 'Иван\\u0000Петров', '$2y$10$XL0G87u4PoXokHDQWEe9w.PSHX6MkRelZF267CeYwdqnnRAVYazwC', '2026-02-09 07:45:47'),
(33, 'Иван\\u202E', '$2y$10$wq8.Kwu7EM4mb78G2MgfV.JP1sB2THcm58/9rC1Si6lO0yjdomsAe', '2026-02-09 07:45:59'),
(34, 'rwqw', '$2y$10$FT9oPVEYsipotocNZePZ0ufVap0V90Sw1HrWFcnrIWxDayuWDecdS', '2026-02-09 08:04:57'),
(35, 'Роман', '$2y$10$doeNCSjYUczWerO5kQIMhO0JNJxzT1hri/8jNzZb5cG/5lZdwHeeS', '2026-02-21 13:51:08'),
(36, 'tea', '$2y$10$djc2tgi/dj/2U/CobuacWOoXQB4i14ezH2qd85.kHrHaDeSw2MbLa', '2026-02-22 10:23:37'),
(37, 'feas', '$2y$10$1drJkJ63VGaQlZwoXowUluqvyFPwumn4KPHpa/rBMZno/SOiWL7T.', '2026-02-22 10:24:16'),
(38, 'dawdawd', '$2y$10$ZeSq3N6qUEqAXDStE7.AUevapqbzTCUC0Hv27/pDW41TeHFijqsBm', '2026-02-22 10:25:07'),
(39, 'dadasd', '$2y$10$RsbjVIWpl7YhYg5tNfCIV..whFCHDdEqbaJ6xlT7shIJ6AYkx8dxq', '2026-02-22 10:26:59'),
(40, 'hndgd', '$2y$10$ihDOYPxZwKsuW8FwXNlg0.PrNxTmT80Ar0.QLvtQ4XyB4S9CrHXy2', '2026-02-22 10:30:24'),
(41, 'пыам', '$2y$10$8bMce2ZyQZTqRp6X/xqRueCvGWvdMgiNLvGQ40xzGwNyJZaQsssxO', '2026-02-22 10:36:55'),
(42, 'reere', '$2y$10$4.KR1OEPg1nq523q1g.bXuTln/ubkJ.vHNgkivVC7odXMKcbTUCEG', '2026-02-23 11:18:18'),
(43, 'dasgegd', '$2y$10$gOcrY.S84ljukZOvJcgPJOw/SpFusJRjoCMr1fwhigoujX9wEATr2', '2026-02-23 11:46:14'),
(44, 'qwergdbnjyuhgfrjkuyt', '$2y$10$92Sg.IWMIn1b/14CQjW6qOxV6.bJlzFXic3MsAu8ck20itHEfOXsu', '2026-02-23 11:56:11'),
(45, 'рыфаыафы', '$2y$10$.R7.xgWJfX3E/.cjFVpZ5O7nPNj4YITZHUGWgpx7ic3ZnFn4pEUmy', '2026-02-23 20:17:04');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_topic_id` (`topic_id`),
  ADD KEY `idx_test_id` (`test_id`),
  ADD KEY `idx_task_id` (`task_id`);

--
-- Индексы таблицы `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `topics`
--
ALTER TABLE `topics`
  ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
