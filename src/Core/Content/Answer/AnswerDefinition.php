<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Answer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use DIW\AiFaq\Core\Content\Question\QuestionDefinition;

class AnswerDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'faq_answer';
    }

    public function getEntityClass(): string
    {
        return AnswerEntity::class;
    }

    public function getCollectionClass(): string
    {
        return AnswerCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new ApiAware(), new PrimaryKey()),
            (new FkField('question_id', 'questionId', QuestionDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new StringField('answer', 'answer'))->addFlags(new Required(), new ApiAware()),
            (new BoolField('active', 'active'))->addFlags(new ApiAware()),
            new CreatedAtField(),
            new UpdatedAtField(),

            new ManyToOneAssociationField(
                'question',
                'question_id',
                QuestionDefinition::class,
                'id',
                false
            ),
        ]);
    }
}
