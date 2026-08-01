<?php

namespace App\Filament\Resources\FeedbackItems;

use App\Filament\Resources\FeedbackItems\Pages\EditFeedbackItem;
use App\Filament\Resources\FeedbackItems\Pages\ListFeedbackItems;
use App\Filament\Resources\FeedbackItems\Schemas\FeedbackItemForm;
use App\Filament\Resources\FeedbackItems\Tables\FeedbackItemsTable;
use App\Models\FeedbackItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedbackItemResource extends Resource
{
    protected static ?string $model = FeedbackItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Feedback';

    protected static ?string $modelLabel = 'feedback item';

    public static function form(Schema $schema): Schema
    {
        return FeedbackItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbackItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedbackItems::route('/'),
            'edit' => EditFeedbackItem::route('/{record}/edit'),
        ];
    }
}
