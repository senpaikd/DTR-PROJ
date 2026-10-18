<?php

namespace App\Filament\Resources\WorkSchedules;

use App\Filament\Resources\WorkSchedules\Pages\CreateWorkSchedule;
use App\Filament\Resources\WorkSchedules\Pages\EditWorkSchedule;
use App\Filament\Resources\WorkSchedules\Pages\ListWorkSchedules;
use App\Models\WorkSchedule;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkScheduleResource extends Resource
{
    protected static ?string $model = WorkSchedule::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(120)->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('Active')->default(true),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Repeater::make('days')
                ->relationship()
                ->label('Schedule days')
                ->schema([
                    Select::make('weekday')->options([
                        1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
                        5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
                    ])->required()->distinct(),
                    Toggle::make('is_workday')->label('Workday')->default(true),
                    TimePicker::make('starts_at')->seconds(false),
                    TimePicker::make('ends_at')->seconds(false),
                    Toggle::make('is_overnight')->label('Ends next day'),
                ])
                ->columns(5)
                ->minItems(1)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('days_count')->counts('days')->label('Days'),
                TextColumn::make('assignments_count')->counts('assignments')->label('Assignments'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkSchedules::route('/'),
            'create' => CreateWorkSchedule::route('/create'),
            'edit' => EditWorkSchedule::route('/{record}/edit'),
        ];
    }
}
