<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\TermResource;
use App\Http\Resources\v1\TermResourceCollection;
use App\Models\Term;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TermController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return TermResourceCollection
     */
    public function index(Request $request): TermResourceCollection
    {
        $keyword = $request->input('keyword');
        $terms = Term::with(
            [
                'user'
            ]
        )->latest()->get();
        return new TermResourceCollection($terms);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return TermResourceCollection
     */
    public function create()
    {
        //
        return new TermResourceCollection(new Term);
    }

    /**
     * Store a new resource.
     * @param Request $request
     * @return TermResource
     * @throws ValidationException
     */
    public function store(Request $request): TermResource
    {
        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $term = Term::create($data);
        return new TermResource($term);
    }

    /**
     * Show a resource.
     * @param $id
     * @return TermResource
     */
    public function show($id): TermResource
    {
        $term = Term::with(['user'])->findOrFail($id);
        return new TermResource($term);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Term $term
     * @return mixed
     */
    public function edit(Term $term): TermResource
    {
        return new TermResource($term);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Term $term
     * @return TermResource
     */
    public function update(Request $request, Term $term): TermResource
    {
        $data = $request->all();
        $term->update($data);
        return new TermResource($term);
    }

    /**
     * Remove the specified resource from storage.
     * @param Term $term
     * @return TermResourceCollection
     * @throws Exception
     */
    public function destroy(Term $term): TermResourceCollection
    {
        $term->delete();
        return new TermResourceCollection(
            Term::with(
                [
                    'user'
                ]
            )->latest()->get()
        );
    }
}
