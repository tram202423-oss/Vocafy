<?php

namespace App\Filament\Resources\IeltsSectionResource\Pages;

use App\Filament\Resources\IeltsSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIeltsSection extends EditRecord
{
    protected static string $resource = IeltsSectionResource::class;

    protected static ?string $title = 'Chỉnh sửa phần thi & câu hỏi';

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterSave(): void
    {
        \App\Services\IeltsAuthoringService::syncSectionTotals($this->record);
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Lưu thay đổi');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->label('Hủy');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Xóa phần thi'),
        ];
    }
}
