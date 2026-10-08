<?php

namespace App\Http\Requests\Admin;

use App\Support\AccountOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTalentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'location' => ['required', 'string', 'max:255'],
            'desired_position' => ['required', 'string', 'max:255'],
            'experience_level' => ['required', Rule::in(AccountOptions::EXPERIENCE_LEVELS)],
            'work_setup' => ['required', Rule::in(AccountOptions::WORK_SETUPS)],
            'skills' => ['nullable', 'array', 'max:12'],
            'skills.*' => ['string', Rule::in(AccountOptions::CANDIDATE_SKILLS)],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(AccountOptions::TALENT_STATUSES)],
            'cv' => ['nullable', 'file', 'mimes:'.AccountOptions::CV_MIMES, 'max:'.AccountOptions::CV_MAX_SIZE],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists.',
            'cv.mimes' => 'Upload the CV as a PDF, DOC, or DOCX file.',
            'cv.max' => 'The CV must be 5 MB or smaller.',
        ];
    }
}
