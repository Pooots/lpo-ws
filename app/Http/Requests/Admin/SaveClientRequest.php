<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\AccountOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Admin create (POST) and update (PUT) of a client account and its company profile. */
class SaveClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['email', 'billing_email'] as $key) {
            if (is_string($this->input($key))) {
                $this->merge([$key => strtolower(trim($this->input($key)))]);
            }
        }
    }

    public function rules(): array
    {
        /** @var User|null $client */
        $client = $this->route('client');
        $creating = $client === null;

        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'industry' => ['required', Rule::in(AccountOptions::INDUSTRIES)],
            'company_size' => ['required', Rule::in(AccountOptions::COMPANY_SIZES)],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],

            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'password' => $creating
                ? ['required', 'string', Password::min(8)->letters()->numbers()]
                : ['prohibited'],

            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', Rule::in(AccountOptions::SERVICES)],
            'message' => ['nullable', 'string', 'max:2000'],
            'lead_source' => ['nullable', Rule::in(AccountOptions::LEAD_SOURCES)],

            'engagement_type' => ['nullable', Rule::in(AccountOptions::ENGAGEMENT_TYPES)],
            'team_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'monthly_value' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'currency' => ['required', Rule::in(AccountOptions::CURRENCIES)],
            'contract_start' => ['nullable', 'date'],
            'contract_end' => ['nullable', 'date', 'after_or_equal:contract_start'],
            'account_manager' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(AccountOptions::CLIENT_STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'services.required' => 'Select at least one service.',
            'services.min' => 'Select at least one service.',
            'contract_end.after_or_equal' => 'The contract end must be on or after the start date.',
        ];
    }
}
