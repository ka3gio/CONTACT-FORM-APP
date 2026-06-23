<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\IndexContactRequest;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {

        $query = Contact::query();

        if ($request->filled("keyword")) {
            $query->where("last_name", "like", "%" . $request->keyword . "%")
                ->orWhere("first_name", "like", "%" . $request->keyword . "%")
                ->orWhere("email", "like", "%" . $request->keyword . "%");
        }

        if ($request->gender != 0) {
            $query->where("gender", $request->gender);
        }

        if ($request->filled("category_id")) {
            $query->where("category_id", $request->category_id);
        }

        if ($request->filled("date")) {
            $query->whereDate("created_at", $request->date);
        }

        $query->orderBy("created_at", "desc");

        $contacts = $query->with('tags')->paginate(7);
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }
}
