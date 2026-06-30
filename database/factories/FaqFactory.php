<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'question'    => $this->faker->sentence() . '?',
            'answer'      => $this->faker->paragraph(),
        ];
    }
}
