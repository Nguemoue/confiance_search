<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\TopicOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicOption>
 */
class TopicOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'type' => fake()->randomElement(['faq', 'link', 'contact', 'download']),
            'action_url' => fake()->optional()->url(),
            'is_published' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
