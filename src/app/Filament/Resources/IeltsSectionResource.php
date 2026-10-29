<?php

namespace App\Filament\Resources;

use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use App\Filament\Resources\IeltsSectionResource\Pages;
use App\Models\IeltsSection;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IeltsSectionResource extends Resource
{
    protected static ?string $model = IeltsSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'IELTS System';

    protected static ?string $navigationLabel = 'Phần thi & câu hỏi';

    protected static ?string $modelLabel = 'phần thi';

    protected static ?string $pluralModelLabel = 'Phần thi & câu hỏi';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema(\App\Filament\Forms\IeltsSectionForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('skill')
                    ->label('Kỹ năng')
                    ->badge()
                    ->color(fn (IeltsSkillEnum $state): string => match ($state) {
                        IeltsSkillEnum::LISTENING => 'info',
                        IeltsSkillEnum::READING => 'success',
                        IeltsSkillEnum::WRITING => 'warning',
                        IeltsSkillEnum::SPEAKING => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state->value)),

                Tables\Columns\TextColumn::make('test_type')
                    ->label('Hệ thi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\TextColumn::make('time_limit_minutes')
                    ->label('Thời gian')
                    ->suffix(' phút'),

                Tables\Columns\TextColumn::make('total_questions')
                    ->label('Số câu'),

                Tables\Columns\TextColumn::make('question_groups_count')
                    ->counts('questionGroups')
                    ->label('Nhóm câu hỏi'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Cho phép sử dụng'),
                Tables\Filters\SelectFilter::make('skill')
                    ->options([
                        'reading' => 'Reading',
                        'listening' => 'Listening',
                        'writing' => 'Writing',
                        'speaking' => 'Speaking',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Chỉnh sửa'),
                Tables\Actions\DeleteAction::make()->label('Xóa'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIeltsSections::route('/'),
            'create' => Pages\CreateIeltsSection::route('/create'),
            'edit' => Pages\EditIeltsSection::route('/{record}/edit'),
        ];
    }
}
