<?php

declare(strict_types=1);

namespace Application\KBV;

enum AnswersOutcome: string
{
    case CompletePass = "COMPLETE_PASS";
    case CompleteFail = "COMPLETE_FAIL";
    case Incomplete = "INCOMPLETE";

    public function isComplete(): bool
    {
        return in_array($this, [self::CompletePass, self::CompleteFail]);
    }

    public function isPass(): bool
    {
        return $this === self::CompletePass;
    }
}
