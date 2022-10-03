<?php

return [
    'frontend' => [
        'typo3fluid_api/api' => [
            'target' => \PhilipHartmann\TYPO3FluidApi\Middleware\Api::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
            ],
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ]
];
