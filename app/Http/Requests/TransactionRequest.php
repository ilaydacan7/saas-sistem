<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\PaymentMethod;
use App\Modules\TransactionType;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tutar = str_replace(' ', '', (string) $this->input('amount'));

        if (str_contains($tutar, ',')) {
            $tutar = str_replace(['.', ','], ['', '.'], $tutar);
        }

        $this->merge(['amount' => $tutar]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getOrFail()->getKey();

        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'occurred_on' => ['required', 'date'],
            'category_id' => [
                'nullable',
                Rule::exists('transaction_categories', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('type', $this->input('type')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tür',
            'amount' => 'tutar',
            'occurred_on' => 'tarih',
            'category_id' => 'kategori',
            'description' => 'açıklama',
            'method' => 'ödeme yöntemi',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Tutar sıfırdan büyük olmalı.',
            'category_id.exists' => 'Seçilen kategori bu tür için geçerli değil.',
        ];
    }

    public function transactionType(): TransactionType
    {
        return TransactionType::from($this->validated('type'));
    }

    /**
     * HTTP üzerinden her alan metin gelir; kategori kimliği tamsayıya çevrilir.
     */
    public function categoryId(): ?int
    {
        $deger = $this->validated('category_id');

        return $deger === null || $deger === '' ? null : (int) $deger;
    }

    public function amountMinor(): int
    {
        return (int) round(((float) $this->validated('amount')) * 100);
    }
}
