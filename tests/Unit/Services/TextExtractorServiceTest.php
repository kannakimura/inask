<?php

namespace Tests\Unit\Services;

use App\Services\TextExtractorService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TextExtractorServiceTest extends TestCase
{
    private TextExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TextExtractorService();
    }

    // テキストファイルからテキストを抽出できる
    public function test_extract_from_text_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.txt', "こんにちは\n世界");

        $result = $this->service->extract('documents/test.txt', 'text/plain');

        $this->assertStringContainsString('こんにちは', $result);
        $this->assertStringContainsString('世界', $result);
    }

    // Markdownファイルからテキストを抽出できる
    public function test_extract_from_markdown_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.md', "# タイトル\n本文です。");

        $result = $this->service->extract('documents/test.md', 'text/markdown');

        $this->assertStringContainsString('タイトル', $result);
        $this->assertStringContainsString('本文です。', $result);
    }

    // 未対応MIMEタイプは例外を投げる
    public function test_unsupported_mime_type_throws_exception(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('documents/test.jpg', 'dummy');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('未対応のMIMEタイプです');

        $this->service->extract('documents/test.jpg', 'image/jpeg');
    }
}
