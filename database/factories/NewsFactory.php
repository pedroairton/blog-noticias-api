<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\News>
 */
class NewsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6);
        return [
            'title' => $title,
            'subtitle' => $this->faker->sentence(10),
            'slug' => \Illuminate\Support\Str::slug($title),
            'excerpt' => $this->faker->paragraph(2),
            'content' => $this->faker->paragraphs(10, true),
            'main_image' => 'news/' . $this->faker->image('public/storage/news', 800, 600, null, false),
            'main_image_caption' => $this->faker->sentence(6),
            'main_image_alt' => $this->faker->words(3,true),
            'is_published' => $this->faker->boolean(80),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'views_count' => $this->faker->numberBetween(0, 1000),
            'category_id' => Category::factory(),
            'author_id' => Admin::factory()
        ];
    }
    public function published(): Factory {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => true,
                'published_at' => now(),
            ];
        });
    }
    public function unpublished(): Factory {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => false,
                'published_at' => null,
            ];
        });
    }
}
