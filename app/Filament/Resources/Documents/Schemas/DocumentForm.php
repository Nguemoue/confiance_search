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
                Section::make('Fichier PDF & Indexation CS Knowledge')
                    ->description('Téléversez le document PDF pour extraction automatique du contenu, des sections et indexation.')
                    ->columnSpanFull()
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
                                    $filename = $state->getClientOriginalName();
                                    $set('original_filename', $filename);
                                    $set('title', pathinfo($filename, PATHINFO_FILENAME));
                                    $set('slug', Str::slug(pathinfo($filename, PATHINFO_FILENAME)));
                                    $set('file_size', $state->getSize());
                                    $set('reference_code', 'CS-DOC-'.strtoupper(Str::random(6)));
                                }
                            }),

                        Grid::make(3)->schema([
                            TextInput::make('reference_code')
                                ->label('Code de Référence Documentaire')
                                ->placeholder('Ex: CS-RH-2026-001')
                                ->required()
                                ->maxLength(50)
                                ->default(fn () => 'CS-DOC-'.strtoupper(Str::random(6)))
                                ->helperText('Référence unique apposée à chaque section extraite'),

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

                        Grid::make(3)->schema([
                            Select::make('department')
                                ->label('Direction / Pôle')
                                ->options([
                                    'Ressources Humaines' => 'Ressources Humaines (HR)',
                                    'Informatique & DSI' => 'Informatique & DSI (IT)',
                                    'Finance & Achats' => 'Finance & Achats (Finance & Procurement)',
                                    'Direction Générale & Juridique' => 'Direction Générale & Juridique (Legal)',
                                    'Sécurité & Conformité' => 'Sécurité & Conformité (Security & Compliance)',
                                    'Moyens Généraux' => 'Moyens Généraux (Operations)',
                                ])
                                ->searchable()
                                ->preload()
                                ->placeholder('Sélectionnez la direction référente'),

                            Select::make('language')
                                ->label('Langue du document')
                                ->options([
                                    'fr' => 'Français (French)',
                                    'en' => 'Anglais (English)',
                                ])
                                ->default('fr')
                                ->required(),

                            Toggle::make('is_published')
                                ->label('Publié (visible dans CS Knowledge)')
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
