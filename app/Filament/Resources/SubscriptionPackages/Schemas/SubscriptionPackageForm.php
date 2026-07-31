<?php

namespace App\Filament\Resources\SubscriptionPackages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Package')
                ->description('Define who receives this package and how long access lasts.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->required()->maxLength(100),
                        TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(100),
                        Select::make('plan')
                            ->options(['personal' => 'Personal', 'pro' => 'Pro'])
                            ->default('personal')
                            ->required(),
                        TextInput::make('device_limit')->numeric()->minValue(1)->default(1)->required(),
                        TextInput::make('user_limit')
                            ->label('Number of eligible users')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty when the package has no user limit.'),
                        TextInput::make('sort_order')->numeric()->minValue(0)->default(0)->required(),
                    ]),
                    Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
            Section::make('Access duration')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('duration_unit')
                            ->options([
                                'days' => 'Days',
                                'months' => 'Months',
                                'lifetime' => 'Lifetime',
                                'unlimited' => 'Unlimited',
                            ])
                            ->default('days')
                            ->required(),
                        TextInput::make('duration_value')
                            ->label('Number of days or months')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Leave empty when duration is unlimited.'),
                    ]),
                ]),
            Section::make('Billing and visibility')
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_paid')->label('Paid package')->default(false),
                        Toggle::make('is_active')
                            ->label('Assign to new users')
                            ->default(true),
                        Toggle::make('is_visible')
                            ->label('Show on website')
                            ->default(true),
                        TextInput::make('price')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Enter the amount in the currency selected beside it.'),
                        TextInput::make('original_price')
                            ->label('Original price')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Optional crossed-out price shown beside the current price.'),
                        TextInput::make('currency')->default('EUR')->maxLength(3)->required(),
                        Select::make('billing_interval')
                            ->label('Billing interval')
                            ->options([
                                'month' => 'Monthly',
                                'year' => 'Yearly',
                                'one_time' => 'One-time',
                            ])
                            ->default('month')
                            ->required(),
                        TextInput::make('paddle_price_id')
                            ->label('Paddle price ID')
                            ->placeholder('pri_...')
                            ->helperText('Required before a paid package can open checkout.'),
                    ]),
                ]),
        ]);
    }
}
