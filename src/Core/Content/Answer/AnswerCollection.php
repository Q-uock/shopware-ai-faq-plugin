<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Answer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(AnswerEntity $entity)
 * @method void              set(string $key, AnswerEntity $entity)
 * @method AnswerEntity[]    getIterator()
 * @method AnswerEntity[]    getElements()
 * @method AnswerEntity|null get(string $key)
 * @method AnswerEntity|null first()
 * @method AnswerEntity|null last()
 */
class AnswerCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return AnswerEntity::class;
    }
}
