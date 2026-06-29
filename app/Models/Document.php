<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'title',
        'file_path',
        'mime_type',
        'status',
    ];

    public function chunks()
    {
        return $this->hasMany(Chunk::class);
    }

    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }
}
