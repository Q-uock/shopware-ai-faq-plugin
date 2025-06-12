<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Questionsanswers;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(QuestionsanswersEntity $entity)
 * @method void set(string $key, QuestionsanswersEntity $entity)
 * @method QuestionsanswersEntity[] getIterator()
 * @method QuestionsanswersEntity[] getElements()
 * @method QuestionsanswersEntity|null get(string $key)
 * @method QuestionsanswersEntity|null first()
 * @method QuestionsanswersEntity|null last()
 */
class QuestionsanswersCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return QuestionsanswersEntity::class;
    }
}
