<?php

declare(strict_types=1);

return [
    'page' => [
        'directory' => 'pages',
        'template' => 'pages/default',
        'listed' => false,
    ],
    'post' => [
        'directory' => 'posts',
        'template' => 'posts/default',
        'listed' => true,
    ],
    'case' => [
        'directory' => 'types/cases',
        'template' => 'types/case',
        'listed' => true,
    ],
    'service' => [
        'directory' => 'types/services',
        'template' => 'types/service',
        'listed' => false,
    ],
    'resource' => [
        'directory' => 'types/resources',
        'template' => 'types/resource',
        'listed' => true,
    ],
];
