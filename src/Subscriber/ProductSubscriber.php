<?php

namespace DIW\AiFaq\Subscriber;

use DIW\AiFaq\Messages\GenerateFaqMessage;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductSubscriber implements EventSubscriberInterface
{

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus,
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
        $this->logger->info('onProductWritten wurde aufgerufen!');

        if ($event->getContext()->hasExtension('ai_faq_generation')) {
            return;             // Ignore writes coming from the handler
        }

        // Ignoriere Nicht-Live-Versionen
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $msg = new GenerateFaqMessage($event->getIds());
        $this->messageBus->dispatch($msg);
    }
}
