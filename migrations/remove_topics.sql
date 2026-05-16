-- Remove Topics entity from the schema.
-- Drops FK constraints, then topic_id columns, then the topics table itself.

SET FOREIGN_KEY_CHECKS = 0;

-- articles.topic_id (FK -> topics)
ALTER TABLE `articles` DROP FOREIGN KEY `articles_ibfk_1`;
ALTER TABLE `articles` DROP COLUMN `topic_id`;

-- tasks.topic_id (FK -> topics)
ALTER TABLE `tasks` DROP FOREIGN KEY `fk_tasks_topic`;
ALTER TABLE `tasks` DROP COLUMN `topic_id`;

-- topics.module_id (FK -> modules) and the table itself
ALTER TABLE `topics` DROP FOREIGN KEY `topics_ibfk_1`;
DROP TABLE `topics`;

SET FOREIGN_KEY_CHECKS = 1;
