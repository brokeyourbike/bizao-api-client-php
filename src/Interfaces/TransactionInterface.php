<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao\Interfaces;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
interface TransactionInterface
{
    public function getReference(): string;
    public function getCurrency(): string;
    public function getAmount(): float;
    public function getRecipientCountry(): ?string;
    public function getRecipientPhone(): ?string;
    public function getRecipientProvider(): ?string;
}
