<?php

namespace App\Filament\Resources\IeltsTestResource\Pages;

use App\Filament\Resources\IeltsTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIeltsTest extends EditRecord
{
    protected static string $resource = IeltsTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
