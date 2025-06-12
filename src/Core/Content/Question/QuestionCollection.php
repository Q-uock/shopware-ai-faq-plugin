<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Question;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(QuestionEntity $entity)
 * @method void              set(string $key, QuestionEntity $entity)
 * @method QuestionEntity[]  getIterator()
 * @method QuestionEntity[]  getElements()
 * @method QuestionEntity|null get(string $key)
 * @method QuestionEntity|null first()
 * @method QuestionEntity|null last()
 */
class QuestionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return QuestionEntity::class;
    }
}
