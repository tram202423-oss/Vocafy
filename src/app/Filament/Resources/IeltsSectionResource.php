<?php

namespace App\Filament\Resources;

use App\Enums\IeltsQuestionTypeEnum;
use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use App\Filament\Resources\IeltsSectionResource\Pages;
use App\Models\IeltsSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IeltsSectionResource extends Resource
{
    protected static ?string $model = IeltsSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'IELTS System';

    protected static ?string $navigationLabel = 'Ngân hàng Đề (Sections)';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin Section / Kỹ năng')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Tiêu đề Section')
                            ->placeholder('Ví dụ: Cambridge 18 - Academic Reading Test 1')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Select::make('skill')
                            ->label('Kỹ năng')
                            ->options([
                                IeltsSkillEnum::READING->value => 'Reading (Đọc)',
                                IeltsSkillEnum::LISTENING->value => 'Listening (Nghe)',
                                IeltsSkillEnum::WRITING->value => 'Writing (Viết)',
                                IeltsSkillEnum::SPEAKING->value => 'Speaking (Nói)',
                            ])
                            ->default(IeltsSkillEnum::READING->value)
                            ->required(),

                        Forms\Components\Select::make('test_type')
                            ->label('Hệ thi')
                            ->options([
                                IeltsTestTypeEnum::ACADEMIC->value => 'Academic (Học thuật)',
                                IeltsTestTypeEnum::GENERAL_TRAINING->value => 'General Training (Tổng quát)',
                            ])
                            ->default(IeltsTestTypeEnum::ACADEMIC->value)
                            ->required(),

                        Forms\Components\TextInput::make('time_limit_minutes')
                            ->label('Thời gian làm bài (Phút)')
                            ->numeric()
                            ->default(60)
                            ->required(),

                        Forms\Components\TextInput::make('total_questions')
                            ->label('Tổng số câu hỏi')
                            ->numeric()
                            ->default(40)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Kích hoạt')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->label('Mô tả / Hướng dẫn chung')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Nội dung Bài đọc / Audio / Nhóm câu hỏi (Question Groups)')
                    ->description('Quản lý các Passage (bài đọc), Audio file nghe hoặc Prompt viết kèm các nhóm câu hỏi liên quan.')
                    ->schema([
                        Forms\Components\Repeater::make('questionGroups')
                            ->relationship('questionGroups')
                            ->label('Danh sách Passages / Parts')
                            ->orderColumn('order')
                            ->collapsed(false)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Nhóm câu hỏi')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->label('Tiêu đề Passage / Part')
                                        ->placeholder('Passage 1: Urban Farming...')
                                        ->required()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('question_type')
                                        ->label('Dạng bài chủ đạo')
                                        ->options(collect(IeltsQuestionTypeEnum::cases())->mapWithKeys(
                                            fn ($type) => [$type->value => $type->label()]
                                        ))
                                        ->required(),
                                ]),

                                Forms\Components\Textarea::make('instruction')
                                    ->label('Hướng dẫn làm bài (Instruction)')
                                    ->placeholder('Questions 1–6: Do the following statements agree...')
                                    ->rows(2),

                                Forms\Components\RichEditor::make('passage_content')
                                    ->label('Nội dung bài đọc (Passage Rich Text)')
                                    ->helperText('Hỗ trợ định dạng in đậm, gạch đầu dòng, các đoạn văn <p id="para-A">...')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('audio_url')
                                        ->label('Đường dẫn file Audio (Listening)')
                                        ->placeholder('https://... hoặc /storage/audio/part1.mp3'),

                                    Forms\Components\TextInput::make('image_url')
                                        ->label('Đường dẫn ảnh Sơ đồ / Biểu đồ (Map/Diagram)')
                                        ->placeholder('https://... hoặc /storage/images/chart.png'),
                                ]),

                                Forms\Components\Textarea::make('transcript')
                                    ->label('Audio Transcript (Bài nghe có kèm lời giải)')
                                    ->placeholder('Nội dung audio transcript...')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Repeater::make('questions')
                                    ->relationship('questions')
                                    ->label('Danh sách câu hỏi trong nhóm này')
                                    ->orderColumn('order')
                                    ->itemLabel(fn (array $state): ?string => 'Câu #' . ($state['question_number'] ?? '?') . ': ' . ($state['prompt'] ?? ''))
                                    ->collapsed(true)
                                    ->schema([
                                        Forms\Components\Grid::make(4)->schema([
                                            Forms\Components\TextInput::make('question_number')
                                                ->label('Số thứ tự câu (1-40)')
                                                ->numeric()
                                                ->required(),

                                            Forms\Components\TextInput::make('correct_answer')
                                                ->label('Đáp án đúng')
                                                ->placeholder('Ví dụ: TRUE, FALSE, A, hoặc từ điền')
                                                ->required()
                                                ->columnSpan(2),

                                            Forms\Components\TextInput::make('word_limit')
                                                ->label('Giới hạn từ (Điền từ)')
                                                ->numeric()
                                                ->placeholder('1, 2, 3...'),
                                        ]),

                                        Forms\Components\Textarea::make('prompt')
                                            ->label('Nội dung câu hỏi / Câu cần điền từ [blank]')
                                            ->required()
                                            ->rows(2),

                                        // options được lưu dạng JSON list: [{"key": "A", "text": "..."}, ...]
                                        // => dùng Repeater thay vì KeyValue để khớp đúng cấu trúc.
                                        Forms\Components\Repeater::make('options')
                                            ->label('Lựa chọn trắc nghiệm')
                                            ->helperText('Áp dụng cho Multiple choice, True/False, Matching...')
                                            ->schema([
                                                Forms\Components\TextInput::make('key')
                                                    ->label('Ký hiệu (A, B, C, D / TRUE, FALSE, NOT GIVEN)')
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('text')
                                                    ->label('Nội dung lựa chọn')
                                                    ->required()
                                                    ->columnSpan(3),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->addActionLabel('Thêm lựa chọn')
                                            ->itemLabel(fn (array $state): ?string => ($state['key'] ?? '?') . '. ' . ($state['text'] ?? ''))
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Textarea::make('quote_reference')
                                                ->label('Trích dẫn vị trí đáp án trong bài')
                                                ->placeholder('Đoạn văn hoặc câu chứa manh mối đáp án...')
                                                ->rows(2),

                                            Forms\Components\Textarea::make('explanation')
                                                ->label('Giải thích chi tiết (Explanation)')
                                                ->placeholder('Tại sao chọn đáp án này...')
                                                ->rows(2),
                                        ]),
                                    ]),
                            ]),
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

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Kích hoạt')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('skill')
                    ->options([
                        'reading' => 'Reading',
                        'listening' => 'Listening',
                        'writing' => 'Writing',
                        'speaking' => 'Speaking',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIeltsSections::route('/'),
            'create' => Pages\CreateIeltsSection::route('/create'),
            'edit' => Pages\EditIeltsSection::route('/{record}/edit'),
        ];
    }
}