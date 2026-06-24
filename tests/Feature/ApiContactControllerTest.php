<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validContactData(Category $category, array $tagIds = []): array
    {
        return [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tagIds,
        ];
    }

    // GET /api/v1/contacts でJSON形式のお問い合わせ一覧を取得できる
    public function test_api_can_get_contact_list_as_json(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(3)->create([
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'category',
                        'first_name',
                        'last_name',
                        'gender',
                        'email',
                        'tel',
                        'address',
                        'building',
                        'detail',
                        'tags',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    // GET /api/v1/contacts で検索とページネーションが機能する
    public function test_api_contact_list_can_be_filtered_and_paginated(): void
    {
        $targetCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            Contact::factory()->create([
                'last_name' => '山田',
                'gender' => 1,
                'email' => "matched{$i}@example.com",
                'category_id' => $targetCategory->id,
                'created_at' => '2026-06-24 10:00:00',
            ]);
        }

        Contact::factory()->create([
            'last_name' => '山田',
            'gender' => 2,
            'email' => 'unmatched-gender@example.com',
            'category_id' => $targetCategory->id,
            'created_at' => '2026-06-24 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'unmatched-category@example.com',
            'category_id' => $otherCategory->id,
            'created_at' => '2026-06-24 10:00:00',
        ]);

        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'date' => '2026-06-24',
            'page' => 2,
            'per_page' => 2,
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonMissing([
                'email' => 'unmatched-gender@example.com',
            ])
            ->assertJsonMissing([
                'email' => 'unmatched-category@example.com',
            ]);
    }

    // GET /api/v1/contacts で不正な検索条件を指定すると422が返る
    public function test_api_contact_list_returns_422_for_invalid_filters(): void
    {
        $response = $this->getJson('/api/v1/contacts?'.http_build_query([
            'gender' => 4,
            'category_id' => 999,
            'page' => 0,
            'per_page' => 101,
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'gender',
                'category_id',
                'page',
                'per_page',
            ])
            ->assertJsonPath('errors.gender.0', '性別の値が不正です')
            ->assertJsonPath(
                'errors.category_id.0',
                '選択されたカテゴリーが存在しません'
            );
    }

    // GET /api/v1/contacts/{id} でJSON形式のお問い合わせ詳細を取得できる
    public function test_api_can_get_contact_detail_as_json(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);
        $contact->tags()->attach($tags->pluck('id')->toArray());

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonCount(2, 'data.tags');
    }

    // GET /api/v1/contacts/{id} で存在しないIDを指定すると404のJSONが返る
    public function test_api_contact_detail_returns_404_for_missing_contact(): void
    {
        $response = $this->getJson('/api/v1/contacts/999');

        $response->assertNotFound()
            ->assertJsonPath('error', 'お問い合わせが見つかりませんでした。');
    }

    // POST /api/v1/contacts でお問い合わせを作成して201が返る
    public function test_api_can_create_contact(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $data = $this->validContactData(
            $category,
            $tags->pluck('id')->toArray()
        );

        $response = $this->postJson('/api/v1/contacts', $data);

        $response->assertCreated()
            ->assertJsonPath('data.email', $data['email']);

        $contact = Contact::where('email', $data['email'])->firstOrFail();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => $data['email'],
        ]);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    // POST /api/v1/contacts で不正な入力値を指定すると422が返る
    public function test_api_contact_create_returns_422_for_invalid_data(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'category_id',
                'detail',
            ]);
    }

    // POST /api/v1/contacts で不正値を指定すると設計指定の日本語エラーが返る
    public function test_api_contact_create_returns_required_japanese_messages(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 4,
            'email' => 'test@example.com',
            'tel' => 'invalid-tel',
            'address' => '東京都',
            'category_id' => 999,
            'detail' => str_repeat('あ', 121),
            'tag_ids' => [999],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.gender.0', '性別の値が不正です')
            ->assertJsonPath(
                'errors.tel.0',
                '電話番号はハイフンなしの10〜11桁で入力してください'
            )
            ->assertJsonPath(
                'errors.category_id.0',
                '選択されたカテゴリーが存在しません'
            )
            ->assertJsonPath(
                'errors.detail.0',
                'お問い合わせ内容は120文字以内で入力してください'
            );

        $this->assertSame(
            '選択されたタグが存在しません',
            $response->json('errors')['tag_ids.0'][0]
        );
    }

    // PUT /api/v1/contacts/{id} でお問い合わせを更新して200が返る
    public function test_api_can_update_contact(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);
        $data = $this->validContactData(
            $category,
            $tags->pluck('id')->toArray()
        );
        $data['email'] = 'updated@example.com';

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.id', $contact->id)
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'updated@example.com',
        ]);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    // PUT /api/v1/contacts/{id} で存在しないIDを指定すると404が返る
    public function test_api_contact_update_returns_404_for_missing_contact(): void
    {
        $category = Category::factory()->create();

        $response = $this->putJson(
            '/api/v1/contacts/999',
            $this->validContactData($category)
        );

        $response->assertNotFound()
            ->assertJsonPath('error', 'お問い合わせが見つかりませんでした。');
    }

    // PUT /api/v1/contacts/{id} で不正な入力値を指定すると422が返る
    public function test_api_contact_update_returns_422_for_invalid_data(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'gender',
                'email',
                'tel',
                'address',
                'category_id',
                'detail',
            ]);
    }

    // DELETE /api/v1/contacts/{id} でお問い合わせを削除して204が返る
    public function test_api_can_delete_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    // DELETE /api/v1/contacts/{id} で存在しないIDを指定すると404が返る
    public function test_api_contact_delete_returns_404_for_missing_contact(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/999');

        $response->assertNotFound()
            ->assertJsonPath('error', 'お問い合わせが見つかりませんでした。');
    }
}
