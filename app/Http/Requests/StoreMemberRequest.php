<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username'         => [
                'required', 'string', 'min:3', 'max:64',
                'regex:/^[a-zA-Z0-9._-]+$/',
                'unique:members,username',
                'unique:vouchers,username',
            ],
            'password'         => ['required', 'string', 'min:6', 'max:64', 'different:username'],
            'plan_id'          => ['required', 'exists:plans,id'],
            'simultaneous_use' => ['required', 'integer', 'min:1', 'max:10'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'    => 'Username sudah digunakan.',
            'username.regex'     => 'Username hanya boleh huruf, angka, titik, strip, dan underscore.',
            'password.different' => 'Password tidak boleh sama dengan username.',
            'plan_id.exists'     => 'Paket tidak ditemukan.',
        ];
    }
}
