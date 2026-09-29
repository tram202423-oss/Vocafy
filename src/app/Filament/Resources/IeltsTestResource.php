<?php

namespace App\Filament\Resources;

use App\Enums\IeltsTestTypeEnum;
use App\Filament\Resources\IeltsTestResource\Pages;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IeltsTestResource extends Resource
{
    protected static ?string $model = IeltsTest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'IELTS System';

    protected static ?string $navigationLabel = 'Bộ đề thi (Full Tests)';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin Bộ đề thi')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Tên bộ đề')
                            ->placeholder('Ví dụ: Cambridge IELTS 18 - Academic Test 1')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('slug')
                            ->label('Đường dẫn URL (Slug)')
                            ->helperText('Tự động tạo nếu để trống'),

                        Forms\Components\Select::make('type')
                            ->label('Hệ thi')
                            ->options([
                                IeltsTestTypeEnum::ACADEMIC->value => 'Academic (Học thuật)',
                                IeltsTestTypeEnum::GENERAL_TRAINING->value => 'General Training (Tổng quát)',
                            ])
                            ->default(IeltsTestTypeEnum::ACADEMIC->value)
                            ->required(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Tổng thời gian thi (Phút)')
                            ->numeric()
                            ->default(150)
                            ->required(),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Xuất bản (Hiển thị cho học viên)')
                            ->default(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Mô tả bộ đề')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

                Forms\Components\Section::make('Lắp ghép các Kỹ năng / Sections')
                    ->description('Chọn các Section từ Ngân hàng đề để ghép thành một bài Full Test.')
                    ->schema([
                        Forms\Components\Select::make('sections')
                            ->relationship('sections', 'title')
                            ->label('Chọn các Section thành phần')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Thứ tự mặc định: Listening -> Reading -> Writing -> Speaking'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Tên bộ đề')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Hệ thi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Số Kỹ năng (Sections)')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Thời gian')
                    ->suffix(' phút'),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Đã xuất bản')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Trạng thái xuất bản'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Xem thử (Test UI)')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn (IeltsTest $record): string => route('ielts.tests.show', $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListIeltsTests::route('/'),
            'create' => Pages\CreateIeltsTest::route('/create'),
            'edit' => Pages\EditIeltsTest::route('/{record}/edit'),
        ];
    }
}
