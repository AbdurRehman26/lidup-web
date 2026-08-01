<?php

namespace App\Filament\Resources\SubscriptionPackages\Tables;

use App\Models\SubscriptionPackage;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;

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
                TextColumn::make('is_paid')
                    ->label('Type')
                    ->badge()
                    ->state(fn (SubscriptionPackage $record): string => $record->is_paid ? 'Paid' : 'Free')
                    ->color(fn (string $state): string => $state === 'Paid' ? 'primary' : 'success'),
                TextColumn::make('price')
                    ->label('Price')
                    ->state(fn (SubscriptionPackage $record): string => match (true) {
                        ! $record->is_paid => 'Free',
                        $record->price === null => 'Not set',
                        default => self::formatMoney($record->price, $record->currency),
                    })
                    ->description(fn (SubscriptionPackage $record): ?string => $record->is_paid && $record->original_price !== null
                        ? 'Original: '.self::formatMoney($record->original_price, $record->currency)
                        : null)
                    ->weight('semibold')
                    ->color(fn (SubscriptionPackage $record): string => $record->is_paid && $record->price === null ? 'danger' : 'primary')
                    ->sortable(),
                TextColumn::make('billing_interval')
                    ->label('Billing')
                    ->badge()
                    ->state(fn (SubscriptionPackage $record): ?string => $record->is_paid ? $record->billing_interval : null)
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'year' => 'Yearly',
                        'one_time' => 'One-time',
                        default => 'Monthly',
                    })
                    ->placeholder('—'),
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

    private static function formatMoney(string|int|float $amount, string $currency): string
    {
        return Number::currency(
            (float) $amount,
            in: strtoupper($currency),
            locale: 'en',
        );
    }
}
