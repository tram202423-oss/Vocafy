<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Vocabulary;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $users = User::count();
        $categories = Category::count();
        $topics = Topic::count();
        $vocabularies = Vocabulary::count();

        return [
            Stat::make('Total User', $users),
            Stat::make('Categories', $categories),
            Stat::make('Topics', $topics),
            Stat::make('Vocabularies', $vocabularies),
        ];
    }
}