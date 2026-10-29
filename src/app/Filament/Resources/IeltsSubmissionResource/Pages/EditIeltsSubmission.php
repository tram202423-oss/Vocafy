<?php
namespace App\Filament\Resources\IeltsSubmissionResource\Pages;

use App\Filament\Resources\IeltsSubmissionResource;
use App\Models\IeltsSubmission;
use App\Services\IeltsAiAssessmentService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EditIeltsSubmission extends EditRecord
{
    protected static string $resource = IeltsSubmissionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            $current = IeltsSubmission::lockForUpdate()->findOrFail($record->id);
            Gate::authorize('update', $current);
            if (! auth()->user()->isAdmin()) unset($data['assigned_examiner_id']);
            if ($current->status->value !== 'completed') $data = \Illuminate\Support\Arr::only($data, ['assigned_examiner_id']);
            $current->fill(\Illuminate\Support\Arr::only($data, ['assigned_examiner_id', 'teacher_band_score', 'teacher_criteria', 'examiner_notes']));
            $current->save();
            return $current;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('review')->label('Xem bài làm')->visible(fn () => $this->record->status->value === 'completed')->url(fn () => route('ielts.exam.result', $this->record))->openUrlInNewTab(),
            Actions\Action::make('retry_ai')->label('Chấm AI lại')
                ->visible(fn () => $this->record->status->value === 'completed' && in_array($this->record->skill, ['writing', 'speaking'], true))
                ->disabled(fn () => in_array(data_get($this->record->metadata, 'ai_assessment.status'), ['pending', 'processing'], true))
                ->action(function () {
                    Gate::authorize('update', $this->record);
                    app(IeltsAiAssessmentService::class)->request($this->record);
                    \Filament\Notifications\Notification::make()->title('Đã đưa bài vào hàng đợi AI')->success()->send();
                }),
        ];
    }
}
