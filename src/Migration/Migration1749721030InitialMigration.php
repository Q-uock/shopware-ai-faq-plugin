<?php declare(strict_types=1);

namespace DIW\AiFaq\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1749721030InitialMigration extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1749711852;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
-- Fragen-Tabelle
CREATE TABLE IF NOT EXISTS `questions` (
    `id` BINARY(16) NOT NULL,
    `question` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Antworten-Tabelle
CREATE TABLE IF NOT EXISTS `answers` (
    `id` BINARY(16) NOT NULL,
    `question_id` BINARY(16) NOT NULL,
    `answer` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3),
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.answers.question_id` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // Optional: Destruktive Änderungen
    }
}
