<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'title',
        'file_path',
        'mime_type',
        'status',
    ];

    // このドキュメントに紐づくチャンク一覧
    public function chunks()
    {
        return $this->hasMany(Chunk::class);
    }

    // このドキュメントから生成されたFAQ一覧
    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }
}
