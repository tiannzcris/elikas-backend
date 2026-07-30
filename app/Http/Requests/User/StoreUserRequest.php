<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            // By role NAME, not role_id -- more readable for whoever's
            // building against this API than requiring them to already
            // know arbitrary role_id integers. 'resident' deliberately
            // excluded: residents never log in at all, per Chapter 1 scope
            // (public API, no account) -- this endpoint only ever creates
            // staff accounts.
            'role' => ['required', 'in:administrator,cswd_personnel,barangay_official'],
            'barangay_id' => ['nullable', 'required_if:role,barangay_official', 'integer', 'exists:barangays,id'],
        ];
    }
}
