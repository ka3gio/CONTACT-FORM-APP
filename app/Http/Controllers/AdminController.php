<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $query = Contact::query()->with(['category', 'tags']);

        if (!empty($validated['keyword'])) {
            $query->where(function ($query) use ($validated) {
                $keyword = '%' . $validated['keyword'] . '%';

                $query->where('last_name', 'like', $keyword)
                    ->orWhere('first_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword);
            });
        }

        if (!empty($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        $query->orderBy('created_at', 'desc');

        $contacts = $query->paginate(7);
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
