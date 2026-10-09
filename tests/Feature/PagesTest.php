<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_accessible(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('ConfianceSearch');
    }

    public function test_about_page_is_accessible(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertSeeText("La documentation interne de l'entreprise");
    }

    public function test_contact_page_is_accessible(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('Contactez notre équipe');
    }

    public function test_contact_message_can_be_submitted(): void
    {
        $payload = [
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@nvonchi.internal',
            'subject' => 'Question télétravail',
            'message' => 'Bonjour, comment déclarer mes jours de télétravail pour le mois prochain ?',
        ];

        $response = $this->post(route('contact.store'), $payload);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'jean.dupont@nvonchi.internal',
            'subject' => 'Question télétravail',
        ]);
    }
}
