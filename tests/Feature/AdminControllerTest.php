<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Contact;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $this->actingAs($user);
    }

    # GET /admin でキーワード・性別・カテゴリ・日付フィルタが機能し、結果が7件ごとにページネーションされる
    public function test_admin_contact_index_filters_contacts_and_paginates_by_7_items(): void
    {
        // Arrange
        $user = User::factory()->create();

        $targetCategory = Category::factory()->create([
            'content' => '商品について',
        ]);

        $otherCategory = Category::factory()->create([
            'content' => 'その他',
        ]);

        $targetDate = '2026-06-23';

        for ($i = 1; $i <= 8; $i++) {
            Contact::factory()->create([
                'last_name' => '山田',
                'first_name' => '太郎',
                'email' => "matched{$i}@example.com",
                'gender' => 1,
                'category_id' => $targetCategory->id,
                'created_at' => $targetDate . ' 10:00:00',
            ]);
        }

        Contact::factory()->create([
            'last_name' => '佐藤',
            'first_name' => '太郎',
            'email' => 'unmatched-keyword@example.com',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'unmatched-gender@example.com',
            'gender' => 2,
            'category_id' => $targetCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'unmatched-category@example.com',
            'gender' => 1,
            'category_id' => $otherCategory->id,
            'created_at' => $targetDate . ' 10:00:00',
        ]);

        Contact::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
            'email' => 'unmatched-date@example.com',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'created_at' => '2026-06-22 10:00:00',
        ]);

        // Act
        $response = $this->get('/admin?' . http_build_query([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $targetCategory->id,
            'date' => $targetDate,
        ]));

        // Assert
        $response->assertOk();

        $response->assertSee('matched1@example.com');
        $response->assertSee('matched7@example.com');
        $response->assertDontSee('matched8@example.com');

        $response->assertDontSee('unmatched-keyword@example.com');
        $response->assertDontSee('unmatched-gender@example.com');
        $response->assertDontSee('unmatched-category@example.com');
        $response->assertDontSee('unmatched-date@example.com');
    }

    # GET /admin/contacts/{contact} で指定したお問い合わせがカテゴリ情報付きで詳細ページに表示される
    public function test_admin_can_view_contact_detail_with_category(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $contact = Contact::factory()->create(['category_id' => $category->id]);

        // Act
        $response = $this->get('/admin/contacts/' . $contact->id);

        // Assert
        $response->assertOk();
        $response->assertSee($category->name);
    }

    # DELETE /admin/contacts/{contact} でレコードが正常に削除され、/admin にリダイレクトされる
    public function test_contact_is_deleted_and_is_redirects_to_admin_page(): void
    {
        // Arange
        $contact = Contact::factory()->create();

        // Act
        $response = $this->delete('/admin/contacts/' . $contact->id);

        //Assert
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
        $this->assertDatabaseMissing('contact_tag', ['contact_id' => $contact->id]);

        $response->assertRedirect(route('admin'));
    }
}
