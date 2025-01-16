<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao\Tests;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use BrokeYourBike\Bizao\Responses\TransactionsResponse;
use BrokeYourBike\Bizao\Responses\TransactionItem;
use BrokeYourBike\Bizao\Interfaces\TransactionInterface;
use BrokeYourBike\Bizao\Interfaces\ConfigInterface;
use BrokeYourBike\Bizao\Enums\TransactionStatusEnum;
use BrokeYourBike\Bizao\Client;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class PayoutStatusTest extends TestCase
{
    /** @test */
    public function it_can_handle_array(): void
    {
        $transaction = $this->getMockBuilder(TransactionInterface::class)->getMock();

        /** @var TransactionInterface $transaction */
        $this->assertInstanceOf(TransactionInterface::class, $transaction);

        $mockedConfig = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $mockedConfig->method('getUrl')->willReturn('https://api.example/');

        $mockedResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockedResponse->method('getStatusCode')->willReturn(200);
        $mockedResponse->method('getBody')
            ->willReturn('{
                "meta": {
                    "source": "bizao",
                    "merchant-name": "bizao-bsc@carbon.super",
                    "type": "bulk",
                    "currency": "XOF",
                    "batchNumber": "bulk-sn_07-10-2021_01",
                    "reference": "bsc-bulk-mt-XOF",
                    "feesType": "FIXED_FEE",
                    "lang": "fr",
                    "totalAmount": 11.00,
                    "totalFees": 2.00,
                    "senderFirstName": "Damith",
                    "senderLastName": "Sulochana",
                    "senderAddress": "Colombo",
                    "senderMobileNumber": "2250512345678",
                    "fromCountry": "cm"
                },
                "data": [
                    {
                        "id": "001",
                        "orderId": "bulk-sn_07-10-2021_01-001",
                        "mno": "free",
                        "beneficiaryFirstName": "Salif",
                        "beneficiaryLastName": "KA",
                        "beneficiaryAddress": "",
                        "beneficiaryMobileNumber": "221765151504",
                        "toCountry": "sn",
                        "feesApplicable": "Yes",
                        "amount": 10.00,
                        "fees": 1.00,
                        "status": "Successful",
                        "intTransaction-Id": "1aa27e52-969b-4fd1-8012-da5606b8de71",
                        "extTransaction-Id": "CI211007.1411.A95748"
                    }
                ]
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

        $requestResult = $api->status($transaction);
        $this->assertInstanceOf(TransactionsResponse::class, $requestResult);
        $this->assertCount(1, $requestResult->data);

        $trx = $requestResult->data[0];
        $this->assertInstanceOf(TransactionItem::class, $trx);
        $this->assertEquals(TransactionStatusEnum::SUCCESSFUL->value, $trx->status);
    }
}