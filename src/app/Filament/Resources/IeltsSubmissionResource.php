<?php

namespace App\Filament\Resources;

use App\Enums\IeltsSubmissionStatusEnum;
use App\Filament\Resources\IeltsSubmissionResource\Pages;
use App\Models\IeltsSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IeltsSubmissionResource extends Resource
{
    protected static ?string $model = IeltsSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'IELTS System';

    protected static ?string $navigationLabel = 'Kết quả bài thi (Submissions)';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Thông tin lượt thi')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Mã bài thi (Session ID)')
                            ->disabled(),

                        Forms\Components\TextInput::make('user.name')
                            ->label('Thí sinh')
                            ->disabled(),

                        Forms\Components\TextInput::make('user.email')
                            ->label('Email')
                            ->disabled(),

                        Forms\Components\TextInput::make('raw_score')
                            ->label(fn (?IeltsSubmission $record): string => $record?->skill === 'writing' ? 'Số Task AI đã chấm' : 'Số câu đúng')
                            ->disabled()->dehydrated(false)
                            ->suffix(fn (?IeltsSubmission $record): string => $record?->skill === 'writing' ? '/ 2 Task' : '/ ' . $record?->total_questions)
                            ->visible(fn (?IeltsSubmission $record): bool => $record?->skill !== 'speaking'),

                        Forms\Components\TextInput::make('band_score')
                            ->label('IELTS Band chính thức')
                            ->disabled()->dehydrated(false)->numeric()
                            ->visible(fn (?IeltsSubmission $record): bool => ! in_array($record?->skill, ['writing', 'speaking'], true)),

                        Forms\Components\TextInput::make('ai_band_score')
                            ->label('Band AI tham khảo')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (?IeltsSubmission $record): bool => in_array($record?->skill, ['writing', 'speaking'], true)),

                        Forms\Components\TextInput::make('teacher_band_score')
                            ->label('Band chính thức do giáo viên chấm')
                            ->disabled(fn (?IeltsSubmission $record) => $record?->status?->value !== 'completed')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(9)
                            ->step(0.5)->rules(['nullable', 'numeric', 'multiple_of:0.5'])
                            ->helperText('Nhập band tổng hoặc điền đủ tiêu chí bên dưới để tự tính band. Muốn nhập tổng trực tiếp sau khi đã chấm theo tiêu chí, cần xóa toàn bộ điểm tiêu chí. Band AI chỉ để tham khảo.')
                            ->visible(fn (?IeltsSubmission $record): bool => in_array($record?->skill, ['writing', 'speaking'], true)),

                        Forms\Components\DateTimePicker::make('teacher_scored_at')
                            ->label('Thời điểm giáo viên chấm')
                            ->disabled()
                            ->visible(fn (?IeltsSubmission $record): bool => in_array($record?->skill, ['writing', 'speaking'], true)),

                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')->disabled()->dehydrated(false)
                            ->options([
                                IeltsSubmissionStatusEnum::IN_PROGRESS->value => 'Đang thi',
                                IeltsSubmissionStatusEnum::COMPLETED->value => 'Đã hoàn thành',
                                IeltsSubmissionStatusEnum::ABANDONED->value => 'Đã hủy',
                            ]),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Thời gian bắt đầu')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Thời gian nộp bài')
                            ->disabled(),

                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Thời lượng làm (Giây)')
                            ->disabled(),

                        Forms\Components\Select::make('assigned_examiner_id')
                            ->label('Người chấm được phân công')->relationship('assignedExaminer', 'name')
                            ->searchable()->preload()->visible(fn () => auth()->user()?->isAdmin())
                            ->helperText('Tài khoản người chấm cần quyền ielts.grade và quyền vào admin. Admin có thể chấm tất cả bài.'),
                        Forms\Components\Placeholder::make('grading_history_display')
                            ->label('Lịch sử chấm điểm')
                            ->content(fn (?IeltsSubmission $record) => collect($record?->grading_history ?? [])->reverse()
                                ->map(fn ($entry) => ($entry['at'] ?? '') . ' · ' . ($entry['by'] ?? 'Hệ thống') . ' · ' . ($entry['previous_band'] ?? '—') . ' → ' . ($entry['band'] ?? '—'))
                                ->implode("\n"))->columnSpanFull(),
                        ...static::rubricFields(),
                        Forms\Components\Textarea::make('examiner_notes')
                            ->label('Nhận xét của giáo viên')
                            ->disabled(fn (?IeltsSubmission $record) => $record?->status?->value !== 'completed')
                            ->columnSpanFull()
                            ->rows(3)->visible(fn (?IeltsSubmission $record): bool => in_array($record?->skill, ['writing', 'speaking'], true)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Thí sinh')
                    ->default('Thí sinh vãng lai')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('test.title')
                    ->label('Đề thi')
                    ->default(fn ($record) => $record->section?->title ?? 'Bài luyện tập')
                    ->searchable(),

                Tables\Columns\TextColumn::make('skill')
                    ->label('Kỹ năng')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'All')),

                Tables\Columns\TextColumn::make('raw_score')
                    ->label('Câu/Task đã chấm')
                    ->formatStateUsing(fn (IeltsSubmission $record) => match ($record->skill) {
                        'writing' => $record->raw_score . ' / 2 Task',
                        'speaking' => '—',
                        default => $record->raw_score . ' / ' . $record->total_questions,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('band_score')
                    ->label('Band chính thức')
                    ->placeholder(fn (IeltsSubmission $record) => in_array($record->skill, ['writing', 'speaking'], true) ? 'Chờ GV' : '—')
                    ->formatStateUsing(fn ($state, IeltsSubmission $record) => in_array($record->skill, ['writing', 'speaking'], true)
                        ? ($record->teacher_band_score !== null ? number_format($record->teacher_band_score, 1) : 'Chờ GV')
                        : ($state !== null ? number_format($state, 1) : '—'))
                    ->badge()
                    ->color(fn ($state, IeltsSubmission $record): string => in_array($record->skill, ['writing', 'speaking'], true) && $record->teacher_band_score === null
                        ? 'gray'
                        : match (true) {
                            (float) $state >= 8.0 => 'success',
                            (float) $state >= 6.5 => 'info',
                            (float) $state >= 5.0 => 'warning',
                            default => 'danger',
                        })
                    ->sortable(),

                Tables\Columns\TextColumn::make('ai_band_score')
                    ->label('AI tham khảo')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) : '—')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Ngày thi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('awaiting_teacher')->label('Chờ giáo viên chấm')
                    ->query(fn ($query) => $query->whereIn('skill', ['writing', 'speaking'])->where('status', 'completed')->whereNull('teacher_band_score')),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Đã hoàn thành',
                        'in_progress' => 'Đang thi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Xem bài làm')
                    ->visible(fn (IeltsSubmission $record) => $record->status->value === 'completed')
                    ->icon('heroicon-o-magnifying-glass')
                    ->url(fn (IeltsSubmission $record): string => route('ielts.exam.result', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        if (! auth()->user()?->isAdmin()) $query->where('assigned_examiner_id', auth()->id())->whereIn('skill', ['writing', 'speaking'])->where('status', 'completed');
        return $query;
    }

    private static function rubricFields(): array
    {
        $fields = [];
        foreach ([
            'task1' => ['Task Achievement', 'Coherence and Cohesion', 'Lexical Resource', 'Grammatical Range and Accuracy'],
            'task2' => ['Task Response', 'Coherence and Cohesion', 'Lexical Resource', 'Grammatical Range and Accuracy'],
            'speaking' => ['Fluency and Coherence', 'Lexical Resource', 'Grammatical Range and Accuracy', 'Pronunciation'],
        ] as $set => $criteria) {
            $fields[] = Forms\Components\Section::make('Tiêu chí ' . ucfirst($set))
                ->description('Có thể nhập band tổng trực tiếp hoặc nhập đủ tiêu chí để hệ thống tính band.')
                ->visible(fn (?IeltsSubmission $record) => $record?->skill === ($set === 'speaking' ? 'speaking' : 'writing'))
                ->columns(2)->schema(array_map(fn ($name, $index) =>
                    Forms\Components\TextInput::make("teacher_criteria.{$set}.{$index}")->label($name)
                        ->disabled(fn (?IeltsSubmission $record) => $record?->status?->value !== 'completed')
                        ->numeric()->minValue(0)->maxValue(9)->step(0.5)->rules(['nullable', 'numeric', 'multiple_of:0.5']),
                    $criteria, array_keys($criteria)));
        }
        return $fields;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIeltsSubmissions::route('/'),
            'edit' => Pages\EditIeltsSubmission::route('/{record}/edit'),
        ];
    }
}
