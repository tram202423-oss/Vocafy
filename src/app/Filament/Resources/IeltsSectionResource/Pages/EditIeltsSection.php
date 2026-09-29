<?php

namespace App\Filament\Resources\IeltsSectionResource\Pages;

use App\Filament\Resources\IeltsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIeltsSection extends EditRecord
{
    protected static string $resource = IeltsSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
