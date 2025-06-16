<?php declare(strict_types=1);

namespace DIW\AiFaq\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
#[Package('core')]
class Migration1750079984productId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1750079984;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `questions` ADD COLUMN `product_id` BINARY(16) NULL AFTER `id`');
        $connection->executeStatement('ALTER TABLE `questions` ADD CONSTRAINT `fk.questions.product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive logic
    }
}
