<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tag::create(['name' => '質問']);
        Tag::create(['name' => '要望']);
        Tag::create(['name' => '不具合報告']);
        Tag::create(['name' => 'ご意見']);
        Tag::create(['name' => 'その他']);
    }
}
