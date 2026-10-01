<?php

namespace App\Filament\Resources\IeltsSectionResource\Pages;

use App\Filament\Resources\IeltsSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIeltsSection extends CreateRecord
{
    protected static string $resource = IeltsSectionResource::class;

    protected static ?string $title = 'Tạo phần thi & câu hỏi';

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterCreate(): void
    {
        \App\Services\IeltsAuthoringService::syncSectionTotals($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Tạo phần thi');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()->label('Tạo và thêm phần thi khác');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()->label('Hủy');
    }
}
