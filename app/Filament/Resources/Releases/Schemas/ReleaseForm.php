<?php

namespace App\Filament\Resources\Releases\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReleaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Build file')
                    ->description('Upload the signed installer that visitors will receive from the download page.')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('macOS installer')
                            ->disk('local')
                            ->directory('releases')
                            ->acceptedFileTypes(config('uploads.release_mime_types'))
                            ->rules([
                                'extensions:'.implode(',', config('uploads.release_extensions')),
                            ])
                            ->maxSize(config('uploads.release_max_kb'))
                            ->downloadable()
                            ->required()
                            ->helperText('DMG, PKG, or ZIP. Maximum file size: '.config('uploads.release_max_mb').' MB.'),
                    ]),
                Section::make('Release details')
                    ->description('These details appear alongside the public download.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('version')
                                    ->label('Version')
                                    ->placeholder('1.0.0')
                                    ->helperText('Use semantic versions such as 1.2.0 or 1.2.0-beta.1.')
                                    ->unique(ignoreRecord: true)
                                    ->required(),
                                Select::make('channel')
                                    ->options([
                                        'stable' => 'Stable',
                                        'beta' => 'Beta',
                                        'alpha' => 'Alpha',
                                    ])
                                    ->default('stable')
                                    ->required(),
                                Select::make('architecture')
                                    ->label('Mac architecture')
                                    ->options([
                                        'universal' => 'Universal (Apple silicon + Intel)',
                                        'arm64' => 'Apple silicon',
                                        'x86_64' => 'Intel',
                                    ])
                                    ->default('universal')
                                    ->required(),
                                TextInput::make('minimum_os')
                                    ->label('Minimum macOS')
                                    ->default('macOS 14 Sonoma')
                                    ->required(),
                            ]),
                        MarkdownEditor::make('release_notes')
                            ->label('What changed')
                            ->placeholder('Add improvements, fixes, and anything users should know…')
                            ->columnSpanFull(),
                    ]),
                Section::make('Availability')
                    ->description('Publish immediately or schedule when this build becomes available.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('published_at')
                                    ->label('Available from')
                                    ->default(now())
                                    ->seconds(false)
                                    ->required(),
                                Toggle::make('is_current')
                                    ->label('Make this the latest build')
                                    ->helperText('The public download button will point to this build.')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
