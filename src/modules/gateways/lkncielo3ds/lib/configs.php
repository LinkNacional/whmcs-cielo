<?php

return [
    'name' => 'lkncielo3ds',
    'friendlyName' => 'Cielo 3DS Crédito e Débito',
    'version' => '1.4.0',
    'resources_path' => __DIR__ . '/resources',

    'dev' => [
        'accessTokenUrl' => 'https://mpisandbox.braspag.com.br',
        'apiTransUrl' => 'https://apisandbox.cieloecommerce.cielo.com.br',
        'apiQueryUrl' => 'https://apiquerysandbox.cieloecommerce.cielo.com.br'
    ],

    'prod' => [
        'accessTokenUrl' => 'https://mpi.braspag.com.br',
        'apiTransUrl' => 'https://api.cieloecommerce.cielo.com.br/',
        'apiQueryUrl' => 'https://apiquery.cieloecommerce.cielo.com.br'
    ]
];
