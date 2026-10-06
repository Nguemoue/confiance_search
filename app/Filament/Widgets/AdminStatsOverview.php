<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicOption;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalTopics = Topic::count();
        $publishedTopics = Topic::where('is_published', true)->count();
        $totalOptions = TopicOption::count();
        $totalTags = Tag::count();
        $unreadMessages = ContactMessage::where('is_read', false)->count();

        return [
            Stat::make('Thématiques Documentées', $totalTopics)
                ->description("{$publishedTopics} publiées sur le portail")
                ->descriptionIcon('heroicon-m-folder')
                ->color('primary')
                ->chart([3, 5, 6, 8, 10, $totalTopics]),

            Stat::make('Procédures & Fiches', $totalOptions)
                ->description('Modes opératoires actifs')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ->chart([5, 8, 12, 15, $totalOptions]),

            Stat::make('Pôles & Catégories', $totalTags)
                ->description('Départements couverts')
                ->descriptionIcon('heroicon-m-tag')
                ->color('success'),

            Stat::make('Demandes Collaborateurs', $unreadMessages)
                ->description($unreadMessages > 0 ? "{$unreadMessages} non traitée(s)" : 'Toutes les demandes traitées')
                ->descriptionIcon($unreadMessages > 0 ? 'heroicon-m-envelope' : 'heroicon-m-check-circle')
                ->color($unreadMessages > 0 ? 'warning' : 'gray'),
        ];
    }
}
