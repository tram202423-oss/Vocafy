<?php

namespace App\Filament\Resources\IeltsTestResource\Pages;

use App\Filament\Resources\IeltsTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIeltsTest extends EditRecord
{
    protected static string $resource = IeltsTestResource::class;

    protected static ?string $title = 'Chỉnh sửa bộ đề thi';

    protected ?bool $hasDatabaseTransactions = true;

    protected function afterSave(): void
    {
        \App\Services\IeltsAuthoringService::syncTestSections($this->record);
        $this->refreshFormData(['slug']);
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
            Actions\Action::make('preview')->label('Xem bộ đề')->icon('heroicon-o-eye')
                ->url(fn (): string => route('ielts.tests.show', ['slug' => $this->record->slug, 'preview' => 1]))->openUrlInNewTab(),
            Actions\DeleteAction::make()->label('Xóa bộ đề'),
        ];
    }
}
