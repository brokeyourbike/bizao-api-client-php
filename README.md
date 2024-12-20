# bizao-api-client-php

[![Latest Stable Version](https://img.shields.io/github/v/release/brokeyourbike/bizao-api-client-php)](https://github.com/brokeyourbike/bizao-api-client-php/releases)
[![Total Downloads](https://poser.pugx.org/brokeyourbike/bizao-api-client/downloads)](https://packagist.org/packages/brokeyourbike/bizao-api-client)

Bizao API Client for PHP

## Installation

```bash
composer require brokeyourbike/bizao-api-client
```

## Usage

```php
use BrokeYourBike\Bizao\Client;
use BrokeYourBike\Bizao\Interfaces\ConfigInterface;

assert($config instanceof ConfigInterface);
assert($httpClient instanceof \GuzzleHttp\ClientInterface);
assert($psrCache instanceof \Psr\SimpleCache\CacheInterface);

$apiClient = new Client($config, $httpClient, $psrCache);
$apiClient->getAuthToken();
```

## Authors
- [Ivan Stasiuk](https://github.com/brokeyourbike) | [Twitter](https://twitter.com/brokeyourbike) | [LinkedIn](https://www.linkedin.com/in/brokeyourbike) | [stasi.uk](https://stasi.uk)

## License
[BSD-3-Clause License](https://github.com/brokeyourbike/bizao-api-client-php/blob/main/LICENSE)