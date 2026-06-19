<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use App\Models\Vocabulary;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';

    protected static string $view = 'filament.pages.dashboard';

    public array $users;

    public int $categories;

    public int $topics;

    public int $vocabularies;

    public int $lessons;

    public int $quizzes;

    public function mount(): void
    {
        $this->users['all'] = User::all();
        $this->users['count'] = User::count();

        $this->categories = Category::count();

        $this->topics = Topic::count();

        $this->vocabularies = Vocabulary::count();

        $this->lessons = Lesson::count();

        $this->quizzes = Quiz::count();
    }
}