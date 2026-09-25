<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Quản lý Blog';

    protected static ?string $navigationLabel = 'Bài viết Blog';

    protected static ?string $modelLabel = 'Bài viết';

    protected static ?string $pluralModelLabel = 'Bài viết Blog';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)->schema([
                    // Main column (8 cols)
                    Section::make('Nội dung bài viết')
                        ->schema([
                            TextInput::make('title')
                                ->label('Tiêu đề bài viết')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, ?string $operation) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                        $set('meta_title', $state);
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Đường dẫn (Slug)')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            Textarea::make('excerpt')
                                ->label('Mô tả ngắn (Excerpt / Sa-pô)')
                                ->rows(3)
                                ->maxLength(500)
                                ->helperText('Đoạn tóm tắt hiển thị trên thẻ bài viết và meta description'),

                            RichEditor::make('content')
                                ->label('Nội dung chi tiết')
                                ->required()
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('blog-attachments')
                                ->columnSpanFull(),
                        ])
                        ->columnSpan(8),

                    // Sidebar column (4 cols)
                    Grid::make(1)->schema([
                        Section::make('Cài đặt xuất bản')
                            ->schema([
                                Select::make('post_category_id')
                                    ->label('Chuyên mục')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Select::make('status')
                                    ->label('Trạng thái')
                                    ->options([
                                        'draft' => 'Bản nháp (Draft)',
                                        'published' => 'Công khai (Published)',
                                    ])
                                    ->default('published')
                                    ->required(),

                                DateTimePicker::make('published_at')
                                    ->label('Thời gian xuất bản')
                                    ->default(now()),

                                Select::make('user_id')
                                    ->label('Tác giả')
                                    ->relationship('author', 'name')
                                    ->default(fn () => Auth::id())
                                    ->searchable()
                                    ->required(),
                            ]),

                        Section::make('Ảnh đại diện (Thumbnail)')
                            ->schema([
                                FileUpload::make('thumbnail')
                                    ->label('Tải ảnh đại diện')
                                    ->image()
                                    ->disk('public')
                                    ->directory('blog-thumbnails')
                                    ->imageEditor()
                                    ->maxSize(3072),
                            ]),

                        Section::make('Cấu hình SEO')
                            ->schema([
                                TextInput::make('meta_title')
                                    ->label('Meta Title')
                                    ->maxLength(70)
                                    ->placeholder('Tiêu đề chuẩn SEO (tối đa 70 ký tự)'),

                                Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->maxLength(160)
                                    ->rows(3)
                                    ->placeholder('Mô tả hiển thị trên kết quả tìm kiếm Google (tối đa 160 ký tự)'),
                            ])
                            ->collapsed(),
                    ])->columnSpan(4),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->label('Ảnh')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(asset('images/logo.png')),

                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->title),

                TextColumn::make('category.name')
                    ->label('Chuyên mục')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Công khai',
                        'draft' => 'Bản nháp',
                        default => $state,
                    }),

                TextColumn::make('author.name')
                    ->label('Tác giả')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('views_count')
                    ->label('Lượt xem')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->label('Ngày đăng')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('post_category_id')
                    ->label('Chuyên mục')
                    ->relationship('category', 'name'),

                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'published' => 'Công khai',
                        'draft' => 'Bản nháp',
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
