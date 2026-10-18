<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employeeProfile !== null;
    }

    public function rules(): array
    {
        return ['phone' => ['nullable', 'string', 'max:32']];
    }
}
