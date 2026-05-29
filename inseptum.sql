-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Май 27 2026 г., 13:38
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
  `title` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `module_id` int(11) DEFAULT NULL,
  `file_path` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `test_id` int(11) DEFAULT NULL,
  `task_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `articles`
--

INSERT INTO `articles` (`id`, `title`, `description`, `module_id`, `file_path`, `created_at`, `test_id`, `task_id`) VALUES
(1, 'Как работать с bootstrap', 'Статья с основами подключения bootstrap в ваш проект', 1, 'bootstrap_connect.docx', '2026-02-13 17:41:15', 1, 1),
(2, 'атрибуты bootstrapз', 'атрибуты bootstrap', 1, 'bootstrap_connect — копия.docx', '2026-02-28 21:00:00', NULL, NULL),
(3, 'dasdsd', 'dasdasd', 7, 'bootstrap_connect — копия.docx', '2026-04-02 09:56:33', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `title` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `module_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `modules`
--

INSERT INTO `modules` (`id`, `title`, `description`, `module_type_id`) VALUES
(1, 'Bootstrap', 'Материалы по Bootstrap: применение, компоненты и адаптивная верстка', 1),
(2, 'Javascript', 'Материалы по JavaScript: основы, фреймворки и современные практики', 4),
(3, 'HTML', 'Материалы по HTML: семантическая верстка и современные стандарты', 2),
(4, 'PHP', 'Материалы по PHP: серверное программирование и фреймворки', 3),
(5, 'Database', 'Материалы по базам данных: SQL, проектирование и оптимизация', 5),
(6, 'Structure', 'Материалы по структурам данных и алгоритмам', 6),
(7, 'Git', 'Основа управления технологией контроля версий', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `module_types`
--

CREATE TABLE `module_types` (
  `id` int(11) NOT NULL,
  `slug` varchar(64) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(80) NOT NULL,
  `highlight_language` varchar(40) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Дамп данных таблицы `module_types`
--

INSERT INTO `module_types` (`id`, `slug`, `name`, `icon`, `highlight_language`, `color`, `created_at`, `updated_at`) VALUES
(1, 'bootstrap', 'Bootstrap', 'FaBootstrap', 'css', '#7952B3', '2026-05-12 23:04:23', '2026-05-12 23:04:23'),
(2, 'html', 'HTML', 'FaHtml5', 'html', '#E34F26', '2026-05-12 23:04:23', '2026-05-12 23:04:23'),
(3, 'php', 'PHP', 'FaPhp', 'php', '#777BB4', '2026-05-12 23:04:23', '2026-05-12 23:04:23'),
(4, 'javascript', 'JavaScript', 'FaJs', 'javascript', '#F7DF1E', '2026-05-12 23:04:23', '2026-05-12 23:04:23'),
(5, 'database', 'DataBase', 'FaDatabase', 'sql', '#00758F', '2026-05-12 23:04:23', '2026-05-18 10:50:51'),
(6, 'structure', 'Structure', 'TbBinaryTree', 'javascript', '#4CAF50', '2026-05-12 23:04:23', '2026-05-18 10:51:03'),
(7, 'console', 'Console', 'FaCode', NULL, '#ffffff', '2026-05-19 08:10:37', '2026-05-19 08:10:37');

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `difficulty`, `created_at`, `module_id`) VALUES
(1, 'Задача на создание Bootstrap классов', 'Создайте bootstrap класс и используйте его', 'easy', '2026-04-17 11:42:50', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(40) NOT NULL,
  `description` text NOT NULL,
  `file_path` varchar(140) NOT NULL,
  `time_limit` int(4) NOT NULL DEFAULT '20',
  `question_count` int(5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `module_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `title`, `description`, `file_path`, `time_limit`, `question_count`, `created_at`, `module_id`) VALUES
(1, 'Основы Bootstrap', 'Тест по теме Основы Bootstrap раскрывающий и дополняющий данную тему', 'bootstrap_connect', 20, 4, '2026-03-01 20:58:26', 1),
(5, 'da', 'dsa', '1775152339_bootstrap_connect — копия', 12, 4, '2026-04-02 17:52:19', 7);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(150) NOT NULL,
  `role` enum('user','spectator','moderator','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'test_user', '123', 'user', '2024-01-15 07:30:00'),
(26, 'bob', '$2y$10$oRf4ymjpxtCTCU30iJNdHOf7le.lNykde54kS1RBk.GmKxDKcE4xy', 'admin', '2026-02-09 07:37:20'),
(27, 'bob1', '$2y$10$IRy7Nf5Dsh96sstCPZooEe/8.hafmtc46959T/jD9.kBtRNs7wuma', 'user', '2026-02-09 07:38:25'),
(28, 'фв', '$2y$10$2vGZTQG77pSynCmwIs5kpOqg1pioJetqEnb3k2V1NWmcNYktRphS.', 'user', '2026-02-09 07:40:33'),
(29, 'ек', '$2y$10$DvaGIiCsHA77XWKO6uCFieUIPc4LkfaUTcuw6dgPR7OqbyRX3Op5C', 'user', '2026-02-09 07:41:46'),
(30, 'Иван', '$2y$10$xfGnQkKrViN0.Q/pEFzafO2MYj9Zm9/XWqCOmGPzacTP7pgv71Jvu', 'user', '2026-02-09 07:44:14'),
(31, 'Иван--', '$2y$10$thP5vu88t/x4abMapFuzmePp6KCqX3O/WCOLGrM0QGE7AQ8/COZbK', 'user', '2026-02-09 07:45:24'),
(32, 'Иван\\u0000Петров', '$2y$10$XL0G87u4PoXokHDQWEe9w.PSHX6MkRelZF267CeYwdqnnRAVYazwC', 'user', '2026-02-09 07:45:47'),
(33, 'Иван\\u202E', '$2y$10$wq8.Kwu7EM4mb78G2MgfV.JP1sB2THcm58/9rC1Si6lO0yjdomsAe', 'user', '2026-02-09 07:45:59'),
(34, 'rwqw', '$2y$10$FT9oPVEYsipotocNZePZ0ufVap0V90Sw1HrWFcnrIWxDayuWDecdS', 'user', '2026-02-09 08:04:57'),
(35, 'Роман', '$2y$10$doeNCSjYUczWerO5kQIMhO0JNJxzT1hri/8jNzZb5cG/5lZdwHeeS', 'user', '2026-02-21 13:51:08'),
(36, 'tea', '$2y$10$djc2tgi/dj/2U/CobuacWOoXQB4i14ezH2qd85.kHrHaDeSw2MbLa', 'user', '2026-02-22 10:23:37'),
(37, 'feas', '$2y$10$1drJkJ63VGaQlZwoXowUluqvyFPwumn4KPHpa/rBMZno/SOiWL7T.', 'user', '2026-02-22 10:24:16'),
(38, 'dawdawd', '$2y$10$ZeSq3N6qUEqAXDStE7.AUevapqbzTCUC0Hv27/pDW41TeHFijqsBm', 'user', '2026-02-22 10:25:07'),
(39, 'dadasd', '$2y$10$RsbjVIWpl7YhYg5tNfCIV..whFCHDdEqbaJ6xlT7shIJ6AYkx8dxq', 'user', '2026-02-22 10:26:59'),
(40, 'hndgd', '$2y$10$ihDOYPxZwKsuW8FwXNlg0.PrNxTmT80Ar0.QLvtQ4XyB4S9CrHXy2', 'user', '2026-02-22 10:30:24'),
(41, 'пыам', '$2y$10$8bMce2ZyQZTqRp6X/xqRueCvGWvdMgiNLvGQ40xzGwNyJZaQsssxO', 'user', '2026-02-22 10:36:55'),
(42, 'reere', '$2y$10$4.KR1OEPg1nq523q1g.bXuTln/ubkJ.vHNgkivVC7odXMKcbTUCEG', 'user', '2026-02-23 11:18:18'),
(43, 'dasgegd', '$2y$10$gOcrY.S84ljukZOvJcgPJOw/SpFusJRjoCMr1fwhigoujX9wEATr2', 'user', '2026-02-23 11:46:14'),
(44, 'qwergdbnjyuhgfrjkuyt', '$2y$10$92Sg.IWMIn1b/14CQjW6qOxV6.bJlzFXic3MsAu8ck20itHEfOXsu', 'user', '2026-02-23 11:56:11'),
(45, 'рыфаыафы', '$2y$10$.R7.xgWJfX3E/.cjFVpZ5O7nPNj4YITZHUGWgpx7ic3ZnFn4pEUmy', 'user', '2026-02-23 20:17:04'),
(46, 'qa_tst_%ts%', '$2y$10$/9cgDCo3oUf66dN.volGie..ZIeg7uDpJzsJMJlxWXF/.NI81q18.', 'user', '2026-05-10 20:58:34'),
(47, 'qa_tst_8888', '$2y$10$OJB.kXx.ve/6KiONF7mZyek4RCcStNIPZuDDVPHSW/PO2D2jZq5Vm', 'user', '2026-05-10 20:58:46');

-- --------------------------------------------------------

--
-- Структура таблицы `user_article_favorite`
--

CREATE TABLE `user_article_favorite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `user_article_favorite`
--

INSERT INTO `user_article_favorite` (`id`, `user_id`, `article_id`, `created_at`) VALUES
(1, 1, 1, '2026-03-19 10:36:00');

-- --------------------------------------------------------

--
-- Структура таблицы `user_article_read`
--

CREATE TABLE `user_article_read` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `progress_percent` int(3) DEFAULT '0' COMMENT 'Прогресс чтения 0-100',
  `last_position` int(11) DEFAULT NULL COMMENT 'Позиция скролла',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `user_article_read`
--

INSERT INTO `user_article_read` (`id`, `user_id`, `article_id`, `is_read`, `read_at`, `progress_percent`, `last_position`, `created_at`, `updated_at`) VALUES
(1, 33, 1, 0, '2026-02-28 16:46:28', 0, NULL, '2026-02-28 15:31:54', '2026-02-28 16:57:58'),
(2, 26, 1, 1, '2026-03-02 17:15:05', 0, NULL, '2026-02-28 15:42:18', '2026-03-02 17:15:05'),
(3, 26, 2, 0, '2026-03-03 12:34:53', 0, NULL, '2026-03-03 12:34:53', NULL),
(4, 1, 1, 1, '2026-03-18 13:43:56', 0, NULL, '2026-03-10 14:24:27', '2026-03-18 13:43:56'),
(5, 1, 2, 0, '2026-03-25 12:30:13', 0, NULL, '2026-03-25 12:30:13', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_test_favorite`
--

CREATE TABLE `user_test_favorite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `user_test_favorite`
--

INSERT INTO `user_test_favorite` (`id`, `user_id`, `test_id`, `created_at`) VALUES
(37, 26, 1, '2026-03-10 10:11:56'),
(38, 1, 1, '2026-03-15 15:13:51');

-- --------------------------------------------------------

--
-- Структура таблицы `user_test_passed`
--

CREATE TABLE `user_test_passed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `is_passed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `user_test_passed`
--

INSERT INTO `user_test_passed` (`id`, `user_id`, `test_id`, `is_passed`, `created_at`, `updated_at`) VALUES
(1, 26, 1, 1, '2026-03-03 18:29:17', NULL),
(2, 1, 1, 1, '2026-03-18 17:53:08', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_id` (`test_id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `fk_articles_module` (`module_id`);

--
-- Индексы таблицы `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_modules_module_type_id` (`module_type_id`);

--
-- Индексы таблицы `module_types`
--
ALTER TABLE `module_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_module_types_slug` (`slug`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tasks_module` (`module_id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tests_module` (`module_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_article_favorite`
--
ALTER TABLE `user_article_favorite`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_article_read`
--
ALTER TABLE `user_article_read`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_article` (`user_id`,`article_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Индексы таблицы `user_test_favorite`
--
ALTER TABLE `user_test_favorite`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `user_test_passed`
--
ALTER TABLE `user_test_passed`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `module_types`
--
ALTER TABLE `module_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT для таблицы `user_article_favorite`
--
ALTER TABLE `user_article_favorite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `user_article_read`
--
ALTER TABLE `user_article_read`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `user_test_favorite`
--
ALTER TABLE `user_test_favorite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT для таблицы `user_test_passed`
--
ALTER TABLE `user_test_passed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `articles_ibfk_3` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_articles_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `fk_modules_module_type` FOREIGN KEY (`module_type_id`) REFERENCES `module_types` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `fk_tests_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_article_read`
--
ALTER TABLE `user_article_read`
  ADD CONSTRAINT `fk_user_article_read_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_article_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
