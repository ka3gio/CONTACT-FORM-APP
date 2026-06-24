<?php

namespace Tests\Unit;

use App\Http\Requests\Api\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApiValidationTest extends TestCase
{
    use RefreshDatabase;

    private function index_contact_validate(array $data)
    {
        $request = new IndexContactRequest();

        return Validator::make($data, $request->rules());
    }

    private function store_contact_validate(array $data)
    {
        $request = new StoreContactRequest();

        return Validator::make($data, $request->rules());
    }

    // API検索でキーワード・性別・カテゴリ・日付・ページ・表示件数を受け付ける
    public function test_api_index_validation_accepts_valid_filter_conditions(): void
    {
        $category = Category::factory()->create();

        $validator = $this->index_contact_validate([
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-06-24',
            'page' => 2,
            'per_page' => 10,
        ]);

        $this->assertTrue($validator->passes());
    }

    // API検索で不正なフィルタ条件を拒否する
    public function test_api_index_validation_rejects_invalid_filter_conditions(): void
    {
        $invalidCases = [
            ['keyword' => ['山田'], 'error' => 'keyword'],
            ['gender' => 0, 'error' => 'gender'],
            ['gender' => 4, 'error' => 'gender'],
            ['category_id' => 999, 'error' => 'category_id'],
            ['date' => 'invalid-date', 'error' => 'date'],
            ['page' => 0, 'error' => 'page'],
            ['per_page' => 0, 'error' => 'per_page'],
            ['per_page' => 101, 'error' => 'per_page'],
        ];

        foreach ($invalidCases as $case) {
            $error = $case['error'];
            unset($case['error']);

            $validator = $this->index_contact_validate($case);

            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey($error, $validator->errors()->toArray());
        }
    }

    // API作成で全必須項目とタグ入力を受け付ける
    public function test_api_store_validation_accepts_required_fields_and_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(3)->create();

        $validator = $this->store_contact_validate([
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'building' => 'テストビル101',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]);

        $this->assertTrue($validator->passes());
    }

    // API作成で必須項目の欠落を拒否する
    public function test_api_store_validation_rejects_missing_required_fields(): void
    {
        $category = Category::factory()->create();
        $validData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ];

        foreach (array_keys($validData) as $field) {
            $data = $validData;
            unset($data[$field]);

            $validator = $this->store_contact_validate($data);

            $this->assertTrue($validator->fails());
            $this->assertArrayHasKey($field, $validator->errors()->toArray());
        }
    }

    // API作成で不正な入力値と存在しないタグを拒否する
    public function test_api_store_validation_rejects_invalid_values(): void
    {
        $category = Category::factory()->create();
        $baseData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
        ];
        $invalidCases = [
            ['field' => 'gender', 'value' => 4],
            ['field' => 'email', 'value' => 'invalid-email'],
            ['field' => 'tel', 'value' => 'invalid-tel'],
            ['field' => 'category_id', 'value' => 999],
            ['field' => 'tag_ids', 'value' => [999]],
        ];

        foreach ($invalidCases as $case) {
            $data = array_merge($baseData, [
                $case['field'] => $case['value'],
            ]);

            $validator = $this->store_contact_validate($data);

            $this->assertTrue($validator->fails());
        }
    }
}
