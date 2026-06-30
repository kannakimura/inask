<?php

namespace App\Services;

class ChunkSplitterService
{
    private int $chunkSize;
    private int $overlap;

    public function __construct()
    {
        // config/inask.phpの設定値を使用する
        $this->chunkSize = (int) config('inask.chunk.size', 500);
        $this->overlap   = (int) config('inask.chunk.overlap', 50);
    }

    // テキストをチャンクに分割して配列で返す
    // オーバーラップを設けることで文脈の途切れを防ぐ
    public function split(string $text): array
    {
        // 空白の正規化（改行・タブ・連続スペースをスペース1つに）
        $normalized = preg_replace('/\s+/', ' ', trim($text));

        if ($normalized === '' || $normalized === null) {
            return [];
        }

        $chunks = [];
        $length = mb_strlen($normalized);
        $start  = 0;

        while ($start < $length) {
            $chunk    = mb_substr($normalized, $start, $this->chunkSize);
            $chunks[] = $chunk;

            // 次の開始位置をchunkSize - overlapだけ進める
            $step  = $this->chunkSize - $this->overlap;
            $start += $step > 0 ? $step : 1;

            // 残りがoverlap以下の場合は重複チャンクになるため終了する
            if ($length - $start <= $this->overlap) {
                break;
            }
        }

        return $chunks;
    }
}
