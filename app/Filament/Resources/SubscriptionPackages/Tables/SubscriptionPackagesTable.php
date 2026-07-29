<?php

namespace App\Filament\Resources\SubscriptionPackages\Tables;

use App\Models\SubscriptionPackage;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SubscriptionPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold'),
                TextColumn::make('duration_unit')
                    ->label('Access')
                    ->badge()
                    ->formatStateUsing(fn (string $state, SubscriptionPackage $record): string => $record->durationLabel()),
                TextColumn::make('users_count')
                    ->label('Assigned')
                    ->counts('users')
                    ->formatStateUsing(fn (int $state, SubscriptionPackage $record): string => $state.' / '.($record->user_limit ?? '∞')),
                TextColumn::make('price')
                    ->label('Type')
                    ->badge()
                    ->state(fn (SubscriptionPackage $record): string => $record->is_paid ? 'Paid' : 'Free')
                    ->color(fn (string $state): string => $state === 'Paid' ? 'primary' : 'success'),
                IconColumn::make('is_active')->label('Active')->boolean(),
                IconColumn::make('is_visible')->label('On website')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_visible')->label('Shown on website'),
                TernaryFilter::make('is_paid')->label('Paid'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }
}
