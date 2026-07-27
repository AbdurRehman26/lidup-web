<?php

namespace App\Filament\Resources\Releases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReleasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')
                    ->label('Version')
                    ->weight('semibold')
                    ->searchable(),
                BadgeColumn::make('channel')
                    ->colors([
                        'success' => 'stable',
                        'warning' => 'beta',
                        'gray' => 'alpha',
                    ]),
                TextColumn::make('architecture')
                    ->label('Architecture')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'universal' => 'Universal',
                        'arm64' => 'Apple silicon',
                        'x86_64' => 'Intel',
                        default => $state,
                    }),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? number_format($state / 1024 / 1024, 1).' MB'
                        : '—')
                    ->sortable(),
                TextColumn::make('sha256')
                    ->label('SHA-256')
                    ->limit(12)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_current')
                    ->label('Latest')
                    ->boolean()
                    ->trueColor('primary'),
                TextColumn::make('published_at')
                    ->label('Available from')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->options([
                        'stable' => 'Stable',
                        'beta' => 'Beta',
                        'alpha' => 'Alpha',
                    ]),
                TernaryFilter::make('is_current')
                    ->label('Latest build'),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
