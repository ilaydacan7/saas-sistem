<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'sku' => strtoupper(trim((string) $this->input('sku'))) ?: null,
            'purchase_price' => $this->normalizeNumber($this->input('purchase_price')),
            'sale_price' => $this->normalizeNumber($this->input('sale_price')),
            'min_stock' => $this->normalizeNumber($this->input('min_stock')),
        ]);
    }

    public function rules(): array
    {
        $mevcut = $this->route('product');

        return [
            'type' => ['required', Rule::enum(ProductType::class)],
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'nullable', 'string', 'max:60',
                Rule::unique('products', 'sku')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->whereNull('deleted_at')
                    ->ignore($mevcut?->getKey()),
            ],
            'unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('tenant_id', $this->user()->tenant_id),
            ],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'sale_price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'vat_rate' => ['required', 'integer', 'min:0', 'max:100'],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'kayıt türü',
            'name' => 'ad',
            'sku' => 'stok kodu',
            'unit_id' => 'birim',
            'purchase_price' => 'alış fiyatı',
            'sale_price' => 'satış fiyatı',
            'vat_rate' => 'KDV oranı',
            'min_stock' => 'minimum stok',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Bu stok kodu başka bir kayıtta kullanılıyor.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productData(): array
    {
        $tur = ProductType::from($this->validated('type'));

        return [
            'type' => $tur,
            'name' => $this->validated('name'),
            'sku' => $this->validated('sku'),
            'unit_id' => $this->validated('unit_id'),
            'purchase_price_minor' => $this->toMinor($this->validated('purchase_price')),
            'sale_price_minor' => $this->toMinor($this->validated('sale_price')),
            'vat_rate' => (int) $this->validated('vat_rate'),
            'tracks_stock' => $tur->tracksStock(),
            'min_stock' => $tur->tracksStock() ? (float) $this->validated('min_stock') : 0,
            'is_active' => $this->boolean('is_active'),
            'note' => $this->validated('note'),
        ];
    }

    private function toMinor(mixed $tutar): int
    {
        return (int) round(((float) $tutar) * 100);
    }

    /**
     * "1.499,90" gibi Türkçe yazımı sayıya çevirir.
     */
    private function normalizeNumber(mixed $deger): string
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
