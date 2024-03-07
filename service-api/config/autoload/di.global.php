<?php

declare(strict_types=1);

use Application\Fixtures\DataImportHandler;
use Application\Fixtures\DataQueryHandler;

return [
    'dependencies' => [
        'auto' => [
            'types' => [
                DataImportHandler::class => [
                    'parameters' => [
                        'tableName' => getenv('PAPER_ID_BACK_DATA_TABLE_NAME') ?: 'identity-verify',
                    ],
                ],
                DataQueryHandler::class => [
                    'parameters' => [
                        'tableName' => getenv('PAPER_ID_BACK_DATA_TABLE_NAME') ?: 'identity-verify',
                    ],
                ]
            ]
        ]
    ]
];
