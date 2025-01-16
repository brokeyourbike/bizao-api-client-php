<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use BrokeYourBike\Bizao\Responses\TransactionsResponse;
use BrokeYourBike\Bizao\Interfaces\TransactionInterface;
use BrokeYourBike\Bizao\Interfaces\ConfigInterface;
use BrokeYourBike\Bizao\Client;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class PayoutTest extends TestCase
{
    /** @test */
    public function it_can_handle_exception(): void
    {
        $transaction = $this->getMockBuilder(TransactionInterface::class)->getMock();

        /** @var TransactionInterface $transaction */
        $this->assertInstanceOf(TransactionInterface::class, $transaction);

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getUrl')->willReturn('https://api.example/');

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(400);
        $mockedResponse->method('getBody')
            ->willReturn('{
                    "requestError": {
                        "serviceException": {
                            "messageId": "BMT20010",
                            "text": "INSUFFICIENT ACCOUNT BALANCE",
                            "variables": "Request failed."
                        }
                    }
                }');

        /** @var \Mockery\MockInterface $mockedClient */
        $mockedClient = \Mockery::mock(\GuzzleHttp\Client::class);
        $mockedClient->shouldReceive('request')->once()->andReturn($mockedResponse);

        $mockedCache = $this->getMockBuilder(CacheInterface::class)->getMock();
        $mockedCache->method('has')->willReturn(true);
        $mockedCache->method('get')->willReturn('secure-token');

        /**
         * @var ConfigInterface $mockedConfig
         * @var \GuzzleHttp\Client $mockedClient
         * @var CacheInterface $mockedCache
         * */
        $api = new Client($mockedConfig, $mockedClient, $mockedCache);

        $requestResult = $api->payout($transaction);
        $this->assertInstanceOf(TransactionsResponse::class, $requestResult);
        $this->assertEquals('INSUFFICIENT ACCOUNT BALANCE', $requestResult->serviceExceptionText);
    }
}