<?php

namespace App\Filament\Resources\IeltsTestResource\Pages;

use App\Filament\Resources\IeltsTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIeltsTests extends ListRecords
{
    protected static string $resource = IeltsTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tạo Bộ đề mới'),
        ];
    }
}
