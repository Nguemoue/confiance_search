<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CS Knowledge - Design System & Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration de la plateforme CS Knowledge pour l'entreprise Confiance
    | (thème épuré, fond gris perle & bleu marine logo #081951).
    |
    */

    'brand' => [
        'name' => 'CS Knowledge',
        'subname' => 'NvonchiSearch',
        'fullname' => 'CS Knowledge',
        'company' => 'Entreprise Confiance',
        'tagline' => 'Portail documentaire & recherche interne',
        'baseline' => 'Référentiel des procédures, modes opératoires et documents officiels',
        'verified_label' => 'Document certifié Confiance',
        'category_label' => 'Direction / Pôle',
        'doc_label' => 'Fiche & démarche',
        'logo_path' => 'images/logo-confiance.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Polices de caractères (Fonts)
    |--------------------------------------------------------------------------
    */
    'fonts' => [
        'display' => '"Manrope", ui-sans-serif, system-ui, sans-serif',
        'sans' => '"Geist", ui-sans-serif, system-ui, sans-serif',
        'mono' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
        'google_fonts_url' => 'https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Palette de Couleurs (Bleu Marine Confiance #081951 & Gris Épuré)
    |--------------------------------------------------------------------------
    */
    'colors' => [
        'brand' => [
            50 => '#f1f4fa',
            100 => '#e2e8f4',
            200 => '#c8d4ec',
            300 => '#a2b8e0',
            400 => '#6b92ce',
            500 => '#3d6db8',
            600 => '#1e4b97',
            700 => '#143673',
            800 => '#0e2454',
            900 => '#081951', // Couleur officielle exacte du logo Confiance
            950 => '#040d2b',
        ],

        'theme' => [
            'bg' => '#f4f5f7',
            'surface' => '#ffffff',
            'surface_muted' => '#f8fafc',
            'border' => '#e2e8f0',
            'border_hover' => '#cbd5e1',
            'text_primary' => '#0f172a',
            'text_secondary' => '#475569',
            'text_muted' => '#64748b',
            'primary' => '#081951',
            'primary_hover' => '#0e2454',
            'primary_light' => '#f1f4fa',
        ],
    ],

    'trust_badges' => [
        [
            'title' => 'Documents certifiés',
            'description' => 'Validés par les référents de l’entreprise Confiance',
            'icon' => 'check-badge',
        ],
        [
            'title' => 'Recherche instantanée',
            'description' => 'Indexation plein texte de tous les modes opératoires',
            'icon' => 'magnifying-glass',
        ],
        [
            'title' => 'Réseau interne',
            'description' => 'Accès exclusif aux collaborateurs Confiance',
            'icon' => 'shield-check',
        ],
    ],

];
