<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'in:administrator,cswd_personnel,barangay_official'],
            'barangay_id' => ['nullable', 'required_if:role,barangay_official', 'integer', 'exists:barangays,id'],
            'status' => ['sometimes', 'in:active,inactive,suspended'],
            // Optional -- only changes the password if actually provided,
            // so editing someone's role doesn't force a password reset too.
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
