<?php

namespace App\Filament\Resources\FeedbackItems\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FeedbackItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'feature' => 'Feature', 'problem' => 'Problem', default => 'Review',
                })->color(fn (string $state): string => match ($state) {
                    'feature' => 'primary', 'problem' => 'danger', default => 'warning',
                }),
                TextColumn::make('title')->searchable()->description(fn ($record): string => $record->submitter_name ?: $record->submitter_email ?: 'Anonymous')->limit(55)->weight('semibold'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('votes_count')->label('Votes')->counts('votes')->sortable(),
                TextColumn::make('rating')->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '—'),
                IconColumn::make('is_public')->label('Public')->boolean(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(['review' => 'Review', 'feature' => 'Feature request', 'problem' => 'Problem report']),
                SelectFilter::make('status')->options(['submitted' => 'Submitted', 'in_review' => 'In review', 'planned' => 'Planned', 'completed' => 'Completed', 'declined' => 'Declined']),
                TernaryFilter::make('is_public')->label('Published'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()]);
    }
}
