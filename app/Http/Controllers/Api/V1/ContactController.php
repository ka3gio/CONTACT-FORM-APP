<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexContactRequest $request)
    {
        $validated = $request->validated();

        $query = Contact::query()->with(['category', 'tags']);

        if (! empty($validated['keyword'])) {
            $query->where(function ($query) use ($validated) {
                $keyword = '%'.$validated['keyword'].'%';

                $query->where('last_name', 'like', $keyword)
                    ->orWhere('first_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword);
            });
        }

        if (isset($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (! empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;

        $contacts = $query->paginate($perPage, ['*'], 'page', $page);

        return ContactResource::collection($contacts)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact = Contact::create($validated);
        $contact->tags()->attach($tagIds);

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contact = Contact::findOrFail($id);
        $contact->load(['category', 'tags']);

        return (new ContactResource($contact))->response()->setStatusCode(200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContactRequest $request, string $id)
    {
        $contact = Contact::findOrFail($id);

        $validated = $request->validated();
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $contact->update($validated);
        $contact->tags()->sync($tagIds);

        return (new ContactResource($contact->load(['category', 'tags'])))->response()->setStatusCode(200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->noContent();
    }
}
