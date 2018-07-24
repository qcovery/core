<?php
return [
    'helpers' => [
        'factories' => [
            'SearchKeys\View\Helper\SearchKeys\SearchBox' => 'SearchKeys\View\Helper\SearchKeys\SearchBoxFactory',
//            'SearchKeys\View\Helper\SearchKeys\SearchParams' => 'SearchKeys\View\Helper\SearchKeys\SearchParamsFactory',
            'SearchKeys\View\Helper\SearchKeys\SearchTabs' => 'SearchKeys\View\Helper\SearchKeys\SearchTabsFactory',
        ],
        'aliases' => [
            'searchbox' => 'SearchKeys\View\Helper\SearchKeys\SearchBox',
//            'searchParams' => 'SearchKeys\View\Helper\SearchKeys\SearchParams',
            'searchTabs' => 'SearchKeys\View\Helper\SearchKeys\SearchTabs',
        ],
    ],
];
