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
            // role and barangay_id are deliberately NOT validated here --
            // both are set once at account creation (StoreUserRequest) and
            // never changeable afterward, to prevent both an accidental
            // role change and a barangay official being silently
            // reassigned to a different barangay. FormRequest::validated()
            // only returns fields present in this rules array, so even a
            // manually-crafted request that includes 'role'/'barangay_id'
            // has them silently dropped before UserController::update()
            // ever sees them -- this isn't just a UI restriction.
            'status' => ['sometimes', 'in:active,inactive,suspended'],
            // Optional -- only changes the password if actually provided,
            // so editing someone's role doesn't force a password reset too.
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
