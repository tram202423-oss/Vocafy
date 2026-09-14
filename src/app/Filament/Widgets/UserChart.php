<?php

namespace App\Filament\Widgets;

use App\Enums\RoleEnum;
use Filament\Widgets\ChartWidget;
use Spatie\Permission\Models\Role;

class UserChart extends ChartWidget
{
    protected static ?string $heading = 'Users by Role';

    protected function getData(): array
    {
        $roles = Role::withCount('users')->get();

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $roles->pluck('users_count')->toArray(),
                    'backgroundColor' => [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $roles->map(function ($role) {
                return RoleEnum::from($role->name)->label();
            })->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [ 
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }
}