<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Question;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use DIW\AiFaq\Core\Content\Answer\AnswerCollection;

class QuestionEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $question = null;
    protected bool $active = true;
    protected ?AnswerCollection $answers = null;

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(?string $question): void
    {
        $this->question = $question;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getAnswers(): ?AnswerCollection
    {
        return $this->answers;
    }

    public function setAnswers(?AnswerCollection $answers): void
    {
        $this->answers = $answers;
    }
}
