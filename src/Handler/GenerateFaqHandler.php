<?php

declare(strict_types=1);

namespace DIW\AiFaq\Handler;

use DIW\AiFaq\Messages\GenerateFaqMessage;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Struct\ArrayStruct;
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
    ) {
    }

    public function __invoke(GenerateFaqMessage $message): void
    {
        $productIds = $message->getProductIds();
        $context = new Context(new SystemSource());
        // Mark context so subscriber can ignore writes coming from this handler
        //• Der zweite Parameter muss ein Objekt sein, das von Shopware\Core\Framework\Struct\Struct erbt.
        //• Skalare Werte wie true, 1, Strings … sind deshalb nicht zulässig → Static-Analyzer meldet den Fehler.
        //
        //Wenn Sie nur ein Flag hinterlegen möchten und keine zusätzlichen Daten brauchen, reicht irgendeine leere Struct-Instanz.
        //ArrayStruct ist die kleinste fertige Implementierung, die Shopware selbst mitliefert, deshalb bietet sie sich an:

        $context->addExtension('ai_faq_generation', new ArrayStruct());// wichtig für DAL-Zugriffe

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));
        //$criteria->addAssociation('faq_question');

        $searchResult = $this->productRepository->search($criteria, $context);

        $initialPrompt = $this->systemConfigService->get('AiFaq.config.FaqPrompt');
        $url = $this->systemConfigService->get('AiFaq.config.FaqUrl');
        $model = $this->systemConfigService->get('AiFaq.config.FaqModel');

        $upsertData = [];
        /** @var ProductEntity $product */
        foreach ($searchResult as $product) {
            $prompt = $initialPrompt .
                ' Information: Product-Name: {name}, Product-Description: {description}. Give me the FAQ as a JSON Response. ' .
                'For Example: [{"question": "question", "answer": "answer"}]';
            //$product->getExtension('faq_question');
            $prompt = str_replace(
                ['{name}', '{description}'],
                [$product->getName(), $product->getDescription()],
                $prompt
            );

            $customFaqFields = $product->getCustomFields();
            $disabled = $customFaqFields['custom_product_faq_disabled'] ?? null;

            if ($disabled === true) {
                $this->logger->info('FAQ is disabled for product by CustomField Settings ' . $product->getId());
                continue;
            }

            try {
                $response = $this->httpClient->request('POST', $url, [
                    'json' => [
                        'model' => $model,
                        'prompt' => $prompt,
                        'stream' => false,
                    ],
                ]);

                $re = '`\`\`\`(?<result>.*)\`\`\``';
                $matches = [];
                preg_match($re, $response->getContent(), $matches);
                $data = $response->toArray();
                $faqData = $data['response'] ?? null;
                $this->logger->info('Generated FAQ for product ' . $product->getId() . ': ' . $faqData);

                $cleanResponse = str_replace(['\n', '\"'], ['', '"'], $matches['result']);

                $resultFaq = json_decode($cleanResponse, true);

                if ($resultFaq === null) {
                    continue;
                }

                $faqTexts = [];
                foreach ($resultFaq as $faqText) {
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
                $data = [
                    'id' => $product->getId(),
                    'questions' => $faqTexts
                ];
                $upsertData[] = $data;
            } catch (\Throwable $e) {
                $this->logger->error(
                    'AI FAQ Generation failed for product ' . $product->getId() . ': ' . $e->getMessage()
                );
            }
        }
        /*
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('question.id','123'));

        $criteria->addFilter(new EqualsFilter('product.productMedia.media.fileExtension','jpg'));
        */
        $this->productRepository->upsert($upsertData, $context);
    }
}
