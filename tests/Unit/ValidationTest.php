<?php

namespace Tests\Unit;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\StoretagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    public function store_contact_validate(array $data)
    {
        $request = new StoreContactRequest;

        return Validator::make($data, $request->rules());
    }

    public function index_contact_validate(array $data)
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules());
    }

    public function export_contact_validate(array $data)
    {
        $request = new ExportContactRequest;

        return Validator::make($data, $request->rules());
    }

    private function store_tag_validate(array $data, ?Tag $tag = null)
    {
        $requestClass = $tag ? UpdateTagRequest::class : StoretagRequest::class;
        $request = $requestClass::create(
            $tag ? "/admin/tags/{$tag->id}" : '/admin/tags',
            $tag ? 'PUT' : 'POST',
            $data
        );

        if ($tag) {
            $route = new Route(['PUT'], '/admin/tags/{tag}', []);

            $route->bind($request);
            $route->setParameter('tag', $tag->id);

            $request->setRouteResolver(fn () => $route);
        }

        return Validator::make($data, $request->rules());
    }

    // 全ての必須項目とタグ入力を受け付ける
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
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $validator = $this->store_contact_validate($data);

        $this->assertTrue($validator->passes());
    }

    // 無効な電話番号は拒否される
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
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $validator = $this->store_contact_validate($data);

        $this->assertFalse($validator->passes());
    }

    // お問い合わせ内容が120文字を超える場合は拒否される
    public function test_contact_detail_must_not_exceed_120_characters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->store_contact_validate([
            'last_name' => '山田',
            'first_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都',
            'category_id' => $category->id,
            'detail' => str_repeat('あ', 121),
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('detail', $validator->errors()->toArray());
    }

    // 正しいフィルタ条件を受け付ける
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

    // 不正な性別を拒否する
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

    // 存在しないカテゴリIDを拒否する
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

    // 正しいフィルタ条件を受け付ける
    public function test_valid_filter_conditions_are_accepted_on_export_csv(): void
    {
        $category = Category::factory()->create();

        // 正しい条件
        $validData = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ];

        $validator = $this->export_contact_validate($validData);

        $this->assertTrue($validator->passes());
    }

    // 不正な性別を拒否する
    public function test_invalid_gender_is_rejected_on_export_csv(): void
    {
        $category = Category::factory()->create();

        // 無効な性別
        $invalidData = [
            'keyword' => '山田',
            'gender' => 'invalid', // 無効な性別
            'category_id' => $category->id,
            'date' => '2026-06-23',
        ];

        $validator = $this->export_contact_validate($invalidData);

        $this->assertFalse($validator->passes());
    }

    // 存在しないカテゴリIDを拒否する
    public function test_invalid_category_id_is_rejected_on_export_csv(): void
    {
        $invalidData = [
            'keyword' => '山田',
            'gender' => 1,
            'category_id' => 999, // 無効なカテゴリID
            'date' => '2026-06-23',
        ];

        $validator = $this->export_contact_validate($invalidData);

        $this->assertFalse($validator->passes());
    }

    // タグ名の入力は必須
    public function test_tag_name_is_required(): void
    {
        $validator = $this->store_tag_validate([
            'name' => '',
        ]);

        $this->assertFalse($validator->passes());
    }

    // タグ名は最大文字数を超えて登録できない
    public function test_tag_name_must_not_exceed_max_length(): void
    {
        $validator = $this->store_tag_validate([
            'name' => str_repeat('あ', 51),
        ]);

        $this->assertFalse($validator->passes());
    }

    // 既に登録されているタグ名は新規登録できない
    public function test_tag_name_must_be_unique(): void
    {
        Tag::factory()->create([
            'name' => '重要',
        ]);

        $validator = $this->store_tag_validate([
            'name' => '重要',
        ]);

        $this->assertFalse($validator->passes());
    }

    // 正しいタグ名を登録できる
    public function test_valid_tag_name_passes_validation(): void
    {
        $validator = $this->store_tag_validate([
            'name' => '新規タグ',
        ]);

        $this->assertTrue($validator->passes());
    }

    // 更新時は現在のタグ名をそのまま使用できる
    public function test_current_tag_name_can_be_kept(): void
    {
        $tag = Tag::factory()->create([
            'name' => '重要',
        ]);

        $validator = $this->store_tag_validate([
            'name' => '重要',
        ], $tag);

        $this->assertTrue($validator->passes());
    }

    // 更新時に別の既存タグ名へ変更できない
    public function test_tag_name_cannot_be_changed_to_another_existing_tag_name(): void
    {
        $tag = Tag::factory()->create([
            'name' => '重要',
        ]);

        Tag::factory()->create([
            'name' => '至急',
        ]);

        $validator = $this->store_tag_validate([
            'name' => '至急',
        ], $tag);

        $this->assertFalse($validator->passes());
    }

    // 更新時もタグ名の入力は必須
    public function test_tag_name_is_required_on_update(): void
    {
        $tag = Tag::factory()->create([
            'name' => '重要',
        ]);

        $validator = $this->store_tag_validate([
            'name' => '',
        ], $tag);

        $this->assertFalse($validator->passes());
    }

    // 更新時もタグ名は最大文字数を超えられない
    public function test_tag_name_must_not_exceed_max_length_on_update(): void
    {
        $tag = Tag::factory()->create([
            'name' => '重要',
        ]);

        $validator = $this->store_tag_validate([
            'name' => str_repeat('あ', 51),
        ], $tag);

        $this->assertFalse($validator->passes());
    }
}
