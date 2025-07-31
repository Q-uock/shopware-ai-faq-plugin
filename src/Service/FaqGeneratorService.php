<?php

declare(strict_types=1);

namespace DIW\AiFaq\Service;

use DIW\AiFaq\Service\PromptBuilderService;
use DIW\AiFaq\Service\AiClientService;
use DIW\AiFaq\Service\FaqPersistenceService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;

class FaqGeneratorService
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly PromptBuilderService $promptBuilder,
        private readonly AiClientService $aiClient,
        private readonly FaqPersistenceService $persistenceService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generates FAQ entries and returns the prepared upsert payload so that callers (e.g. unit-tests)
     * can assert against the content.
     *
     * @param list<string> $productIds
     * @return array<int, array<string,mixed>> Upsert payload for the productRepository
     */
    public function generate(array $productIds): array
    {
        $context = new Context(new SystemSource());
        // Mark context so subscriber can ignore writes coming from this handler
        $context->addExtension('ai_faq_generation', new ArrayStruct());

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));

        $products = $this->productRepository->search($criteria, $context);

        $upsertData = [];

        /** @var ProductEntity $product */
        foreach ($products as $product) {
            // Skip if disabled
            $customFaqFields = $product->getCustomFields();
            if (($customFaqFields['custom_product_faq_disabled'] ?? false) === true) {
                $this->logger->info('FAQ disabled via custom field for product ' . $product->getId());
                continue;
            }

            $prompt = $this->promptBuilder->build($product);
            $faqArray = $this->aiClient->requestFaq($prompt);

            if ($faqArray === null) {
                continue;
            }

            $upsertData[] = $this->persistenceService->buildUpsertData($product, $faqArray);
        }
        $this->logger->info("FAQ upsert payload:\n" . json_encode($upsertData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->persistenceService->upsert($upsertData, $context);

        return $upsertData;
    }
}
