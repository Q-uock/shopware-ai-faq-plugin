<?php

namespace DIW\AiFaq\Subscriber;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger,
        private readonly HttpClientInterface $httpClient
    )
    {

    }
    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        // Ignoriere Nicht-Live-Versionen
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id',$event->getIds()));

        /** @var EntitySearchResult<ProductCollection<ProductEntity>> $searchResult */
        $searchResult = $this->productRepository->search($criteria, $event->getContext());
        $initialPrompt = $this->systemConfigService->get('AiFaq.config.FaqPrompt');
        $url = $this->systemConfigService->get('AiFaq.config.FaqUrl');
        $model = $this->systemConfigService->get('AiFaq.config.FaqModel');
        dump($initialPrompt, $url, $model);
        /** @var ProductEntity $product */
        foreach ($searchResult as $product) {
            $prompt = str_replace(
                ['{name}', '{description}'],
                [$product->getName(), $product->getDescription()],
                $initialPrompt
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
dump($faqText);
                $this->logger->info('Generated FAQ: ' . $faqText);
            } catch (\Throwable $e) {
                $this->logger->error('AI FAQ Generation failed: ' . $e->getMessage());
            }
        }
    }
}
