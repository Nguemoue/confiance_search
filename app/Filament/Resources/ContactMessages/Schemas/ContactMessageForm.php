<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du message')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nom de l\'expéditeur')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('Adresse Email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                        ]),

                        TextInput::make('subject')
                            ->label('Sujet')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('message')
                            ->label('Contenu du message')
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),

                        Toggle::make('is_read')
                            ->label('Marquer comme traité / lu')
                            ->default(false),
                    ]),
            ]);
    }
}
