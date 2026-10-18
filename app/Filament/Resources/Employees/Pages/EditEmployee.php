<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Domain\Accounts\EmployeeAccountService;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data + ($this->record->employeeProfile?->only([
            'employee_number', 'department_id', 'position_id', 'phone', 'employment_start_date',
        ]) ?? []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $isActive = (bool) ($data['is_active'] ?? $record->is_active);
            $record->update(Arr::only($data, ['name', 'email']));
            if ($record->is_active !== $isActive) {
                app(EmployeeAccountService::class)->setActiveStatus($record, $isActive, auth()->user());
            }
            $record->employeeProfile()->updateOrCreate([], Arr::only($data, [
                'employee_number', 'department_id', 'position_id', 'phone', 'employment_start_date',
            ]));

            return $record->fresh();
        });
    }
}
