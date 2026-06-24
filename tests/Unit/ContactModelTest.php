<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactModelTest extends TestCase
{
    use RefreshDatabase;

    // コンタクトのレコードを作成できること
    public function test_can_create_contact(): void
    {
        $contact = Contact::factory()->create();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
        ]);
    }

    // ユーザーのレコードを作成できること
    public function test_can_create_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    // 1つのカテゴリから、紐づく複数のお問い合わせが正しく取得できる
    public function test_category_has_many_contacts(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->count(3)->create(['category_id' => $category->id]);

        $this->assertCount(3, $category->contacts);
    }

    // お問い合わせから、紐づくカテゴリが正しく取得できる
    public function test_contact_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $contact = Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'category_id' => $category->id,
        ]);

        $this->assertEquals($category->id, $contact->category->id);
    }

    // お問い合わせから、紐づく複数のタグが正しく同期できる
    public function test_contact_can_sync_tags(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $contact->tags()->sync($tags->pluck('id')->toArray());

        $this->assertCount(3, $contact->tags);
    }

    // タグから、紐づく複数のお問い合わせが正しく取得できる
    public function test_tag_belongs_to_many_contacts(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(3)->create();

        $tag->contacts()->attach($contacts->pluck('id')->toArray());

        $this->assertCount(3, $tag->contacts);
        $this->assertEquals($contacts->pluck('id')->toArray(), $tag->contacts->pluck('id')->toArray());
    }
}
