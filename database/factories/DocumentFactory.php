<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title'     => $this->faker->word() . '.pdf',
            'file_path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'status'    => config('inask.document_status.pending'),
        ];
    }
}
