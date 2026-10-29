<?php

namespace App\Filament\Resources\IeltsSectionResource\Pages;

use App\Filament\Resources\IeltsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIeltsSections extends ListRecords
{
    protected static string $resource = IeltsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tạo phần thi'),
        ];
    }
}
