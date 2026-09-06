<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\CustomerType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))) ?: null,
            'phone' => $this->normalizePhone((string) $this->input('phone')),
            'tax_number' => preg_replace('/\D/', '', (string) $this->input('tax_number')) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(CustomerType::class)],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_office' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'digits_between:10,11'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('type') !== CustomerType::Company->value) {
                    return;
                }

                if (blank($this->input('company_name'))) {
                    $validator->errors()->add('company_name', 'Kurumsal kayıtlarda firma unvanı zorunludur.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'kayıt türü',
            'name' => 'ad soyad',
            'company_name' => 'firma unvanı',
            'tax_office' => 'vergi dairesi',
            'tax_number' => 'vergi / TC no',
            'phone' => 'telefon',
            'email' => 'e-posta',
            'city' => 'şehir',
            'address' => 'adres',
            'note' => 'not',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_number.digits_between' => 'Vergi numarası 10, TC kimlik numarası 11 hane olmalıdır.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customerData(): array
    {
        return array_merge($this->validated(), [
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    private function normalizePhone(string $phone): ?string
    {
        $temiz = preg_replace('/[^\d+]/', '', $phone);

        return $temiz === '' ? null : $temiz;
    }
}
