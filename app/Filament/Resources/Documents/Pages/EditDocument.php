<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\PdfParserService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reindex')
                ->label('Ré-extraire le texte PDF')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    app(PdfParserService::class)->processDocument($this->record);
                    Notification::make()
                        ->title('Texte et sections ré-extraits avec succès !')
                        ->success()
                        ->send();
                }),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['file_path']) && Storage::disk('public')->exists($data['file_path'])) {
            $data['file_size'] = Storage::disk('public')->size($data['file_path']);
            if (empty($data['original_filename'])) {
                $data['original_filename'] = basename($data['file_path']);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('file_path')) {
            app(PdfParserService::class)->processDocument($this->record);
        }
    }
}
