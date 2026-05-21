<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'      => ['required', 'exists:plans,id'],
            'prefix'       => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9-_]*$/'],
            'length'       => ['required', 'integer', 'min:4', 'max:20'],
            'charset_mode' => ['required', 'in:numeric,alpha_upper,alpha_lower,alpha,alphanumeric'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:500'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required'      => 'Paket harus dipilih.',
            'plan_id.exists'        => 'Paket tidak ditemukan.',
            'length.min'            => 'Panjang minimal 4 karakter.',
            'length.max'            => 'Panjang maksimal 20 karakter.',
            'quantity.max'          => 'Maksimal 500 voucher per generate.',
            'charset_mode.in'       => 'Jenis karakter tidak valid.',
            'prefix.regex'          => 'Prefix hanya boleh huruf, angka, strip, dan underscore.',
        ];
    }
}
