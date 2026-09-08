<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SalePayment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Satış tahsilatı alındı. Finans modülü bunu dinleyip kasaya gelir yazar;
 * modül kapalıysa hiçbir şey olmaz. Satış modülü finanstan habersiz kalır.
 */
class SalePaymentRecorded
{
    use Dispatchable;

    public function __construct(public readonly SalePayment $payment) {}
}
