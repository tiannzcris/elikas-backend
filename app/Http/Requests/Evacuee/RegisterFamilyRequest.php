<?php

namespace App\Http\Requests\Evacuee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegisterFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role is checked by the 'role:' route middleware; barangay-level
        // scoping is checked in the controller (needs the barangay_id from
        // the request body, which isn't available at this stage).
        return true;
    }

    public function rules(): array
    {
        return [
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'home_address' => ['nullable', 'string', 'max:255'],
            'displacement_type' => ['required', 'in:inside_center,outside_center'],
            'evacuation_center_id' => ['nullable', 'required_if:displacement_type,inside_center', 'integer', 'exists:evacuation_centers,id'],
            'is_4ps_beneficiary' => ['boolean'],

            'members' => ['required', 'array', 'min:1'],
            'members.*.first_name' => ['required', 'string', 'max:100'],
            'members.*.middle_name' => ['nullable', 'string', 'max:100'],
            'members.*.last_name' => ['required', 'string', 'max:100'],
            'members.*.suffix' => ['nullable', 'string', 'max:10'],
            'members.*.sex' => ['required', 'in:male,female'],
            'members.*.date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'members.*.civil_status' => ['nullable', 'in:single,married,widowed,separated,divorced'],
            // Required for every member now (not just the head of
            // family) -- if a member genuinely has no personal phone
            // (e.g. a child, or an elderly member without one), staff
            // are expected to reuse the head of family's number rather
            // than leave this blank, so every registered person has a
            // usable contact point on file.
            'members.*.contact_number' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'members.*.is_pwd' => ['boolean'],
            'members.*.pwd_type' => ['nullable', 'required_if:members.*.is_pwd,true', 'string', 'max:100'],
            'members.*.is_pregnant' => ['boolean'],
            'members.*.is_lactating' => ['boolean'],
            'members.*.is_solo_parent' => ['boolean'],
            'members.*.is_indigenous_person' => ['boolean'],
            'members.*.is_4ps_beneficiary' => ['boolean'],
            'members.*.is_head_of_family' => ['required', 'boolean'],
        ];
    }

    /**
     * Plain field names: these messages show directly under each member's
     * field on the Register family form, so "members.0.contact_number"
     * must read as "contact number".
     */
    public function attributes(): array
    {
        return [
            'evacuation_event_id' => 'disaster event',
            'barangay_id' => 'barangay',
            'evacuation_center_id' => 'evacuation center',
            'members.*.first_name' => 'first name',
            'members.*.middle_name' => 'middle name',
            'members.*.last_name' => 'last name',
            'members.*.sex' => 'sex',
            'members.*.date_of_birth' => 'date of birth',
            'members.*.contact_number' => 'contact number',
            'members.*.pwd_type' => 'PWD type',
        ];
    }

    public function messages(): array
    {
        return [
            'members.*.contact_number.regex' => 'Enter an 11-digit mobile number starting with 09 (or +639).',
            'members.*.pwd_type.required_if' => 'Enter the PWD type.',
            'members.*.date_of_birth.before_or_equal' => 'The date of birth can\'t be in the future.',
            'evacuation_center_id.required_if' => 'Choose the evacuation center.',
        ];
    }

    /**
     * Exactly one member must be flagged as head of family -- Family's
     * head_of_family_evacuee_id needs exactly one value to point to, and
     * DROMIC's family-count logic assumes one head per household.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $members = $this->input('members', []);
            $headCount = collect($members)->filter(fn ($m) => ! empty($m['is_head_of_family']))->count();

            if ($headCount !== 1) {
                $validator->errors()->add(
                    'members',
                    $headCount === 0
                        ? 'Mark one member as Head of family.'
                        : "Only one member can be Head of family ({$headCount} are marked)."
                );
            }
        });
    }
}
