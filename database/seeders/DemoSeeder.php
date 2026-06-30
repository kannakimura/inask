<?php

namespace Database\Seeders;

use App\Clients\VoyageClient;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    // デモ用ドキュメントデータ（タイトル・チャンク群・FAQ群）
    private array $documents = [
        [
            'title' => '就業規則.txt',
            'mime_type' => 'text/plain',
            'chunks' => [
                "【第1章 総則】\n当社の就業規則は、労働基準法その他の労働関係法令に基づき、社員が安心して働ける環境を整備することを目的とします。",
                "【勤務時間】\n所定労働時間は1日8時間、週40時間とします。始業は9:00、終業は18:00（休憩1時間）です。フレックスタイム制を採用しており、コアタイムは10:00〜15:00です。",
                "【有給休暇】\n入社6ヶ月経過後に10日の有給休暇を付与します。以降1年ごとに日数が増加し、最大20日となります。有給は申請書をHRに提出、または社内システムから申請できます。",
                "【残業・休日出勤】\n残業は事前に上長の承認が必要です。残業代は法定割増賃金を支払います。休日出勤が発生した場合は振替休日または休日手当を支給します。",
                "【テレワーク制度】\n週3日までテレワークが可能です。在宅勤務時も通常の勤務時間を守り、コアタイム中は連絡が取れる状態を保つ必要があります。",
            ],
            'faqs' => [
                ['question' => '有給休暇は何日もらえますか？', 'answer' => '入社6ヶ月後に10日付与され、最大20日まで増加します。'],
                ['question' => 'フレックスタイムのコアタイムはいつですか？', 'answer' => 'コアタイムは10:00〜15:00です。この時間帯は必ず勤務する必要があります。'],
                ['question' => 'テレワークは週何日できますか？', 'answer' => '週3日までテレワーク勤務が可能です。'],
                ['question' => '残業する場合はどうすればいいですか？', 'answer' => '事前に上長の承認が必要です。残業代は法定割増賃金が支払われます。'],
                ['question' => '有給休暇の申請方法を教えてください。', 'answer' => '申請書をHRに提出するか、社内システムから申請することができます。'],
            ],
        ],
        [
            'title' => '経費精算ガイドライン.txt',
            'mime_type' => 'text/plain',
            'chunks' => [
                "【経費精算の基本方針】\n業務上必要な経費は会社が負担します。経費精算は月末締め、翌月15日払いです。領収書の保存は必須で、紛失した場合は支払明細書での代替が可能です。",
                "【交通費】\n通勤交通費は月額上限5万円まで支給します。出張の際の交通費は事前申請が必要です。新幹線はグリーン車利用不可、飛行機はエコノミークラスが原則です。",
                "【接待交際費】\n取引先との接待は1人あたり1万円を上限とします。事前に部長承認が必要です。アルコールを伴う接待は深夜0時以降の継続不可とします。",
                "【備品・消耗品の購入】\n1万円未満の備品は各自購入後に精算可能です。1万円以上の場合は事前に総務部に申請し、発注を依頼してください。",
                "【精算の流れ】\n①領収書を保管 → ②経費精算システムに入力 → ③上長承認 → ④経理確認 → ⑤翌月15日に指定口座へ振込。不備があった場合はシステム上で差し戻されます。",
            ],
            'faqs' => [
                ['question' => '経費精算はいつ支払われますか？', 'answer' => '月末締め、翌月15日に指定口座へ振り込まれます。'],
                ['question' => '通勤交通費の上限はいくらですか？', 'answer' => '月額5万円までが支給上限です。'],
                ['question' => '領収書を失くしてしまった場合はどうすればいいですか？', 'answer' => '支払明細書での代替が可能です。総務部にご相談ください。'],
                ['question' => '接待費の1人あたりの上限はいくらですか？', 'answer' => '1人あたり1万円が上限で、事前に部長承認が必要です。'],
                ['question' => '備品を購入したいのですが申請は必要ですか？', 'answer' => '1万円未満は購入後精算可能ですが、1万円以上は事前に総務部への申請が必要です。'],
            ],
        ],
        [
            'title' => 'オンボーディングガイド.txt',
            'mime_type' => 'text/plain',
            'chunks' => [
                "【入社初日の流れ】\n9:00に本社受付へお越しください。人事担当者がお迎えします。午前中は書類手続き・PC設定・社内ツールのアカウント発行を行います。午後はオリエンテーションと各部署への顔合わせです。",
                "【使用ツール一覧】\n・Slack：社内コミュニケーション（チャンネルルールは別紙参照）\n・GitHub：ソースコード管理\n・Notion：ドキュメント管理\n・Google Workspace：メール・カレンダー・ドライブ\n・Jira：タスク・プロジェクト管理",
                "【試用期間と評価】\n入社後3ヶ月間は試用期間です。試用期間終了時に上長との1on1評価面談を実施します。評価は「業務理解度」「コミュニケーション」「自発性」の3軸で行われます。",
                "【メンター制度】\n入社後6ヶ月間、先輩社員がメンターとして支援します。週1回の1on1が設定され、業務上の疑問や悩みを相談できます。メンターは原則同部署の3年以上のメンバーが担当します。",
                "【福利厚生】\n健康保険・厚生年金・雇用保険・労災保険に加入します。書籍購入補助（月3,000円まで）、資格取得支援（受験料全額補助）、社員食堂（昼食500円補助）などがあります。",
            ],
            'faqs' => [
                ['question' => '入社初日は何時に行けばいいですか？', 'answer' => '9:00に本社受付へお越しください。人事担当者がお迎えします。'],
                ['question' => '試用期間はどのくらいですか？', 'answer' => '入社後3ヶ月間が試用期間です。終了時に上長との評価面談があります。'],
                ['question' => 'メンター制度はどのようなものですか？', 'answer' => '入社後6ヶ月間、先輩社員（同部署3年以上）がメンターとしてサポートします。週1回の1on1があります。'],
                ['question' => '書籍購入の補助はありますか？', 'answer' => '月3,000円まで書籍購入補助があります。また、資格取得の受験料も全額補助されます。'],
                ['question' => '社内ではどんなツールを使いますか？', 'answer' => 'Slack・GitHub・Notion・Google Workspace・Jiraを使用します。'],
            ],
        ],
    ];

    public function run(): void
    {
        // デモユーザーを取得する（DatabaseSeederで作成済みであること）
        $user = User::where('email', 'demo@innask.local')->firstOrFail();

        $voyageClient = app(VoyageClient::class);

        foreach ($this->documents as $docData) {
            // 既存のデモドキュメントがあればスキップする（冪等）
            if (Document::where('title', $docData['title'])->exists()) {
                $this->command->info("スキップ: {$docData['title']}（既存）");
                continue;
            }

            $this->command->info("投入中: {$docData['title']}");

            // ドキュメントを作成する
            $document = Document::create([
                'title'     => $docData['title'],
                'file_path' => 'demo/' . $docData['title'],
                'mime_type' => $docData['mime_type'],
                'status'    => 'done',
            ]);

            // チャンクのembeddingをVoyage AIで生成する
            $embeddings = $voyageClient->embedBatch($docData['chunks']);

            // チャンクとembeddingをDBに保存する
            foreach ($docData['chunks'] as $index => $content) {
                $chunkId = DB::table('chunks')->insertGetId([
                    'document_id'    => $document->id,
                    'document_title' => $document->title,
                    'content'        => $content,
                    'chunk_index'    => $index,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                // embeddingをvector型としてRAW SQLで更新する
                $vectorLiteral = '[' . implode(',', $embeddings[$index]) . ']';
                DB::statement(
                    'UPDATE chunks SET embedding = ?::vector WHERE id = ?',
                    [$vectorLiteral, $chunkId]
                );
            }

            // FAQを保存する
            foreach ($docData['faqs'] as $faq) {
                DB::table('faqs')->insert([
                    'document_id' => $document->id,
                    'question'    => $faq['question'],
                    'answer'      => $faq['answer'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $chunkCount = count($docData['chunks']);
            $faqCount   = count($docData['faqs']);
            $this->command->info("完了: {$docData['title']} （チャンク{$chunkCount}件・FAQ{$faqCount}件）");
        }
    }
}
