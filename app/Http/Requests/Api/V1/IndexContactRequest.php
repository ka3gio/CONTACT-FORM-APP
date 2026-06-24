<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => 'nullable|string|max:255',
            'gender' => 'nullable|integer|in:1,2,3',
            'category_id' => 'nullable|integer|exists:categories,id',
            'date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'gender.integer' => '性別の値が不正です',
            'gender.in' => '性別の値が不正です',
            'category_id.exists' => '選択されたカテゴリーが存在しません',
        ];
    }
}
