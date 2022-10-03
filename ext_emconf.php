<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'TYPO3 Fluid API',
    'description' => 'API to render TYPO3 Fluid templates or partials.',
    'category' => 'fe',
    'state' => 'stable',
    'author' => 'Philip Hartmann',
    'author_email' => 'hartm.philip@gmail.com',
    'version' => '0.2.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
