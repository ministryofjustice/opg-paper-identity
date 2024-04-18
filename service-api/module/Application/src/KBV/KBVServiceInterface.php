<?php

declare(strict_types=1);

namespace Application\KBV;

interface KBVServiceInterface
{
    /**
     * @return array{
     *  question: string,
     *  prompts: string[],
     *  answer: string
     * }[]
     */
    public function getKBVQuestions(string $uuid): array;
}
