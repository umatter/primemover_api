<?php

namespace App\Http\Controllers\API\v1;

use App\Term;
use Illuminate\Http\Request;

use App\Http\Resources\v1\TermResource;
use App\Http\Resources\v1\TermResourceCollection;

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
        $terms = Term::latest()->get();
        return new TermResourceCollection($terms);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return TermResourceCollection
     */
    public function store(Request $request): TermResourceCollection
    {

        $this->validate($request, [
            //'name' => 'required',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->user() ? auth()->user()->id : 1;

        $term = Term::create($data);
        $terms = Term::latest()->paginate(5);
        return new TermResourceCollection($terms, $term);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Term $term
     * @return \Illuminate\Http\Response
     */
    public function show($id): TermResource
    {
        $term = Term::findOrFail($id);
        return new TermResource($term);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Term $term
     * @return \Illuminate\Http\Response
     */
    public function edit(Term $term)
    {
        //
        return;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Term $term
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Term $term)
    {
        //
        return;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Term $term
     * @return \Illuminate\Http\Response
     */
    public function destroy(Term $term): TermResourceCollection
    {
        if ($term) {
            $term->delete();
            //return Term::latest()->get();
            $terms = Term::latest()->paginate(5);
            return new TermResourceCollection($terms);
        }
        return false;
    }
}
