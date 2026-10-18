<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $recordTitleAttribute = 'action';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('System')->searchable(),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('subject_type')->label('Subject')->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('subject_id')->label('Subject ID')->placeholder('—'),
                TextColumn::make('ip_address')->label('IP')->placeholder('—'),
            ])
            ->filters([SelectFilter::make('action')->options(fn () => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all())])
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
