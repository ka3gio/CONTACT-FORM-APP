<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Contact;

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
            'tags' => $tags->pluck('id')->toArray(),
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
}