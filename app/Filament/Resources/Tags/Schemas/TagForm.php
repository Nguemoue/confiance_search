<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Tag')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nom du tag')
                                ->placeholder('Ex: Bourses')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                    'slug',
                                    Str::slug($state ?? ''),
                                )),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ]),

                        Select::make('color')
                            ->label('Couleur du badge')
                            ->options([
                                'sky' => 'Bleu Ciel',
                                'indigo' => 'Indigo',
                                'purple' => 'Violet',
                                'emerald' => 'Émeraude',
                                'amber' => 'Ambre / Jaune',
                                'rose' => 'Rose',
                                'zinc' => 'Gris neutre',
                            ])
                            ->default('indigo')
                            ->required(),
                    ]),
            ]);
    }
}
