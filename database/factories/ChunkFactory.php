<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class ChunkFactory extends Factory
{
    public function definition(): array
    {
        $dummyVector = '[' . implode(',', array_fill(0, 1024, 0.1)) . ']';

        return [
            'document_id'    => Document::factory(),
            'document_title' => $this->faker->sentence(),
            'content'        => $this->faker->paragraph(),
            'chunk_index'    => 0,
            'embedding'      => DB::raw("'{$dummyVector}'::vector"),
        ];
    }
}
