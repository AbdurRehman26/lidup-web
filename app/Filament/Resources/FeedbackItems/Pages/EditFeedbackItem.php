<?php

namespace App\Filament\Resources\FeedbackItems\Pages;

use App\Filament\Resources\FeedbackItems\FeedbackItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedbackItem extends EditRecord
{
    protected static string $resource = FeedbackItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
