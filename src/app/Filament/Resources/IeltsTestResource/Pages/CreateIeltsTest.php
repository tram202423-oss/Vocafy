<?php

namespace App\Filament\Resources\IeltsTestResource\Pages;

use App\Filament\Resources\IeltsTestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIeltsTest extends CreateRecord
{
    protected static string $resource = IeltsTestResource::class;

    protected static ?string $title = 'Tạo bộ đề thi';

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterCreate(): void
    {
        \App\Services\IeltsAuthoringService::syncTestSections($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Tạo bộ đề');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->label('Tạo và thêm bộ đề khác');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->label('Hủy');
    }
}
