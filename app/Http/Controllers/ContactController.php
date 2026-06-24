<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\ExportContactRequest;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;

class ContactController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('contact.index', compact('categories', 'tags'));
    }

    public function thanks()
    {
        return view('contact.thanks');
    }

    public function confirm(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $category = Category::findOrFail($validated['category_id']);
        $tags = Tag::findOrFail($validated['tag_ids'] ?? []);

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $contact = Contact::create($validated);

        $contact->tags()->attach($validated['tag_ids'] ?? []);

        return redirect('/thanks');
    }

    public function export(ExportContactRequest $request)
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

        $fileName = 'contacts.csv';

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');

            // BOM付きUTF-8
            fwrite($stream, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($stream, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            // データ行
            foreach ($query->cursor() as $contact) {
                fputcsv($stream, [
                    $contact->id,
                    $contact->last_name . ' ' . $contact->first_name,
                    $contact->gender_label,
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category->content,
                    $contact->detail,
                    $contact->created_at,
                ]);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
