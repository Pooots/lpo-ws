<?php

namespace App\Http\Requests\Auth;

use App\Support\AccountOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'industry' => ['required', Rule::in(AccountOptions::INDUSTRIES)],
            'company_size' => ['required', Rule::in(AccountOptions::COMPANY_SIZES)],
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', Rule::in(AccountOptions::SERVICES)],
            'message' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists. Try signing in instead.',
            'services.required' => 'Select at least one service you are interested in.',
            'services.min' => 'Select at least one service you are interested in.',
            'terms.accepted' => 'Please agree to the terms to continue.',
        ];
    }
}
