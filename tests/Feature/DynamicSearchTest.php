<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentSection;
use App\Models\Topic;
use App\Models\TopicOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_published_topics(): void
    {
        $topic = Topic::factory()->create([
            'title' => 'Écoles Primaires et Secondaires',
            'is_published' => true,
        ]);

        TopicOption::factory()->create([
            'topic_id' => $topic->id,
            'title' => 'Dossier d’inscription',
            'is_published' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Écoles Primaires et Secondaires');
        $response->assertSee('Dossier d’inscription');
    }

    public function test_unpublished_topics_are_hidden(): void
    {
        Topic::factory()->create([
            'title' => 'Sujet Brouillon Secret',
            'is_published' => false,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Sujet Brouillon Secret');
    }

    public function test_can_search_topics_by_keyword(): void
    {
        $topicA = Topic::factory()->create(['title' => 'Bourses Universitaires']);
        $topicB = Topic::factory()->create(['title' => 'Transport Scolaire']);

        Livewire::test('dynamic-search')
            ->set('search', 'Bourses')
            ->assertSee('Universitaires')
            ->assertDontSee('Transport Scolaire');
    }

    public function test_can_search_by_option_content(): void
    {
        $topic = Topic::factory()->create(['title' => 'Concours Nationaux']);
        TopicOption::factory()->create([
            'topic_id' => $topic->id,
            'title' => 'Calendrier des épreuves',
            'content' => 'Les inscriptions débutent en novembre chaque année.',
        ]);

        $otherTopic = Topic::factory()->create(['title' => 'Frais de Scolarité']);

        Livewire::test('dynamic-search')
            ->set('search', 'novembre')
            ->assertSee('Concours Nationaux')
            ->assertDontSee('Frais de Scolarité');
    }

    public function test_can_search_pdf_documents_and_display_parent_section(): void
    {
        $doc = Document::create([
            'title' => 'Accord Collectif sur le Télétravail',
            'slug' => 'accord-teletravail',
            'department' => 'Ressources Humaines',
            'file_path' => 'documents/sample.pdf',
            'original_filename' => 'sample.pdf',
            'page_count' => 3,
            'is_published' => true,
            'raw_text' => 'Texte complet du document...',
        ]);

        DocumentSection::create([
            'document_id' => $doc->id,
            'page_number' => 2,
            'section_title' => 'Chapitre II : Ergonomie du poste',
            'content' => 'Conformément aux règles de l\'Article 4, l\'employeur prend en charge le matériel.',
            'order' => 1,
        ]);

        Livewire::test('dynamic-search')
            ->set('search', 'Article')
            ->assertSee('Accord Collectif sur le Télétravail')
            ->assertSee('Chapitre II : Ergonomie du poste')
            ->assertSee('Page 2')
            ->assertSeeHtml('<mark');
    }

    public function test_search_query_is_recorded_in_history(): void
    {
        Livewire::test('dynamic-search')
            ->set('search', 'Télétravail')
            ->assertSet('searchHistory', ['Télétravail']);

        $this->assertEquals(['Télétravail'], session('search_history'));
    }

    public function test_can_apply_search_from_history(): void
    {
        Topic::factory()->create(['title' => 'Guide de Télétravail et Matériel']);
        Topic::factory()->create(['title' => 'Politique des Congés Annuels']);

        session(['search_history' => ['Télétravail']]);

        Livewire::test('dynamic-search')
            ->assertSee('Télétravail')
            ->call('applyHistory', 'Télétravail')
            ->assertSet('search', 'Télétravail')
            ->assertSeeHtml('Guide de <mark')
            ->assertSee('Matériel')
            ->assertDontSee('Politique des Congés Annuels');
    }

    public function test_can_remove_item_from_search_history(): void
    {
        session(['search_history' => ['Télétravail', 'Mutuelle']]);

        Livewire::test('dynamic-search')
            ->call('removeFromHistory', 'Télétravail')
            ->assertSet('searchHistory', ['Mutuelle']);

        $this->assertEquals(['Mutuelle'], session('search_history'));
    }

    public function test_can_clear_all_search_history(): void
    {
        session(['search_history' => ['Télétravail', 'Mutuelle', 'Congés']]);

        Livewire::test('dynamic-search')
            ->call('clearHistory')
            ->assertSet('searchHistory', []);

        $this->assertEquals([], session('search_history'));
    }

    public function test_default_view_displays_only_document_headers_without_subsections(): void
    {
        $doc = Document::create([
            'title' => 'Politique Sécurité Informatique 2026',
            'slug' => 'politique-securite-2026',
            'reference_code' => 'CS-DSI-2026-999',
            'language' => 'fr',
            'file_path' => 'documents/sec.pdf',
            'original_filename' => 'sec.pdf',
            'page_count' => 5,
            'is_published' => true,
        ]);

        DocumentSection::create([
            'document_id' => $doc->id,
            'page_number' => 1,
            'section_title' => 'Article 12 : Mots de passe et Authentification',
            'content' => 'Les mots de passe doivent comporter au moins 14 caractères alphanumériques.',
            'reference_label' => 'CS-DSI-2026-999 § P.1 — Article 12',
            'order' => 1,
        ]);

        // Default state (no search): Header is visible, but subsection content is not rendered
        Livewire::test('dynamic-search')
            ->assertSee('Politique Sécurité Informatique 2026')
            ->assertSee('CS-DSI-2026-999')
            ->assertDontSee('Les mots de passe doivent comporter au moins 14 caractères alphanumériques.');

        // When searching, matching sub-sections become visible
        Livewire::test('dynamic-search')
            ->set('search', 'alphanumériques')
            ->assertSee('CS-DSI-2026-999')
            ->assertSee('Article 12 : Mots de passe et Authentification')
            ->assertSee('14 caractères');
    }

    public function test_language_selector_filters_documents_strictly_by_chosen_language(): void
    {
        Document::create([
            'title' => 'Document Français Spécifique',
            'slug' => 'doc-fr',
            'reference_code' => 'CS-FR-001',
            'language' => 'fr',
            'file_path' => 'documents/fr.pdf',
            'original_filename' => 'fr.pdf',
            'page_count' => 1,
            'is_published' => true,
        ]);

        Document::create([
            'title' => 'Specific English Document',
            'slug' => 'doc-en',
            'reference_code' => 'CS-EN-002',
            'language' => 'en',
            'file_path' => 'documents/en.pdf',
            'original_filename' => 'en.pdf',
            'page_count' => 1,
            'is_published' => true,
        ]);

        // When language is set to 'fr', only French documents appear
        Livewire::test('dynamic-search')
            ->call('switchLanguageAndLocale', 'fr')
            ->assertSee('Document Français Spécifique')
            ->assertDontSee('Specific English Document');

        // When language is switched to 'en', only English documents appear
        Livewire::test('dynamic-search')
            ->call('switchLanguageAndLocale', 'en')
            ->assertSee('Specific English Document')
            ->assertDontSee('Document Français Spécifique');
    }
}
