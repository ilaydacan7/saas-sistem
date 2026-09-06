<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantStatus;
use Illuminate\Support\Facades\DB;

class PaymentRecorder
{
    public function record(
        Tenant $tenant,
        int $amountMinor,
        int $months,
        ?User $recordedBy = null,
        ?string $note = null,
    ): Payment {
        return DB::transaction(function () use ($tenant, $amountMinor, $months, $recordedBy, $note) {
            $baslangic = $tenant->paid_until?->isFuture() ? $tenant->paid_until : now();
            $bitis = $baslangic->copy()->addMonths($months);

            $payment = Payment::create([
                'tenant_id' => $tenant->getKey(),
                'amount_minor' => $amountMinor,
                'currency' => config('billing.currency'),
                'method' => config('billing.gateway'),
                'period_starts_at' => $baslangic,
                'period_ends_at' => $bitis,
                'recorded_by_id' => $recordedBy?->getKey(),
                'note' => $note,
            ]);

            $tenant->forceFill([
                'paid_until' => $bitis,
                'status' => $tenant->status === TenantStatus::Suspended
                    ? TenantStatus::Suspended
                    : TenantStatus::Active,
            ])->save();

            return $payment;
        });
    }
}
