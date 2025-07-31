<?php

declare(strict_types=1);

namespace DIW\AiFaq\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

class FaqPersistenceService
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Builds the upsert payload for one product.
     *
     * @param array<int, array<string,string>> $faqArray decoded JSON from AI
     * @return array<string,mixed>
     */
    public function buildUpsertData(ProductEntity $product, array $faqArray): array
    {
        $faqTexts = [];
        foreach ($faqArray as $faqText) {
            $questionId = Uuid::fromStringToHex($faqText['question']);
            $faqTexts[] = [
                'id' => $questionId,
                'productId' => $product->getId(),
                'question' => $faqText['question'],
                'answers' => [
                    [
                        'id' => Uuid::fromStringToHex($faqText['answer']),
                        'questionId' => $questionId,
                        'answer' => $faqText['answer'],
                    ],
                ],
            ];
        }

        return [
            'id' => $product->getId(),
            'questions' => $faqTexts,
        ];
    }

    /**
     * Writes aggregated upsert data to the database.
     *
     * @param list<array<string,mixed>> $upsertData
     */
    public function upsert(array $upsertData, Context $context): void
    {
        $this->logger->info('Persisting FAQ data', ['count' => \count($upsertData)]);
        $this->productRepository->upsert($upsertData, $context);
    }
}
