<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Subscriber')
                    ->description(fn (Subscription $record): ?string => $record->user?->email)
                    ->searchable(['name', 'email'])
                    ->weight('semibold'),
                TextColumn::make('plan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'Unknown')),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due', 'paused' => 'warning',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paddle_id')
                    ->label('Paddle ID')
                    ->copyable()
                    ->placeholder('Local only')
                    ->toggleable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime()
                    ->placeholder('Renews')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'trialing' => 'Trialing',
                    'past_due' => 'Past due',
                    'paused' => 'Paused',
                    'canceled' => 'Canceled',
                ]),
                SelectFilter::make('plan')->options([
                    'personal' => 'Personal',
                    'pro' => 'Pro',
                ]),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancel at period end')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record): bool => filled($record->paddle_id) && ! $record->canceled())
                    ->requiresConfirmation()
                    ->modalDescription('Paddle will cancel this subscription at the end of its current billing period. The user keeps access until then.')
                    ->action(function (Subscription $record): void {
                        try {
                            $record->cancel();

                            Notification::make()
                                ->title('Subscription cancellation scheduled')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('Paddle could not cancel the subscription')
                                ->body('Check the Paddle environment and API credentials, then try again.')
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No paid subscriptions yet')
            ->emptyStateDescription('Paddle subscriptions will appear here after checkout and webhook synchronization.');
    }
}
