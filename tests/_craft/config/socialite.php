<?php

return [
    'providers' => [
        'generic' => [
            'clientId' => 'demoapp',    // The client ID assigned to you by the provider
            'clientSecret' => 'demopass',   // The client password assigned to you by the provider
            'urlAuthorize' => 'https://example.com/oauth2/lockdin/authorize',
            'urlAccessToken' => 'https://example.com/oauth2/lockdin/token',
            'urlResourceOwnerDetails' => 'https://example.com/oauth2/lockdin/resource'
        ],
        'azure' => [
            'clientId' => '{azure-client-id}',
            'clientSecret' => '{azure-client-secret}',
        ]
    ]
];