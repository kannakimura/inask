<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    // 一括代入を許可するカラム
    protected $fillable = [
        'document_id',
        'question',
        'answer',
    ];

    // このFAQが属するドキュメント
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
