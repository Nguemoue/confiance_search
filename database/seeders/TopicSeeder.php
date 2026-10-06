<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Topic;
use App\Models\TopicOption;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds with professional internal enterprise topics.
     */
    public function run(): void
    {
        // 1. Enterprise Tags / Pôles
        $tags = [
            'Ressources Humaines' => Tag::firstOrCreate(['name' => 'Ressources Humaines'], ['slug' => 'rh', 'color' => 'indigo']),
            'Informatique & IT' => Tag::firstOrCreate(['name' => 'Informatique & IT'], ['slug' => 'it', 'color' => 'sky']),
            'Finance & Achats' => Tag::firstOrCreate(['name' => 'Finance & Achats'], ['slug' => 'finance-achats', 'color' => 'emerald']),
            'Opérations & Projets' => Tag::firstOrCreate(['name' => 'Opérations & Projets'], ['slug' => 'operations', 'color' => 'purple']),
            'Sécurité & Conformité' => Tag::firstOrCreate(['name' => 'Sécurité & Conformité'], ['slug' => 'securite', 'color' => 'amber']),
            'Onboarding' => Tag::firstOrCreate(['name' => 'Onboarding'], ['slug' => 'onboarding', 'color' => 'rose']),
            'Télétravail' => Tag::firstOrCreate(['name' => 'Télétravail'], ['slug' => 'teletravail', 'color' => 'indigo']),
        ];

        // 2. Enterprise Topics & Operating Procedures (SOPs / FAQ)
        $topicsData = [
            [
                'title' => 'Ressources Humaines & Avantages Collaborateurs',
                'slug' => 'ressources-humaines-avantages',
                'description' => 'Politiques relatives aux congés, notes de frais, mutuelle santé, télétravail et avantages sociaux de l’entreprise.',
                'icon' => 'academic-cap',
                'order' => 1,
                'tags' => ['Ressources Humaines', 'Télétravail'],
                'options' => [
                    [
                        'title' => 'Procédure de pose des congés payés & RTT',
                        'content' => 'Toute demande de congé doit être saisie sur le portail SIRH au minimum 15 jours avant la date de départ souhaitée. La validation hiérarchique par votre responsable d’équipe intervient sous 48 heures ouvrées.',
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 1,
                    ],
                    [
                        'title' => 'Remboursement des notes de frais & déplacements professionnels',
                        'content' => 'Les notes de frais doivent être soumises avant le 20 de chaque mois accompagnées des justificatifs numérisés (factures détaillées avec TVA). Le remboursement est opéré avec le virement du salaire du mois en cours.',
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 2,
                    ],
                    [
                        'title' => 'Accéder au portail SIRH d’entreprise (Lucca / PayFit)',
                        'content' => 'Espace sécurisé pour consulter vos bulletins de salaire dématérialisés, déclarer vos jours de télétravail et gérer vos compteurs de congés.',
                        'type' => 'link',
                        'action_url' => 'https://sirh.entreprise.internal',
                        'order' => 3,
                    ],
                    [
                        'title' => 'Guide complet de la mutuelle & prévoyance santé',
                        'content' => 'Téléchargez le tableau récapitulatif des garanties de santé, les formulaires de rattachement des ayants droit et les démarches de tiers payant.',
                        'type' => 'download',
                        'action_url' => 'https://rh.entreprise.internal/docs/guide-mutuelle.pdf',
                        'order' => 4,
                    ],
                ],
            ],
            [
                'title' => 'Support Informatique, Outils & Sécurité IT',
                'slug' => 'support-it-outils-securite',
                'description' => 'Accès aux postes de travail, configuration du VPN sécurisé, messagerie professionnelle et bonnes pratiques de cybersécurité.',
                'icon' => 'compass',
                'order' => 2,
                'tags' => ['Informatique & IT', 'Sécurité & Conformité', 'Télétravail'],
                'options' => [
                    [
                        'title' => 'Comment configurer et activer le VPN d’entreprise en télétravail ?',
                        'content' => 'Installez le client VPN officiel disponible sur le portail applicatif interne. Connectez-vous avec vos identifiants SSO professionnels et validez la notification push sur votre application d’authentification à deux facteurs (MFA).',
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 1,
                    ],
                    [
                        'title' => 'Ouvrir un ticket d’assistance auprès du Helpdesk IT',
                        'content' => 'Portail de gestion des incidents et demandes matérielles (PC, écran supplémentaire, droits d’accès serveurs, licences logicielles).',
                        'type' => 'link',
                        'action_url' => 'https://helpdesk.entreprise.internal',
                        'order' => 2,
                    ],
                    [
                        'title' => 'Ligne d’urgence IT & Support utilisateurs',
                        'content' => 'Support technique disponible du lundi au vendredi de 7h30 à 18h30 pour tout blocage bloquant la production : poste interne 4040.',
                        'type' => 'contact',
                        'action_url' => 'tel:+33180004040',
                        'order' => 3,
                    ],
                    [
                        'title' => 'Charte de sécurité informatique & gestion des mots de passe',
                        'content' => 'Consignes relatives au chiffrement des postes, à l’utilisation du gestionnaire de mots de passe d’entreprise et à la protection des données sensibles.',
                        'type' => 'download',
                        'action_url' => 'https://it.entreprise.internal/docs/charte-securite-it.pdf',
                        'order' => 4,
                    ],
                ],
            ],
            [
                'title' => 'Finance, Achats & Moyens Généraux',
                'slug' => 'finance-achats-moyens-generaux',
                'description' => 'Processus de commande fournisseur, seuils de validation budgétaire, gestion des locaux et réservations d’espaces.',
                'icon' => 'banknotes',
                'order' => 3,
                'tags' => ['Finance & Achats', 'Opérations & Projets'],
                'options' => [
                    [
                        'title' => 'Circuit d’approbation des bons de commande (PO)',
                        'content' => "Tout achat supérieur à 500 € HT requiert la génération d'un bon de commande validé par le responsable de département. Au-delà de 5 000 € HT, la validation de la Direction Financière est obligatoire avant tout engagement.",
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 1,
                    ],
                    [
                        'title' => 'Outil de gestion des achats & factures fournisseurs',
                        'content' => 'Plateforme centralisée pour la création des demandes d’achats, suivi des livraisons et rapprochement des factures.',
                        'type' => 'link',
                        'action_url' => 'https://achats.entreprise.internal',
                        'order' => 2,
                    ],
                    [
                        'title' => 'Réservation des salles de réunion et visioconférence',
                        'content' => 'Les salles de conférence sont réservables directement depuis votre calendrier Outlook / Google Workspace avec équipement Teams/Zoom Rooms.',
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 3,
                    ],
                ],
            ],
            [
                'title' => 'Onboarding & Intégration des Nouveaux Collaborateurs',
                'slug' => 'onboarding-integration-collaborateurs',
                'description' => 'Programme d’accueil, livret collaborateur, remise du matériel et étapes clés des 90 premiers jours.',
                'icon' => 'calendar',
                'order' => 4,
                'tags' => ['Onboarding', 'Ressources Humaines', 'Sécurité & Conformité'],
                'options' => [
                    [
                        'title' => 'Étapes clés de la première semaine',
                        'content' => "Jour 1 : Accueil RH et remise du badge et matériel informatique.\nJour 2 : Découverte des équipes et présentation du tuteur désigné.\nJour 3-5 : Formations initiales aux outils internes et conformité de sécurité.",
                        'type' => 'faq',
                        'action_url' => null,
                        'order' => 1,
                    ],
                    [
                        'title' => 'Livret d’accueil collaborateur (Édition interne)',
                        'content' => 'Document complet détaillant l’organigramme, la culture d’entreprise, les services sur site et les contacts essentiels.',
                        'type' => 'download',
                        'action_url' => 'https://rh.entreprise.internal/docs/livret-accueil.pdf',
                        'order' => 2,
                    ],
                    [
                        'title' => 'Contact du pôle Accueil & Moyens Généraux',
                        'content' => 'Accueil central du siège social pour la création ou renouvellement de badges d’accès : poste interne 1010.',
                        'type' => 'contact',
                        'action_url' => 'tel:+33180001010',
                        'order' => 3,
                    ],
                ],
            ],
        ];

        Topic::withoutSyncingToSearch(function () use ($topicsData, $tags): void {
            foreach ($topicsData as $data) {
                $topic = Topic::updateOrCreate(
                    ['slug' => $data['slug']],
                    [
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'icon' => $data['icon'],
                        'is_published' => true,
                        'order' => $data['order'],
                    ]
                );

                // Attach tags
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    if (isset($tags[$tagName])) {
                        $tagIds[] = $tags[$tagName]->id;
                    }
                }
                $topic->tags()->sync($tagIds);

                // Create or update options
                foreach ($data['options'] as $optionData) {
                    TopicOption::updateOrCreate(
                        [
                            'topic_id' => $topic->id,
                            'title' => $optionData['title'],
                        ],
                        [
                            'content' => $optionData['content'],
                            'type' => $optionData['type'],
                            'action_url' => $optionData['action_url'],
                            'is_published' => true,
                            'order' => $optionData['order'],
                        ]
                    );
                }
            }
        });
    }
}
