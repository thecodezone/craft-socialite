# socialite plugin for Craft CMS 3.x

Login to Craft with third-party services like Azure and Google. 

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require thecodezone/socialite

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for socialite.

## socialite Overview

Provides turn-key social auth to CraftCMS.

The following services are supported: 

- Generic social providers
- Azure
- Google

## Configuring socialite

``` php
    return [
        'providers' => [
            'generic' => [
                'clientId' => getenv('SERVICE_ID'),    // The client ID assigned to you by the provider
                'clientSecret' => getenv('SERVICE_SECRET'),   // The client password assigned to you by the provider
                'urlAuthorize' => 'https://example.com/oauth2/lockdin/authorize',
                'urlAccessToken' => 'https://example.com/oauth2/lockdin/token',
                'urlResourceOwnerDetails' => 'https://example.com/oauth2/lockdin/resource'
            ],
            'azure' => [
                'clientId' => getenv('AZURE_CLIENT_ID'),
                'clientSecret' => getenv('AZURE_CLIENT_SECRET')
            ],
            'google' => [
                'clientId' => getenv('GOOGLE_CLIENT_ID'),
                'clientSecret' => getenv('GOOGLE_CLIENT_SECRET'),
                'hostedDomain' => getenv('GOOGLE_HOSTED_DOMAIN'),
            ]
        ]
    ];
```

## Using socialite

### Adding buttons

``` html
    <a href="{{ craft.socialite.url('azure') }}">
        Login with Azure
    </a>
    <a href="{{ craft.socialite.url('google') }}">
        Login with Google
    </a>
```

## socialite Roadmap

- Add more services.

Brought to you by [CodeZone](https://codezone.io)
