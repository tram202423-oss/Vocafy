<?php

namespace App\Filament\Resources\IeltsSubmissionResource\Pages;

use App\Filament\Resources\IeltsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIeltsSubmission extends EditRecord
{
    protected static string $resource = IeltsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
