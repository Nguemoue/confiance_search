<?php

namespace Tests\Feature;

use App\Models\Tag;
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

    public function test_can_filter_by_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Primaire', 'slug' => 'primaire']);

        $topicA = Topic::factory()->create(['title' => 'École Maternelle et Primaire']);
        $topicA->tags()->attach($tag);

        $topicB = Topic::factory()->create(['title' => 'Université Master']);

        Livewire::test('dynamic-search')
            ->set('selectedTag', 'primaire')
            ->assertSee('École Maternelle et Primaire')
            ->assertDontSee('Université Master');
    }
}
