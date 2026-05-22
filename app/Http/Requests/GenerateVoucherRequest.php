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
            'prefix'       => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9\-_]*$/'],
            'length'       => ['required', 'integer', 'min:4', 'max:20'],
            'charset_mode' => ['required', 'in:numeric,uppercase,lowercase,mixed'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:500'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Validasi tambahan setelah rules: pastikan suffix panjangnya minimal 2 karakter.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $prefix = $this->input('prefix', '');
            $length = (int) $this->input('length', 0);

            $suffixLength = $length - strlen($prefix);
            if ($suffixLength < 2) {
                $validator->errors()->add(
                    'length',
                    'Panjang suffix (panjang total dikurangi prefix) minimal 2 karakter. ' .
                    'Saat ini suffix hanya ' . max(0, $suffixLength) . ' karakter.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'plan_id.required'      => 'Paket harus dipilih.',
            'plan_id.exists'        => 'Paket tidak ditemukan.',
            'length.required'       => 'Panjang voucher wajib diisi.',
            'length.min'            => 'Panjang minimal 4 karakter.',
            'length.max'            => 'Panjang maksimal 20 karakter.',
            'charset_mode.required' => 'Jenis karakter wajib dipilih.',
            'charset_mode.in'       => 'Jenis karakter tidak valid.',
            'quantity.required'     => 'Jumlah voucher wajib diisi.',
            'quantity.min'          => 'Jumlah minimal 1 voucher.',
            'quantity.max'          => 'Maksimal 500 voucher per generate.',
            'prefix.regex'          => 'Prefix hanya boleh huruf, angka, strip, dan underscore.',
        ];
    }
}
