<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'password'         => ['nullable', 'string', 'min:6', 'max:64'],
            'plan_id'          => ['required', 'exists:plans,id'],
            'simultaneous_use' => ['required', 'integer', 'min:1', 'max:10'],
            'status'           => ['required', 'in:active,isolated,expired,inactive'],
            'expired_at'       => ['nullable', 'date'],
            'price_snapshot'   => ['nullable', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
