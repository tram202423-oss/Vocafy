<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VocabularyResource\Pages;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Vocabulary;
use App\Services\GeminiService;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VocabularyResource extends Resource
{
    protected static ?string $model = Vocabulary::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Set $set, $record) {
                        if ($record?->topic?->category_id) {
                            $set('category_id', $record->topic->category_id);
                        }
                    })
                    ->afterStateUpdated(fn (Set $set) => $set('topic_id', null)),

                Select::make('topic_id')
                    ->label('Topic')
                    ->options(fn (Get $get) => Topic::where('category_id', $get('category_id'))
                        ->pluck('name', 'id')
                    )
                    ->required()
                    ->disabled(fn (Get $get) => ! $get('category_id'))
                    ->live(),

                TextInput::make('word')
                    ->required()
                    ->live(debounce: 500),

                TextInput::make('pronunciation')
                    ->hintAction(
                        Action::make('generatePronunciation')
                            ->label('AI')
                            ->icon('heroicon-m-sparkles')
                            ->color('warning')
                            ->tooltip('Auto-fill pronunciation with AI')
                            ->disabled(fn (Get $get) => blank($get('word')))
                            ->action(function (Get $get, Set $set) {
                                try {
                                    $result = app(GeminiService::class)
                                        ->generateVocabularyExample($get('word'), $get('meaning'));

                                    if (blank($get('pronunciation'))) {
                                        $set('pronunciation', $result['pronunciation']);
                                    }
                                    if (blank($get('example'))) {
                                        $set('example', $result['example']);
                                    }
                                    if (blank($get('meaning'))) {
                                        $set('meaning', $result['meaning']);
                                    }

                                    Notification::make()
                                        ->title('Generated successfully!')
                                        ->success()
                                        ->send();
                                } catch (\Throwable $e) {
                                    Notification::make()
                                        ->title('AI generation failed')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ),

                TextInput::make('meaning')
                    ->required(),

                Textarea::make('example')
                    ->hintAction(
                        Action::make('generateExample')
                            ->label('Generate with AI')
                            ->icon('heroicon-m-sparkles')
                            ->color('primary')
                            ->tooltip('Generate an example sentence with AI (requires word to be filled)')
                            ->disabled(fn (Get $get) => blank($get('word')))
                            ->action(function (Get $get, Set $set) {
                                try {
                                    $result = app(GeminiService::class)
                                        ->generateVocabularyExample($get('word'), $get('meaning'));

                                    $set('example', $result['example']);

                                    if (blank($get('pronunciation'))) {
                                        $set('pronunciation', $result['pronunciation']);
                                    }
                                    if (blank($get('meaning'))) {
                                        $set('meaning', $result['meaning']);
                                    }

                                    Notification::make()
                                        ->title('Example generated!')
                                        ->success()
                                        ->send();
                                } catch (\Throwable $e) {
                                    Notification::make()
                                        ->title('AI generation failed')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                    ),

                Select::make('level')
                    ->options([
                        'easy' => 'Easy',
                        'medium' => 'Medium',
                        'hard' => 'Hard',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('topic.category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('topic.name')
                    ->label('Topic')
                    ->searchable(),

                TextColumn::make('word')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pronunciation'),

                TextColumn::make('meaning')
                    ->limit(50),

                TextColumn::make('example'),

                TextColumn::make('level')
                    ->badge(),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVocabularies::route('/'),
        ];
    }
}
