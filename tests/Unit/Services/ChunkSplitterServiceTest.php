<?php

namespace Tests\Unit\Services;

use App\Services\ChunkSplitterService;
use Tests\TestCase;

class ChunkSplitterServiceTest extends TestCase
{
    private ChunkSplitterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChunkSplitterService();
    }

    // 空文字を渡すと空配列が返る
    public function test_empty_text_returns_empty_array(): void
    {
        $this->assertSame([], $this->service->split(''));
    }

    // 空白のみのテキストも空配列が返る
    public function test_whitespace_only_returns_empty_array(): void
    {
        $this->assertSame([], $this->service->split('   '));
    }

    // chunkSize以下のテキストは1チャンクになる
    public function test_short_text_returns_single_chunk(): void
    {
        // config('inask.chunk.size') = 500 より短いテキスト
        $text   = 'これは短いテキストです。';
        $chunks = $this->service->split($text);

        $this->assertCount(1, $chunks);
        $this->assertSame('これは短いテキストです。', $chunks[0]);
    }

    // chunkSizeを超えるテキストは複数チャンクに分割される
    public function test_long_text_is_split_into_multiple_chunks(): void
    {
        // chunkSize=500, overlap=50 の設定で600文字のテキストを作る
        $text   = str_repeat('あ', 600);
        $chunks = $this->service->split($text);

        // 600文字 → チャンク1: 0-499, チャンク2: 450-599 の2チャンク
        $this->assertGreaterThan(1, count($chunks));
    }

    // 各チャンクの長さはchunkSize以下である
    public function test_each_chunk_length_does_not_exceed_chunk_size(): void
    {
        $chunkSize = (int) config('inask.chunk.size', 500);
        $text      = str_repeat('あ', 2000);
        $chunks    = $this->service->split($text);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual($chunkSize, mb_strlen($chunk));
        }
    }

    // オーバーラップにより隣接チャンクの末尾と先頭が重複している
    public function test_adjacent_chunks_overlap(): void
    {
        $overlap   = (int) config('inask.chunk.overlap', 50);
        $chunkSize = (int) config('inask.chunk.size', 500);
        // chunkSizeより長いテキストを作る
        $text   = str_repeat('a', $chunkSize + $overlap + 10);
        $chunks = $this->service->split($text);

        $this->assertGreaterThanOrEqual(2, count($chunks));

        // チャンク1の末尾overlap文字 = チャンク2の先頭overlap文字
        $tail  = mb_substr($chunks[0], -$overlap);
        $head  = mb_substr($chunks[1], 0, $overlap);
        $this->assertSame($tail, $head);
    }

    // 連続した空白・改行が正規化される
    public function test_whitespace_is_normalized(): void
    {
        $text   = "行1\n\n行2\t\t行3";
        $chunks = $this->service->split($text);

        $this->assertCount(1, $chunks);
        $this->assertSame('行1 行2 行3', $chunks[0]);
    }
}
