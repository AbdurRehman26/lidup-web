<?php

namespace App\Filament\Resources\FeedbackItems\Pages;

use App\Filament\Resources\FeedbackItems\FeedbackItemResource;
use Filament\Resources\Pages\ListRecords;

class ListFeedbackItems extends ListRecords
{
    protected static string $resource = FeedbackItemResource::class;
}
