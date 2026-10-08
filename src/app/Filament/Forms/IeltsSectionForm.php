<?php

namespace App\Filament\Forms;

use App\Enums\IeltsQuestionTypeEnum;
use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use App\Models\IeltsQuestionGroup;
use App\Services\IeltsAuthoringService as Authoring;
use App\Services\IeltsAudioService;
use App\Services\IeltsMultiSelectService as MultiSelect;
use Filament\Forms\Components as C;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class IeltsSectionForm
{
    public static function schema(): array
    {
        return [
            C\Tabs::make('Phần thi')->id(fn (C\Tabs $component): string => 'section-editor-'.str_replace('.', '-', $component->getStatePath()))->columnSpanFull()->tabs([
                C\Tabs\Tab::make('Thông tin')->icon('heroicon-o-adjustments-horizontal')->schema([
                    C\Section::make('Thông tin phần thi')->description('Tạo một kỹ năng, nhập các nhóm câu hỏi rồi ghép vào bộ đề.')->columns(3)->schema([
                        C\TextInput::make('title')->label('Tên phần thi')->placeholder('Cambridge 18 — Reading Test 1')->required()->maxLength(255)->columnSpan(2),
                        C\Select::make('skill')->label('Kỹ năng')->options(collect(IeltsSkillEnum::cases())->mapWithKeys(fn ($skill) => [$skill->value => $skill->label()]))
                            ->default('reading')->required()->live()->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                $set('time_limit_minutes', IeltsSkillEnum::tryFrom($state ?? '')?->defaultMinutes() ?? 60);
                                if (in_array($state, ['writing', 'speaking'], true)) {
                                    $groups = $get('questionGroups') ?? [];
                                    foreach ($groups as &$group) {
                                        $group['response_mode'] = 'standard';
                                        $group['settings']['multi_select'] = false;
                                        if (($group['question_type'] ?? '') === 'drag_drop') {
                                            $group['question_type'] = 'short_answer';
                                        }
                                    }
                                    unset($group);
                                    $set('questionGroups', $groups);
                                }
                            }),
                        C\Select::make('test_type')->label('Hệ thi')->options(collect(IeltsTestTypeEnum::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))->default('academic')->required(),
                        C\TextInput::make('time_limit_minutes')->label('Thời gian')->suffix('phút')->integer()->minValue(1)->maxValue(300)->default(60)->required(),
                        C\Placeholder::make('question_count')->label('Số câu đã nhập')->content(fn (Get $get): string => self::summary($get('questionGroups') ?? [])),
                        C\Hidden::make('total_questions')->default(0),
                        C\Toggle::make('is_active')->label('Cho phép sử dụng phần thi')->default(true)->columnSpanFull(),
                        C\Textarea::make('description')->label('Mô tả / hướng dẫn chung')->rows(3)->columnSpanFull(),
                    ]),
                ]),
                C\Tabs\Tab::make('Nội dung & câu hỏi')->icon('heroicon-o-document-text')->schema([
                    C\Placeholder::make('authoring_guide')->label('Cách nhập đề')->content(fn (Get $get): string => match ($get('skill')) {
                        'reading' => 'Mỗi nhóm có một dạng câu hỏi. Nhập bài đọc nguồn ở nhóm đầu của mỗi Passage; các nhóm tiếp theo của Passage để trống bài đọc nguồn. Nhập đề có ô trống ở tab Đề bài của nhóm, không chèn ô vào bài đọc nguồn.',
                        'listening' => 'Nhập câu hỏi, file nghe và transcript theo từng nhóm. Part 1: câu 1–10; Part 2: 11–20; Part 3: 21–30; Part 4: 31–40. Với đề kéo thả, nhập nội dung có [blank_N] rồi tạo câu hỏi từ các ô trống.',
                        default => 'Mỗi nhóm là một Task / Part. Nhập đề bài trong từng câu; lời giải và trích dẫn là tùy chọn.',
                    }),
                    C\Repeater::make('questionGroups')->relationship('questionGroups')->label('Các nhóm câu hỏi')->orderColumn('order')
                        ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => MultiSelect::hydrate($data))
                        ->defaultItems(0)->collapsed()->collapsible()->live(onBlur: true)
                        ->addActionLabel('Thêm nhóm câu hỏi')->deleteAction(fn (Action $action) => $action->label('Xóa nhóm')->requiresConfirmation())
                        ->collapseAllAction(fn (Action $action) => $action->label('Thu gọn tất cả'))
                        ->expandAllAction(fn (Action $action) => $action->label('Mở tất cả'))
                        ->itemLabel(fn (array $state): string => self::groupLabel($state))
                        ->rules(fn (Get $get): array => [function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                            Authoring::validateGroups($value ?? [], $get('skill') ?? 'reading', $attribute);
                        }])
                        ->schema(self::groupSchema()),
                ]),
            ]),
        ];
    }

    private static function groupSchema(): array
    {
        return [
            C\Grid::make(3)->schema([
                C\TextInput::make('title')->label('Tên nhóm câu hỏi')->placeholder('Passage 1 — Questions 1–7')->required()->maxLength(255)->live(onBlur: true)->columnSpan(2),
                C\Select::make('question_type')->label('Dạng câu hỏi')->options(collect(IeltsQuestionTypeEnum::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]))
                    ->default('fill_in_blanks')->required()->live()
                    ->afterStateUpdated(function (string $state, Get $get, Set $set): void {
                        if ($state !== 'multiple_choice') {
                            $set('settings.multi_select', false);
                        }
                        if ($state === 'matching_headings' && in_array($get('../../skill'), ['reading', 'listening'], true)) {
                            $set('response_mode', 'drag_drop');
                            $set('option_usage', 'once');
                        } elseif (! Authoring::supportsDragDrop($state)) {
                            $set('response_mode', 'standard');
                        }
                    })
                    ->visible(fn (Get $get): bool => in_array($get('../../skill'), ['reading', 'listening'], true))->dehydratedWhenHidden(),
            ]),
            C\Toggle::make('settings.multi_select')->label('Chọn nhiều đáp án (Choose TWO / THREE…)')->default(false)->live()
                ->visible(fn (Get $get): bool => $get('question_type') === 'multiple_choice' && in_array($get('../../skill'), ['reading', 'listening'], true))
                ->dehydratedWhenHidden()
                ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                    if ($state) {
                        $set('response_mode', 'standard');
                        $legacy = MultiSelect::hydrate($get());
                        if (empty($get('questions')) && blank($get('settings.start_number'))) {
                            $legacy['settings']['start_number'] = self::nextQuestionNumber($get('../../questionGroups') ?? []);
                        }
                        foreach (['start_number', 'prompt', 'options', 'correct_keys', 'explanation', 'quote_reference'] as $field) {
                            if (blank($get('settings.'.$field))) {
                                $set('settings.'.$field, $legacy['settings'][$field] ?? null);
                            }
                        }
                    }
                }),
            C\Tabs::make('Biên tập nhóm')->id(fn (C\Tabs $component): string => 'group-editor-'.str_replace('.', '-', $component->getStatePath()))->tabs([
                C\Tabs\Tab::make('Đề bài')->icon('heroicon-o-document-text')->schema([
                    C\Grid::make(2)->schema([
                        C\Select::make('response_mode')->label('Cách trả lời')->options(['standard' => 'Nhập chữ / chọn đáp án', 'drag_drop' => 'Kéo thả từ ngân hàng đáp án'])
                            ->disableOptionWhen(fn (string $value, Get $get): bool => $value === 'drag_drop' && ! Authoring::supportsDragDrop($get('question_type')))
                            ->default('standard')->required()->live()->visible(fn (Get $get): bool => in_array($get('../../skill'), ['reading', 'listening'], true))->dehydratedWhenHidden(),
                        C\Select::make('option_usage')->label('Sử dụng đáp án')->options(['repeat' => 'Một đáp án được dùng nhiều lần', 'once' => 'Mỗi đáp án chỉ dùng một lần'])
                            ->default('repeat')->required()->live()
                            ->helperText('Matching Information: chọn theo hướng dẫn đề. Áp dụng cho cả chọn đáp án và kéo thả.')
                            ->visible(fn (Get $get): bool => Authoring::isDragDrop($get()) || $get('question_type') === 'matching_information')->dehydratedWhenHidden(),
                    ]),
                    C\Textarea::make('instruction')->label('Hướng dẫn cho nhóm câu hỏi')->placeholder('Choose the correct heading for each paragraph from the list below.')->rows(3),
                    C\Select::make('settings.map_answer_mode')->label('Trả lời bản đồ')
                        ->options(['choices' => 'Chọn từ danh sách', 'text' => 'Tự nhập từ'])->default('choices')->live()
                        ->helperText('Đổi chế độ trả lời sẽ xóa đáp án đúng của các câu trong nhóm; hãy chọn lại trước khi lưu.')
                        ->visible(fn (Get $get): bool => $get('question_type') === 'map_labeling' && ! Authoring::isDragDrop($get()))
                        ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                            $questions = $get('questions') ?? [];
                            foreach ($questions as &$question) {
                                if ($state === 'text') $question['options'] = [];
                                $question['correct_answer'] = null;
                                $question['answer_choice'] = null;
                                $question['answer_text'] = null;
                            }
                            unset($question);
                            $set('questions', $questions);
                        }),
                    C\RichEditor::make('question_content')
                        ->view('filament.forms.components.ielts-table-rich-editor')
                        ->label(fn (Get $get): string => $get('question_type') === 'map_labeling' ? 'Nội dung câu hỏi chung cho sơ đồ' : 'Đề bài / ghi chú / bảng có ô trống')
                        ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'blockquote', 'undo', 'redo'])
                        ->helperText(fn (Get $get): string => $get('question_type') === 'map_labeling'
                            ? 'Nhập đề bài dùng chung cho tất cả vị trí trên ảnh; có thể dùng Hướng dẫn cho nhóm câu hỏi ở trên. Từng câu chỉ cần số câu, đáp án đúng và vị trí.'
                            : 'Completion theo đoạn: chèn [blank_1], [blank_2]… khớp số câu; server sẽ nối từng ô với một câu. Kéo thả cần thêm ngân hàng. Matching Headings có thể để trống đề chung và nhập Paragraph A, Paragraph B… trong từng câu.')
                        ->visible(fn (Get $get): bool => in_array($get('../../skill'), ['reading', 'listening'], true))->dehydratedWhenHidden(),
                    C\TextInput::make('image_url')->label('Ảnh sơ đồ / bản đồ')->placeholder('/storage/images/map.png hoặc https://…')
                        ->maxLength(255)->live(onBlur: true)->required(fn (Get $get): bool => $get('question_type') === 'map_labeling')
                        ->helperText('Nhập URL ảnh, sau đó mở tab Câu hỏi & đáp án để đặt vị trí các câu trên cùng một ảnh.')
                        ->visible(fn (Get $get): bool => $get('question_type') === 'map_labeling' || in_array($get('../../skill'), ['writing', 'speaking'], true))->dehydratedWhenHidden(),
                ]),
                C\Tabs\Tab::make('Ngân hàng đáp án')->icon('heroicon-o-queue-list')->visible(fn (Get $get): bool => Authoring::isDragDrop($get()))->schema([
                    C\Placeholder::make('bank_guide')->label('Lựa chọn dùng chung')->content('Nhập cả đáp án đúng và đáp án nhiễu. Sau đó chọn đáp án đúng cho từng câu ở tab Câu hỏi & đáp án.'),
                    C\Actions::make([
                        Action::make('importBank')->label('Dán danh sách đáp án')->icon('heroicon-o-clipboard-document')->modalHeading('Nhập nhiều lựa chọn')->modalSubmitActionLabel('Thêm vào ngân hàng')->modalCancelActionLabel('Hủy')
                            ->form([C\Textarea::make('entries')->label('Mỗi dòng một lựa chọn')->placeholder("i | The search for the reasons\nii | Industrial growth\niii | Changes in diet")
                                ->helperText('Ký hiệu | Nội dung. Có thể dán hai cột từ bảng tính. Danh sách mới được thêm vào ngân hàng hiện tại.')->rows(10)->required()])
                            ->action(function (array $data, Get $get, Set $set, Action $action): void {
                                try {
                                    $set('answerOptions', Authoring::appendOptions($get('answerOptions') ?? [], $data['entries']));
                                } catch (ValidationException $exception) {
                                    Notification::make()->title('Chưa nhập được danh sách')->body($exception->getMessage())->danger()->send();
                                    $action->halt();
                                }
                            }),
                    ]),
                    C\Repeater::make('answerOptions')->relationship('answerOptions')->label('Ngân hàng đáp án')->orderColumn('order')->defaultItems(0)
                        ->afterStateHydrated(function (C\Repeater $component, Get $get, ?IeltsQuestionGroup $record): void {
                            if ($component->getState()) {
                                return;
                            }
                            // Show old banks stored in settings or per-question JSON, too.
                            $legacy = $get('settings.drag_options')
                                ?: ($record?->questions()->first()?->options ?? (collect($get('questions') ?? [])->first()['options'] ?? []));
                            if ($legacy) {
                                $state = [];
                                foreach ($legacy as $option) {
                                    $state[(string) Str::uuid()] = ['option_key' => $option['option_key'] ?? $option['key'] ?? '', 'label' => $option['label'] ?? $option['text'] ?? ''];
                                }
                                $component->state($state);
                            }
                        })
                        ->columns(4)->addActionLabel('Thêm lựa chọn')->live(onBlur: true)
                        ->schema([
                            C\TextInput::make('option_key')->label('Ký hiệu')->placeholder('A / i / 1')->required()->maxLength(50)->regex('/^[\p{L}\p{N}_-]+$/u')->distinct()->live(onBlur: true),
                            C\TextInput::make('label')->label('Nội dung')->required()->maxLength(1000)->columnSpan(3)->live(onBlur: true),
                        ]),
                ]),
                C\Tabs\Tab::make('Câu hỏi & đáp án')->icon('heroicon-o-list-bullet')->schema([
                    C\Section::make('Một câu hỏi chung, nhiều ô đáp án')
                        ->visible(fn (Get $get): bool => MultiSelect::enabled($get()))
                        ->schema(self::multiSelectSchema()),
                    C\Grid::make(2)->schema([
                      C\Actions::make([
                        Action::make('generateQuestions')->label('Tạo dãy câu hỏi')->icon('heroicon-o-plus-circle')->modalHeading('Tạo nhiều câu hỏi')->modalSubmitActionLabel('Tạo câu hỏi')->modalCancelActionLabel('Hủy')
                            ->fillForm(fn (Get $get): array => ['start' => self::nextQuestionNumber($get('../../questionGroups') ?? []), 'count' => 5])
                            ->form([
                                C\TextInput::make('start')->label('Bắt đầu từ câu số')->integer()->minValue(1)->maxValue(200)->required(),
                                C\TextInput::make('count')->label('Số câu cần tạo')->integer()->minValue(1)->maxValue(40)->default(5)->required(),
                            ])
                            ->action(fn (array $data, Get $get, Set $set) => $set('questions', Authoring::appendQuestions($get('questions') ?? [], range((int) $data['start'], (int) $data['start'] + (int) $data['count'] - 1)))),
                      ]),
                      C\Actions::make([
                        Action::make('generateFromBlanks')->label('Tạo câu từ [blank_N]')->icon('heroicon-o-sparkles')
                            ->action(function (Get $get, Set $set): void {
                                $numbers = Authoring::blankNumbers($get('question_content') ?: $get('passage_content'));
                                if (! $numbers) {
                                    Notification::make()->title('Chưa có ô trống')->body('Thêm [blank_1], [blank_2]… trong tab Đề bài trước.')->warning()->send();
                                    return;
                                }
                                $set('questions', Authoring::appendQuestions($get('questions') ?? [], array_unique($numbers)));
                            }),
                      ])->visible(fn (Get $get): bool => Authoring::isDragDrop($get())),
                    ])->visible(fn (Get $get): bool => ! MultiSelect::enabled($get())),
                    C\ViewField::make('map_position_picker')->label('Đặt vị trí các câu trên ảnh')->dehydrated(false)
                        ->visible(fn (Get $get): bool => $get('question_type') === 'map_labeling' && filled($get('image_url')))
                        ->view('filament.forms.components.ielts-map-position-picker', fn (Get $get, C\ViewField $component): array => [
                            'imagePath' => $component->generateRelativeStatePath('image_url'),
                            'questionsPath' => $component->generateRelativeStatePath('questions'),
                        ]),
                    C\Repeater::make('questions')->relationship('questions')->label('Các câu hỏi của nhóm')->orderColumn('order')->reorderable(false)->defaultItems(0)->collapsed()->live(onBlur: true)
                        ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => [
                            ...$data,
                            'answer_choice' => $data['correct_answer'] ?? null,
                            'answer_text' => $data['correct_answer'] ?? null,
                        ])
                        ->visible(fn (Get $get): bool => ! MultiSelect::enabled($get()))
                        ->helperText('Câu hỏi hiển thị theo số câu. Sửa số câu để thay đổi vị trí trong phần thi.')
                        ->addActionLabel('Thêm một câu hỏi')->deleteAction(fn (Action $action) => $action->label('Xóa câu hỏi')->requiresConfirmation())
                        ->collapseAllAction(fn (Action $action) => $action->label('Thu gọn tất cả'))
                        ->expandAllAction(fn (Action $action) => $action->label('Mở tất cả'))
                        ->itemLabel(function (array $state, Get $get): string {
                            $answer = Authoring::selectedAnswer($state, $get() ?? []);
                            return 'Câu '.($state['question_number'] ?? '?').(($get('question_type') !== 'map_labeling' && filled($state['prompt'] ?? null)) ? ' · '.Str::limit(strip_tags($state['prompt']), 70) : '').(filled($answer) ? ' → '.$answer : ' · Chưa có đáp án');
                        })
                        ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): array => Authoring::prepareQuestion($data, $get('question_type'), $get() ?? [], $get('../../skill')))
                        ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, Get $get, \App\Models\IeltsQuestion $record): array => Authoring::prepareQuestion($data, $get('question_type'), $get() ?? [], $get('../../skill'), $record))
                        ->schema(self::questionSchema()),
                ]),
                C\Tabs\Tab::make('Bài đọc nguồn')->icon('heroicon-o-book-open')->visible(fn (Get $get): bool => $get('../../skill') === 'reading')->schema([
                    C\RichEditor::make('passage_content')->label('Nội dung Reading Passage')->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'blockquote', 'undo', 'redo'])
                        ->helperText('Đây là bài đọc ở khung bên trái. Nhập toàn bộ Passage tại nhóm đầu của bài đọc; các nhóm còn lại của cùng Passage để trống trường này. Đề câu hỏi và ô kéo thả nhập ở tab Đề bài.'),
                ]),
                C\Tabs\Tab::make('Audio & transcript')->icon('heroicon-o-speaker-wave')->visible(fn (Get $get): bool => $get('../../skill') === 'listening')->schema([
                    ...self::audioSchema(),
                    C\Textarea::make('transcript')->label('Transcript / lời bài nghe')->rows(12)->helperText('Hiển thị khi xem lại kết quả, không hiển thị cho thí sinh đang làm bài.'),
                ]),
            ]),
        ];
    }

    private static function multiSelectSchema(): array
    {
        return [
            C\TextInput::make('settings.start_number')->label('Bắt đầu từ câu số')->integer()->minValue(1)->maxValue(199)->required()->live(onBlur: true),
            C\Textarea::make('settings.prompt')->label('Nội dung câu hỏi chung')->rows(3)->required(),
            C\Actions::make([
                Action::make('pasteMultiOptions')->label('Dán danh sách lựa chọn')->icon('heroicon-o-clipboard-document')
                    ->modalHeading('Nhập các lựa chọn dùng chung')->modalSubmitActionLabel('Thêm lựa chọn')->modalCancelActionLabel('Hủy')
                    ->form([C\Textarea::make('entries')->label('Mỗi dòng một lựa chọn')->placeholder("A | First option\nB | Second option\nC | Third option")
                        ->helperText('Ký hiệu | Nội dung. Có thể dán hai cột từ bảng tính.')->rows(8)->required()])
                    ->action(function (array $data, Get $get, Set $set, Action $action): void {
                        try {
                            $bank = collect($get('settings.options') ?? [])->map(fn ($option) => ['option_key' => $option['key'] ?? '', 'label' => $option['text'] ?? ''])->all();
                            $bank = Authoring::appendOptions($bank, $data['entries']);
                            $set('settings.options', collect($bank)->map(fn ($option) => ['key' => $option['option_key'], 'text' => $option['label']])->all());
                        } catch (ValidationException $exception) {
                            Notification::make()->title('Chưa nhập được lựa chọn')->body($exception->getMessage())->danger()->send();
                            $action->halt();
                        }
                    }),
            ]),
            C\Repeater::make('settings.options')->label('Các lựa chọn (gồm cả đáp án nhiễu)')->columns(4)->defaultItems(0)->minItems(2)->required()->live(onBlur: true)
                ->addActionLabel('Thêm lựa chọn')->schema([
                    C\TextInput::make('key')->label('Ký hiệu')->placeholder('A')->required()->maxLength(20)->regex('/^[A-Za-z0-9_-]+$/')->distinct()->live(onBlur: true),
                    C\TextInput::make('text')->label('Nội dung')->required()->columnSpan(3)->live(onBlur: true),
                ]),
            C\CheckboxList::make('settings.correct_keys')->label('Các đáp án đúng')->required()->minItems(2)->live()
                ->options(fn (Get $get): array => collect($get('settings.options') ?? [])
                    ->filter(fn ($option) => filled($option['key'] ?? null))
                    ->mapWithKeys(fn ($option) => [$option['key'] => $option['key'].' — '.($option['text'] ?? '')])->all())
                ->helperText('Ví dụ: tích B và D. Hệ thống tự tạo 2 câu, mỗi câu 1 điểm; chấp nhận mọi thứ tự trả lời.'),
            C\Placeholder::make('multi_preview')->label('Dãy câu được tạo')->content(function (Get $get): string {
                $count = count($get('settings.correct_keys') ?? []);
                $start = (int) ($get('settings.start_number') ?? 1);
                return $count >= 2 ? 'Questions '.$start.'–'.($start + $count - 1).' · Choose '.$count.' · '.$count.' điểm' : 'Chọn ít nhất 2 đáp án đúng để tạo dãy câu.';
            }),
            C\Section::make('Lời giải chung (tùy chọn)')->collapsible()->collapsed()->columns(2)->schema([
                C\Textarea::make('settings.quote_reference')->label('Trích dẫn chứa đáp án')->rows(3),
                C\Textarea::make('settings.explanation')->label('Giải thích')->rows(3),
            ]),
        ];
    }

    private static function audioSchema(): array
    {
        return [
            C\Section::make('Audio Listening')->description('Dùng audio chung cho toàn bài Listening; chỉ cần chọn audio ở một nhóm. Chọn file từ máy hoặc dán link audio / Google Drive, rồi lưu phần thi.')->schema([
                C\Actions::make([
                    Action::make('uploadAudio')->label('Tải audio lên')->icon('heroicon-o-arrow-up-tray')
                        ->modalHeading('Tải file audio Listening')->modalSubmitActionLabel('Dùng file audio')->modalCancelActionLabel('Hủy')
                        ->form([
                            C\FileUpload::make('audio_file')->label('File audio')->required()->storeFiles(false)
                                ->acceptedFileTypes(array_keys(IeltsAudioService::MIME_EXTENSIONS))->maxSize(IeltsAudioService::MAX_SIZE_KB)
                                ->helperText('MP3, WAV, OGG, M4A. Tối đa 50 MB.')->previewable(false)
                                ->uploadingMessage('Đang tải audio lên…'),
                        ])
                        ->action(function (array $data, Set $set, Action $action): void {
                            try {
                                $url = app(IeltsAudioService::class)->upload($data['audio_file']);
                                $set('audio_url', $url);
                                Notification::make()->title('Đã chọn file audio')->body('Bạn có thể nghe thử bên dưới. Lưu phần thi để hoàn tất.')->success()->send();
                            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                                Notification::make()->title('Chưa tải được audio')->body($exception->getMessage())->danger()->send();
                                $action->halt();
                            }
                        }),
                    Action::make('pasteAudioLink')->label('Dán link audio')->icon('heroicon-o-link')
                        ->modalHeading('Dùng audio từ đường dẫn')->modalSubmitActionLabel('Dùng link audio')->modalCancelActionLabel('Hủy')
                        ->fillForm(fn (Get $get): array => ['link' => $get('audio_url') ?? ''])
                        ->form([
                            C\TextInput::make('link')->label('Link audio hoặc Google Drive')->placeholder('https://…/listening.mp3 hoặc https://drive.google.com/file/d/…/view')
                                ->required()->maxLength(2048)
                                ->rules([new \App\Rules\ValidIeltsAudioLink]),
                            C\Placeholder::make('drive_audio_help')->label('Với Google Drive')->content('File cần bật Anyone with the link và cho phép tải xuống. Vocafy nhập file về storage để phát trong bài thi; tối đa 50 MB. Với link khác, hãy dùng đường dẫn trực tiếp tới file audio.'),
                        ])
                        ->action(function (array $data, Set $set, Action $action): void {
                            try {
                                $url = app(IeltsAudioService::class)->fromLink($data['link']);
                                if (strlen($url) > 255) {
                                    throw new \InvalidArgumentException('Link phát audio quá dài (tối đa 255 ký tự). Hãy tải file lên hoặc dùng link Google Drive.');
                                }
                                $set('audio_url', $url);
                                Notification::make()->title('Đã chọn audio')->body('Bạn có thể nghe thử bên dưới. Lưu phần thi để hoàn tất.')->success()->send();
                            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                                Notification::make()->title('Chưa dùng được link audio')->body($exception->getMessage())->danger()->send();
                                $action->halt();
                            }
                        }),
                ]),
                C\TextInput::make('audio_url')->label('Link phát audio')->readOnly()->maxLength(255)
                    ->helperText('Dùng hai nút phía trên để thêm hoặc thay audio.')
                    ->rules([new \App\Rules\ValidIeltsAudioLink(allowGoogleDrive: false)]),
                C\Placeholder::make('audio_preview')->label('Nghe thử')->content(function (Get $get): HtmlString|string {
                    $url = $get('audio_url');
                    if (blank($url)) {
                        return 'Chưa chọn audio.';
                    }
                    $service = app(IeltsAudioService::class);
                    try {
                        $service->validateLink($url);
                    } catch (\InvalidArgumentException) {
                        return 'Link hiện tại chưa hợp lệ. Dùng Dán link audio để thay đường dẫn.';
                    }
                    if ($service->isGoogleDriveLink($url)) {
                        return 'Dùng Dán link audio để nhập file từ link Google Drive hiện tại.';
                    }
                    return new HtmlString('<div x-data="{ audioError: false }" wire:key="audio-preview-'.sha1($url).'">'
                        .'<audio controls preload="metadata" class="w-full" style="width:100%" src="'.e($url).'" @error="audioError = true" @loadedmetadata="audioError = false">Trình duyệt không hỗ trợ phát audio.</audio>'
                        .'<p x-show="audioError" x-cloak class="mt-2 text-sm text-danger-600">Không phát được audio. Kiểm tra link trực tiếp tới file hoặc dùng Tải audio lên.</p></div>');
                }),
                C\Actions::make([
                    Action::make('clearAudio')->label('Bỏ chọn audio')->color('gray')->icon('heroicon-o-x-mark')
                        ->action(fn (Set $set) => $set('audio_url', null)),
                ])->visible(fn (Get $get): bool => filled($get('audio_url'))),
            ]),
        ];
    }

    private static function questionSchema(): array
    {
        return [
            C\TextInput::make('question_number')->label('Số câu trong toàn phần thi')->integer()->minValue(1)->maxValue(200)->required()->live(onBlur: true),
            C\Textarea::make('prompt')->label('Nội dung câu hỏi')->placeholder('Paragraph A / nội dung câu hỏi / câu có [blank]')->rows(3)->live(onBlur: true)
                ->helperText('Có thể để trống nếu đề kéo thả đã có [blank_N] đúng với số câu này.')
                ->visible(fn (Get $get): bool => $get('../../question_type') !== 'map_labeling'),
            C\Repeater::make('options')->label('Các lựa chọn của câu này')->defaultItems(0)->columns(4)->live(onBlur: true)->addActionLabel('Thêm lựa chọn')
                ->visible(fn (Get $get): bool => Authoring::needsOptions($get('../../') ?? []))
                ->schema([
                    C\TextInput::make('key')->label('Ký hiệu')->placeholder('A')->required()->maxLength(50)->distinct()->live(onBlur: true),
                    C\TextInput::make('text')->label('Nội dung lựa chọn')->required()->columnSpan(3)->live(onBlur: true),
                ]),
            C\Select::make('answer_choice')->label('Đáp án đúng')->searchable()->required()
                ->visible(fn (Get $get): bool => in_array($get('../../../../skill'), ['reading', 'listening'], true) && self::answerChoices($get) !== null)
                ->options(fn (Get $get): array => self::answerChoices($get) ?? [])
                ->formatStateUsing(function (?string $state, Get $get): ?string {
                    if (Authoring::fixedAnswers($get('../../question_type'))) {
                        return match (strtoupper(trim($state ?? ''))) { 'T' => 'TRUE', 'F' => 'FALSE', 'NG' => 'NOT GIVEN', 'Y' => 'YES', 'N' => 'NO', default => $state };
                    }
                    return $state;
                }),
            C\TextInput::make('answer_text')->label('Đáp án đúng')->required()
                ->visible(fn (Get $get): bool => in_array($get('../../../../skill'), ['reading', 'listening'], true) && self::answerChoices($get) === null)
                ->helperText('Có thể chấp nhận nhiều cách viết: center / centre. Câu chưa có đáp án sẽ được báo lỗi khi lưu.'),
            C\Select::make('word_limit_mode')->label('Quy tắc giới hạn')
                ->options(['tokens' => 'Tối đa N từ/số', 'words' => 'Chỉ từ (WORDS ONLY)', 'words_and_number' => 'N từ và/hoặc một số'])
                ->placeholder('Theo hướng dẫn nhóm, hoặc tổng từ/số')->nullable()
                ->visible(fn (Get $get): bool => ! Authoring::isDragDrop($get('../../') ?? []) && in_array($get('../../question_type'), ['fill_in_blanks', 'short_answer', 'map_labeling'], true)),
            C\TextInput::make('word_limit')->label('Giới hạn số từ')->integer()->minValue(1)->maxValue(1000)
                ->visible(fn (Get $get): bool => ! Authoring::isDragDrop($get('../../') ?? []) && in_array($get('../../question_type'), ['fill_in_blanks', 'short_answer', 'map_labeling'], true))
                ->helperText('Reading/Listening dạng nhập chữ: server từ chối câu trả lời vượt giới hạn. Writing hiện dùng mốc hiển thị cố định 150/250 từ; trường này chưa được scorer Writing áp dụng.'),
            C\Hidden::make('drop_x')->visible(fn (Get $get): bool => $get('../../question_type') === 'map_labeling')->dehydratedWhenHidden(),
            C\Hidden::make('drop_y')->visible(fn (Get $get): bool => $get('../../question_type') === 'map_labeling')->dehydratedWhenHidden(),
            C\Hidden::make('map_position_cleared')->default(false)
                ->visible(fn (Get $get): bool => $get('../../question_type') === 'map_labeling')->dehydratedWhenHidden(),
            C\Section::make('Lời giải (tùy chọn)')->collapsible()->collapsed()->columns(2)->schema([
                C\Textarea::make('quote_reference')->label('Trích dẫn chứa đáp án')->rows(3),
                C\Textarea::make('explanation')->label('Giải thích')->rows(3),
            ]),
        ];
    }

    private static function answerChoices(Get $get): ?array
    {
        $group = $get('../../') ?? [];
        if (Authoring::isDragDrop($group)) {
            return collect($group['answerOptions'] ?? [])->filter(fn ($option) => filled($option['option_key'] ?? null))
                ->mapWithKeys(fn ($option) => [$option['option_key'] => $option['option_key'].' — '.($option['label'] ?? '')])->all();
        }
        $fixed = Authoring::fixedAnswers($group['question_type'] ?? null);
        if ($fixed) {
            return $fixed;
        }
        if (Authoring::needsOptions($group)) {
            return collect($get('options') ?? [])->filter(fn ($option) => filled($option['key'] ?? null))
                ->mapWithKeys(fn ($option) => [$option['key'] => $option['key'].' — '.($option['text'] ?? '')])->all();
        }
        return null;
    }

    public static function summary(array $groups): string
    {
        $questions = collect($groups)->flatMap(fn ($group) => MultiSelect::questions($group));
        $answered = collect($groups)->sum(fn ($group) => collect(MultiSelect::questions($group))
            ->filter(fn ($question) => filled(Authoring::selectedAnswer($question, $group)))->count());
        return $questions->count().' câu · '.count($groups).' nhóm · '.$answered.' câu có đáp án';
    }

    private static function groupLabel(array $group): string
    {
        $numbers = collect(MultiSelect::questions($group))->pluck('question_number')->filter()->map(fn ($n) => (int) $n);
        return ($group['title'] ?? 'Nhóm mới').' · '.($numbers->count() ? 'Câu '.$numbers->min().'–'.$numbers->max().' ('.$numbers->count().')' : 'Chưa có câu hỏi');
    }

    private static function nextQuestionNumber(array $groups): int
    {
        return 1 + (int) collect($groups)->flatMap(fn ($group) => MultiSelect::questions($group))->max('question_number');
    }
}
