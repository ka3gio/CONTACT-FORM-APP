<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $categories = Category::all();
        $tags = Tag::all();

        Contact::factory()
            ->count(20)
            ->create()
            ->each(function ($contact) use ($categories, $tags) {
                $contact->update(['category_id' => $categories->random()->id]);
                $contact->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
            });
    }
}
