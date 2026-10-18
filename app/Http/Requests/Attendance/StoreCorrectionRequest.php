<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->employeeProfile !== null;
    }

    public function rules(): array
    {
        return ['attendance_record_id' => ['nullable', 'integer'], 'work_date' => ['required', 'date_format:Y-m-d'], 'proposed_clock_in_at' => ['nullable', 'date'], 'proposed_clock_out_at' => ['nullable', 'date', 'after:proposed_clock_in_at'], 'reason' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
