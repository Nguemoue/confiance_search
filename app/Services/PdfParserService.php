<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentSection;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    /**
     * Parse a PDF file and sync its sections and raw content.
     */
    public function processDocument(Document $document): void
    {
        $filePath = Storage::disk('public')->path($document->file_path);

        if (! file_exists($filePath)) {
            return;
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        $document->update([
            'page_count' => count($pages),
            'raw_text' => $pdf->getText(),
        ]);

        // Delete any existing sections before re-populating
        $document->sections()->delete();

        $order = 1;
        $currentSectionTitle = 'Introduction / Généralités';

        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $pageText = trim($page->getText());

            if (empty($pageText)) {
                continue;
            }

            // Split page text into blocks/paragraphs
            $paragraphs = preg_split("/(\r\n|\n|\r){2,}/", $pageText);

            foreach ($paragraphs as $paragraph) {
                $cleanParagraph = trim($paragraph);

                if (empty($cleanParagraph)) {
                    continue;
                }

                // Check if this paragraph starts with a structural heading
                // e.g. "Chapitre X", "Section Y", "Article Z", "Titre 1", "I -", "1.1", etc.
                if (preg_match('/^(chapitre|section|article|titre|partie|annexe|[IVXLCDM]+\s*[-–.]|[0-9]+\.[0-9]*)\s*[:–-]?\s*(.*)$/iu', $cleanParagraph, $matches)) {
                    // Extract a meaningful section title
                    $firstLine = strtok($cleanParagraph, "\r\n");
                    if (mb_strlen($firstLine) <= 120) {
                        $currentSectionTitle = $firstLine;
                    }
                }

                DocumentSection::create([
                    'document_id' => $document->id,
                    'page_number' => $pageNumber,
                    'section_title' => $currentSectionTitle,
                    'content' => $cleanParagraph,
                    'order' => $order++,
                ]);
            }
        }
    }

    /**
     * Extract snippet surrounding a search term with periphery (KWIC).
     *
     * @return array{snippet: string, match_count: int}
     */
    public static function extractSnippet(string $content, string $term, int $radius = 160): array
    {
        $cleanTerm = trim($term);
        if (empty($cleanTerm) || mb_strlen($cleanTerm) < 2) {
            return [
                'snippet' => mb_substr($content, 0, $radius * 2),
                'match_count' => 0,
            ];
        }

        $pattern = '/'.preg_quote($cleanTerm, '/').'/iu';
        if (! preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [
                'snippet' => mb_substr($content, 0, $radius * 2),
                'match_count' => 0,
            ];
        }

        $firstMatch = $matches[0][0];
        $matchPos = $firstMatch[1];
        $matchLen = mb_strlen($firstMatch[0]);

        $start = max(0, $matchPos - $radius);
        $length = $matchLen + ($radius * 2);

        $snippet = mb_substr($content, $start, $length);

        if ($start > 0) {
            $snippet = '... '.ltrim($snippet);
        }

        if (($start + $length) < mb_strlen($content)) {
            $snippet = rtrim($snippet).' ...';
        }

        return [
            'snippet' => $snippet,
            'match_count' => count($matches[0]),
        ];
    }
}
