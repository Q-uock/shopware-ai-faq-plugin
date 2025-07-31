<?php

declare(strict_types=1);

namespace DIW\AiFaq\Service;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PromptBuilderService
{
    public function __construct(
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    /**
     * Builds the final prompt string for a given product.
     */
    public function build(ProductEntity $product): string
    {
        $initialPrompt = (string) $this->systemConfigService->get('AiFaq.config.FaqPrompt');

        $prompt = $initialPrompt .
            ' Information: Product-Name: {name}, Product-Description: {description}. ' .
            'Give me the FAQ as a JSON Response. The length of each question and answer must not exceed 200 characters. ' .
            'For Example: [{"question": "question", "answer": "answer"}].';

        return str_replace(
            ['{name}', '{description}'],
            [$product->getName(), $product->getDescription()],
            $prompt
        );
    }
}
