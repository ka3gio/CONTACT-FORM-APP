<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationTest extends TestCase
{

    use RefreshDatabase;

    # テストデータの作成
    public function validate(array $data)
    {
        $request = new StoreContactRequest();
        return Validator::make($data, $request->rules());
    }

    # 全ての必須項目とタグ入力を受け付けること
    public function test_valid_data_is_accepted(): void
    {
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

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    # 無効な電話番号は拒否されること
    public function test_invalid_phone_number_is_rejected(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $data = [
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '000aaaaaaa', // 無効な電話番号
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tags' => $tags->pluck('id')->toArray(),
        ];

        $validator = $this->validate($data);

        $this->assertFalse($validator->passes());
    }
}
