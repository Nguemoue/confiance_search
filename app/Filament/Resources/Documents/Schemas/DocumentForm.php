<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fichier PDF & Indexation')
                    ->description('Téléversez le document PDF pour extraction automatique du contenu, des sections et indexation.')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Fichier PDF officiel')
                            ->disk('public')
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Téléversez un document PDF numérique contenant du texte pour permettre la recherche plein texte.')
                            ->afterStateUpdated(function (Set $set, $state): void {
                                if ($state && method_exists($state, 'getClientOriginalName')) {
                                    $set('original_filename', $state->getClientOriginalName());
                                    $set('title', pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME));
                                    $set('slug', Str::slug(pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME)));
                                    $set('file_size', $state->getSize());
                                }
                            }),

                        Grid::make(2)->schema([
                            TextInput::make('title')
                                ->label('Titre du document')
                                ->placeholder('Ex: Accord d’entreprise & Télétravail 2026')
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

                        Grid::make(2)->schema([
                            Select::make('department')
                                ->label('Direction / Pôle')
                                ->options([
                                    'Ressources Humaines' => 'Ressources Humaines',
                                    'Informatique & DSI' => 'Informatique & DSI',
                                    'Finance & Achats' => 'Finance & Achats',
                                    'Direction Générale & Juridique' => 'Direction Générale & Juridique',
                                    'Sécurité & Conformité' => 'Sécurité & Conformité',
                                    'Moyens Généraux' => 'Moyens Généraux',
                                ])
                                ->searchable()
                                ->preload()
                                ->placeholder('Sélectionnez la direction référente'),

                            Toggle::make('is_published')
                                ->label('Publié (accessible aux collaborateurs)')
                                ->default(true)
                                ->inline(false),
                        ]),

                        Textarea::make('description')
                            ->label('Description synthétique / Objet du document')
                            ->placeholder('Résumé de la directive ou des règles contenues dans ce PDF...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
