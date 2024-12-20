<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao;

use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\ClientInterface;
use BrokeYourBike\ResolveUri\ResolveUriTrait;
use BrokeYourBike\HttpEnums\HttpMethodEnum;
use BrokeYourBike\HttpClient\HttpClientTrait;
use BrokeYourBike\HttpClient\HttpClientInterface;
use BrokeYourBike\HasSourceModel\HasSourceModelTrait;
use BrokeYourBike\Bizao\Responses\TransactionResponse;
use BrokeYourBike\Bizao\Responses\TokenResponse;
use BrokeYourBike\Bizao\Interfaces\TransactionInterface;
use BrokeYourBike\Bizao\Interfaces\ConfigInterface;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class Client implements HttpClientInterface
{
    use HttpClientTrait;
    use ResolveUriTrait;
    use HasSourceModelTrait;

    private ConfigInterface $config;
    private CacheInterface $cache;

    public function __construct(ConfigInterface $config, ClientInterface $httpClient, CacheInterface $cache)
    {
        $this->config = $config;
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    public function authTokenCacheKey(): string
    {
        return get_class($this) . ':authToken:';
    }

    public function getAuthToken(): string
    {
        if ($this->cache->has($this->authTokenCacheKey())) {
            $cachedToken = $this->cache->get($this->authTokenCacheKey());
            if (is_string($cachedToken)) {
                return $cachedToken;
            }
        }

        $response = $this->fetchAuthTokenRaw();
        $this->cache->set($this->authTokenCacheKey(), $response->access_token, $response->getTTL());
        return (string) $response->access_token;
    }

    public function fetchAuthTokenRaw(): TokenResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            \GuzzleHttp\RequestOptions::AUTH => [
                $this->config->getUsername(),
                $this->config->getPassword(),
            ],
            \GuzzleHttp\RequestOptions::FORM_PARAMS => [
                'grant_type' => 'client_credentials',
            ],
        ];

        $response = $this->httpClient->request(
            HttpMethodEnum::POST->value,
            (string) $this->resolveUriFor(rtrim($this->config->getUrl(), '/'), '/token'),
            $options
        );

        return new TokenResponse($response);
    }

    public function payout(TransactionInterface $transaction): TransactionResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->getAuthToken()}",
                'mno-name' => $transaction->getRecipientProvider() ? strtolower($transaction->getRecipientProvider()) : null,
                'country-code' => $transaction->getRecipientCountry() ? strtolower($transaction->getRecipientCountry()) : null,
                'channel' => 'tpe',
                'lang' => 'fr',
            ],
            \GuzzleHttp\RequestOptions::JSON => [
                'currency' => $transaction->getCurrency(),
                'amount' => $transaction->getAmount(),
                'order_id' => $transaction->getReference(),
                'reference' => $this->config->getMerchantReference(),
                'state' => 'COMPLETED',
                'user_msisdn' => $transaction->getRecipientPhone(),
                'return_url' => $this->config->getMerchantReturnUrl(),
                'cancel_url' => $this->config->getMerchantCancelUrl(),
            ],
        ];

        $response = $this->httpClient->request(
            HttpMethodEnum::POST->value,
            (string) $this->resolveUriFor(rtrim($this->config->getUrl(), '/'), '/mobilemoney/v1'),
            $options
        );

        return new TransactionResponse($response);
    }

    public function status(string $reference): TransactionResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->getAuthToken()}",
            ],
        ];

        $response = $this->httpClient->request(
            HttpMethodEnum::GET->value,
            (string) $this->resolveUriFor(rtrim($this->config->getUrl(), '/'), "/mobilemoney/v1/getStatus/{$reference}"),
            $options
        );

        return new TransactionResponse($response);
    }
}
