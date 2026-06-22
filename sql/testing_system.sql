-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Июн 22 2026 г., 18:01
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
-- База данных: `testing_system`
--

-- --------------------------------------------------------

--
-- Структура таблицы `achievements`
--

CREATE TABLE `achievements` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `xp_reward` int(11) DEFAULT '50'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `achievements`
--

INSERT INTO `achievements` (`id`, `name`, `description`, `icon`, `xp_reward`) VALUES
(1, '🎯 Первый шаг', 'Пройти первый тест', 'step.png', 50),
(2, '💯 Идеал', 'Получить 100% в любом тесте', 'star.png', 50),
(3, '🔥 Три в ряд', 'Пройти 3 теста подряд без ошибок', 'fire.png', 100),
(4, '🏅 Марафонец', 'Пройти 10 тестов', 'medal.png', 150),
(5, '⚡ Спринтер', 'Закончить тест быстрее 2 минут', 'lightning.png', 50),
(6, '🧪 Экспериментатор', 'Пройти тест с адаптивной сложностью', 'flask.png', 50);

-- --------------------------------------------------------

--
-- Структура таблицы `answer_options`
--

CREATE TABLE `answer_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `answer_options`
--

INSERT INTO `answer_options` (`id`, `question_id`, `option_text`, `is_correct`) VALUES
(42, 14, '1', 'EYS6FemP9mKmHqtxezirMjVObmZqMCtuMStDUGJZdkp1RmltcWc9PQ=='),
(43, 14, '2', 'EYS6FemP9mKmHqtxezirMjVObmZqMCtuMStDUGJZdkp1RmltcWc9PQ=='),
(44, 15, 'тест1', '0d0m4OI8hozhJRNxIKtRVk50N3lVMU5aTU5GTW5TdUxDMThZb0E9PQ=='),
(45, 15, 'тест3', '1dhdlL2IdMU1Oh5m10t2gytqNTBtZE1VSVFmWlV5akR3NlJXZGc9PQ=='),
(46, 16, 'тест2', 'c8w0b0orDf4wWZWdHJsQlnoycjRRcVNPYWxBTUI2T1FQdFZDL3c9PQ=='),
(47, 16, 'тест4', 'qCaLyniTNpz/0IbUqVPfqlFRVE9WeUVBcTV2RWh3b1diSGRvTlE9PQ==');

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `created_by`, `created_at`) VALUES
(1, 'тест1', 'тест', 2, '2026-06-05 21:42:48'),
(2, 'тест2', 'тест', 2, '2026-06-05 21:43:10');

-- --------------------------------------------------------

--
-- Структура таблицы `leaderboard`
--

CREATE TABLE `leaderboard` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_score` int(11) DEFAULT '0',
  `total_xp` int(11) DEFAULT '0',
  `rank_position` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `levels`
--

CREATE TABLE `levels` (
  `id` int(11) NOT NULL,
  `level_number` int(11) NOT NULL,
  `level_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_xp` int(11) NOT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `levels`
--

INSERT INTO `levels` (`id`, `level_number`, `level_name`, `min_xp`, `icon`) VALUES
(1, 1, '🍼 Новичок', 0, NULL),
(2, 2, '📚 Ученик', 100, NULL),
(3, 3, '🧠 Знаток', 400, NULL),
(4, 4, '🎓 Эрудит', 900, NULL),
(5, 5, '🏆 Эксперт', 1600, NULL),
(6, 6, '👑 Профессор', 2500, NULL),
(7, 7, '⚡ Гений', 3600, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('single','multiple') COLLATE utf8mb4_unicode_ci DEFAULT 'single',
  `difficulty` int(11) DEFAULT '5'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `questions`
--

INSERT INTO `questions` (`id`, `test_id`, `question_text`, `question_type`, `difficulty`) VALUES
(14, 6, 'тест', 'multiple', 5),
(15, 9, 'тесты', 'single', 5),
(16, 9, 'тест2 ', 'single', 5);

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(3, 'admin'),
(1, 'student'),
(2, 'teacher');

-- --------------------------------------------------------

--
-- Структура таблицы `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `student_answers`
--

INSERT INTO `student_answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`) VALUES
(1, 31, 16, 46);

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `time_limit` int(11) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `max_attempts` int(11) DEFAULT '0',
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `title`, `description`, `created_by`, `created_at`, `time_limit`, `is_active`, `max_attempts`, `group_id`) VALUES
(6, 'тест2', 'тест2', 2, '2026-06-05 22:30:09', 5, 1, 1, NULL),
(7, 'тест', '', 2, '2026-06-15 21:17:37', 0, 1, 0, NULL),
(8, 'тест', '', 2, '2026-06-15 21:17:42', 0, 1, 0, NULL),
(9, 'тест5', 'тестовое задание', 2, '2026-06-16 23:57:43', 10, 1, 2, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `test_attempts`
--

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `started_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  `status` enum('in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'in_progress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_attempts`
--

INSERT INTO `test_attempts` (`id`, `student_id`, `test_id`, `started_at`, `finished_at`, `score`, `status`) VALUES
(25, 5, 6, '2026-06-05 22:44:53', '2026-06-05 22:44:54', 0, 'completed'),
(27, 5, 7, '2026-06-16 23:56:56', '2026-06-16 23:56:56', 0, 'completed'),
(28, 5, 8, '2026-06-16 23:57:05', NULL, 0, 'in_progress'),
(31, 5, 9, '2026-06-16 23:59:28', '2026-06-16 23:59:29', 1, 'completed');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int(11) DEFAULT '1',
  `current_xp` int(11) DEFAULT '0',
  `current_level_id` int(11) DEFAULT '1',
  `total_tests_passed` int(11) DEFAULT '0',
  `total_correct_answers` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `password_hash`, `full_name`, `role_id`, `current_xp`, `current_level_id`, `total_tests_passed`, `total_correct_answers`, `created_at`, `group_id`) VALUES
(1, 'admin', '$2y$10$2p7CGN2e5a5775BOVZGmkeaxdwMYwSF.RiMLX2k/.n6rSB2musBOq', 'Администратор', 3, 0, 1, 0, 0, '2026-06-05 12:49:42', NULL),
(2, 'teacher', '$2y$10$2p7CGN2e5a5775BOVZGmkeaxdwMYwSF.RiMLX2k/.n6rSB2musBOq', 'Преподаватель Иванов', 2, 0, 1, 0, 0, '2026-06-05 12:49:42', NULL),
(5, 'student', '$2y$10$AqkS0RHVOh8yx6EPZu8YL.QO7wGTavf.2mKux2GnrXoK4FpZ9KyUK', 'Пьянков', 1, 74, 1, 4, 2, '2026-06-05 22:22:11', 1),
(6, 'teacher2', '$2y$10$4QFgjhN.oZs50BaChh8PFu//f4BRagZb32OBvKbUwAZr./qKGm0G.', 'Соколов', 2, 0, 1, 0, 0, '2026-06-05 22:47:09', NULL),
(7, '123', '$2y$10$ezgjfydEDcvEzfJPCzF9uePuDmTB03P7E2PEGqwqs8rKdzcKhf2Xu', 'Третьяков', 1, 0, 1, 0, 0, '2026-06-17 00:00:34', NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `user_achievements`
--

CREATE TABLE `user_achievements` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `achievement_id` int(11) NOT NULL,
  `earned_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `user_achievements`
--

INSERT INTO `user_achievements` (`id`, `user_id`, `achievement_id`, `earned_at`) VALUES
(3, 5, 1, '2026-06-05 22:44:46'),
(4, 5, 2, '2026-06-16 23:56:57');

-- --------------------------------------------------------

--
-- Структура таблицы `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `joined_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `xp_log`
--

CREATE TABLE `xp_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `xp_log`
--

INSERT INTO `xp_log` (`id`, `user_id`, `amount`, `source`, `source_id`, `created_at`) VALUES
(7, 5, 12, 'test_completed', 5, '2026-06-05 22:44:46'),
(8, 5, 10, 'test_completed', 6, '2026-06-05 22:44:54'),
(9, 5, 40, 'test_completed', 7, '2026-06-16 23:56:57'),
(10, 5, 12, 'test_completed', 9, '2026-06-16 23:59:29');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `answer_options`
--
ALTER TABLE `answer_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Индексы таблицы `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Индексы таблицы `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_number` (`level_number`);

--
-- Индексы таблицы `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_questions_test` (`test_id`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `selected_option_id` (`selected_option_id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `group_id` (`group_id`);

--
-- Индексы таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_attempts_ibfk_1` (`test_id`),
  ADD KEY `fk_test_attempts_user` (`student_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Индексы таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_achievements_user` (`user_id`),
  ADD KEY `fk_user_achievements_achievement` (`achievement_id`);

--
-- Индексы таблицы `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_group` (`user_id`,`group_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Индексы таблицы `xp_log`
--
ALTER TABLE `xp_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_xp_log_user` (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `answer_options`
--
ALTER TABLE `answer_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `leaderboard`
--
ALTER TABLE `leaderboard`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `xp_log`
--
ALTER TABLE `xp_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `answer_options`
--
ALTER TABLE `answer_options`
  ADD CONSTRAINT `answer_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD CONSTRAINT `leaderboard_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_test` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `student_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `test_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`),
  ADD CONSTRAINT `student_answers_ibfk_3` FOREIGN KEY (`selected_option_id`) REFERENCES `answer_options` (`id`);

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tests_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD CONSTRAINT `fk_test_attempts_user` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_attempts_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`);

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_achievements`
--
ALTER TABLE `user_achievements`
  ADD CONSTRAINT `fk_user_achievements_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_achievements_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_groups`
--
ALTER TABLE `user_groups`
  ADD CONSTRAINT `user_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `xp_log`
--
ALTER TABLE `xp_log`
  ADD CONSTRAINT `fk_xp_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
