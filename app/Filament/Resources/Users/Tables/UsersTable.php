<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use App\Services\ApiKeyService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->weight('semibold'),
                TextColumn::make('appSubscription.plan')
                    ->label('Plan')
                    ->badge()
                    ->placeholder('No plan')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'No plan')
                    ->color(fn (?string $state): string => match ($state) {
                        'pro' => 'primary',
                        'personal' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('appSubscription.status')
                    ->label('Subscription')
                    ->badge()
                    ->placeholder('Inactive')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Inactive')
                    ->color(fn (?string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due' => 'warning',
                        'canceled', 'expired' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('tokens_exists')
                    ->label('API key')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('active_activations_count')
                    ->label('Active Macs')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('subscription_plan')
                    ->label('Plan')
                    ->relationship('appSubscription', 'plan')
                    ->options([
                        'personal' => 'Personal',
                        'pro' => 'Pro',
                    ]),
            ])
            ->recordActions([
                Action::make('generateActivationKey')
                    ->label('Generate & email key')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn (User $record): bool => ! $record->tokens_exists)
                    ->requiresConfirmation()
                    ->modalHeading('Generate an activation key?')
                    ->modalDescription(fn (User $record): string => "A new activation key will be generated and emailed to {$record->email}.")
                    ->modalSubmitActionLabel('Generate and send')
                    ->action(function (User $record): void {
                        $created = app(ApiKeyService::class)->create($record);
                        $record->notify(new ActivationKeyIssued($created['plain_text']));

                        Notification::make()
                            ->title('Activation key generated')
                            ->body("The key was emailed to {$record->email}.")
                            ->success()
                            ->send();
                    }),
                Action::make('replaceActivationKey')
                    ->label('Replace & resend key')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->visible(fn (User $record): bool => (bool) $record->tokens_exists)
                    ->requiresConfirmation()
                    ->modalHeading('Replace this activation key?')
                    ->modalDescription('For security, the existing key cannot be read or resent. This creates and emails a new key, invalidates the old key, and revokes its active Mac activations.')
                    ->modalSubmitActionLabel('Replace and send')
                    ->action(function (User $record): void {
                        $created = app(ApiKeyService::class)->rotate($record);
                        $record->notify(new ActivationKeyIssued($created['plain_text'], replaced: true));

                        Notification::make()
                            ->title('Activation key replaced')
                            ->body("The new key was emailed to {$record->email}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No LidUp users yet')
            ->emptyStateDescription('New accounts will appear here with their subscription and activation status.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
