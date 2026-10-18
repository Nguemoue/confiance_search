<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Services\PdfParserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create realistic sample PDF files in storage
        if (! Storage::disk('public')->exists('documents')) {
            Storage::disk('public')->makeDirectory('documents');
        }

        $samplePdfs = [
            [
                'title' => 'Accord Collectif sur le Télétravail & Flexibilité',
                'filename' => 'accord_teletravail_confiance.pdf',
                'department' => 'Ressources Humaines',
                'description' => 'Règles régissant le travail à distance, équipements alloués et allocations forfaitaires pour les salariés de Confiance.',
                'pages' => [
                    "ENTREPRISE CONFIANCE — ACCORD D'ENTREPRISE N° 2026-01\n\nSection 1 : Principes généraux et champ d'application\nLe présent accord s'applique à l'ensemble des collaborateurs sous contrat CDI ou CDD ayant validé leur période d'essai.",
                    "Chapitre II : Organisation matérielle et ergonomie du poste\nArticle 4 : Équipements fournis par l'entreprise\nChaque salarié éligible au télétravail se voit confier un ordinateur portable sécurisé, un casque audio antibruit, ainsi qu'une station d'accueil double écran sur demande.",
                    "Section 3 : Prise en charge financière et indemnité forfaitaire\nArticle 12 : Modalités d'indemnisation\nUne indemnité forfaitaire mensuelle nette de 50 € est allouée pour couvrir les frais de connexion internet et les consommables informatiques. Cette indemnité est exonérée de cotisations sociales.",
                ],
            ],
            [
                'title' => 'Guide Pratique de Cybersécurité & Utilisation du VPN',
                'filename' => 'guide_cybersecurite_vpn.pdf',
                'department' => 'Informatique & DSI',
                'description' => "Consignes obligatoires pour les accès aux serveurs internes, authentification MFA et procédure de signalement d'incidents.",
                'pages' => [
                    "DIRECTION DES SYSTÈMES D'INFORMATION (DSI) — GUIDE DE SÉCURITÉ\n\nChapitre I : Règles d'accès au réseau informatique\nL'accès distant aux ressources sensibles de l'entreprise Confiance nécessite obligatoirement l'activation du tunnel VPN d'entreprise.",
                    "Section 2 : Authentification et gestion des mots de passe\nArticle 3 : Politique de complexité\nLes mots de passe doivent comporter au moins 14 caractères combinant majuscules, minuscules, chiffres et caractères spéciaux. L'authentification multifacteur (MFA) est requise à chaque connexion.",
                    "Chapitre III : Procédure en cas d'incident de sécurité\nArticle 8 : Signalement obligatoire sous 2 heures\nToute tentative d'intrusion, réception de courriel de phishing suspect ou perte de matériel professionnel doit immédiatement être notifiée au pôle CERT interne.",
                ],
            ],
            [
                'title' => 'Politique des Achats & Circuit de Validation Budgétaire',
                'filename' => 'politique_achats_engagements.pdf',
                'department' => 'Finance & Achats',
                'description' => "Seuils d'engagement financier, création de bons de commande et rapprochement comptable des factures fournisseurs.",
                'pages' => [
                    "DIRECTION FINANCIÈRE — PROCÉDURE D'ACHATS ET ENGAGEMENTS\n\nSection 1 : Principes directeurs des dépenses d'entreprise\nToute acquisition de prestation ou de fourniture doit faire l'objet d'un bon de commande formel avant tout début d'exécution.",
                    "Chapitre II : Seuils de délégation et d'approbation\nArticle 5 : Circuit de validation selon les montants\nJusqu'à 1 000 € HT : validation par le responsable de pôle direct.\nDe 1 000 € à 10 000 € HT : visa préalable du Directeur de Département.\nAu-delà de 10 000 € HT : approbation conjointe de la Direction Financière et de la Direction Générale.",
                ],
            ],
        ];

        foreach ($samplePdfs as $pdfData) {
            $filePath = 'documents/'.$pdfData['filename'];
            $fullDiskPath = Storage::disk('public')->path($filePath);

            $this->generatePdfFile($fullDiskPath, $pdfData['pages']);

            $fileSize = file_exists($fullDiskPath) ? filesize($fullDiskPath) : 10240;

            $document = Document::updateOrCreate(
                ['slug' => Str::slug($pdfData['title'])],
                [
                    'title' => $pdfData['title'],
                    'department' => $pdfData['department'],
                    'description' => $pdfData['description'],
                    'file_path' => $filePath,
                    'original_filename' => $pdfData['filename'],
                    'file_size' => $fileSize,
                    'page_count' => count($pdfData['pages']),
                    'is_published' => true,
                    'raw_text' => implode("\n\n", $pdfData['pages']),
                ]
            );

            // Re-parse with PdfParserService to index sections cleanly
            app(PdfParserService::class)->processDocument($document);
        }
    }

    /**
     * Create a valid PDF 1.4 binary file with embedded text content.
     */
    protected function generatePdfFile(string $targetFile, array $pages): void
    {
        $dir = dirname($targetFile);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pageCount = count($pages);
        $objects = [];
        $offsets = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        for ($i = 1; $i <= $pageCount; $i++) {
            $kids[] = (2 + ($i * 2) - 1).' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [ '.implode(' ', $kids)." ] /Count {$pageCount} >>";

        $objIdx = 3;
        foreach ($pages as $pageText) {
            $pageObj = $objIdx++;
            $streamObj = $objIdx++;

            // Split into lines for display
            $lines = explode("\n", $pageText);
            $streamCommands = ['BT', '/F1 12 Tf', '50 780 Td', '16 TL'];
            foreach ($lines as $line) {
                $sanitized = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
                $streamCommands[] = "({$sanitized}) Tj";
                $streamCommands[] = 'T*';
            }
            $streamCommands[] = 'ET';

            $streamData = implode("\n", $streamCommands);
            $streamLength = strlen($streamData);

            $objects[$pageObj] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents {$streamObj} 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>";
            $objects[$streamObj] = "<< /Length {$streamLength} >>\nstream\n{$streamData}\nendstream";
        }

        $output = "%PDF-1.4\n";
        foreach ($objects as $num => $content) {
            $offsets[$num] = strlen($output);
            $output .= "{$num} 0 obj\n{$content}\nendobj\n";
        }

        $xrefOffset = strlen($output);
        $totalObjs = count($objects) + 1;
        $output .= "xref\n0 {$totalObjs}\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $output .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $output .= "trailer\n<< /Size {$totalObjs} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        file_put_contents($targetFile, $output);
    }
}
