<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\PaymentMethod;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $satirlar = [];

        foreach ((array) $this->input('satirlar', []) as $satir) {
            if (! is_array($satir)) {
                continue;
            }

            $miktar = $this->sayi($satir['quantity'] ?? '');
            $fiyat = $this->sayi($satir['unit_price'] ?? '');

            // Tamamen boş satırlar sessizce atılır; kullanıcı fazladan satır açmış olabilir.
            if (($satir['product_id'] ?? '') === '' && $miktar === '0' && $fiyat === '0') {
                continue;
            }

            $satirlar[] = [
                'product_id' => $satir['product_id'] ?? null,
                'quantity' => $miktar,
                'unit_price' => $fiyat,
            ];
        }

        $this->merge([
            'satirlar' => $satirlar,
            'indirim' => $this->sayi($this->input('indirim', '0')),
            'tahsilat' => $this->sayi($this->input('tahsilat', '0')),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getOrFail()->getKey();

        return [
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'warehouse_id' => ['nullable', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'sold_at' => ['required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:sold_at'],
            'indirim' => ['required', 'numeric', 'min:0'],
            'tahsilat' => ['required', 'numeric', 'min:0'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:255'],

            'satirlar' => ['required', 'array', 'min:1'],
            'satirlar.*.product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'satirlar.*.quantity' => ['required', 'numeric', 'gt:0'],
            'satirlar.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $toplam = 0;

                foreach ($this->validated('satirlar') as $satir) {
                    $toplam += (float) $satir['quantity'] * (float) $satir['unit_price'];
                }

                if ((float) $this->validated('indirim') > $toplam) {
                    $validator->errors()->add('indirim', 'İndirim, satış tutarından büyük olamaz.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'müşteri',
            'warehouse_id' => 'depo',
            'sold_at' => 'satış tarihi',
            'due_on' => 'vade tarihi',
            'indirim' => 'indirim',
            'tahsilat' => 'tahsilat',
            'method' => 'ödeme yöntemi',
            'satirlar' => 'satış satırları',
            'satirlar.*.product_id' => 'ürün',
            'satirlar.*.quantity' => 'miktar',
            'satirlar.*.unit_price' => 'birim fiyat',
        ];
    }

    public function messages(): array
    {
        return [
            'satirlar.required' => 'Satışa en az bir ürün eklemelisiniz.',
            'satirlar.min' => 'Satışa en az bir ürün eklemelisiniz.',
            'satirlar.*.quantity.gt' => 'Miktar sıfırdan büyük olmalı.',
        ];
    }

    /**
     * "1.499,90" gibi Türkçe yazımı sayıya çevirir.
     */
    private function sayi(mixed $deger): string
    {
        $metin = str_replace(' ', '', (string) $deger);

        if ($metin === '') {
            return '0';
        }

        if (str_contains($metin, ',')) {
            $metin = str_replace(['.', ','], ['', '.'], $metin);
        }

        return $metin;
    }
}
