<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestContactMessagesWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Dernières demandes collaborateurs reçues';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ContactMessage::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('Expéditeur')
                    ->searchable()
                    ->description(fn (ContactMessage $record): string => $record->email),

                TextColumn::make('subject')
                    ->label('Objet de la demande')
                    ->limit(45)
                    ->placeholder('(Sans objet)'),

                IconColumn::make('is_read')
                    ->label('Traité')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Reçu')
                    ->since(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Consulter')
                    ->icon('heroicon-m-eye')
                    ->url(fn (ContactMessage $record): string => ContactMessageResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10]);
    }
}
