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
                            ->label('Điểm thô (Số câu đúng)')
                            ->suffix('/ 40'),

                        Forms\Components\TextInput::make('band_score')
                            ->label('Điểm quy đổi (IELTS Band)')
                            ->numeric(),

                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
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

                        Forms\Components\Textarea::make('examiner_notes')
                            ->label('Nhận xét của Giám khảo / Hệ thống')
                            ->columnSpanFull()
                            ->rows(3),
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
                    ->label('Điểm thô')
                    ->formatStateUsing(fn ($record) => $record->raw_score . ' / ' . $record->total_questions)
                    ->sortable(),

                Tables\Columns\TextColumn::make('band_score')
                    ->label('IELTS Band')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 8.0 => 'success',
                        $state >= 6.5 => 'info',
                        $state >= 5.0 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Đã hoàn thành',
                        'in_progress' => 'Đang thi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Xem bài làm')
                    ->icon('heroicon-o-magnifying-glass')
                    ->url(fn (IeltsSubmission $record): string => route('ielts.exam.result', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIeltsSubmissions::route('/'),
            'edit' => Pages\EditIeltsSubmission::route('/{record}/edit'),
        ];
    }
}
