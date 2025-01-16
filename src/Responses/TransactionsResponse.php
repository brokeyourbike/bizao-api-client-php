<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao\Responses;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Casters\ArrayCaster;
use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\Attributes\CastWith;
use BrokeYourBike\DataTransferObject\JsonResponse;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
class TransactionsResponse extends JsonResponse
{
    #[MapFrom('meta.batchNumber')]
    public ?string $batchNumber;

    #[MapFrom('meta.reference')]
    public ?string $reference;

    #[MapFrom('requestError.serviceException.text')]
    public ?string $serviceExceptionText;

    /** @var TransactionItem[] $data */
    #[CastWith(ArrayCaster::class, TransactionItem::class)]
    public ?array $data;
}

class TransactionItem extends DataTransferObject
{
    public ?string $status;
    public ?string $statusDescription;
}