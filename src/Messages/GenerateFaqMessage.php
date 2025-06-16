<?php declare(strict_types=1);
namespace DIW\AiFaq\Messages;;

use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class GenerateFaqMessage implements AsyncMessageInterface
{

    public function __construct(private array $productIds)
    {
    }

    public function getProductIds(): array
    {
        return $this->productIds;
    }
}