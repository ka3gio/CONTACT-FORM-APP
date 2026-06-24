<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnauthenticatedRedirectTest extends TestCase
{
    use RefreshDatabase;

    // 未認証ユーザーは /login にアクセスするとログインページにリダイレクトされる
    public function test_unauthenticated_user_is_redirected_to_login_page(): void
    {
        // Act
        $response = $this->get(route('admin'));

        // Assert
        $response->assertRedirect(route('login'));
    }
}
