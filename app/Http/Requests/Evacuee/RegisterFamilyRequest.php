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
            // Accepts the two formats Filipino users naturally type:
            // 09XXXXXXXXX (11 digits, most common) or +639XXXXXXXXX
            // (international). Semaphore's API itself accepts the local
            // 09-prefixed format directly and normalizes it internally, so
            // no conversion is needed before sending -- this regex is
            // purely about catching genuinely malformed input at entry.
            'members.*.contact_number' => ['nullable', 'string', 'regex:/^(09|\+639)\d{9}$/'],
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
     * Exactly one member must be flagged as head of family -- Family's
     * head_of_family_evacuee_id needs exactly one value to point to, and
     * DROMIC's family-count logic assumes one head per household.
     *
     * The head of family's contact_number specifically (not every
     * member's) is required -- this is the number actually used for SMS
     * alerts and the welcome message, so it needs to be reliably present,
     * but requiring it from every individual member would add friction to
     * registration without adding real reachability.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $members = $this->input('members', []);
            $headCount = collect($members)->filter(fn ($m) => ! empty($m['is_head_of_family']))->count();

            if ($headCount !== 1) {
                $validator->errors()->add(
                    'members',
                    "Exactly one member must be marked is_head_of_family (found {$headCount})."
                );

                return;
            }

            $headIndex = collect($members)->search(fn ($m) => ! empty($m['is_head_of_family']));
            if (empty($members[$headIndex]['contact_number'] ?? null)) {
                $validator->errors()->add(
                    "members.{$headIndex}.contact_number",
                    'The head of family\'s contact number is required, since this is used for SMS alerts and notifications.'
                );
            }
        });
    }
}
