<?php

namespace Tests\Unit;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationTest extends TestCase
{

    use RefreshDatabase;

    # テストデータの作成
    public function store_contact_validate(array $data)
    {
        $request = new StoreContactRequest();
        return Validator::make($data, $request->rules());
    }

    public function index_contact_validate(array $data)
    {
        $request = new IndexContactRequest();
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

        $validator = $this->store_contact_validate($data);

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

        $validator = $this->store_contact_validate($data);

        $this->assertFalse($validator->passes());
    }

    # 正しいフィルタ条件を受け付けること
    public function test_valid_filter_conditions_are_accepted(): void
    {
        $category = Category::factory()->create();

        // 正しい条件
        $validData = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ];

        $validator = $this->index_contact_validate($validData);

        $this->assertTrue($validator->passes());
    }

    # 不正な性別を拒否すること
    public function test_invalid_gender_is_rejected(): void
    {
        $category = Category::factory()->create();

        // 無効な性別
        $invalidData = [
            'keyword' => '山田',
            'gender' => 'invalid', // 無効な性別
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ];

        $validator = $this->index_contact_validate($invalidData);

        $this->assertFalse($validator->passes());
    }

    # 存在しないカテゴリIDを拒否すること
    public function test_invalid_category_id_is_rejected(): void
    {
        $invalidData = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => 999, // 無効なカテゴリID
            'date' => '2026-06-23',
        ];

        $validator = $this->index_contact_validate($invalidData);

        $this->assertFalse($validator->passes());
    }
}
