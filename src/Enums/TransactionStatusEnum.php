<?php

// Copyright (C) 2024 Ivan Stasiuk <ivan@stasi.uk>.
// Use of this source code is governed by a BSD-style
// license that can be found in the LICENSE file.

namespace BrokeYourBike\Bizao\Enums;

/**
 * @author Ivan Stasiuk <ivan@stasi.uk>
 */
enum TransactionStatusEnum: string
{
    case SUCCESSFUL = 'Successful';
    case PENDING = 'Pending';
    case FAILED = 'Failed';
}
