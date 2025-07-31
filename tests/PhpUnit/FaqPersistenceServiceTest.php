<?php

declare(strict_types=1);

namespace DIW\AiFaq\Tests\PhpUnit;

use DIW\AiFaq\Service\FaqPersistenceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

final class FaqPersistenceServiceTest extends TestCase
{
    /**
     * Test: verifies that FaqPersistenceService::buildUpsertData()
     * creates the correct nested payload from a given FAQ array
     * AND that ::upsert() forwards this payload to the repository.
     */
    public function testBuildUpsertDataAndUpsert(): void
    {
        // Arrange
        $product = new ProductEntity();
        $productId = Uuid::randomHex();
        $product->setId($productId);

        $repo = $this->createMock(EntityRepository::class);
        $logger = new NullLogger();
        $service = new FaqPersistenceService($repo, $logger);

        $faqArray = [
            [
                'question' => 'Why?',
                'answer' => 'Because.'
            ],
            [
                'question' => 'How?',
                'answer' => 'Quickly.'
            ],
        ];

        // Act: build payload
        $payload = $service->buildUpsertData($product, $faqArray);

        // Expected structure
        $expected = [
            'id' => $productId,
            'questions' => [
                [
                    'id' => Uuid::fromStringToHex('Why?'),
                    'productId' => $productId,
                    'question' => 'Why?',
                    'answers' => [
                        [
                            'id' => Uuid::fromStringToHex('Because.'),
                            'questionId' => Uuid::fromStringToHex('Why?'),
                            'answer' => 'Because.',
                        ],
                    ],
                ],
                [
                    'id' => Uuid::fromStringToHex('How?'),
                    'productId' => $productId,
                    'question' => 'How?',
                    'answers' => [
                        [
                            'id' => Uuid::fromStringToHex('Quickly.'),
                            'questionId' => Uuid::fromStringToHex('How?'),
                            'answer' => 'Quickly.',
                        ],
                    ],
                ],
            ],
        ];

        // Assert: buildUpsertData returns expected nested structure
        $this->assertSame($expected, $payload);

        // Act: perform upsert
        $context = Context::createDefaultContext();
        $captured = null;
        $repo->expects($this->once())->method('upsert')
            ->with($this->callback(function ($payload) use (&$captured) {
                $captured = $payload;
                return true;
            }), $this->isInstanceOf(Context::class));
        $service->upsert([$payload], $context);

        // Assert: repository upsert received the same payload
        $this->assertSame([$payload], $captured);
    }
}
