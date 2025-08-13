<?php

namespace App\Modules\Term\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Term\Repository\TermRepository;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Terms",
 *     description="Term management endpoints"
 * )
 */
class TermController extends Controller
{
    public function __construct(protected TermRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/terms",
     *     summary="List all terms",
     *     description="Get paginated list of terms",
     *     operationId="getTerms",
     *     tags={"Terms"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of terms",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="term", type="string", example="search term"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        $terms = $this->repository->all();

        return ResourceCollection::make($terms);
    }
}
