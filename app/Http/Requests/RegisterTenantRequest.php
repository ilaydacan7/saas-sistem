<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Kullanıcı adresini Türkçe karakterle veya boşlukla yazabilir:
        // "Lefkoşa Gönyeli" -> "lefkosa-gonyeli". Reddetmek yerine düzeltiyoruz.
        // Adres boş bırakılmışsa şirket adından türetilir.
        $adres = trim((string) $this->input('slug'));

        if ($adres === '') {
            $adres = trim((string) $this->input('company'));
        }

        $this->merge([
            'slug' => Str::slug($adres),
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::notIn($this->reservedSlugs()),
                Rule::unique('tenants', 'slug'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return [
            'company' => 'şirket adı',
            'slug' => 'adres',
            'name' => 'ad soyad',
            'email' => 'e-posta',
            'password' => 'parola',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => ':attribute geçerli bir adrese çevrilemedi. Harf ve rakam içeren bir ad yazın.',
            'slug.min' => ':attribute en az 3 karakter olmalı.',
            'slug.not_in' => 'Bu :attribute sistem tarafından ayrılmış, lütfen başka bir tane seçin.',
            'slug.unique' => 'Bu :attribute başka bir şirket tarafından kullanılıyor.',
        ];
    }

    /**
     * @return list<string>
     */
    private function reservedSlugs(): array
    {
        return array_values(array_unique(array_merge(
            config('tenancy.reserved_slugs', []),
            config('tenancy.reserved_subdomains', []),
        )));
    }
}
