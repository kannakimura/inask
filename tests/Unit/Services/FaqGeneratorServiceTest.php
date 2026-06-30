<?php

namespace Tests\Unit\Services;

use App\Clients\ClaudeClient;
use App\Models\Document;
use App\Models\Faq;
use App\Services\FaqGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FaqGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    // generateAndSave()がFAQをDBに保存する
    public function test_generate_and_save_creates_faqs(): void
    {
        $document = Document::factory()->create();
        $chunks   = ['チャンク1のテキストです。', 'チャンク2のテキストです。'];

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->expects($this->once())
            ->method('generate')
            ->willReturn('[{"question":"Qその1?","answer":"Aその1。"},{"question":"Qその2?","answer":"Aその2。"}]');

        $service = new FaqGeneratorService($claudeClient);
        $service->generateAndSave($document, $chunks);

        $this->assertDatabaseCount('faqs', 2);
        $this->assertDatabaseHas('faqs', [
            'document_id' => $document->id,
            'question'    => 'Qその1?',
            'answer'      => 'Aその1。',
        ]);
        $this->assertDatabaseHas('faqs', [
            'document_id' => $document->id,
            'question'    => 'Qその2?',
            'answer'      => 'Aその2。',
        ]);
    }

    // 空チャンク配列を渡した場合は既存FAQを削除せず例外を投げる
    public function test_generate_and_save_throws_on_empty_chunks(): void
    {
        $document = Document::factory()->create();

        // 事前にFAQを作成しておく（既存データが消えないことを確認するため）
        Faq::factory()->create(['document_id' => $document->id]);

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->expects($this->never())->method('generate');

        $service = new FaqGeneratorService($claudeClient);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(config('errors.faq_generator.empty_chunks'));

        $service->generateAndSave($document, []);

        // 既存FAQが削除されていないことを確認する
        $this->assertDatabaseCount('faqs', 1);
    }

    // Claude APIが不正なJSONを返した場合は例外を投げる
    public function test_generate_and_save_throws_on_invalid_json(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')->willReturn('これはJSONではありません');

        $service = new FaqGeneratorService($claudeClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.faq_generator.invalid_json'));

        $service->generateAndSave($document, ['テスト']);
    }

    // レスポンスにquestionフィールドが欠けている場合は例外を投げる
    public function test_generate_and_save_throws_on_missing_question_field(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        // questionキーが欠けた不正レスポンスをモックする
        $claudeClient->method('generate')->willReturn('[{"answer":"回答のみ"}]');

        $service = new FaqGeneratorService($claudeClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.faq_generator.invalid_format'));

        $service->generateAndSave($document, ['テスト']);
    }

    // レスポンスにanswerフィールドが欠けている場合は例外を投げる
    public function test_generate_and_save_throws_on_missing_answer_field(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        // answerキーが欠けた不正レスポンスをモックする
        $claudeClient->method('generate')->willReturn('[{"question":"質問のみ?"}]');

        $service = new FaqGeneratorService($claudeClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.faq_generator.invalid_format'));

        $service->generateAndSave($document, ['テスト']);
    }

    // questionが空文字の場合は不正フォーマットとして例外を投げる
    public function test_generate_and_save_throws_on_empty_question(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')->willReturn('[{"question":"","answer":"回答"}]');

        $service = new FaqGeneratorService($claudeClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(config('errors.faq_generator.invalid_format'));

        $service->generateAndSave($document, ['テスト']);
    }

    // 再生成時は既存FAQを削除してから新しいFAQを保存する
    public function test_generate_and_save_replaces_existing_faqs(): void
    {
        $document = Document::factory()->create();

        // 事前に古いFAQを2件作成しておく
        Faq::factory()->count(2)->create(['document_id' => $document->id]);

        $claudeClient = $this->createMock(ClaudeClient::class);
        // 新しいFAQを3件返すレスポンスをモックする
        $claudeClient->method('generate')->willReturn(
            '[{"question":"新Q1?","answer":"新A1。"},{"question":"新Q2?","answer":"新A2。"},{"question":"新Q3?","answer":"新A3。"}]'
        );

        $service = new FaqGeneratorService($claudeClient);
        $service->generateAndSave($document, ['テスト']);

        // 古いFAQが削除されて新しい3件だけ存在することを確認する
        $this->assertDatabaseCount('faqs', 3);
        $this->assertDatabaseHas('faqs', ['document_id' => $document->id, 'question' => '新Q1?']);
    }

    // Claude APIがコードブロック（```json）で囲んだレスポンスを返した場合もパースできる
    public function test_generate_and_save_parses_response_wrapped_in_code_block(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        // コードブロックで囲まれたJSONをモックする
        $claudeClient->method('generate')->willReturn(
            "```json\n[{\"question\":\"Q?\",\"answer\":\"A。\"}]\n```"
        );

        $service = new FaqGeneratorService($claudeClient);
        $service->generateAndSave($document, ['テスト']);

        $this->assertDatabaseCount('faqs', 1);
        $this->assertDatabaseHas('faqs', [
            'document_id' => $document->id,
            'question'    => 'Q?',
            'answer'      => 'A。',
        ]);
    }

    // generateAndSave()完了後にログが出力される
    public function test_generate_and_save_logs_on_success(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')->willReturn('[{"question":"Q?","answer":"A。"}]');

        Log::spy();

        $service = new FaqGeneratorService($claudeClient);
        $service->generateAndSave($document, ['テスト']);

        Log::shouldHaveReceived('info')
            ->with('FAQの生成と保存が完了しました', \Mockery::any())
            ->once();
    }

    // ClaudeClientが例外を投げた場合はDBに保存されない
    public function test_generate_and_save_aborts_on_claude_error(): void
    {
        $document = Document::factory()->create();

        $claudeClient = $this->createMock(ClaudeClient::class);
        $claudeClient->method('generate')
            ->willThrowException(new \RuntimeException('Claude API error'));

        $service = new FaqGeneratorService($claudeClient);

        try {
            $service->generateAndSave($document, ['テスト']);
            $this->fail('例外が発生しませんでした');
        } catch (\RuntimeException $e) {
            $this->assertSame('Claude API error', $e->getMessage());
        }

        // APIエラーでトランザクションに入る前に失敗するためFAQは保存されない
        $this->assertDatabaseCount('faqs', 0);
    }
}
