<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'password'         => ['nullable', 'string', 'min:6', 'max:64'],
            'plan_id'          => ['required', Rule::exists('plans', 'id')->where(fn ($query) => $query->where('type', 'member')->where('is_active', true))],
            'simultaneous_use' => ['required', 'integer', 'min:1', 'max:10'],
            'activated_at'     => ['nullable', 'date'],
            'status'           => ['required', 'in:active,isolated,expired,inactive'],
            'expired_at'       => ['nullable', 'date', 'after:activated_at'],
            'price_snapshot'   => ['nullable', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }
}
