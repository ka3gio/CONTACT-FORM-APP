<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

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
            ->create(['category_id' => fn () => $categories->random()->id])
            ->each(function ($contact) use ($tags) {
                $contact->tags()->attach($tags->random(rand(1, 3))->pluck('id')->toArray());
            });
    }
}
