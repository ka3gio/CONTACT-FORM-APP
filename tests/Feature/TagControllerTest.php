<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tag;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    // 認証済みユーザーはタグ編集画面を表示できる
    public function test_authenticated_user_can_view_tag_edit_page(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '既存タグ',
        ]);

        $response = $this->actingAs($user)
            ->get("/admin/tags/{$tag->id}/edit");

        $response->assertOk();

        $response->assertViewHas('tag', function ($viewTag) use ($tag) {
            return $viewTag->id === $tag->id;
        });
    }

    // 認証済みユーザーはタグを新規登録して管理画面へリダイレクトされる
    public function test_authenticated_user_can_create_tag_and_redirect_to_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/admin/tags', [
                'name' => '新規タグ',
            ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'name' => '新規タグ',
        ]);
    }

    // 認証済みユーザーはタグを更新して管理画面へリダイレクトされる
    public function test_authenticated_user_can_update_tag_and_redirect_to_admin(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '既存タグ',
        ]);

        $response = $this->actingAs($user)
            ->put("/admin/tags/{$tag->id}", [
                'name' => '更新タグ',
            ]);

        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '更新タグ',
        ]);
    }

    // 認証済みユーザーはタグを削除して管理画面へリダイレクトされる
    public function test_authenticated_user_can_delete_tag_and_redirect_to_admin(): void
    {
        $user = User::factory()->create();

        $tag = Tag::factory()->create([
            'name' => '削除対象タグ',
        ]);

        $response = $this->actingAs($user)
            ->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('contact_tag', [
            'id' => $tag->id,
        ]);
    }

    // 未認証ユーザーはタグ編集画面を表示できない
    public function test_guest_cannot_view_tag_edit_page(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get("/admin/tags/{$tag->id}/edit");

        $response->assertRedirect('/login');
    }

    // 未認証ユーザーはタグを新規登録できない
    public function test_guest_cannot_create_tag(): void
    {
        $response = $this->post('/admin/tags', [
            'name' => '新規タグ',
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseMissing('tags', [
            'name' => '新規タグ',
        ]);
    }

    // 未認証ユーザーはタグを更新できない
    public function test_guest_cannot_update_tag(): void
    {
        $tag = Tag::factory()->create([
            'name' => '既存タグ',
        ]);

        $response = $this->put("/admin/tags/{$tag->id}", [
            'name' => '更新タグ',
        ]);

        $response->assertRedirect('/login');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '既存タグ',
        ]);
    }

    // 未認証ユーザーはタグを削除できない
    public function test_guest_cannot_delete_tag(): void
    {
        $tag = Tag::factory()->create([
            'name' => '削除対象タグ',
        ]);

        $response = $this->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/login');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => '削除対象タグ',
        ]);
    }
}
