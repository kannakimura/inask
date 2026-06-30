<?php

namespace Tests\Feature;

use App\DTOs\AnswerResult;
use App\DTOs\SearchResult;
use App\Models\User;
use App\Services\AnswerGeneratorService;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    // 未認証ユーザーは検索フォームにアクセスできない
    public function test_unauthenticated_user_cannot_access_search(): void
    {
        $this->withoutVite();
        $response = $this->get(route('search.index'));

        $response->assertRedirect(route('login'));
    }

    // 認証済みユーザーは検索フォームを表示できる
    public function test_authenticated_user_can_view_search_form(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.index'));

        $response->assertStatus(200);
        $response->assertSee('社内ドキュメント検索');
    }

    // 正常なクエリを送信すると回答と出典が表示される
    public function test_search_returns_answer_and_sources(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $source = new SearchResult(
            chunkId:       1,
            documentId:    1,
            documentTitle: '就業規則.pdf',
            content:       '有給休暇は年10日付与されます。',
            distance:      0.1,
        );
        $answerResult = new AnswerResult(
            answer:  '有給休暇は年10日付与されます。',
            sources: [$source],
        );

        // サービス層をモックしてDB・API依存を排除する
        $this->mock(SearchService::class)
            ->shouldReceive('search')
            ->with('有給休暇について教えて')
            ->andReturn([$source]);

        $this->mock(AnswerGeneratorService::class)
            ->shouldReceive('generate')
            ->with('有給休暇について教えて', [$source])
            ->andReturn($answerResult);

        $response = $this->actingAs($user)->post(route('search.query'), [
            'query' => '有給休暇について教えて',
        ]);

        $response->assertStatus(200);
        // 回答本文が表示されることを確認する
        $response->assertSee('有給休暇は年10日付与されます。');
        // 出典ドキュメント名が表示されることを確認する
        $response->assertSee('就業規則.pdf');
        // 入力クエリがフォームに残っていることを確認する
        $response->assertSee('有給休暇について教えて');
    }

    // 検索結果が0件の場合はエラーメッセージを表示する
    public function test_search_shows_error_when_no_sources_found(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        // SearchServiceは空配列を返し、AnswerGeneratorServiceはInvalidArgumentExceptionを投げる
        $this->mock(SearchService::class)
            ->shouldReceive('search')
            ->andReturn([]);

        $this->mock(AnswerGeneratorService::class)
            ->shouldReceive('generate')
            ->andThrow(new \InvalidArgumentException(config('errors.answer_generator.no_sources')));

        $response = $this->actingAs($user)->post(route('search.query'), [
            'query' => '存在しないトピック',
        ]);

        $response->assertStatus(200);
        $response->assertSee(config('errors.answer_generator.no_sources'));
    }

    // queryが空の場合はバリデーションエラーになる
    public function test_search_validates_empty_query(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('search.query'), [
            'query' => '',
        ]);

        $response->assertSessionHasErrors('query');
    }

    // queryが200文字を超えた場合はバリデーションエラーになる
    public function test_search_validates_query_max_length(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('search.query'), [
            'query' => str_repeat('あ', 201),
        ]);

        $response->assertSessionHasErrors('query');
    }

    // 未認証ユーザーはPOST /searchにアクセスできない
    public function test_unauthenticated_user_cannot_post_search(): void
    {
        $response = $this->post(route('search.query'), ['query' => 'テスト']);

        $response->assertRedirect(route('login'));
    }
}
