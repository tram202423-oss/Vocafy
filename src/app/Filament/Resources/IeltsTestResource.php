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

    protected static ?string $navigationLabel = 'Bộ đề thi';

    protected static ?string $modelLabel = 'bộ đề thi';

    protected static ?string $pluralModelLabel = 'Bộ đề thi';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Biên tập bộ đề')->id('test-editor')->columnSpanFull()->tabs([
                Forms\Components\Tabs\Tab::make('Thông tin')->icon('heroicon-o-document-text')->schema([
                    Forms\Components\Section::make('Thông tin bộ đề')->columns(2)->schema([
                        Forms\Components\TextInput::make('title')->label('Tên bộ đề')->placeholder('Cambridge IELTS 18 — Academic Test 1')->required()->maxLength(255)->columnSpanFull(),
                        Forms\Components\Select::make('type')->label('Hệ thi')->options(collect(IeltsTestTypeEnum::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))->default('academic')->required()->live(),
                        Forms\Components\TextInput::make('duration_minutes')->label('Tổng thời gian')->suffix('phút')->integer()->minValue(1)->maxValue(600)->default(150)->required(),
                        Forms\Components\Textarea::make('description')->label('Giới thiệu bộ đề')->rows(4)->columnSpanFull(),
                        Forms\Components\TextInput::make('slug')->label('Đường dẫn bộ đề (tùy chọn)')->placeholder('Tự tạo khi để trống')->helperText('Để trống sẽ tạo đường dẫn mới từ tên bộ đề. Khi chỉnh sửa, liên kết cũ sẽ thay đổi.')->maxLength(255)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')->unique(ignoreRecord: true)->columnSpanFull(),
                    ]),
                ]),
                Forms\Components\Tabs\Tab::make('Chọn phần thi')->icon('heroicon-o-rectangle-stack')->schema([
                    Forms\Components\Section::make('Ghép các kỹ năng')->description('Chọn phần thi đã có hoặc bấm dấu + để tạo ngay tại đây. Mỗi kỹ năng chọn một phần thi; có thể tạo bộ đề chỉ gồm Reading hoặc Listening.')->schema([
                        Forms\Components\Select::make('sections')->label('Các phần thi trong bộ đề')->multiple()->preload()->searchable()->live()
                            ->relationship('sections', 'title', modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, Forms\Get $get) => $query->where('test_type', $get('type') ?? 'academic'))
                            ->getOptionLabelFromRecordUsing(fn (IeltsSection $record): string => $record->skill->label().' · '.$record->title.' · '.$record->total_questions.' câu · '.$record->time_limit_minutes.' phút'.($record->is_active ? '' : ' · Đang tắt'))
                            ->helperText('Thứ tự thi được lưu tự động: Listening → Reading → Writing → Speaking. Tạo phần thi mới trong hộp thoại sẽ thêm vào lựa chọn hiện tại.')
                            ->createOptionModalHeading('Tạo phần thi & câu hỏi')
                            ->createOptionForm(fn (): array => \App\Filament\Forms\IeltsSectionForm::schema())
                            ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action->modalWidth('7xl')->modalSubmitActionLabel('Tạo và chọn phần thi')->modalCancelActionLabel('Hủy')
                                ->extraModalFooterActions(fn (Forms\Components\Actions\Action $action): array => [
                                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])->label('Tạo và thêm phần thi khác'),
                                ]))
                            ->createOptionUsing(function (array $data, Forms\Form $form): int {
                                return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $form): int {
                                    $section = IeltsSection::create($data);
                                    $form->model($section)->saveRelationships();
                                    \App\Services\IeltsAuthoringService::syncSectionTotals($section);
                                    return $section->id;
                                });
                            })
                            ->rules(fn (Forms\Get $get): array => [function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $sections = IeltsSection::whereIn('id', $value ?? [])->get();
                                if ($sections->count() !== count($value ?? [])) {
                                    $fail('Có phần thi không còn tồn tại. Hãy chọn lại.');
                                }
                                if ($sections->pluck('skill')->map(fn ($skill) => $skill->value)->duplicates()->isNotEmpty()) {
                                    $fail('Mỗi kỹ năng chỉ chọn một phần thi trong bộ đề.');
                                }
                                if ($sections->contains(fn ($section) => $section->test_type->value !== $get('type'))) {
                                    $fail('Phần thi phải cùng hệ Academic / General Training với bộ đề.');
                                }
                                if ($get('is_published') && ($sections->isEmpty() || $sections->contains(fn ($section) => ! $section->is_active || ! $section->questionGroups()->whereHas('questions')->exists()))) {
                                    $fail('Để xuất bản, hãy chọn ít nhất một phần thi đã có câu hỏi và đang cho phép sử dụng.');
                                }
                            }]),
                        Forms\Components\Placeholder::make('selected_sections')->label('Tổng quan & chỉnh sửa nhanh')->content(function (Forms\Get $get): \Illuminate\Support\HtmlString {
                            $sections = IeltsSection::whereIn('id', $get('sections') ?? [])->get()->sortBy(fn ($section) => array_search($section->skill->value, ['listening', 'reading', 'writing', 'speaking'], true));
                            if ($sections->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('Chưa chọn phần thi. Bạn có thể lưu bộ đề ở trạng thái bản nháp.');
                            }
                            $html = '<div class="space-y-3">';
                            foreach ($sections as $section) {
                                $html .= '<div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700"><div><strong>'.e($section->skill->label()).'</strong> · '.e($section->title).'<div class="text-sm text-gray-500">'.$section->total_questions.' câu · '.$section->time_limit_minutes.' phút</div></div><a class="font-semibold text-primary-600" target="_blank" rel="noopener" href="'.e(IeltsSectionResource::getUrl('edit', ['record' => $section])).'">Chỉnh sửa phần thi ↗</a></div>';
                            }
                            $html .= '<p class="text-sm">Tổng: '.$sections->sum('total_questions').' câu · '.$sections->sum('time_limit_minutes').' phút theo các phần thi. Tổng thời gian bộ đề đặt riêng tại tab Thông tin.</p></div>';
                            return new \Illuminate\Support\HtmlString($html);
                        }),
                    ]),
                ]),
                Forms\Components\Tabs\Tab::make('Xuất bản')->icon('heroicon-o-eye')->schema([
                    Forms\Components\Section::make('Trạng thái hiển thị')->schema([
                        Forms\Components\Toggle::make('is_published')->label('Xuất bản cho học viên')->default(false)->live()->helperText('Tắt để giữ bản nháp. Khi bật, các phần thi được chọn phải có câu hỏi và đang cho phép sử dụng.'),
                        Forms\Components\Placeholder::make('workflow_help')->label('Hoàn tất bộ đề')->content('Lưu bộ đề, sau đó dùng nút Xem bộ đề để kiểm tra trang giới thiệu và bắt đầu làm bài. Có thể quay lại chỉnh sửa các phần thi bất cứ lúc nào.'),
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
                    ->label('Kỹ năng')
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
                    ->label('Xem bộ đề')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->url(fn (IeltsTest $record): string => route('ielts.tests.show', $record->slug))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListIeltsTests::route('/'),
            'create' => Pages\CreateIeltsTest::route('/create'),
            'edit' => Pages\EditIeltsTest::route('/{record}/edit'),
        ];
    }
}
