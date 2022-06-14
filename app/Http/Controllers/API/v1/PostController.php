<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\v1\PostResource;
use App\Http\Resources\v1\PostResourceCollection;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request): PostResourceCollection
    {
        $keyword = $request->input('keyword');
        $posts = Post::with('user')->latest()->get();

        return new PostResourceCollection($posts);
    }

    public function create(): JsonResource
    {
        return JsonResource::make([]);
    }

    public function store(): JsonResource
    {
        return JsonResource::make([]);
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->with('user'));
    }

    public function edit(Post $post): JsonResource
    {
        return JsonResource::make([]);
    }

    public function update(Request $request, Post $post): JsonResource
    {
        return JsonResource::make([]);
    }

    public function destroy(Post $post): JsonResource
    {
        return JsonResource::make([]);
    }
}
