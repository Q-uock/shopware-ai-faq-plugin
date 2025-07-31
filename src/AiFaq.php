<?php declare(strict_types=1);

namespace DIW\AiFaq;

use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class AiFaq extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->upsertFaqCustomField($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->removeFaqCustomField($uninstallContext->getContext());
    }

    public function activate(ActivateContext $activateContext): void
    {
        // Activate entities, such as a new payment method
        // Or create new entities here, because now your plugin is installed and active for sure
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        // Deactivate entities, such as a new payment method
        // Or remove previously created entities
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        $this->upsertFaqCustomField($updateContext->getContext());
    }

    public function postInstall(InstallContext $installContext): void
    {
    }

    public function postUpdate(UpdateContext $updateContext): void
    {
    }

    private function upsertFaqCustomField(Context $context): void
    {
        /** @var EntityRepository $repo */
        $repo = $this->container->get('custom_field_set.repository');

        $repo->upsert([
            [
                'name' => 'faq_custom_fields',
                'config' => [
                    'label' => [
                        'en-GB' => 'FAQ',
                        'de-DE' => 'FAQ',
                    ],
                ],
                'relations' => [
                    ['entityName' => 'product'],
                ],
                'customFields' => [
                    [
                        'name'   => 'custom_product_faq_disabled',
                        'type'   => 'bool',
                        'config' => [
                            'label' => [
                                'en-GB' => 'Disable FAQ generation',
                                'de-DE' => 'FAQ-Generierung deaktivieren',
                            ],
                            'componentName'   => 'sw-field',
                            'customFieldType' => 'switch',
                        ],
                    ],
                ],
            ],
        ], $context);
    }

    private function removeFaqCustomField(Context $context): void
    {
        /** @var EntityRepository $repo */
        $repo = $this->container->get('custom_field_set.repository');

        // find set id by name
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'faq_custom_fields'));
        $setId = $repo->searchIds($criteria, $context)->firstId();
        if ($setId) {
            $repo->delete([['id' => $setId]], $context);
        }
    }
}
