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
            $query->where(function ($query) use ($request) {
                $keyword = "%" . $request->keyword . "%";

                $query->where("last_name", "like", $keyword)
                    ->orWhere("first_name", "like", $keyword)
                    ->orWhere("email", "like", $keyword);
            });
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

    public function show($id)
    {
        $contact = Contact::findOrFail($id);

        return view('admin.show', compact('contact'));
    }


    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);

        $contact->delete();

        return redirect('/admin');
    }
}
