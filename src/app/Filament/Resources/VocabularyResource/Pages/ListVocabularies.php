<?php

namespace App\Filament\Resources\VocabularyResource\Pages;

use App\Exports\VocabularyExport;
use App\Filament\Resources\VocabularyResource;
use App\Imports\VocabularyImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Maatwebsite\Excel\Facades\Excel;

class ListVocabularies extends ListRecords
{
    protected static string $resource = VocabularyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('import_excel')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel / CSV')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                        ])
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $path = storage_path('app/public/' . $data['file']);
                    Excel::import(new VocabularyImport(), $path);

                    Notification::make()
                        ->title('Import thành công!')
                        ->success()
                        ->send();
                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->action(function (): \Symfony\Component\HttpFoundation\BinaryFileResponse {
                    return Excel::download(new VocabularyExport(), 'vocabularies_' . now()->format('Ymd_His') . '.xlsx');
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ];
    }
}