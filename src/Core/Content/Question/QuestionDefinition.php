<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Question;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use DIW\AiFaq\Core\Content\Answer\AnswerDefinition;

class QuestionDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'faq_question';
    }

    public function getEntityClass(): string
    {
        return QuestionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return QuestionCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new ApiAware(), new PrimaryKey()),
            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id', false)),
            (new StringField('question', 'question'))->addFlags(new Required(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            new OneToManyAssociationField(
                'answers',
                AnswerDefinition::class,
                'question_id',
                'id'
            ),
        ]);
    }
}
