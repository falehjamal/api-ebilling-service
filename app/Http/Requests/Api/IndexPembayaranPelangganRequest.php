<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class IndexPembayaranPelangganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'from' => ['sometimes', 'required_with:to', 'date_format:Y-m-d', 'prohibits:bulan'],
            'to' => ['sometimes', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from', 'prohibits:bulan'],
            'bulan' => ['sometimes', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'prohibits:from,to'],
        ];
    }
}
