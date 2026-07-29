<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use App\Services\ApiKeyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
                TextColumn::make('subscriptionPackage.name')
                    ->label('Package')
                    ->badge()
                    ->placeholder('No package')
                    ->color('primary'),
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
                TextColumn::make('trial_ends_at')
                    ->label('Free trial')
                    ->badge()
                    ->state(fn (User $record): string => match (true) {
                        $record->onAppTrial() => 'Active',
                        $record->trial_ends_at !== null => 'Expired',
                        default => 'Not granted',
                    })
                    ->description(fn (User $record): ?string => $record->trial_ends_at?->format('M j, Y'))
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Expired' => 'danger',
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
                Action::make('assignPackage')
                    ->label('Assign subscription')
                    ->icon('heroicon-o-rectangle-stack')
                    ->color('info')
                    ->modalHeading('Assign a subscription package')
                    ->modalDescription('This immediately replaces the user’s current package access. The expiry date is calculated from the selected package, and unlimited packages never expire.')
                    ->modalSubmitActionLabel('Assign subscription')
                    ->schema([
                        Select::make('subscription_package_id')
                            ->label('Subscription package')
                            ->options(SubscriptionPackage::query()
                                ->active()
                                ->withCount('users')
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (SubscriptionPackage $package): array => [
                                    $package->id => implode(' · ', [
                                        $package->name,
                                        $package->is_paid ? "{$package->currency} {$package->price}" : 'Free',
                                        $package->durationLabel(),
                                        $package->device_limit.' '.str('Mac')->plural($package->device_limit),
                                        $package->users_count.' / '.($package->user_limit ?? '∞').' assigned',
                                        $package->is_visible ? 'Shown on website' : 'Hidden',
                                    ]),
                                ]))
                            ->searchable()
                            ->helperText('Only active packages are listed. Package capacity is shown for context; super admins may assign a package even when its public allocation is full.')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $package = SubscriptionPackage::findOrFail($data['subscription_package_id']);

                        $record->forceFill([
                            'subscription_package_id' => $package->id,
                            'trial_plan' => $package->plan,
                            'trial_started_at' => now(),
                            'trial_ends_at' => $package->endsAt(),
                        ])->save();

                        Notification::make()
                            ->title("{$package->name} assigned")
                            ->body("Access was updated for {$record->email}.")
                            ->success()
                            ->send();
                    }),
                Action::make('endPackageAccess')
                    ->label('End package access')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->subscription_package_id !== null && $record->onAppTrial())
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->forceFill([
                            'trial_ends_at' => now(),
                        ])->save();

                        Notification::make()
                            ->title('Package access ended')
                            ->success()
                            ->send();
                    }),
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
