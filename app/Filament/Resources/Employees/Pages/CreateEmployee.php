<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Domain\Accounts\EmployeeAccountService;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    private ?string $temporaryPassword = null;

    protected function handleRecordCreation(array $data): Model
    {
        $profile = collect($data)->only([
            'employee_number', 'department_id', 'position_id', 'phone', 'employment_start_date',
        ])->all();
        $user = collect($data)->only(['name', 'email'])->all();
        $manualPassword = ($data['password_mode'] ?? 'generated') === 'manual'
            ? ($data['temporary_password'] ?? null)
            : null;
        $provisioned = app(EmployeeAccountService::class)->create($user, $profile, $manualPassword, auth()->user());
        $this->temporaryPassword = $provisioned->temporaryPassword;

        return $provisioned->user;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title('Employee account created')
            ->body("Give this temporary password to the employee in person: {$this->temporaryPassword}")
            ->persistent()
            ->send();
    }
}
