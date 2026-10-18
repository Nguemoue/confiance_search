<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Models\Document;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Document')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Document $record): ?string => $record->original_filename ? Str::limit($record->original_filename, 40) : null),

                TextColumn::make('department')
                    ->label('Direction / Pôle')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('page_count')
                    ->label('Pages')
                    ->numeric()
                    ->suffix(' p.')
                    ->sortable(),

                TextColumn::make('sections_count')
                    ->label('Sections extraites')
                    ->counts('sections')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn (Document $record): string => $record->formatted_file_size)
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('department')
                    ->label('Direction')
                    ->options([
                        'Ressources Humaines' => 'Ressources Humaines',
                        'Informatique & DSI' => 'Informatique & DSI',
                        'Finance & Achats' => 'Finance & Achats',
                        'Direction Générale & Juridique' => 'Direction Générale & Juridique',
                        'Sécurité & Conformité' => 'Sécurité & Conformité',
                    ]),

                TernaryFilter::make('is_published')
                    ->label('Statut de publication')
                    ->trueLabel('Publiés')
                    ->falseLabel('Brouillons'),
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
