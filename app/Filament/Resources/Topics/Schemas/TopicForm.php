<?php

namespace App\Filament\Resources\Topics\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de la Thématique')
                    ->description('Détails généraux, département de rattachement et métadonnées de publication.')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('Titre de la thématique')
                                ->placeholder('Ex: Ressources Humaines & Avantages')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                                    'slug',
                                    Str::slug($state ?? ''),
                                )),

                            TextInput::make('slug')
                                ->label('Identifiant (Slug)')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                        ]),

                        Textarea::make('description')
                            ->label('Description synthétique')
                            ->placeholder('Présentation du domaine ou des démarches couvertes...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('icon')
                                ->label('Icône représentative')
                                ->options([
                                    'academic-cap' => 'Ressources Humaines / Formation (academic-cap)',
                                    'compass' => 'Informatique & Outils (compass)',
                                    'banknotes' => 'Finance & Achats (banknotes)',
                                    'calendar' => 'Onboarding & Calendrier (calendar)',
                                    'folder' => 'Dossier standard (folder)',
                                ])
                                ->default('folder'),

                            TextInput::make('order')
                                ->label('Ordre d\'affichage')
                                ->numeric()
                                ->default(0),
                        ]),

                        Toggle::make('is_published')
                            ->label('Publié (visible sur CS Knowledge)')
                            ->default(true),
                    ]),
            ]);
    }
}
