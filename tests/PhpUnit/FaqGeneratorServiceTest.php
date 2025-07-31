<?php

declare(strict_types=1);

namespace DIW\AiFaq\Tests\PhpUnit;

use DIW\AiFaq\Service\AiClientService;
use DIW\AiFaq\Service\FaqGeneratorService;
use DIW\AiFaq\Service\FaqPersistenceService;
use DIW\AiFaq\Service\PromptBuilderService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Psr\Log\NullLogger;

final class FaqGeneratorServiceTest extends TestCase
{
    /**
     * Test: ensure FaqGeneratorService::generate()
     * – fetches products via repository
     * – builds prompt & parses FAQ via AiClient
     * – delegates write to repository with correct upsert payload
     * – returns that payload to caller
     */
    public function testGenerateBuildsCorrectUpsertPayload(): void
    {
        $product = new ProductEntity();
        $productId = Uuid::randomHex();
        $product->setId($productId);
        $product->setName('Test product');
        $product->setDescription('Nice description');
        $product->setCustomFields([]);

        $repo = $this->createMock(EntityRepository::class);

        // repository->search should return an EntitySearchResult that yields our product
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $repo->method('search')->willReturn($searchResult);

        // Expectation: service should write exactly one upsert with built payload
        $captured = null;
        $repo->expects($this->once())
            ->method('upsert')
            ->with($this->callback(function ($data) use (&$captured) {
                $captured = $data;
                return true;
            }), $this->isInstanceOf(Context::class));

        // System config stub
        $systemConfig = $this->createStub(SystemConfigService::class);
        $systemConfig->method('get')->willReturnMap([
            ['AiFaq.config.FaqPrompt', null, 'FAQ:'],
        ]);

        $logger = new NullLogger();

        $promptBuilder = new PromptBuilderService($systemConfig);

        // AiClientService stub that returns parsed FAQ array
        $aiClient = $this->createStub(AiClientService::class);
        $aiClient->method('requestFaq')->willReturn([
            [
                'question' => 'What?',
                'answer' => 'Because.',
            ],
        ]);

        $persistence = new FaqPersistenceService($repo, $logger);
        $service = new FaqGeneratorService($repo, $promptBuilder, $aiClient, $persistence, $logger);

        $result = $service->generate([$productId]);

        $expected = [
            'id' => $productId,
            'questions' => [
                [
                    'id' => Uuid::fromStringToHex('What?'),
                    'productId' => $productId,
                    'question' => 'What?',
                    'answers' => [
                        [
                            'id' => Uuid::fromStringToHex('Because.'),
                            'questionId' => Uuid::fromStringToHex('What?'),
                            'answer' => 'Because.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert: returned payload matches expectation
        $this->assertSame([$expected], $result);
        // Assert: repository received identical payload
        $this->assertSame([$expected], $captured);
    }

    /**
     * Test: generation skips products that have the custom field
     *       custom_product_faq_disabled = true.
     *       Repository->upsert must NOT be called and result is empty.
     */
    public function testGenerateSkipsDisabledProduct(): void
    {
        $product = new ProductEntity();
        $product->setId(Uuid::randomHex());
        $product->setCustomFields(['custom_product_faq_disabled' => true]);

        $repo = $this->createMock(EntityRepository::class);

        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $repo->method('search')->willReturn($searchResult);

        // Expectation: disabled product ⇒ one upsert with empty payload array
        $repo->expects($this->once())->method('upsert')
            ->with($this->equalTo([]), $this->isInstanceOf(Context::class));

        $systemConfig = $this->createStub(SystemConfigService::class);
        $logger = new NullLogger();
        $promptBuilder = new PromptBuilderService($systemConfig);

        // AiClient won't be invoked but stub anyway
        $aiClient = $this->createStub(AiClientService::class);

        $persistence = new FaqPersistenceService($repo, $logger);
        $service = new FaqGeneratorService($repo, $promptBuilder, $aiClient, $persistence, $logger);

        $result = $service->generate([$product->getId()]);

        // Assert: no upsert data generated
        $this->assertSame([], $result);
    }

    /**
     * Test: generation skips a product when the AI returns null (e.g. API error).
     *       No upsert call, result remains empty.
     */
    public function testGenerateSkipsWhenAiReturnsNull(): void
    {
        $product = new ProductEntity();
        $product->setId(Uuid::randomHex());

        $repo = $this->createMock(EntityRepository::class);
        $searchResult = $this->createMock(EntitySearchResult::class);
        $searchResult->method('getIterator')->willReturn(new \ArrayIterator([$product]));
        $repo->method('search')->willReturn($searchResult);

        // Expectation: AI returned null ⇒ one upsert with empty payload array
        $repo->expects($this->once())->method('upsert')
            ->with($this->equalTo([]), $this->isInstanceOf(Context::class));

        $systemConfig = $this->createStub(SystemConfigService::class);
        $logger = new NullLogger();
        $promptBuilder = new PromptBuilderService($systemConfig);

        $aiClient = $this->createStub(AiClientService::class);
        $aiClient->method('requestFaq')->willReturn(null);

        $persistence = new FaqPersistenceService($repo, $logger);
        $service = new FaqGeneratorService($repo, $promptBuilder, $aiClient, $persistence, $logger);

        $result = $service->generate([$product->getId()]);

        // Assert: empty result when AI returns null
        $this->assertSame([], $result);
    }
}
