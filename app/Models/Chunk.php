<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chunk extends Model
{
    // 一括代入を許可するカラム
    protected $fillable = [
        'document_id',
        'document_title',
        'content',
        'chunk_index',
        'embedding',
    ];

    // このチャンクが属するドキュメント
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
