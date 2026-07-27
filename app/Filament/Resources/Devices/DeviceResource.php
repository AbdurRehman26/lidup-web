<?php

namespace App\Filament\Resources\Devices;

use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use App\Models\AppActivation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceResource extends Resource
{
    protected static ?string $model = AppActivation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Devices';

    protected static ?string $modelLabel = 'device';

    protected static ?string $pluralModelLabel = 'devices';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.appSubscription']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
        ];
    }
}
