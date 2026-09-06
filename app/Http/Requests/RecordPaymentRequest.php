<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tutar = trim((string) $this->input('tutar'));

        if ($tutar !== '') {
            $tutar = str_replace(' ', '', $tutar);

            if (str_contains($tutar, ',')) {
                $tutar = str_replace(['.', ','], ['', '.'], $tutar);
            }

            $this->merge(['tutar' => $tutar]);
        }
    }

    public function rules(): array
    {
        return [
            'tutar' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'ay' => ['required', 'integer', 'min:1', 'max:36'],
            'not' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tutar' => 'tutar',
            'ay' => 'süre',
            'not' => 'not',
        ];
    }

    public function amountMinor(): int
    {
        return (int) round(((float) $this->validated('tutar')) * 100);
    }
}
