<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

/**
 * Stopping the clock: the note and the billable flag the dialog collected.
 *
 * Both are optional, because a stop from an older client sends nothing at all
 * and must still close the entry with whatever the start recorded.
 */
final class StopTimerRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'billable' => ['sometimes', 'boolean'],
        ];
    }
}
