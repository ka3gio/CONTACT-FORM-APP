<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create(['content' => '商品のお届けについて']);
        Category::create(['content' => '商品の交換について']);
        Category::create(['content' => '商品トラブル']);
        Category::create(['content' => 'ショップへのお問い合わせ']);
        Category::create(['content' => 'その他']);
    }
}
