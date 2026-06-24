<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
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
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);
        $contact->tags()->attach($tagIds);

        return redirect('/thanks');
    }

    public function export(ExportContactRequest $request)
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
