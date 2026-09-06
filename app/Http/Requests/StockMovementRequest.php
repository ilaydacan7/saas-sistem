<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\StockMovementType;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Miktar alanı Türkçe yazımla gelebilir: "12,5" veya "1.250,75".
     */
    protected function prepareForValidation(): void
    {
        $miktar = str_replace(' ', '', (string) $this->input('quantity'));

        if (str_contains($miktar, ',')) {
            $miktar = str_replace(['.', ','], ['', '.'], $miktar);
        }

        $this->merge(['quantity' => $miktar]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getOrFail()->getKey();

        return [
            'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('tenant_id', $tenantId)],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'ürün',
            'warehouse_id' => 'depo',
            'type' => 'hareket türü',
            'quantity' => 'miktar',
            'note' => 'açıklama',
        ];
    }

    public function movementType(): StockMovementType
    {
        return StockMovementType::from($this->validated('type'));
    }

    public function quantity(): float
    {
        return (float) $this->validated('quantity');
    }
}
