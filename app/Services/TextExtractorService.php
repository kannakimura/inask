<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class TextExtractorService
{
    // ファイルパスからMIMEタイプに応じてテキストを抽出して返す
    public function extract(string $filePath, string $mimeType): string
    {
        $absolutePath = Storage::disk('local')->path($filePath);

        return match (true) {
            $mimeType === 'application/pdf'              => $this->extractFromPdf($absolutePath),
            in_array($mimeType, ['text/plain', 'text/markdown'], true) => $this->extractFromText($absolutePath),
            default => throw new RuntimeException(config('errors.extract.unsupported_mime') . ": {$mimeType}"),
        };
    }

    // PDFファイルからテキストを抽出する
    private function extractFromPdf(string $absolutePath): string
    {
        $parser = new PdfParser();
        $pdf    = $parser->parseFile($absolutePath);

        return $pdf->getText();
    }

    // テキスト・Markdownファイルの内容をそのまま返す
    private function extractFromText(string $absolutePath): string
    {
        $content = file_get_contents($absolutePath);

        if ($content === false) {
            throw new RuntimeException(config('errors.extract.read_failed') . ": {$absolutePath}");
        }

        return $content;
    }
}
