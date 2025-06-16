<?php declare(strict_types=1);

namespace DIW\AiFaq\Handler;

use DIW\AiFaq\Messages\GenerateFaqMessage;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Shopware\Core\Framework\Context;

#[AsMessageHandler]
class GenerateFaqHandler
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(GenerateFaqMessage $message): void
    {
        $productIds = $message->getProductIds();
        $context = new Context(new SystemSource()); // wichtig für DAL-Zugriffe

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));

        $searchResult = $this->productRepository->search($criteria, $context);

        $initialPrompt = $this->systemConfigService->get('AiFaq.config.FaqPrompt');
        $url = $this->systemConfigService->get('AiFaq.config.FaqUrl');
        $model = $this->systemConfigService->get('AiFaq.config.FaqModel');

        foreach ($searchResult as $product) {
            $prompt = $initialPrompt .
                ' Information: Product-Name: {name}, Product-Description: {description}. Give me the FAQ as a JSON Response. ' .
                'For Example: [{"question": "question", "answer": "answer"}]';

            $prompt = str_replace(
                ['{name}', '{description}'],
                [$product->getName(), $product->getDescription()],
                $prompt
            );


            try {
                $response = $this->httpClient->request('POST', $url, [
                    'json' => [
                        'model' => $model,
                        'prompt' => $prompt,
                        'stream' => false,
                    ],
                ]);

                $data = $response->toArray();
                $faqText = $data['response'] ?? 'No response';
                $this->logger->info('Generated FAQ for product ' . $product->getId() . ': ' . $faqText);
                // TODO: Ergebnis ggf. in Datenbank speichern
            } catch (\Throwable $e) {
                $this->logger->error('AI FAQ Generation failed for product ' . $product->getId() . ': ' . $e->getMessage());
            }
        }
    }
}
