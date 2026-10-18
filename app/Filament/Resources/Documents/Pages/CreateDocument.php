<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Services\PdfParserService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['file_path']) && Storage::disk('public')->exists($data['file_path'])) {
            $data['file_size'] = Storage::disk('public')->size($data['file_path']);
            if (empty($data['original_filename'])) {
                $data['original_filename'] = basename($data['file_path']);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(PdfParserService::class)->processDocument($this->record);
    }
}
