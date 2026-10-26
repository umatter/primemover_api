<?php

namespace App\Rules;

use App\Access\Isolation;
use App\Access\LibraryOwnership;
use App\Modules\Crawler\Model\Crawler;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Rejects attaching a crawler to a Library item (agent, proxy, configuration)
 * the caller does not own -- see LibraryOwnership for what owning means.
 *
 * `exists` alone let a bot pick up another study's item from the unscoped
 * GET lists: a shared configuration locks its owner out of PUT
 * configurations/{id} and lets either study rewrite the other's treatment; a
 * shared agent is one browser profile personalised by both studies' sessions;
 * a shared proxy is one exit IP for both.
 *
 * Sending the id the crawler already has passes: it attaches nothing new, and
 * a legacy shared setup stays editable (the GUI's Edit setup sends all three).
 *
 * A no-op while isolation is off or for service accounts, like OwnedParent.
 */
class AttachableToCrawler implements ValidationRule
{
    /**
     * @param class-string<Model> $model      the item's model
     * @param string              $foreignKey its column on crawlers
     * @param Crawler|null        $crawler    the crawler being updated, if any
     */
    public function __construct(
        private string $model,
        private string $foreignKey,
        private ?Crawler $crawler = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Isolation::bypasses()) {
            return;
        }

        if (null === $value || '' === $value) {
            return; // presence is a separate rule's job
        }

        if (null !== $this->crawler && (string) $this->crawler->getAttribute($this->foreignKey) === (string) $value) {
            return;
        }

        $item = $this->model::query()->find($value);

        if (null === $item) {
            return; // `exists` reports it
        }

        if ( ! LibraryOwnership::owns(Auth::user(), $item, $this->foreignKey)) {
            // "not found", not "forbidden": as in OwnedParent, do not confirm that
            // someone else's id exists.
            $fail('The selected :attribute could not be found.');
        }
    }
}
