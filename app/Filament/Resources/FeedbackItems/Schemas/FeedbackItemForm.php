<?php

namespace App\Filament\Resources\FeedbackItems\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedbackItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Submission')
                ->description('Review the message before publishing it to the public roadmap.')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('type')->options([
                            'review' => 'Review',
                            'feature' => 'Feature request',
                            'problem' => 'Problem report',
                        ])->required(),
                        Select::make('rating')->options([
                            1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars',
                        ])->placeholder('Not applicable'),
                    ]),
                    TextInput::make('title')->required()->maxLength(160)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(7)->columnSpanFull(),
                    Grid::make(2)->schema([
                        TextInput::make('submitter_name')->label('Submitted by')->maxLength(120),
                        TextInput::make('submitter_email')->label('Email')->email()->maxLength(255),
                    ]),
                ]),
            Section::make('Roadmap moderation')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('status')->options([
                            'submitted' => 'Submitted',
                            'in_review' => 'In review',
                            'planned' => 'Planned',
                            'completed' => 'Completed',
                            'declined' => 'Declined',
                        ])->required(),
                        Toggle::make('is_public')
                            ->label('Publish publicly')
                            ->helperText('Only approved submissions should be visible on the roadmap.'),
                    ]),
                    Textarea::make('admin_response')
                        ->label('Team response')
                        ->rows(4)
                        ->helperText('Shown publicly beneath this submission when it is published.')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
