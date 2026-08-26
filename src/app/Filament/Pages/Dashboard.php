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
}