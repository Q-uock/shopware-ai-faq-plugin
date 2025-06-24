<?php

namespace DIW\AiFaq\Extension;

use DIW\AiFaq\Core\Content\Question\QuestionDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToManyAssociationField('questions', QuestionDefinition::class, 'product_id', 'id'
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}