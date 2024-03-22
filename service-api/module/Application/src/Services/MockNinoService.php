<?php

declare(strict_types=1);

namespace Application\Services;

use Application\Services\Contract\NINOServiceInterface;

class MockNinoService implements NINOServiceInterface
{
    public function validateNINO(string $nino): string
    {
        if (str_ends_with($nino, 'C')) {
            return 'No Match';
        }
        elseif (str_ends_with($nino, 'D')) {
            return 'Not Enough Details To Continue';
        } else {
            return 'Pass';
        }
    }
}
