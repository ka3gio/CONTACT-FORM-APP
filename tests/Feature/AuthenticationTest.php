<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ログイン画面を表示できる
    public function test_login_page_can_be_displayed(): void
    {
        // Act
        $response = $this->get(route('login'));

        // Assert
        $response->assertStatus(200);
    }

    // 正しい認証情報でログインできる
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertRedirect(route('admin'));
        $this->assertAuthenticatedAs($user);
    }

    // パスワードが誤っている場合はログインできない
    public function test_user_cannot_login_with_invalid_password(): void
    {
        // Arrange
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest();
    }

    // 存在しないメールアドレスではログインできない
    public function test_user_cannot_login_with_non_existing_email(): void
    {
        // Act
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
        $this->assertGuest();
    }

    // ログイン時にメールアドレスは必須
    public function test_email_is_required_for_login(): void
    {
        // Act
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // ログイン時にパスワードは必須
    public function test_password_is_required_for_login(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // ログアウトできる
    public function test_user_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->post(route('logout'));

        // Assert
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    // 認証済みユーザーがログイン画面にアクセスすると管理画面へリダイレクトされる
    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get(route('login'));

        // Assert
        $response->assertRedirect(route('admin'));
    }
}
