<?php

namespace Tests\Feature;

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
}
