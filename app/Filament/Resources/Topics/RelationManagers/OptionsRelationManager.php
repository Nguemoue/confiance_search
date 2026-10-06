<?php

namespace App\Filament\Resources\Topics\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Fiches Pratiques & Démarches';

    protected static ?string $modelLabel = 'Fiche pratique';

    protected static ?string $pluralModelLabel = 'Fiches pratiques & Démarches';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('title')
                        ->label('Titre / Démarche')
                        ->placeholder('Ex: Procédure de validation des congés')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Select::make('type')
                        ->label('Type de démarche')
                        ->options([
                            'faq' => 'Procédure interne (Guide)',
                            'link' => 'Outil / Plateforme métier',
                            'download' => 'Document modèle / Fichier',
                            'contact' => 'Contact / Poste interne',
                        ])
                        ->default('faq')
                        ->required(),
                ]),

                RichEditor::make('content')
                    ->label('Contenu détaillé / Mode opératoire')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'bulletList',
                        'orderedList',
                    ])
                    ->columnSpanFull(),

                Grid::make(3)->schema([
                    TextInput::make('action_url')
                        ->label('Lien direct (URL ou tel:)')
                        ->placeholder('https://sirh.entreprise.internal ou tel:+3318000...')
                        ->columnSpan(2),

                    Toggle::make('is_published')
                        ->label('Publié')
                        ->default(true),

                    TextInput::make('order')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Titre de la démarche')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'faq' => 'primary',
                        'link' => 'info',
                        'download' => 'success',
                        'contact' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'faq' => 'Procédure',
                        'link' => 'Outil interne',
                        'download' => 'Document',
                        'contact' => 'Contact',
                        default => $state,
                    }),

                TextColumn::make('action_url')
                    ->label('Lien associé')
                    ->limit(30)
                    ->placeholder('-'),

                IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->headerActions([
                CreateAction::make()
                    ->label('Nouvelle démarche'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
