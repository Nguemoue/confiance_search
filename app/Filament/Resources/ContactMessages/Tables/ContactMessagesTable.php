<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Expéditeur')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ContactMessage $record): string => $record->email),

                TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->limit(40)
                    ->placeholder('(Sans objet)'),

                TextColumn::make('message')
                    ->label('Message')
                    ->limit(60)
                    ->wrap(),

                IconColumn::make('is_read')
                    ->label('Traité / Lu')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date de réception')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_read')
                    ->label('Statut de lecture')
                    ->trueLabel('Messages traités')
                    ->falseLabel('Non traités'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
