<?php

namespace Tests\Feature\Config;

use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Every api/v1 route is described in the OpenAPI annotations.
 *
 * Nothing enforced this. l5-swagger's generate_always is false, so the spec is
 * only rebuilt when somebody remembers to run l5-swagger:generate, and there is
 * no CI step for it at all -- so an endpoint could be added, deployed and used
 * without ever appearing in the documented contract. That is how this suite
 * ended up with seven undocumented routes, one of them added the same day this
 * test was written.
 *
 * The point of the allow-list below is that it does NOT grow. The six entries
 * are pre-existing gaps, recorded rather than fixed, because documenting them
 * properly means understanding contracts this test's author has not reviewed.
 * A new undocumented route fails instead of joining them.
 *
 * Parameter NAMES are normalised away: Laravel says /crawlers/{crawler} and the
 * annotation says /crawlers/{id}, and both describe the same endpoint. Checking
 * the literal strings reported 44 "missing" routes of which 37 were this
 * spelling difference -- a signal so noisy nobody would have kept it.
 *
 * @coversNothing
 */
class ApiDocumentationTest extends TestCase
{
    /**
     * Routes with no @OA annotation, as of 2026-09-10. Do not add to this list.
     *
     * /upload and /download/{filename} belong to the S3Controller, whose disk
     * has never been functional here. The three /runner/* endpoints are the
     * runner's own contract and deserve annotating on their own merits.
     */
    private const UNDOCUMENTED = [
        '/download/{x}',
        '/runner/agents/{x}',
        '/runner/jobs/{x}',
        '/runner/queues/{x}',
        '/terms/{x}',
        '/upload',
    ];

    public function test_no_new_api_route_is_undocumented(): void
    {
        $undocumented = array_values(array_diff($this->apiRoutes(), $this->annotatedPaths()));

        sort($undocumented);
        $known = self::UNDOCUMENTED;
        sort($known);

        $this->assertSame(
            $known,
            $undocumented,
            "an api/v1 route has no @OA annotation.\n"
            .'Add one to its controller, or -- if it is genuinely internal -- say so by '
            ."adding it to ApiDocumentationTest::UNDOCUMENTED with a reason.\n"
            ."l5-swagger's generate_always is false, so nothing else will notice."
        );
    }

    public function test_every_annotation_reached_the_generated_spec(): void
    {
        // An annotation that never reaches storage/api-docs/api-docs.json
        // documents nothing: that file is what /api/documentation serves and
        // what a client reads. generate_always is false, so writing the
        // annotation and regenerating are two separate acts and only the first
        // is obvious.
        //
        // This is not hypothetical. The watched endpoint's annotation sat in a
        // FLOATING docblock -- one @OA block, then a second prose block, then
        // the method -- so swagger-php never attached it, and the spec stayed
        // at 66 paths while the source looked correctly annotated.
        $spec = json_decode(file_get_contents(base_path('storage/api-docs/api-docs.json')), true);

        $generated = array_map([$this, 'normalise'], array_keys($spec['paths'] ?? []));
        $missing = array_values(array_diff($this->annotatedPaths(), $generated));

        sort($missing);

        $this->assertSame(
            ['/experiments/upload'],
            $missing,
            "an @OA path is annotated but absent from the generated spec.\n"
            .'Run `php artisan l5-swagger:generate` and commit the result. If the path is '
            .'there but still missing, check the annotation is in the docblock ATTACHED to '
            .'the method -- a floating one is silently ignored.'
        );
    }

    public function test_the_allow_list_does_not_name_routes_that_no_longer_exist(): void
    {
        // Otherwise it silently rots into a list of excuses for endpoints that
        // were deleted or documented years ago.
        $stale = array_diff(self::UNDOCUMENTED, $this->apiRoutes());

        $this->assertSame([], array_values($stale), 'these are documented or gone; drop them from the list');
    }

    /**
     * @return string[]
     */
    private function apiRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            if ( ! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            $routes[] = $this->normalise('/'.mb_substr($route->uri(), mb_strlen('api/v1/')));
        }

        return array_values(array_unique($routes));
    }

    /**
     * @return string[]
     */
    private function annotatedPaths(): array
    {
        $paths = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

        foreach ($files as $file) {
            if ( ! $file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            preg_match_all('/path="([^"]+)"/', file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $path) {
                $paths[] = $this->normalise($path);
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * One shape for both sides: parameter names differ, endpoints do not.
     */
    private function normalise(string $path): string
    {
        return rtrim(preg_replace('/\{[^}]+\}/', '{x}', $path), '/');
    }
}
