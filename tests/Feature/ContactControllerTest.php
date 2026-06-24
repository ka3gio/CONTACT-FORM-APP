<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;
use App\Models\User;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    # POST /contacts/confirm でバリデーション通過時にお問い合わせフォーム確認ページが表示され、入力内容が画面に表示されること
    public function test_contact_confirm_page_is_displayed_on_valid_input(): void
    {

        // Arrange
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $data = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        // Act
        $response = $this->post('/contacts/confirm', $data);

        // Assert
        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee($data['last_name']);
        $response->assertSee($data['first_name']);
        $response->assertSee('男性');
        $response->assertSee($data['email']);
        $response->assertSee($data['tel']);
        $response->assertSee($data['address']);
        $response->assertSee($category->name);
        $response->assertSee($data['detail']);
    }

    # POST /contacts/confirm でバリデーションエラー時にお問い合わせフォームページにリダイレクトされ、エラーメッセージが表示されること
    public function test_contact_confirm_page_redirects_back_on_invalid_input(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $data = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => 'invalid-tel', // 無効な電話番号
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        // Act
        $response = $this->post('/contacts/confirm', $data);

        // Assert
        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['tel']);
    }

    # POST /contacts でバリデーション通過時にお問い合わせが保存され、サンクスページにリダイレクトされること
    public function test_contact_is_saved_and_redirects_to_thanks_on_valid_input(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $data = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        // Act
        $response = $this->post('/contacts', $data);

        // Assert
        $this->assertDatabaseHas('contacts', [
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'gender' => $data['gender'],
            'email' => $data['email'],
            'tel' => $data['tel'],
            'address' => $data['address'],
            'category_id' => $category->id,
            'detail' => $data['detail'],
        ]);

        $contact = Contact::where('email', $data['email'])->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }

        $response->assertRedirect('/thanks');
    }

    # POST /contacts でバリデーションエラー時にお問い合わせフォームページにリダイレクトされ、エラーメッセージが表示されること
    public function test_contact_redirects_back_on_invalid_input(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $data = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => 'invalid-tel', // 無効な電話番号
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        // Act
        $response = $this->post('/contacts', $data);

        // Assert
        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['tel']);

        $this->assertDatabaseMissing('contacts', [
            'detail' => $data['detail'],
        ]);
    }

    # ログイン済み管理者がフィルタ条件付きでCSVをDLできる
    public function test_authenticated_user_can_download_csv_with_filter_conditions(): void
    {
        // Arrange
        $user = User::factory()->create();

        $targetCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $otherCategory = Category::factory()->create([
            'content' => 'その他',
        ]);

        $targetDate = '2026-06-24';

        $matchedContact = Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'matched@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '佐藤',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'unmatched-keyword@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 2,
            'email' => 'unmatched-gender@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'unmatched-category@example.com',
            'category_id' => $otherCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'unmatched-date@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => '2026-06-23 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/contacts/export?' . http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'date' => $targetDate,
        ]));

        // Assert
        $response->assertOk();

        $csv = $this->csvContent($response);

        $this->assertStringContainsString('matched@example.com', $csv);

        $this->assertStringNotContainsString('unmatched-keyword@example.com', $csv);
        $this->assertStringNotContainsString('unmatched-gender@example.com', $csv);
        $this->assertStringNotContainsString('unmatched-category@example.com', $csv);
        $this->assertStringNotContainsString('unmatched-date@example.com', $csv);
    }

    # CSVをDLにおいて条件無指定時は新着順で全件出力される
    public function test_authenticated_user_can_download_csv_without_conditions_ordered_by_latest(): void
    {
        // Arrange
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Contact::factory()->create([
            'last_name' => '古い',
            'first_name' => '問い合わせ',
            'email' => 'old@example.com',
            'gender' => 1,
            'category_id' => $category->id,
            'created_at' => '2026-06-22 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '中間',
            'first_name' => '問い合わせ',
            'email' => 'middle@example.com',
            'gender' => 1,
            'category_id' => $category->id,
            'created_at' => '2026-06-23 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '新しい',
            'first_name' => '問い合わせ',
            'email' => 'new@example.com',
            'gender' => 1,
            'category_id' => $category->id,
            'created_at' => '2026-06-24 10:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/contacts/export');

        // Assert
        $response->assertOk();

        $csv = $this->csvContent($response);

        $this->assertStringContainsString('new@example.com', $csv);
        $this->assertStringContainsString('middle@example.com', $csv);
        $this->assertStringContainsString('old@example.com', $csv);

        $newPosition = strpos($csv, 'new@example.com');
        $middlePosition = strpos($csv, 'middle@example.com');
        $oldPosition = strpos($csv, 'old@example.com');

        $this->assertTrue($newPosition < $middlePosition);
        $this->assertTrue($middlePosition < $oldPosition);
    }

    private function csvContent($response): string
    {
        $content = method_exists($response, 'streamedContent')
            ? $response->streamedContent()
            : $response->getContent();

        return mb_convert_encoding(
            $content,
            'UTF-8',
            'UTF-8, SJIS-win, SJIS, EUC-JP, ASCII'
        );
    }
}