<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Topics\TopicResource;
use App\Models\Topic;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestTopicsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Thématiques récemment mises à jour';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Topic::query()->with('tags')->withCount('options')->latest('updated_at'))
            ->columns([
                TextColumn::make('title')
                    ->label('Thématique')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('tags.name')
                    ->label('Pôles & Catégories')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('options_count')
                    ->label('Fiches & Démarches')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Dernière modif.')
                    ->since(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Modifier')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Topic $record): string => TopicResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10]);
    }
}
