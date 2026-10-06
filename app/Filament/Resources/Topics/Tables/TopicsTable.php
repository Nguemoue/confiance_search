<?php

namespace App\Filament\Resources\Topics\Tables;

use App\Models\Topic;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Sujet')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Topic $record): ?string => $record->description ? Str::limit($record->description, 50) : null),

                TextColumn::make('tags.name')
                    ->label('Tags')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('options_count')
                    ->label('Réponses / Options')
                    ->counts('options')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                TernaryFilter::make('is_published')
                    ->label('Statut de publication')
                    ->trueLabel('Publiés')
                    ->falseLabel('Brouillons'),

                SelectFilter::make('tags')
                    ->label('Filtrer par Tag')
                    ->relationship('tags', 'name')
                    ->preload()
                    ->multiple(),
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
