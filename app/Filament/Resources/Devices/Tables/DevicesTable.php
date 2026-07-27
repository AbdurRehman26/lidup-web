<?php

namespace App\Filament\Resources\Devices\Tables;

use App\Models\AppActivation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device_name')
                    ->label('Mac')
                    ->placeholder('Unnamed Mac')
                    ->description(fn (AppActivation $record): string => $record->device_id)
                    ->icon('heroicon-o-computer-desktop')
                    ->searchable(['device_name', 'device_id'])
                    ->weight('semibold'),
                TextColumn::make('user.name')
                    ->label('User')
                    ->description(fn (AppActivation $record): string => $record->user->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('user.appSubscription.plan')
                    ->label('Plan')
                    ->badge()
                    ->placeholder('No plan')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'No plan')
                    ->color(fn (?string $state): string => match ($state) {
                        'pro' => 'primary',
                        'personal' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('app_version')
                    ->label('LidUp version')
                    ->badge()
                    ->placeholder('Unknown')
                    ->searchable(),
                TextColumn::make('status')
                    ->state(fn (AppActivation $record): string => $record->revoked_at ? 'Revoked' : 'Active')
                    ->badge()
                    ->icon(fn (string $state): string => $state === 'Active'
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-no-symbol')
                    ->color(fn (string $state): string => $state === 'Active' ? 'success' : 'danger'),
                TextColumn::make('activated_at')
                    ->label('Activated')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
                TextColumn::make('last_verified_at')
                    ->label('Last seen')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('revoked_at')
                    ->label('Device status')
                    ->nullable()
                    ->trueLabel('Revoked')
                    ->falseLabel('Active')
                    ->placeholder('All devices'),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (AppActivation $record): bool => ! $record->revoked_at)
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate this Mac?')
                    ->modalDescription('LidUp will require a valid activation before it can be used on this Mac again.')
                    ->modalSubmitActionLabel('Deactivate Mac')
                    ->action(function (AppActivation $record): void {
                        $record->update(['revoked_at' => now()]);

                        Notification::make()
                            ->title('Mac deactivated')
                            ->body(($record->device_name ?: 'The selected Mac').' can no longer use this activation.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('last_verified_at', 'desc')
            ->emptyStateHeading('No activated Macs yet')
            ->emptyStateDescription('A device appears here after the LidUp app verifies a user’s activation key.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }
}
