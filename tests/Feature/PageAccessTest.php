<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use App\Models\Tag;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    # / にアクセスした際、正常に表示され、カテゴリとタグが表示されること
    public function test_contact_page_is_accessible(): void
    {
        // Arrange
        Category::factory()->create([
            'content' => '商品のお届けについて',
        ]);
        Tag::factory()->create([
            'name' => '質問',
        ]);

        // Act
        $response = $this->get('/');

        // Assert
        $response->assertOk();

        $response->assertViewHas('categories');
        $response->assertViewHas('tags');

        $response->assertSee('商品のお届けについて');
        $response->assertSee('質問');
    }

    public function test_thanks_page_is_accessible(): void
    {
        // Act
        $response = $this->get('/thanks');

        // Assert
        $response->assertOk();
    }

    public function test_admin_page_is_accessible_on_login(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/admin');

        // Assert
        $response->assertOk();
    }

    public function test_admin_page_is_not_accessible_without_login(): void
    {
        // Act
        $response = $this->get('/admin');

        // Assert
        $response->assertRedirect('/login');
    }
}
