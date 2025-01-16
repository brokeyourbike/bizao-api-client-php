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
use BrokeYourBike\HasSourceModel\SourceModelInterface;
use BrokeYourBike\HasSourceModel\HasSourceModelTrait;
use BrokeYourBike\Bizao\Responses\TransactionsResponse;
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

    // https://www.bizao.com/cashin-bulk-mobile-money-2/
    public function payout(TransactionInterface $transaction): TransactionsResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->getAuthToken()}",
                'country-code' => strtolower($transaction->getRecipientCountry()),
                'channel' => 'web',
                'type' => 'bulk',
                'lang' => 'fr',
            ],
            \GuzzleHttp\RequestOptions::JSON => [
                'currency' => $transaction->getCurrency(),
                'reference' => $this->config->getMerchantReference(),
                'batchNumber' => $transaction->getReference(),
                'state' => 'COMPLETED',
                'data' => [[
                    'id' => '001',
                    'beneficiaryFirstName' => $transaction->getRecipientFirstName(),
                    'beneficiaryLastName' => $transaction->getRecipientLastName(),
                    'beneficiaryAddress' => $transaction->getRecipientCountry(),
                    'beneficiaryMobileNumber' => $transaction->getRecipientPhone(),
                    'amount' => $transaction->getAmount(),
                    'mno' => $transaction->getRecipientProvider(),
                    'feesApplicable' => 'YES',
                ]]
            ],
        ];

        if ($transaction instanceof SourceModelInterface){
            $options[\BrokeYourBike\HasSourceModel\Enums\RequestOptions::SOURCE_MODEL] = $transaction;
        }

        $response = $this->httpClient->request(
            HttpMethodEnum::POST->value,
            (string) $this->resolveUriFor(rtrim($this->config->getUrl(), '/'), '/bulk/v1'),
            $options
        );

        return new TransactionsResponse($response);
    }

    // https://www.bizao.com/cashin-bulk-mobile-money-2/
    public function status(TransactionInterface $transaction): TransactionsResponse
    {
        $options = [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$this->getAuthToken()}",
                'channel' => 'web',
                'type' => 'bulk',
            ],
        ];

        if ($transaction instanceof SourceModelInterface){
            $options[\BrokeYourBike\HasSourceModel\Enums\RequestOptions::SOURCE_MODEL] = $transaction;
        }

        $response = $this->httpClient->request(
            HttpMethodEnum::GET->value,
            (string) $this->resolveUriFor(rtrim($this->config->getUrl(), '/'), "/bulk/v1/getStatus/{$transaction->getReference()}"),
            $options
        );

        return new TransactionsResponse($response);
    }
}
