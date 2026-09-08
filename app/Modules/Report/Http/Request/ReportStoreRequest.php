<?php

namespace App\Modules\Report\Http\Request;

use App\Modules\Job\Model\Job;
use App\Modules\Report\Model\Report;
use App\Rules\OwnedParent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the runner writes report rows, and it writes exactly two shapes.
     *
     * Report is $guarded = [] and this request used to accept `path` as free
     * text with no format rule at all, so any authenticated caller could store
     * a row on a job they own pointing at any object in the bucket. The readers
     * authorise the ROW (job -> queue -> crawler -> experiment) and then served
     * whatever the path named, which made that row a read of someone else's HAR
     * or decision payload.
     *
     * The readers no longer trust the stored value either -- Report::storageKey()
     * re-derives it -- because the ~70,400 rows already in the table predate any
     * rule here. This end stops a new one being written.
     *
     * `job_id` gets the same OwnedParent treatment as queue_id on StoreJobRequest:
     * a row may only be attached to a job the caller can see. Inert while
     * isolation is off and for service accounts, so the runner is unaffected.
     */
    public function rules(): array
    {
        return [
            'job_id' => ['required', 'integer', new OwnedParent(Job::class)],
            'mimetype' => ['required'],
            'filename' => ['required'],
            'active' => ['required'],
            'description' => ['nullable'],
            'user_id' => ['required'],
            'filecontent' => ['nullable'],
            'path' => ['required', 'string'],
        ];
    }

    /**
     * The path has to be an object key belonging to the job it is filed under.
     *
     * Cross-field, so it cannot be a plain rule: the check is "is this a key
     * Report::storageKey() would later agree to read for THIS job_id", which is
     * the one predicate that makes the write side and the read side agree.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['path', 'job_id'])) {
                return;
            }

            if (null !== Report::storageKeyFor($this->input('path'), (int) $this->input('job_id'))) {
                return;
            }

            $validator->errors()->add(
                'path',
                'The path must name an object this job wrote under '.Report::S3PREFIX
                    .implode(' or ', Report::OBJECT_PREFIXES).'.'
            );
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => Auth::id(),
            'active' => 1,
        ]);
    }
}
