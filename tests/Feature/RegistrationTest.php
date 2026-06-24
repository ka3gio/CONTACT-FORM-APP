<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    // 登録画面を表示できる
    public function test_registration_page_can_be_displayed(): void
    {
        // Act
        $response = $this->get(route('register'));

        // Assert
        $response->assertStatus(200);
    }

    // 新規ユーザーを登録できる
    public function test_new_user_can_register(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertRedirect(route('admin'));
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
        $this->assertAuthenticated();
    }

    // 名前が空だとバリデーションエラーになる
    public function test_name_is_required(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスが空だとバリデーションエラーになる
    public function test_email_is_required(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // 無効なメールアドレス形式だとバリデーションエラーになる
    public function test_email_must_be_valid_format(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスはメール形式で入力してください',
        ]);
    }

    // 既に登録済みのメールアドレスだとバリデーションエラーになる
    public function test_email_must_be_unique(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
    }

    // パスワードが空だとバリデーションエラーになる
    public function test_password_is_required(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // パスワードが8文字未満だとバリデーションエラーになる
    public function test_password_must_be_at_least_8_characters(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    // パスワード確認が一致しないとバリデーションエラーになる
    public function test_password_confirmation_must_match(): void
    {
        // Act
        $response = $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }
}
