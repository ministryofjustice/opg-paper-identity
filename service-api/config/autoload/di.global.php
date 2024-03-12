<?php

declare(strict_types=1);

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;

$tableName = getenv("PAPER_ID_BACK_DATA_TABLE_NAME");

if (! is_string($tableName) || empty($tableName)) {
    $tableName = 'identity-verify';
}

return [
    'dependencies' => [
        'auto' => [
            'types' => [
                DataImportHandler::class => [
                    'parameters' => [
                        'tableName' => $tableName,
                    ],
                ],
                DataQueryHandler::class => [
                    'parameters' => [
                        'tableName' => $tableName,
                    ],
                ]
            ]
        ]
    ]
];
