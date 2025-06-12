<?php declare(strict_types=1);

namespace DIW\AiFaq\Core\Content\Answer;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use DIW\AiFaq\Core\Content\Question\QuestionEntity;

class AnswerEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $answer = null;
    protected bool $active = true;
    protected ?string $questionId = null;
    protected ?QuestionEntity $question = null;

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): void
    {
        $this->answer = $answer;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getQuestionId(): ?string
    {
        return $this->questionId;
    }

    public function setQuestionId(?string $questionId): void
    {
        $this->questionId = $questionId;
    }

    public function getQuestion(): ?QuestionEntity
    {
        return $this->question;
    }

    public function setQuestion(?QuestionEntity $question): void
    {
        $this->question = $question;
    }
}
