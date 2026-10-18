<?php

namespace App\Filament\Resources\Employees;

use App\Domain\Accounts\EmployeeAccountService;
use App\Enums\UserRole;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Models\Department;
use App\Models\EmployeeScheduleAssignment;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class EmployeeResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'employee';

    protected static ?string $pluralModelLabel = 'employees';

    protected static ?string $navigationLabel = 'Employees';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('email')->email()->required()->maxLength(190)->unique(ignoreRecord: true),
            TextInput::make('employee_number')->label('Employee number')->required()->maxLength(50),
            Select::make('department_id')->label('Department')->options(fn () => Department::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable(),
            Select::make('position_id')->label('Position')->options(fn () => Position::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable(),
            TextInput::make('phone')->tel()->maxLength(32),
            DatePicker::make('employment_start_date')->label('Employment start date'),
            Toggle::make('is_active')->label('Active')->default(true)->visibleOn('edit'),
            Select::make('password_mode')
                ->label('Temporary password')
                ->options(['generated' => 'Generate securely', 'manual' => 'Enter manually'])
                ->default('generated')
                ->live()
                ->required()
                ->visibleOn('create'),
            TextInput::make('temporary_password')
                ->label('Manual temporary password')
                ->password()
                ->revealable()
                ->requiredIf('password_mode', 'manual')
                ->rule(Password::min(12)->mixedCase()->numbers()->symbols())
                ->visibleOn('create')
                ->visible(fn (Get $get): bool => $get('password_mode') === 'manual'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employeeProfile.employee_number')->label('Employee no.')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('employeeProfile.department.name')->label('Department')->sortable(),
                TextColumn::make('employeeProfile.position.name')->label('Position'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active accounts')])
            ->defaultSort('name')
            ->recordActions([
                Action::make('assignSchedule')
                    ->label('Assign schedule')
                    ->schema([
                        Select::make('work_schedule_id')->options(fn () => WorkSchedule::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))->searchable()->required(),
                        DatePicker::make('effective_from')->required()->default(now()->toDateString()),
                        DatePicker::make('effective_to')->afterOrEqual('effective_from'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $profile = $record->employeeProfile;
                        $overlap = EmployeeScheduleAssignment::query()
                            ->where('employee_profile_id', $profile->id)
                            ->whereDate('effective_from', '<=', $data['effective_to'] ?: '9999-12-31')
                            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $data['effective_from']))
                            ->exists();

                        if ($overlap) {
                            Notification::make()->danger()->title('Schedule dates overlap an existing assignment.')->send();

                            return;
                        }

                        $profile->scheduleAssignments()->create($data + ['created_by' => auth()->id()]);
                        Notification::make()->success()->title('Schedule assigned')->send();
                    }),
                Action::make('resetPassword')
                    ->label('Reset password')
                    ->schema([
                        Select::make('mode')->options(['generated' => 'Generate securely', 'manual' => 'Enter manually'])->default('generated')->live()->required(),
                        TextInput::make('password')->label('Manual temporary password')->password()->revealable()->requiredIf('mode', 'manual')->visible(fn (Get $get): bool => $get('mode') === 'manual'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (User $record, array $data, EmployeeAccountService $accounts): void {
                        $password = $data['mode'] === 'manual' ? ($data['password'] ?? '') : null;
                        Validator::make(['password' => $password], [
                            'password' => $data['mode'] === 'manual'
                                ? ['required', Password::min(12)->mixedCase()->numbers()->symbols()]
                                : ['nullable'],
                        ])->validate();
                        $credential = $accounts->resetPassword($record, $password, auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Temporary password created')
                            ->body("Give this password to the employee in person: {$credential->temporaryPassword}")
                            ->persistent()
                            ->send();
                    }),
                EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::Employee->value)
            ->with(['employeeProfile.department', 'employeeProfile.position']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
