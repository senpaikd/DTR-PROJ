<?php

namespace App\Filament\Pages;

use App\Enums\AttendanceStatus;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Reports\AttendanceReportFilters;
use App\Reports\AttendanceReportQuery;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class AttendanceReports extends Page
{
    use WithPagination;

    protected static ?string $navigationLabel = 'Attendance reports';

    protected static ?string $title = 'Attendance reports';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'reports/attendance';

    protected string $view = 'filament.pages.attendance-reports';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'monthly',
            'anchor' => now()->toDateString(),
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('period')->options([
                'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'custom' => 'Custom range',
            ])->required(),
            DatePicker::make('anchor')->label('Day in period')->required(),
            DatePicker::make('from')->label('Custom from'),
            DatePicker::make('to')->label('Custom to')->afterOrEqual('from'),
            Select::make('employee_id')->label('Employee')->options(fn () => EmployeeProfile::query()
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->orderBy('users.name')->pluck('users.name', 'employee_profiles.id'))->searchable(),
            Select::make('department_id')->label('Department')->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
            Select::make('status')->options(collect(AttendanceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => str($case->value)->replace('_', ' ')->title()])->all()),
        ])->columns(4)->statePath('filters');
    }

    public function run(): void
    {
        $normalized = AttendanceReportFilters::fromArray($this->form->getState());
        $this->filters = array_merge($this->filters ?? [], $normalized->toArray());
        $this->resetPage();
    }

    public function getRecordsProperty(): LengthAwarePaginator
    {
        $filters = AttendanceReportFilters::fromArray($this->filters ?? []);

        return app(AttendanceReportQuery::class)->build($filters)->paginate(25);
    }

    public function exportUrl(string $format): string
    {
        return route('admin.reports.export', ['format' => $format] + AttendanceReportFilters::fromArray($this->filters ?? [])->toArray());
    }
}
