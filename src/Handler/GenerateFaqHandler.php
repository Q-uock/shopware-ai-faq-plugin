<?php

declare(strict_types=1);

namespace DIW\AiFaq\Handler;

use DIW\AiFaq\Messages\GenerateFaqMessage;
use DIW\AiFaq\Service\FaqGeneratorService;
use Shopware\Core\Framework\Context;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateFaqHandler
{

    public function __construct(
        private readonly FaqGeneratorService $faqGenerator,
    ) {
    }

    public function __invoke(GenerateFaqMessage $message): void
    {
        $this->faqGenerator->generate($message->getProductIds());
    }
}
